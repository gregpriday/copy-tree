<?php

namespace GregPriday\CopyTree\Ruleset;

class LaravelRuleset implements RulesetInterface
{
    public function shouldIncludeDirectory(string $directory): bool
    {
        $allowedDirs = ['app', 'database/migrations', 'routes'];
        return in_array(basename($directory), $allowedDirs);
    }

    public function shouldIncludeFile(string $file): bool
    {
        return true; // Include all files
    }
}
