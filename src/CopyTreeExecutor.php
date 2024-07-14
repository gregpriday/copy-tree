<?php

namespace GregPriday\CopyTree;

use GregPriday\CopyTree\Ruleset\RulesetManager;
use GregPriday\CopyTree\Views\FileContentsView;
use GregPriday\CopyTree\Views\FileTreeView;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Executes the copy tree operation, applying rulesets and generating output.
 *
 * Manages the process of filtering files, rendering the tree view and file contents.
 */
class CopyTreeExecutor
{
    private RulesetManager $rulesetManager;

    public function __construct(RulesetManager $rulesetManager)
    {
        $this->rulesetManager = $rulesetManager;
    }

    public function execute(InputInterface $input, OutputInterface $output): array
    {
        $path = $input->getArgument('path');
        $rulesetOption = $input->getOption('ruleset');
        $noContents = $input->getOption('no-contents');

        $ruleset = $this->rulesetManager->getRuleset($rulesetOption);
        $filteredFiles = iterator_to_array($ruleset->getFilteredFiles());

        $treeOutput = FileTreeView::render($filteredFiles);
        $combinedOutput = $treeOutput;

        if (! $noContents) {
            $fileContentsOutput = FileContentsView::render($filteredFiles);
            $combinedOutput .= "\n\n---\n\n".$fileContentsOutput;
        }

        return [
            'output' => $combinedOutput,
            'fileCount' => count($filteredFiles),
        ];
    }
}
