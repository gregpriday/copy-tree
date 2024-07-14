<?php

namespace GregPriday\CopyTree\Ruleset;

use finfo;
use Generator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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

    private array $alwaysIncludeFiles = [];

    private array $alwaysExcludeFiles = [];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim(realpath($basePath), '/');
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

        if (isset($data['rules'])) {
            foreach ($data['rules'] as $ruleSet) {
                $engine->addIncludeRuleSet($ruleSet);
            }
        }

        if (isset($data['globalExcludeRules'])) {
            foreach ($data['globalExcludeRules'] as $rule) {
                $engine->addGlobalExcludeRule($rule);
            }
        }

        if (isset($data['always'])) {
            if (isset($data['always']['include'])) {
                $engine->addAlwaysIncludeFiles($data['always']['include']);
            }
            if (isset($data['always']['exclude'])) {
                $engine->addAlwaysExcludeFiles($data['always']['exclude']);
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

    public function addAlwaysIncludeFiles(array $files): self
    {
        $this->alwaysIncludeFiles = array_merge($this->alwaysIncludeFiles, $files);

        return $this;
    }

    public function addAlwaysExcludeFiles(array $files): self
    {
        $this->alwaysExcludeFiles = array_merge($this->alwaysExcludeFiles, $files);

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
            new \RecursiveDirectoryIterator(
                $this->basePath,
                \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = $this->getRelativePath($file);
                if ($this->isAlwaysIncluded($relativePath)) {
                    yield $relativePath;

                    continue;
                } elseif ($this->isAlwaysExcluded($relativePath)) {
                    continue;
                }

                if ($this->shouldIncludeFile($file)) {
                    yield $relativePath;
                }
            }
        }
    }

    private function isAlwaysIncluded(string $relativePath): bool
    {
        return in_array($relativePath, $this->alwaysIncludeFiles);
    }

    private function isAlwaysExcluded(string $relativePath): bool
    {
        return in_array($relativePath, $this->alwaysExcludeFiles);
    }

    private function shouldIncludeFile(SplFileInfo $file): bool
    {
        // Check global exclude rules
        foreach ($this->globalExcludeRules as $rule) {
            if ($this->applyRule($file, $rule)) {
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
            default => $this->handleStrOperations($operator, $fieldValue, $value),
        };
    }

    private function handleStrOperations(string $operator, mixed $fieldValue, mixed $value): bool
    {
        if (method_exists(Str::class, $operator)) {
            return Str::$operator($fieldValue, $value);
        }

        if (Str::endsWith($operator, ['Any', 'All'])) {
            $baseOperator = Str::substr($operator, 0, -3);
            if (method_exists(Str::class, $baseOperator)) {
                $suffix = Str::substr($operator, -3);
                if (! is_array($value)) {
                    throw new InvalidArgumentException("Value must be an array for {$operator} operation");
                }
                $checkFunction = function ($v) use ($baseOperator, $fieldValue) {
                    return Str::$baseOperator($fieldValue, $v);
                };
                $collection = new Collection($value);

                return $suffix === 'Any' ? $collection->contains($checkFunction)
                    : $collection->every($checkFunction);
            }
        }

        throw new InvalidArgumentException("Unsupported operator: $operator");
    }

    private function getFieldValue(SplFileInfo $file, string $field): mixed
    {
        $relativePath = $this->getRelativePath($file);
        $pathInfo = pathinfo($relativePath);

        return match ($field) {
            'folder' => dirname($relativePath),
            'path' => $relativePath,
            'dirname' => $pathInfo['dirname'],
            'basename' => $pathInfo['basename'],
            'extension' => $pathInfo['extension'] ?? '',
            'filename' => $pathInfo['filename'],
            'contents' => file_get_contents($file->getPathname()),
            'contents_slice' => substr(file_get_contents($file->getPathname()), 0, 256),
            'size' => $file->getSize(),
            'mtime' => $file->getMTime(),
            'mimeType' => $this->getMimeType($file),
            default => throw new InvalidArgumentException("Unsupported field: $field"),
        };
    }

    private function getRelativePath(SplFileInfo $file): string
    {
        return str_replace($this->basePath.'/', '', $file->getRealPath());
    }

    private function getMimeType(SplFileInfo $file): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        return $finfo->file($file->getPathname());
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
