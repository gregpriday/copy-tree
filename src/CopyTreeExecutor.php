<?php

namespace GregPriday\CopyTree;

use GregPriday\CopyTree\Ruleset\RulesetManager;
use GregPriday\CopyTree\Views\FileContentsView;
use GregPriday\CopyTree\Views\FileTreeView;

/**
 * Executes the copy tree operation, applying rulesets and generating output.
 *
 * Manages the process of filtering files, rendering the tree view and file contents.
 */
class CopyTreeExecutor
{
    private RulesetManager $rulesetManager;

    public function __construct(
        RulesetManager $rulesetManager,
        private string $rulesetOption,
        private bool $onlyTree
    ) {
        $this->rulesetManager = $rulesetManager;
    }

    public function execute(): array
    {
        $ruleset = $this->rulesetManager->getRuleset($this->rulesetOption);
        $filteredFiles = iterator_to_array($ruleset->getFilteredFiles());

        $treeOutput = FileTreeView::render($filteredFiles);
        $combinedOutput = $treeOutput;

        if (! $this->onlyTree) {
            $fileContentsOutput = FileContentsView::render($filteredFiles);
            $combinedOutput .= "\n\n---\n\n".$fileContentsOutput;
        }

        return [
            'output' => $combinedOutput,
            'fileCount' => count($filteredFiles),
        ];
    }
}
