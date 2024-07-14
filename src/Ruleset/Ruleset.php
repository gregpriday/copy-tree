<?php

namespace GregPriday\CopyTree\Ruleset;

use finfo;
use Generator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SplFileInfo;
use Symfony\Component\Finder\Glob;

class Ruleset
{
    private string $basePath;

    private array $includeRuleSets = [];

    private array $globalExcludeRules = [];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public static function fromJson(string $jsonString, string $basePath): self
    {
        $data = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON string: '.json_last_error_msg());
        }

        return self::fromArray($data, $basePath);
    }

    public static function fromArray(array $data, string $basePath): self
    {
        $engine = new self($basePath);

        if (! isset($data['rules'])) {
            throw new InvalidArgumentException("The 'rules' key is required in the configuration array.");
        }

        foreach ($data['rules'] as $ruleSet) {
            $engine->addIncludeRuleSet($ruleSet);
        }

        if (isset($data['global'])) {
            foreach ($data['global'] as $rule) {
                $engine->addGlobalExcludeRule($rule);
            }
        }

        return $engine;
    }

    public function addIncludeRuleSet(array $rules): self
    {
        $this->includeRuleSets[] = $rules;

        return $this;
    }

    public function addGlobalExcludeRule(array $rule): self
    {
        $this->globalExcludeRules[] = $rule;

        return $this;
    }

    /**
     * Get filtered files using a generator approach.
     *
     * @return Generator<string> A generator yielding relative file paths that match the ruleset
     */
    public function getFilteredFiles(): Generator
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->basePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $this->shouldIncludeFile($file)) {
                yield $this->getRelativePath($file);
            }
        }
    }

    private function shouldIncludeFile(SplFileInfo $file): bool
    {
        // Check global exclude rules
        foreach ($this->globalExcludeRules as $rule) {
            if (! $this->applyRule($file, $rule)) {
                return false;
            }
        }

        // Check include rule sets
        foreach ($this->includeRuleSets as $ruleSet) {
            if ($this->matchesAllRules($file, $ruleSet)) {
                return true;
            }
        }

        return false;
    }

    private function matchesAllRules(SplFileInfo $file, array $rules): bool
    {
        foreach ($rules as $rule) {
            if (is_array($rule) && isset($rule[0]) && $rule[0] === 'OR') {
                if (! $this->matchesAnyRule($file, $rule[1])) {
                    return false;
                }
            } elseif (! $this->applyRule($file, $rule)) {
                return false;
            }
        }

        return true;
    }

    private function matchesAnyRule(SplFileInfo $file, array $rules): bool
    {
        foreach ($rules as $rule) {
            if ($this->applyRule($file, $rule)) {
                return true;
            }
        }

        return false;
    }

    private function applyRule(SplFileInfo $file, array $rule): bool
    {
        [$field, $operator, $value] = $rule;
        $fieldValue = $this->getFieldValue($file, $field);

        $result = $this->compareValues($fieldValue, $value, $operator);

        return match ($operator) {
            '>' => $result > 0,
            '>=' => $result >= 0,
            '<' => $result < 0,
            '<=' => $result <= 0,
            '=' => $result === 0,
            '!=' => $result !== 0,
            'oneOf' => in_array($fieldValue, $value),
            'regex' => preg_match($value, $fieldValue) === 1,
            'glob' => preg_match(Glob::toRegex($value), $fieldValue) === 1,
            'fnmatch' => fnmatch($value, $fieldValue),
            default => method_exists(Str::class, $operator) ?
                Str::$operator($fieldValue, $value) :
                throw new InvalidArgumentException("Unsupported operator: $operator"),
        };
    }

    private function getFieldValue(SplFileInfo $file, string $field): mixed
    {
        $relativePath = $this->getRelativePath($file);
        $pathInfo = pathinfo($file->getPathname());

        return match ($field) {
            'folder' => dirname($relativePath),
            'path' => $relativePath,
            'dirname' => $pathInfo['dirname'],
            'basename' => $pathInfo['basename'],
            'extension' => $pathInfo['extension'] ?? '',
            'filename' => $pathInfo['filename'],
            'contents' => file_get_contents($file->getPathname()),
            'size' => $file->getSize(),
            'mtime' => $file->getMTime(),
            'mimeType' => $this->getMimeType($file),
            default => throw new InvalidArgumentException("Unsupported field: $field"),
        };
    }

    private function getRelativePath(SplFileInfo $file): string
    {
        return str_replace($this->basePath.'/', '', $file->getPathname());
    }

    private function getMimeType(SplFileInfo $file): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        return $finfo->file($file->getPathname());
    }

    private function matchRegex(string $value, string $pattern): bool
    {
        return preg_match($pattern, $value) === 1;
    }

    private function compareValues($a, $b, string $operator): int
    {
        // If comparing dates
        if (is_numeric($a) && is_string($b) && strtotime($b) !== false) {
            $dateA = Carbon::createFromTimestamp($a);
            $dateB = Carbon::parse($b);

            return $dateA->compare($dateB);
        }

        // If comparing file sizes
        if (is_numeric($a) && is_string($b) && preg_match('/^\s*([0-9\.]+)\s*([kmg]i?)?\s*$/i', $b, $matches)) {
            $size = (float) $matches[1];
            $unit = $matches[2] ?? '';
            $sizeInBytes = $this->convertToBytes($size, $unit);

            return $a <=> $sizeInBytes;
        }

        // Default comparison
        return $a <=> $b;
    }

    private function convertToBytes(float $size, string $unit): float
    {
        return match (strtolower($unit)) {
            'k' => $size * 1000,
            'ki' => $size * 1024,
            'm' => $size * 1000000,
            'mi' => $size * 1024 * 1024,
            'g' => $size * 1000000000,
            'gi' => $size * 1024 * 1024 * 1024,
            default => $size,
        };
    }
}
