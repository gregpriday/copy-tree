<?php

namespace GregPriday\CopyTree;

use GregPriday\CopyTree\External\ExternalSourceHandler;
use GregPriday\CopyTree\Filters\FilterPipelineConfiguration;
use GregPriday\CopyTree\Filters\FilterPipelineFactory;
use GregPriday\CopyTree\Views\FileContentsView;
use GregPriday\CopyTree\Views\FileTreeView;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CopyTreeExecutor
{
    private FilterPipelineFactory $pipelineFactory;

    public function __construct(
        private readonly FilterPipelineConfiguration $config,
        private readonly bool $onlyTree = false,
        private readonly ?SymfonyStyle $io = null
    ) {
        $this->pipelineFactory = new FilterPipelineFactory;
    }

    /**
     * Execute the copy tree operation.
     *
     * @return array{output: string, fileCount: int, files: array} Operation result.
     *
     * @throws RuntimeException|\Exception If execution fails.
     */
    public function execute(): array
    {
        try {
            $this->validateConfiguration();
            $pipeline = $this->createPipeline();
            $this->logPipelineConfiguration($pipeline);
            $localFiles = $this->getInitialFiles();
            $filteredFiles = $this->executeFilterPipeline($pipeline, $localFiles);

            // Merge external files (if configured) with the filtered local files.
            $mergedFiles = $this->mergeExternalFiles($filteredFiles);

            return $this->generateOutput($mergedFiles);
        } catch (\Exception $e) {
            $this->handleExecutionError($e);
            throw $e;
        }
    }

    /**
     * Validate the configuration and base path.
     */
    private function validateConfiguration(): void
    {
        $filterConfig = $this->config->getFilterConfig();
        $filterConfig->validate();

        if (! is_dir($this->config->getBasePath())) {
            throw new RuntimeException('Invalid base path: '.$this->config->getBasePath());
        }
    }

    /**
     * Create the filter pipeline using the configuration.
     *
     * @return mixed The filter pipeline instance.
     */
    private function createPipeline()
    {
        return $this->pipelineFactory->createPipeline(
            $this->config->getBasePath(),
            $this->config->toPipelineOptions(),
            $this->config->getRuleset(),
            $this->io
        );
    }

    /**
     * Retrieve the initial list of files from the ruleset.
     *
     * @return array The array of files.
     */
    private function getInitialFiles(): array
    {
        return iterator_to_array($this->config->getRuleset()->getFilteredFiles());
    }

    /**
     * Execute the filter pipeline on the provided files.
     *
     * @param  mixed  $pipeline  The filter pipeline.
     * @param  array  $files  The array of files to filter.
     * @return array The filtered files.
     *
     * @throws RuntimeException If the pipeline execution fails.
     */
    private function executeFilterPipeline($pipeline, array $files): array
    {
        try {
            return $pipeline->execute($files);
        } catch (\Exception $e) {
            throw new RuntimeException('Pipeline execution failed: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Merge external files with the local files.
     *
     * This method checks for an "external" configuration in the ruleset (via getExternal()),
     * processes those external items using ExternalSourceHandler, and then merges
     * the resulting file list with the local file list.
     *
     * In case of duplicate relative paths, a warning is logged and external files override local ones.
     *
     * @param  array  $localFiles  The local files array.
     * @return array The merged file list.
     */
    private function mergeExternalFiles(array $localFiles): array
    {
        // Check if the local ruleset provides external configuration.
        if (! method_exists($this->config->getRuleset(), 'getExternal')) {
            // No external configuration defined; return local files as is.
            return $localFiles;
        }

        $externalItems = $this->config->getRuleset()->getExternal();
        if (empty($externalItems) || ! is_array($externalItems)) {
            return $localFiles;
        }

        // Process external items using the ExternalSourceHandler.
        $externalProcessor = new ExternalSourceHandler($externalItems, $this->config->getBasePath(), $this->io);
        $externalFiles = $externalProcessor->process();

        // Merge local and external files keyed by their relative path.
        $mergedFiles = [];
        foreach ($localFiles as $file) {
            $mergedFiles[$file['path']] = $file;
        }
        foreach ($externalFiles as $extFile) {
            if (isset($mergedFiles[$extFile['path']])) {
                if ($this->io) {
                    $this->io->warning("Conflict detected for path {$extFile['path']}: external file overriding local file.");
                }
            }
            $mergedFiles[$extFile['path']] = $extFile;
        }

        // Return the merged files as a re-indexed array.
        return array_values($mergedFiles);
    }

    /**
     * Generate the final XML output.
     *
     * Wraps the tree view in <ct:tree> tags and, if not in tree-only mode,
     * wraps the file contents in <ct:project_files> tags—all enclosed in a root <ct:project> element.
     *
     * @param  array  $filteredFiles  The filtered files array.
     * @return array{output: string, fileCount: int, files: array} The operation result.
     */
    private function generateOutput(array $filteredFiles): array
    {
        // Generate the tree view (XML markup provided by the view).
        $treeOutput = FileTreeView::render($filteredFiles);

        // Start with a namespaced root element.
        $combinedOutput = '<ct:project>'."\n";
        $combinedOutput .= "<ct:tree>\n".$treeOutput."\n</ct:tree>\n";

        if (! $this->onlyTree) {
            // Generate file contents output if required.
            $fileContentsOutput = FileContentsView::render(
                $filteredFiles,
                $this->config->getFilterConfig()->getMaxLines()
            );
            $combinedOutput .= "<ct:project_files>\n".$fileContentsOutput."\n</ct:project_files>\n";
        }

        $combinedOutput .= "</ct:project><!-- END OF PROJECT -->\n\n\n";

        return [
            'output' => $combinedOutput,
            'fileCount' => count($filteredFiles),
            'files' => $filteredFiles,
        ];
    }

    /**
     * Handle execution errors by logging if an IO interface is available.
     *
     * @param  \Exception  $e  The exception to handle.
     */
    private function handleExecutionError(\Exception $e): void
    {
        if ($this->io) {
            $this->io->error('Execution failed: '.$e->getMessage());
            $this->io->writeln(
                'Stack trace: '.$e->getTraceAsString(),
                OutputInterface::VERBOSITY_DEBUG
            );
        }
    }

    /**
     * Log the configuration of the pipeline filters.
     *
     * @param  mixed  $pipeline  The filter pipeline.
     */
    private function logPipelineConfiguration($pipeline): void
    {
        if (! $this->io) {
            return;
        }

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
