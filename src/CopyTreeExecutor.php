<?php

namespace GregPriday\CopyTree;

use GregPriday\CopyTree\Filters\FilterPipelineFactory;
use GregPriday\CopyTree\Filters\Ruleset\RulesetFilter;
use GregPriday\CopyTree\Views\FileContentsView;
use GregPriday\CopyTree\Views\FileTreeView;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Executes the copy tree operation using a pipeline of filters.
 *
 * Manages the process of filtering files through a configurable pipeline,
 * then rendering the tree view and file contents as requested.
 */
class CopyTreeExecutor
{
    private FilterPipelineFactory $pipelineFactory;

    public function __construct(
        private readonly string $path,
        private readonly bool $onlyTree,
        private readonly ?string $aiFilterDescription = null,
        private readonly ?SymfonyStyle $io = null,
        private readonly int $maxLines = 0,
        private readonly bool $modifiedOnly = false,
        private readonly ?string $changes = null
    ) {
        $this->pipelineFactory = new FilterPipelineFactory;
    }

    /**
     * Execute the copy tree operation.
     *
     * @param  RulesetFilter  $ruleset  The base ruleset filter
     * @return array{output: string, fileCount: int, files: array} Operation result
     *
     * @throws RuntimeException|\Exception If execution fails
     */
    public function execute(RulesetFilter $ruleset): array
    {
        // Create filter pipeline with all requested filters
        $pipeline = $this->pipelineFactory->createPipeline(
            $this->path,
            [
                'modified' => $this->modifiedOnly,
                'changes' => $this->changes,
                'ai-filter' => $this->aiFilterDescription,
            ],
            $ruleset,
            $this->io
        );

        // Log pipeline configuration if verbose
        if ($this->io) {
            $this->logPipelineConfiguration($pipeline);
        }

        try {
            // Get initial files from base path
            $initialFiles = iterator_to_array($ruleset->getFilteredFiles());

            // Execute the pipeline
            $filteredFiles = $pipeline->execute($initialFiles);

            // Generate output
            return $this->generateOutput($filteredFiles);

        } catch (\Exception $e) {
            if ($this->io) {
                $this->io->error('Pipeline execution failed: '.$e->getMessage());
                // Log more details in verbose mode
                $this->io->writeln(
                    'Stack trace: '.$e->getTraceAsString(),
                    OutputInterface::VERBOSITY_DEBUG
                );

                return $this->generateOutput([]);
            }
            throw $e;
        }
    }

    /**
     * Generate the final output from filtered files.
     *
     * @param  array  $filteredFiles  The files that passed through all filters
     * @return array Operation result with output and stats
     */
    private function generateOutput(array $filteredFiles): array
    {
        // Generate the tree view
        $treeOutput = FileTreeView::render($filteredFiles);
        $combinedOutput = $treeOutput;

        // Add file contents if requested
        if (! $this->onlyTree) {
            $fileContentsOutput = FileContentsView::render($filteredFiles, $this->maxLines);
            $combinedOutput .= "\n\n---\n\n".$fileContentsOutput;
        }

        return [
            'output' => $combinedOutput,
            'fileCount' => count($filteredFiles),
            'files' => $filteredFiles,
        ];
    }

    /**
     * Log the pipeline configuration in verbose mode.
     */
    private function logPipelineConfiguration($pipeline): void
    {
        if ($pipeline->hasFilters()) {
            $this->io->writeln(
                'Configured filters:',
                OutputInterface::VERBOSITY_VERBOSE
            );
            foreach ($pipeline->getFilterDescriptions() as $description) {
                $this->io->writeln(
                    "- {$description}",
                    OutputInterface::VERBOSITY_VERBOSE
                );
            }
        } else {
            $this->io->writeln(
                'No filters configured, using raw file list',
                OutputInterface::VERBOSITY_VERBOSE
            );
        }
    }
}
