<?php

namespace GregPriday\CopyTree\Ruleset;

use Symfony\Component\Finder\Glob;

class IgnoreRuleset
{
    private array $includeRules = [];

    private array $excludeRules = [];

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
            if (str_starts_with($line, '!')) {
                $this->includeRules[] = substr($line, 1);
            } else {
                $this->excludeRules[] = $line;
            }
        }

        // Sort rules from most specific to least specific
        usort($this->includeRules, [$this, 'comparePatterns']);
        usort($this->excludeRules, [$this, 'comparePatterns']);
    }

    public function shouldInclude(string $path): bool
    {
        // If there are include rules, the path must match at least one
        if (! empty($this->includeRules)) {
            $included = false;
            foreach ($this->includeRules as $rule) {
                $pattern = Glob::toRegex($rule);
                if (preg_match($pattern, $path)) {
                    $included = true;
                    break;
                }
            }
            if (! $included) {
                return false;
            }
        }

        // The path must not match any exclude rules
        foreach ($this->excludeRules as $rule) {
            $pattern = Glob::toRegex($rule);
            if (preg_match($pattern, $path)) {
                return false;
            }
        }

        // If we've made it this far, the path should be included
        return true;
    }

    private function comparePatterns($a, $b): int
    {
        // Patterns with more path separators are considered more specific
        $aDepth = substr_count($a, '/');
        $bDepth = substr_count($b, '/');

        if ($aDepth != $bDepth) {
            return $bDepth - $aDepth;
        }

        // If same depth, longer patterns are considered more specific
        return strlen($b) - strlen($a);
    }
}
