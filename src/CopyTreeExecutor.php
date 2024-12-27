<?php

namespace GregPriday\CopyTree;

use GregPriday\CopyTree\Ruleset\RulesetFilter;
use GregPriday\CopyTree\Utilities\OpenAI\OpenAIFileFilter;
use GregPriday\CopyTree\Views\FileContentsView;
use GregPriday\CopyTree\Views\FileTreeView;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Executes the copy tree operation, applying rulesets and generating output.
 *
 * Manages the process of filtering files, rendering the tree view and file contents.
 * Can apply both ruleset-based and AI-based filtering.
 */
class CopyTreeExecutor
{
    public function __construct(
        private readonly bool $onlyTree,
        private readonly ?string $aiFilterDescription = null,
        private readonly ?SymfonyStyle $io = null,
        private readonly int $maxLines = 0
    ) {}

    public function execute(RulesetFilter $ruleset): array
    {
        // First apply the ruleset filter
        $filteredFiles = iterator_to_array($ruleset->getFilteredFiles());

        // Then apply AI filtering if requested
        if ($this->aiFilterDescription !== null && ! empty($filteredFiles)) {
            try {
                $aiFilter = new OpenAIFileFilter;

                if ($this->io) {
                    $this->io->writeln('Applying AI filter...', \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERBOSE);
                }

                $filterResult = $aiFilter->filterFiles($filteredFiles, $this->aiFilterDescription);

                // Update filtered files with AI results
                $filteredFiles = $filterResult['files'];

                // Show AI explanation if we have IO
                if ($this->io) {
                    $this->io->writeln(
                        'AI Filter explanation: '.$filterResult['explanation'],
                        \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERBOSE
                    );
                }
            } catch (\Exception $e) {
                if ($this->io) {
                    $this->io->warning('AI filtering failed: '.$e->getMessage());
                    $this->io->writeln(
                        'Continuing with unfiltered results...',
                        \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERBOSE
                    );
                } else {
                    throw $e; // Re-throw if we can't communicate the error
                }
            }
        }

        // Generate the tree view
        $treeOutput = FileTreeView::render($filteredFiles);
        $combinedOutput = $treeOutput;

        // Add file contents if requested
        if (! $this->onlyTree) {
            $fileContentsOutput = FileContentsView::render($filteredFiles, $this->maxLines);
            $combinedOutput .= "\n\n---\n\n".$fileContentsOutput;
        }

        // Return the final result
        return [
            'output' => $combinedOutput,
            'fileCount' => count($filteredFiles),
            'files' => $filteredFiles,
        ];
    }
}
