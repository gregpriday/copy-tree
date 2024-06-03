<?php

namespace GregPriday\CopyTree\Ruleset;

class LaravelRuleset implements RulesetInterface
{
    protected static array $allowedDirs = ['app', 'database/migrations', 'routes'];
    protected static array $skippedFiles = ['composer.lock', 'package-lock.json', 'phpunit.xml'];

    public function shouldIncludeDirectory(string $directory): bool
    {
        foreach (self::$allowedDirs as $allowedDir) {
            if (str_starts_with($directory, $allowedDir)) {
                return true;
            }
        }
        return false;
    }

    public function shouldIncludeFile(string $file): bool
    {
        // Do not include composer.lock or package-lock.json
        if (in_array(basename($file), self::$skippedFiles)) {
            return false;
        }

        return true; // Include all files
    }
}
