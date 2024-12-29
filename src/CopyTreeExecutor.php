<?php

namespace GregPriday\CopyTree;

use GregPriday\CopyTree\Filters\FilterPipelineFactory;
use GregPriday\CopyTree\Filters\FilterPipelineConfiguration;
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
     * @return array{output: string, fileCount: int, files: array} Operation result
     *
     * @throws RuntimeException|\Exception If execution fails
     */
    public function execute(): array
    {
        try {
            $this->validateConfiguration();

            $pipeline = $this->createPipeline();
            $this->logPipelineConfiguration($pipeline);

            $files = $this->getInitialFiles();
            $filteredFiles = $this->executeFilterPipeline($pipeline, $files);

            return $this->generateOutput($filteredFiles);

        } catch (\Exception $e) {
            $this->handleExecutionError($e);
            throw $e;
        }
    }

    private function validateConfiguration(): void
    {
        $filterConfig = $this->config->getFilterConfig();
        $filterConfig->validate();

        if (!is_dir($this->config->getBasePath())) {
            throw new RuntimeException('Invalid base path: ' . $this->config->getBasePath());
        }
    }

    private function createPipeline()
    {
        return $this->pipelineFactory->createPipeline(
            $this->config->getBasePath(),
            $this->config->toPipelineOptions(),
            $this->config->getRuleset(),
            $this->io
        );
    }

    private function getInitialFiles(): array
    {
        return iterator_to_array($this->config->getRuleset()->getFilteredFiles());
    }

    private function executeFilterPipeline($pipeline, array $files): array
    {
        try {
            return $pipeline->execute($files);
        } catch (\Exception $e) {
            throw new RuntimeException('Pipeline execution failed: ' . $e->getMessage(), 0, $e);
        }
    }

    private function generateOutput(array $filteredFiles): array
    {
        // Generate the tree view
        $treeOutput = FileTreeView::render($filteredFiles);
        $combinedOutput = $treeOutput;

        if (!$this->onlyTree) {
            $fileContentsOutput = FileContentsView::render(
                $filteredFiles,
                $this->config->getFilterConfig()->getMaxLines()
            );
            $combinedOutput .= "\n\n---\n\n" . $fileContentsOutput;
        }

        return [
            'output' => $combinedOutput,
            'fileCount' => count($filteredFiles),
            'files' => $filteredFiles,
        ];
    }

    private function handleExecutionError(\Exception $e): void
    {
        if ($this->io) {
            $this->io->error('Execution failed: ' . $e->getMessage());
            $this->io->writeln(
                'Stack trace: ' . $e->getTraceAsString(),
                OutputInterface::VERBOSITY_DEBUG
            );
        }
    }

    private function logPipelineConfiguration($pipeline): void
    {
        if (!$this->io) {
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
