<?php

namespace GregPriday\CopyTree\Filters;

use GregPriday\CopyTree\Filters\Ruleset\RulesetFilter;

class FilterPipelineConfiguration
{
    public function __construct(
        private readonly string $basePath,
        private readonly FilterConfiguration $filterConfig,
        private readonly RulesetFilter $ruleset
    ) {}

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getFilterConfig(): FilterConfiguration
    {
        return $this->filterConfig;
    }

    public function getRuleset(): RulesetFilter
    {
        return $this->ruleset;
    }

    public function toPipelineOptions(): array
    {
        return [
            'modified' => $this->filterConfig->isModifiedOnly(),
            'changes' => $this->filterConfig->getChanges(),
            'ai-filter' => $this->filterConfig->getAiFilterDescription(),
            'search' => $this->filterConfig->getSearch(),
            'max-depth' => $this->filterConfig->getMaxDepth(),
            'max-lines' => $this->filterConfig->getMaxLines(),
        ];
    }
}
