<?php

namespace GregPriday\CopyTree\Filters;

use GregPriday\CopyTree\Filters\Ruleset\RulesetFilter;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;

class FilterConfiguration
{
    public function __construct(
        private readonly ?string $rulesetName,
        private readonly array $globPatterns,
        private readonly ?string $aiFilterDescription,
        private readonly bool $modifiedOnly,
        private readonly ?string $changes,
        private readonly int $maxDepth,
        private readonly int $maxLines
    ) {}

    public static function fromInput(InputInterface $input): self
    {
        return new self(
            rulesetName: $input->getOption('ruleset'),
            globPatterns: (array) $input->getOption('filter'),
            aiFilterDescription: $input->getOption('ai-filter') !== false ?
                ($input->getOption('ai-filter') ?: null) : null,
            modifiedOnly: $input->getOption('modified'),
            changes: $input->getOption('changes'),
            maxDepth: (int) $input->getOption('depth'),
            maxLines: (int) $input->getOption('max-lines')
        );
    }

    public function getRulesetName(): ?string
    {
        return $this->rulesetName;
    }

    public function getGlobPatterns(): array
    {
        return $this->globPatterns;
    }

    public function getAiFilterDescription(): ?string
    {
        return $this->aiFilterDescription;
    }

    public function isModifiedOnly(): bool
    {
        return $this->modifiedOnly;
    }

    public function getChanges(): ?string
    {
        return $this->changes;
    }

    public function getMaxDepth(): int
    {
        return $this->maxDepth;
    }

    public function getMaxLines(): int
    {
        return $this->maxLines;
    }

    public function validate(): void {
        if ($this->modifiedOnly && $this->changes !== null) {
            throw new RuntimeException('The --modified and --changes options cannot be used together');
        }

        if ($this->maxDepth < 1) {
            throw new RuntimeException('Depth must be greater than 0');
        }

        if ($this->maxLines < 0) {
            throw new RuntimeException('Max lines cannot be negative');
        }
    }
}

