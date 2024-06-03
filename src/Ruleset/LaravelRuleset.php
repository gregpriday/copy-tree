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
        // Do not include composer.lock or package-lock.json
        if (basename($file) === 'composer.lock' || basename($file) === 'package-lock.json') {
            return false;
        }

        return true; // Include all files
    }
}
