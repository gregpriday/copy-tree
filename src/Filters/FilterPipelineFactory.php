<?php

namespace GregPriday\CopyTree\Filters;

use GregPriday\CopyTree\Filters\AI\JinaCodeSearchFilter;
use GregPriday\CopyTree\Filters\AI\OpenAIFilter;
use GregPriday\CopyTree\Filters\Git\ChangeFilter;
use GregPriday\CopyTree\Filters\Git\ModifiedFilter;
use GregPriday\CopyTree\Filters\Ruleset\LocalRulesetFilter;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Factory class for creating and configuring filter pipelines.
 *
 * This class handles the creation of filter pipelines based on command options
 * and configuration settings, ensuring proper order and compatibility of filters.
 *
 * It now uses the LocalRulesetFilter (local file filtering only) instead of the old RulesetFilter.
 */
class FilterPipelineFactory
{
    /**
     * Create a filter pipeline based on command options.
     *
     * @param  string  $basePath  Base path for file operations.
     * @param  array  $options  Command options array.
     * @param  LocalRulesetFilter|null  $ruleset  Optional preconfigured local ruleset filter.
     * @param  SymfonyStyle|null  $io  Optional IO interface for logging.
     * @return FilterPipelineManager Configured pipeline manager.
     */
    public function createPipeline(
        string $basePath,
        array $options,
        ?LocalRulesetFilter $ruleset = null,
        ?SymfonyStyle $io = null
    ): FilterPipelineManager {
        $pipeline = new FilterPipelineManager($io);

        // Add local ruleset filter first if provided.
        if ($ruleset !== null) {
            $pipeline->addFilter($ruleset);
        }

        // Add Git filters if requested.
        // Note: changes and modified are mutually exclusive.
        if (! empty($options['changes'])) {
            $this->addChangeFilter($pipeline, $basePath, $options['changes']);
        } elseif (! empty($options['modified'])) {
            $this->addModifiedFilter($pipeline, $basePath);
        }

        // Add AI filters if requested.
        if (! empty($options['ai-filter'])) {
            $this->addAiFilters($pipeline, $options['ai-filter']);
        }

        if (! empty($options['search'])) {
            $this->addSearchFilter($pipeline, $options['search']);
        }

        return $pipeline;
    }

    /**
     * Add Git change filter to the pipeline.
     *
     * @param  string  $changes  Changes in the format "commit1:commit2".
     */
    private function addChangeFilter(
        FilterPipelineManager $pipeline,
        string $basePath,
        string $changes
    ): void {
        // Parse the commits from the changes parameter.
        $commits = explode(':', $changes);
        $fromCommit = $commits[0];
        $toCommit = $commits[1] ?? 'HEAD';

        try {
            $filter = new ChangeFilter($basePath, $fromCommit, $toCommit);
            $pipeline->addFilter($filter);
        } catch (\Exception $e) {
            // Log error but continue – the filter will handle errors in the pipeline.
            if ($pipeline->getIo()) {
                $pipeline->getIo()->warning(
                    "Failed to create Git change filter: {$e->getMessage()}"
                );
            }
        }
    }

    /**
     * Add Git modified filter to the pipeline.
     */
    private function addModifiedFilter(
        FilterPipelineManager $pipeline,
        string $basePath
    ): void {
        try {
            $filter = new ModifiedFilter($basePath);
            $pipeline->addFilter($filter);
        } catch (\Exception $e) {
            // Log error but continue.
            if ($pipeline->getIo()) {
                $pipeline->getIo()->warning(
                    "Failed to create Git modified filter: {$e->getMessage()}"
                );
            }
        }
    }

    /**
     * Add AI filters to the pipeline based on configuration.
     *
     * @param  string|bool  $aiFilterOption  AI filter configuration from command options.
     */
    private function addAiFilters(
        FilterPipelineManager $pipeline,
        string|bool $aiFilterOption
    ): void {
        // Skip if AI filtering is not enabled.
        if ($aiFilterOption === false) {
            return;
        }

        $description = is_string($aiFilterOption) ? $aiFilterOption : '';

        // If no specific description provided, prompt the user via IO.
        if (empty($description) && $pipeline->getIo()) {
            $description = $pipeline->getIo()->ask('Enter your filtering description');
        }

        if (empty($description)) {
            return;
        }

        // Add OpenAI filter.
        try {
            $openaiFilter = new OpenAIFilter($description);
            $pipeline->addFilter($openaiFilter);
        } catch (\Exception $e) {
            if ($pipeline->getIo()) {
                $pipeline->getIo()->warning(
                    "Failed to create OpenAI filter: {$e->getMessage()}"
                );
            }
        }
    }

    /**
     * Add Jina AI search filter to the pipeline.
     *
     * @param  FilterPipelineManager  $pipeline  The filter pipeline.
     * @param  string|bool  $query  The search query.
     */
    private function addSearchFilter(
        FilterPipelineManager $pipeline,
        string $query
    ): void {
        // If no specific query is provided, prompt the user via IO.
        if (empty($query) && $pipeline->getIo()) {
            $query = $pipeline->getIo()->ask('Enter your search query');
        }

        if (empty($query)) {
            return;
        }

        // Add Jina search filter.
        try {
            $searchFilter = new JinaCodeSearchFilter($query);
            $pipeline->addFilter($searchFilter);
        } catch (\Exception $e) {
            if ($pipeline->getIo()) {
                $pipeline->getIo()->warning(
                    "Failed to create Jina search filter: {$e->getMessage()}"
                );
            }
        }
    }

    /**
     * Get the IO interface from the pipeline if available.
     */
    private function getIo(FilterPipelineManager $pipeline): ?SymfonyStyle
    {
        return method_exists($pipeline, 'getIo') ? $pipeline->getIo() : null;
    }
}
