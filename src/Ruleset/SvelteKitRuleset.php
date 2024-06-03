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
        // Do not include composer.lock or package-lock.json
        if (basename($file) === 'package-lock.json') {
            return false;
        }

        return true; // Include all files
    }
}
