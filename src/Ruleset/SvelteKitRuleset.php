<?php

namespace GregPriday\CopyTree\Ruleset;

class SvelteKitRuleset implements RulesetInterface
{
    public function shouldIncludeDirectory(string $directory): bool
    {
        return basename($directory) !== 'node_modules';
    }

    public function shouldIncludeFile(string $file): bool
    {
        return true; // Include all files
    }
}
