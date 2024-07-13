<?php

namespace GregPriday\CopyTree\Ruleset;

use Symfony\Component\Finder\Glob;

class IncludeRuleset
{
    private array $primaryIncludeRules = [];

    private array $secondaryIncludeRules = [];

    private array $excludeRules = [];

    private array $forceIncludeRules = [];

    private array $forceExcludeRules = [];

    public function __construct(string $rulesetPath)
    {
        $this->loadRules($rulesetPath);
    }

    private function loadRules(string $rulesetPath): void
    {
        if (! file_exists($rulesetPath)) {
            throw new \RuntimeException("Ruleset file not found: $rulesetPath");
        }

        $lines = file($rulesetPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_filter($lines, fn ($line) => ! str_starts_with(trim($line), '#'));

        foreach ($lines as $line) {
            if (str_starts_with($line, '+')) {
                $this->forceIncludeRules[] = substr($line, 1);
            } elseif (str_starts_with($line, '-')) {
                $this->forceExcludeRules[] = substr($line, 1);
            } elseif (str_starts_with($line, '!')) {
                $this->excludeRules[] = substr($line, 1);
            } elseif (str_starts_with($line, '~')) {
                $this->secondaryIncludeRules[] = substr($line, 1);
            } else {
                $this->primaryIncludeRules[] = $line;
            }
        }
    }

    public function shouldInclude(string $path): bool
    {
        // Check force include rules first
        foreach ($this->forceIncludeRules as $rule) {
            if ($this->matchesPattern($path, $rule)) {
                return true;
            }
        }

        // Check force exclude rules next
        foreach ($this->forceExcludeRules as $rule) {
            if ($this->matchesPattern($path, $rule)) {
                return false;
            }
        }

        // If there are no primary include rules, include everything except excluded
        if (empty($this->primaryIncludeRules)) {
            foreach ($this->excludeRules as $rule) {
                if ($this->matchesPattern($path, $rule)) {
                    return false;
                }
            }

            return true;
        }

        // Check if the path matches any primary include rule
        $includedByPrimary = false;
        foreach ($this->primaryIncludeRules as $rule) {
            if ($this->matchesPattern($path, $rule)) {
                $includedByPrimary = true;
                break;
            }
        }

        // If not included by primary rules, return false
        if (! $includedByPrimary) {
            return false;
        }

        // If there are secondary include rules, the path must match at least one
        if (! empty($this->secondaryIncludeRules)) {
            $includedBySecondary = false;
            foreach ($this->secondaryIncludeRules as $rule) {
                if ($this->matchesPattern($path, $rule)) {
                    $includedBySecondary = true;
                    break;
                }
            }
            if (! $includedBySecondary) {
                return false;
            }
        }

        // Check if the path matches any exclude rule
        foreach ($this->excludeRules as $rule) {
            if ($this->matchesPattern($path, $rule)) {
                return false;
            }
        }

        return true;
    }

    private function matchesPattern(string $path, string $pattern): bool
    {
        $pattern = $this->globToRegex($pattern);

        return preg_match($pattern, $path) === 1;
    }

    private function globToRegex(string $glob): string
    {
        $regex = preg_quote($glob, '/');

        // Convert glob wildcards to regex
        $regex = str_replace(
            ['\*', '\?', '\[', '\]', '\\-', '\{', '\}', ','],
            ['.*', '.', '[', ']', '-', '(', ')', '|'],
            $regex
        );

        // Handle directory matching
        $regex = str_replace('/**/', '(/|/.*/)', $regex);
        $regex = str_replace('/**', '(/.*)?', $regex);
        $regex = str_replace('/*', '/[^/]*', $regex);

        return '/^'.$regex.'$/';
    }
}
