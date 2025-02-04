<?php

namespace GregPriday\CopyTree\Filters\Ruleset;

use GregPriday\CopyTree\Filters\FileFilterInterface;
use GregPriday\CopyTree\Filters\Ruleset\Rules\FileAttributeExtractor;
use GregPriday\CopyTree\Filters\Ruleset\Rules\Rule;
use GregPriday\CopyTree\Filters\Ruleset\Rules\RuleEvaluator;
// Added use statement for FilteredDirIterator
use InvalidArgumentException;
use RuntimeException;
use SplFileInfo;

/**
 * LocalRulesetFilter handles filtering of local files based on a JSON-defined ruleset.
 *
 * This class is responsible solely for filtering local files using rules, global exclude rules,
 * and always-include/exclude lists. Any logic related to external source processing has been removed.
 *
 * It implements the FileFilterInterface so that upstream consumers of the filtering logic do not
 * require any changes.
 */
class LocalRulesetFilter implements FileFilterInterface
{
    private string $basePath;

    private RuleEvaluator $ruleEvaluator;

    private FileAttributeExtractor $attributeExtractor;

    /**
     * An array of rule sets to include.
     *
     * Each rule set is an array of Rule objects or an array representing an "OR" condition.
     */
    private array $includeRuleSets = [];

    /**
     * An array of global exclude rules (each a Rule object).
     */
    private array $globalExcludeRules = [];

    /**
     * An array of file paths that should always be included.
     */
    private array $alwaysIncludeFiles = [];

    /**
     * An array of file paths that should always be excluded.
     */
    private array $alwaysExcludeFiles = [];

    /**
     * Optional description for the filter (used for logging and debugging).
     */
    private ?string $description = null;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim(realpath($basePath), '/');
        $this->ruleEvaluator = new RuleEvaluator($this->basePath);
        $this->attributeExtractor = new FileAttributeExtractor($this->basePath);
    }

    /**
     * Set a custom description for this local ruleset filter.
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Create a LocalRulesetFilter instance from a JSON string.
     *
     * @param  string  $jsonString  The JSON string defining the ruleset.
     * @param  string  $basePath  The base path for file scanning.
     *
     * @throws InvalidArgumentException if the JSON is invalid.
     */
    public static function fromJson(string $jsonString, string $basePath): self
    {
        $data = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON string: '.json_last_error_msg());
        }

        return self::fromArray($data, $basePath);
    }

    /**
     * Create a LocalRulesetFilter instance from an array.
     *
     * @param  array  $data  The ruleset data.
     * @param  string  $basePath  The base path for file scanning.
     */
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

        // External configuration is intentionally ignored in this local filter.

        return $engine;
    }

    /**
     * Convert an array of rule definitions into an array of Rule objects.
     *
     * Handles simple rules as well as OR conditions.
     *
     * @param  array  $rules  Array of rule definitions.
     * @return array Array of Rule objects or OR-condition arrays.
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

    /**
     * Add a new include rule set.
     *
     * @param  array  $rules  Array of Rule objects (or OR condition arrays).
     */
    public function addIncludeRuleSet(array $rules): self
    {
        $this->includeRuleSets[] = $rules;

        return $this;
    }

    /**
     * Add a new global exclude rule.
     *
     * @param  Rule  $rule  A Rule object.
     */
    public function addGlobalExcludeRule(Rule $rule): self
    {
        $this->globalExcludeRules[] = $rule;

        return $this;
    }

    /**
     * Add file paths that should always be included.
     *
     * @param  array  $files  Array of relative file paths.
     */
    public function addAlwaysIncludeFiles(array $files): self
    {
        $this->alwaysIncludeFiles = array_merge($this->alwaysIncludeFiles, $files);

        return $this;
    }

    /**
     * Add file paths that should always be excluded.
     *
     * @param  array  $files  Array of relative file paths.
     */
    public function addAlwaysExcludeFiles(array $files): self
    {
        $this->alwaysExcludeFiles = array_merge($this->alwaysExcludeFiles, $files);

        return $this;
    }

    /**
     * Filter the given files based on the defined rules.
     *
     * If the files array is empty, the base path will be scanned.
     *
     * @param  array  $files  Array of files (each an array with 'path' and 'file' keys).
     * @param  array  $context  Optional context data.
     * @return array Filtered array of files in the same format as input.
     */
    public function filter(array $files, array $context = []): array
    {
        if (empty($files)) {
            return iterator_to_array($this->getFilteredFiles());
        }

        return array_filter($files, function ($file) {
            return $this->shouldIncludeFile($file['file'], $file['path']);
        });
    }

    /**
     * Scan the base path and yield filtered files using a generator.
     *
     * @return \Generator Yields arrays with 'path' and 'file' keys.
     */
    public function getFilteredFiles(): \Generator
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
     * Return a human-readable description of this filter's configuration.
     *
     * @return string Description text.
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
     * Determine if this filter should be applied.
     *
     * @param  array  $context  Optional context data.
     * @return bool True if the filter should be applied.
     */
    public function shouldApply(array $context = []): bool
    {
        return ! empty($this->includeRuleSets)
            || ! empty($this->globalExcludeRules)
            || ! empty($this->alwaysIncludeFiles)
            || ! empty($this->alwaysExcludeFiles);
    }

    /**
     * Check if a given relative file path is in the always-include list.
     */
    private function isAlwaysIncluded(string $relativePath): bool
    {
        return in_array($relativePath, $this->alwaysIncludeFiles);
    }

    /**
     * Check if a given relative file path is in the always-exclude list.
     */
    private function isAlwaysExcluded(string $relativePath): bool
    {
        return in_array($relativePath, $this->alwaysExcludeFiles);
    }

    /**
     * Determine if a file should be included based on the filtering rules.
     */
    private function shouldIncludeFile(SplFileInfo $file, string $relativePath): bool
    {
        // Always skip images.
        if ($this->attributeExtractor->isImage($file)) {
            return false;
        }

        // Check always-exclude list first.
        if ($this->isAlwaysExcluded($relativePath)) {
            return false;
        }

        // If file is in the always-include list, include it immediately.
        if ($this->isAlwaysIncluded($relativePath)) {
            return true;
        }

        // Apply global exclude rules.
        foreach ($this->globalExcludeRules as $rule) {
            if ($this->ruleEvaluator->evaluateRule($rule, $file)) {
                return false;
            }
        }

        // If no include rule sets are defined, include the file.
        if (empty($this->includeRuleSets)) {
            return true;
        }

        // Check each include rule set; if the file matches all rules in any set, include it.
        foreach ($this->includeRuleSets as $ruleSet) {
            if ($this->matchesAllRules($file, $ruleSet)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a file matches all the rules in a given rule set.
     */
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

    /**
     * Determine if a file matches any one of the provided rules.
     */
    private function matchesAnyRule(SplFileInfo $file, array $rules): bool
    {
        foreach ($rules as $rule) {
            if ($this->ruleEvaluator->evaluateRule($rule, $file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the relative file path from the base path.
     */
    private function getRelativePath(SplFileInfo $file): string
    {
        return str_replace($this->basePath.'/', '', $file->getRealPath());
    }
}
