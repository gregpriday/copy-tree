<?php

namespace GregPriday\CopyTree\Filters;

use GregPriday\CopyTree\Filters\AI\JinaCodeSearchFilter;
use GregPriday\CopyTree\Filters\AI\OpenAIFilter;
use GregPriday\CopyTree\Filters\Git\ChangeFilter;
use GregPriday\CopyTree\Filters\Git\ModifiedFilter;
use GregPriday\CopyTree\Filters\Ruleset\RulesetFilter;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Factory class for creating and configuring filter pipelines.
 *
 * This class handles the creation of filter pipelines based on command options
 * and configuration settings, ensuring proper order and compatibility of filters.
 */
class FilterPipelineFactory
{
    /**
     * Create a filter pipeline based on command options.
     *
     * @param  string  $basePath  Base path for file operations
     * @param  array  $options  Command options array
     * @param  RulesetFilter|null  $ruleset  Optional preconfigured ruleset filter
     * @param  SymfonyStyle|null  $io  Optional IO interface for logging
     * @return FilterPipelineManager Configured pipeline manager
     */
    public function createPipeline(
        string $basePath,
        array $options,
        ?RulesetFilter $ruleset = null,
        ?SymfonyStyle $io = null
    ): FilterPipelineManager {
        $pipeline = new FilterPipelineManager($io);

        // Add ruleset filter first if provided
        if ($ruleset !== null) {
            $pipeline->addFilter($ruleset);
        }

        // Add Git filters if requested
        // Note: changes and modified are mutually exclusive
        if (! empty($options['changes'])) {
            $this->addChangeFilter($pipeline, $basePath, $options['changes']);
        } elseif (! empty($options['modified'])) {
            $this->addModifiedFilter($pipeline, $basePath);
        }

        // Add AI filters if requested
        if (isset($options['ai-filter'])) {
            $this->addAiFilters($pipeline, $options['ai-filter']);
        }

        return $pipeline;
    }

    /**
     * Add Git change filter to the pipeline.
     */
    private function addChangeFilter(
        FilterPipelineManager $pipeline,
        string $basePath,
        string $changes
    ): void {
        // Parse the commits from the changes parameter
        $commits = explode(':', $changes);
        $fromCommit = $commits[0];
        $toCommit = $commits[1] ?? 'HEAD';

        try {
            $filter = new ChangeFilter($basePath, $fromCommit, $toCommit);
            $pipeline->addFilter($filter);
        } catch (\Exception $e) {
            // Log error but continue - filter will handle error in pipeline
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
            // Log error but continue - filter will handle error in pipeline
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
     * @param  string|bool  $aiFilterOption  AI filter configuration from command options
     */
    private function addAiFilters(
        FilterPipelineManager $pipeline,
        string|bool $aiFilterOption
    ): void {
        // Skip if AI filtering is not enabled
        if ($aiFilterOption === false) {
            return;
        }

        $description = is_string($aiFilterOption) ? $aiFilterOption : '';

        // If no specific description provided, the IO interface should have prompted for one
        if (empty($description) && $pipeline->getIo()) {
            $description = $pipeline->getIo()->ask('Enter your filtering description');
        }

        if (empty($description)) {
            return;
        }

        // Add OpenAI filter
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

        // Add Jina filter with slightly lower threshold
        try {
            $jinaFilter = new JinaCodeSearchFilter($description, 0.7);
            $pipeline->addFilter($jinaFilter);
        } catch (\Exception $e) {
            if ($pipeline->getIo()) {
                $pipeline->getIo()->warning(
                    "Failed to create Jina filter: {$e->getMessage()}"
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
