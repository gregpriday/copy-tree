<?php

namespace GregPriday\CopyTree;

use GregPriday\CopyTree\Ruleset\RulesetFilter;
use GregPriday\CopyTree\Utilities\GitStatusChecker;
use GregPriday\CopyTree\Utilities\OpenAI\OpenAIFileFilter;
use GregPriday\CopyTree\Views\FileContentsView;
use GregPriday\CopyTree\Views\FileTreeView;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Executes the copy tree operation, applying rulesets and generating output.
 *
 * Manages the process of filtering files, rendering the tree view and file contents.
 * Can apply ruleset-based, AI-based, and Git-based filtering.
 */
class CopyTreeExecutor
{
    public function __construct(
        private readonly string $path,
        private readonly bool $onlyTree,
        private readonly ?string $aiFilterDescription = null,
        private readonly ?SymfonyStyle $io = null,
        private readonly int $maxLines = 0,
        private readonly bool $modifiedOnly = false
    ) {}

    public function execute(RulesetFilter $ruleset): array
    {
        // First apply the ruleset filter
        $filteredFiles = iterator_to_array($ruleset->getFilteredFiles());

        // If modified only flag is set, filter by Git status
        if ($this->modifiedOnly && ! empty($filteredFiles)) {
            try {
                $gitChecker = new GitStatusChecker;

                if (! $gitChecker->isGitRepository($this->path)) {
                    throw new RuntimeException('Not a Git repository');
                }

                $gitChecker->initRepository($this->path);
                $modifiedFiles = $gitChecker->getModifiedFiles();
                $repoRoot = $gitChecker->getRepositoryRoot();

                // Filter files to only include those in the modified list
                $filteredFiles = array_filter($filteredFiles, function ($file) use ($modifiedFiles, $repoRoot) {
                    $relativePath = str_replace($repoRoot.'/', '', $file['file']->getRealPath());

                    return in_array($relativePath, $modifiedFiles);
                });

                if ($this->io) {
                    $this->io->writeln(
                        'Filtered to '.count($filteredFiles).' modified files',
                        OutputInterface::VERBOSITY_VERBOSE
                    );
                }

            } catch (\Exception $e) {
                if ($this->io) {
                    $this->io->warning('Git filtering failed: '.$e->getMessage());
                    $this->io->writeln(
                        'Continuing with unfiltered results...',
                        OutputInterface::VERBOSITY_VERBOSE
                    );
                } else {
                    throw $e; // Re-throw if we can't communicate the error
                }
            }
        }

        // Then apply AI filtering if requested
        if ($this->aiFilterDescription !== null && ! empty($filteredFiles)) {
            try {
                $aiFilter = new OpenAIFileFilter;

                if ($this->io) {
                    $this->io->writeln('Applying AI filter...', OutputInterface::VERBOSITY_VERBOSE);
                }

                $filterResult = $aiFilter->filterFiles($filteredFiles, $this->aiFilterDescription);

                // Update filtered files with AI results
                $filteredFiles = $filterResult['files'];

                // Show AI explanation if we have IO
                if ($this->io) {
                    $this->io->writeln(
                        'AI Filter explanation: '.$filterResult['explanation'],
                        OutputInterface::VERBOSITY_VERBOSE
                    );
                }
            } catch (\Exception $e) {
                if ($this->io) {
                    $this->io->warning('AI filtering failed: '.$e->getMessage());
                    $this->io->writeln(
                        'Continuing with unfiltered results...',
                        OutputInterface::VERBOSITY_VERBOSE
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
