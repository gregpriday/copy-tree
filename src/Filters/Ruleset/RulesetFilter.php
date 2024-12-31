<?php

namespace GregPriday\CopyTree\Filters\Ruleset;

use Generator;
use GregPriday\CopyTree\Filters\FileFilterInterface;
use GregPriday\CopyTree\Ruleset\FilteredDirIterator;
use GregPriday\CopyTree\Ruleset\Rules\FileAttributeExtractor;
use GregPriday\CopyTree\Ruleset\Rules\Rule;
use GregPriday\CopyTree\Ruleset\Rules\RuleEvaluator;
use InvalidArgumentException;
use RuntimeException;
use SplFileInfo;

/**
 * Applies ruleset filters to determine which files to include or exclude.
 *
 * Supports complex rule combinations, including global exclusions, conditional inclusions, and always-include files.
 */
class RulesetFilter implements FileFilterInterface
{
    private string $basePath;

    private RuleEvaluator $ruleEvaluator;

    private FileAttributeExtractor $attributeExtractor;

    private array $includeRuleSets = [];

    private array $globalExcludeRules = [];

    private array $alwaysIncludeFiles = [];

    private array $alwaysExcludeFiles = [];

    private ?string $description = null;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim(realpath($basePath), '/');
        $this->ruleEvaluator = new RuleEvaluator($this->basePath);
        $this->attributeExtractor = new FileAttributeExtractor($this->basePath);
    }

    /**
     * Set a custom description for this ruleset filter.
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
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
                $engine->addIncludeRuleSet(self::convertToRules($ruleSet));
            }
        }

        if (isset($data['globalExcludeRules'])) {
            foreach ($data['globalExcludeRules'] as $rule) {
                $engine->addGlobalExcludeRule(Rule::fromArray($rule));
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

    /**
     * Convert array-based rules to Rule objects
     *
     * @param  array  $rules  Array of rule arrays
     * @return array<Rule> Array of Rule objects
     */
    private static function convertToRules(array $rules): array
    {
        return array_map(function ($rule) {
            // Handle OR conditions
            if (is_array($rule) && isset($rule[0]) && $rule[0] === 'OR') {
                return [
                    'type' => 'OR',
                    'rules' => array_map(fn ($r) => Rule::fromArray($r), $rule[1]),
                ];
            }

            return Rule::fromArray($rule);
        }, $rules);
    }

    public function addIncludeRuleSet(array $rules): self
    {
        $this->includeRuleSets[] = $rules;

        return $this;
    }

    public function addGlobalExcludeRule(Rule $rule): self
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
     * Implementation of FileFilterInterface::filter()
     *
     * If files array is empty, scans the base path. Otherwise, filters the provided files.
     */
    public function filter(array $files, array $context = []): array
    {
        // If no files provided, scan the base path
        if (empty($files)) {
            return iterator_to_array($this->getFilteredFiles());
        }

        // Otherwise, filter the provided files
        return array_filter($files, function ($file) {
            return $this->shouldIncludeFile($file['file'], $file['path']);
        });
    }

    /**
     * Get filtered files using a generator approach.
     *
     * @return Generator<array{path: string, file: SplFileInfo}>
     */
    public function getFilteredFiles(): Generator
    {
        if (! is_dir($this->basePath)) {
            throw new RuntimeException("Base path does not exist: {$this->basePath}");
        }

        $iterator = new \RecursiveIteratorIterator(
            new FilteredDirIterator(
                $this->basePath,
                \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = $this->getRelativePath($file);
                if ($this->shouldIncludeFile($file, $relativePath)) {
                    yield [
                        'path' => $relativePath,
                        'file' => $file,
                    ];
                }
            }
        }
    }

    /**
     * Implementation of FileFilterInterface::getDescription()
     */
    public function getDescription(): string
    {
        if ($this->description !== null) {
            return $this->description;
        }

        $parts = [];

        if (! empty($this->includeRuleSets)) {
            $parts[] = sprintf('%d include rule sets', count($this->includeRuleSets));
        }

        if (! empty($this->globalExcludeRules)) {
            $parts[] = sprintf('%d global exclude rules', count($this->globalExcludeRules));
        }

        if (! empty($this->alwaysIncludeFiles)) {
            $parts[] = sprintf('%d always-include files', count($this->alwaysIncludeFiles));
        }

        if (! empty($this->alwaysExcludeFiles)) {
            $parts[] = sprintf('%d always-exclude files', count($this->alwaysExcludeFiles));
        }

        return empty($parts)
            ? 'No rules configured'
            : 'Ruleset filter with '.implode(', ', $parts);
    }

    /**
     * Implementation of FileFilterInterface::shouldApply()
     */
    public function shouldApply(array $context = []): bool
    {
        // RulesetFilter should always apply if it has any rules configured
        return ! empty($this->includeRuleSets)
            || ! empty($this->globalExcludeRules)
            || ! empty($this->alwaysIncludeFiles)
            || ! empty($this->alwaysExcludeFiles);
    }

    private function isAlwaysIncluded(string $relativePath): bool
    {
        return in_array($relativePath, $this->alwaysIncludeFiles);
    }

    private function isAlwaysExcluded(string $relativePath): bool
    {
        return in_array($relativePath, $this->alwaysExcludeFiles);
    }

    private function shouldIncludeFile(SplFileInfo $file, string $relativePath): bool
    {
        // Always skip images
        if ($this->attributeExtractor->isImage($file)) {
            return false;
        }

        // Check always exclude files second
        if ($this->isAlwaysExcluded($relativePath)) {
            return false;
        }

        // Check always include files first
        if ($this->isAlwaysIncluded($relativePath)) {
            return true;
        }

        // Check global exclude rules
        foreach ($this->globalExcludeRules as $rule) {
            if ($this->ruleEvaluator->evaluateRule($rule, $file)) {
                return false;
            }
        }

        if (empty($this->includeRuleSets)) {
            return true;
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
            if (is_array($rule) && isset($rule['type']) && $rule['type'] === 'OR') {
                if (! $this->matchesAnyRule($file, $rule['rules'])) {
                    return false;
                }
            } elseif ($rule instanceof Rule) {
                if (! $this->ruleEvaluator->evaluateRule($rule, $file)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function matchesAnyRule(SplFileInfo $file, array $rules): bool
    {
        foreach ($rules as $rule) {
            if ($this->ruleEvaluator->evaluateRule($rule, $file)) {
                return true;
            }
        }

        return false;
    }

    private function getRelativePath(SplFileInfo $file): string
    {
        return str_replace($this->basePath.'/', '', $file->getRealPath());
    }
}
