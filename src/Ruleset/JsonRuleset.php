<?php

namespace GregPriday\CopyTree\Ruleset;

use JsonSchema\Validator;
use Symfony\Component\Finder\Glob;

class JsonRuleset
{
    private array $rules;

    private string $rulesetDir;

    public function __construct(string $jsonFilePath)
    {
        $this->rulesetDir = dirname($jsonFilePath);
        $this->rules = $this->loadAndMergeRulesets($jsonFilePath);
    }

    private function loadAndMergeRulesets(string $jsonFilePath): array
    {
        $ruleset = $this->loadAndValidateJson($jsonFilePath);

        if (isset($ruleset['extends'])) {
            $parentRulesetPath = $this->rulesetDir.'/'.$ruleset['extends'].'.json';
            $parentRuleset = $this->loadAndMergeRulesets($parentRulesetPath);

            return $this->mergeRulesets($parentRuleset, $ruleset);
        }

        return $ruleset;
    }

    private function loadAndValidateJson(string $jsonFilePath): array
    {
        if (! file_exists($jsonFilePath)) {
            throw new \RuntimeException("JSON file not found: $jsonFilePath");
        }

        $jsonContent = file_get_contents($jsonFilePath);
        $json = json_decode($jsonContent);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON: '.json_last_error_msg());
        }

        $validator = new Validator();
        $schema = (object) ['$ref' => 'file://'.realpath(__DIR__.'/../../rulesets/schema.json')];
        $validator->validate($json, $schema);

        if (! $validator->isValid()) {
            $errors = array_map(function ($error) {
                return sprintf('[%s] %s', $error['property'], $error['message']);
            }, $validator->getErrors());

            throw new \RuntimeException("JSON does not validate against the schema:\n".implode("\n", $errors));
        }

        return json_decode($jsonContent, true);
    }

    private function mergeRulesets(array $parent, array $child): array
    {
        $merged = array_merge($parent, $child);

        foreach (['include', 'exclude'] as $section) {
            if (isset($parent[$section]) && isset($child[$section])) {
                $merged[$section] = [
                    'directories' => array_merge($parent[$section]['directories'] ?? [], $child[$section]['directories'] ?? []),
                    'files' => array_merge($parent[$section]['files'] ?? [], $child[$section]['files'] ?? []),
                ];
            }
        }

        return $merged;
    }

    public function shouldIncludeDirectory(string $directory): bool
    {
        if ($this->matchesPatterns($directory, $this->alwaysRules['exclude']['directories'] ?? [])) {
            return false;
        }
        if ($this->matchesPatterns($directory, $this->rules['exclude']['directories'] ?? [])) {
            return false;
        }

        return $this->matchesPatterns($directory, $this->rules['include']['directories'] ?? []);
    }

    public function shouldIncludeFile(string $file): bool
    {
        if ($this->matchesPatterns($file, $this->alwaysRules['exclude']['files'] ?? [])) {
            return false;
        }
        if ($this->matchesPatterns($file, $this->rules['exclude']['files'] ?? [])) {
            return false;
        }

        return $this->matchesPatterns($file, $this->rules['include']['files'] ?? []);
    }

    private function matchesPatterns(string $path, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            $regex = match ($pattern['type']) {
                'glob' => Glob::toRegex($pattern['pattern']),
                'regex' => '/'.str_replace('/', '\/', $pattern['pattern']).'/',
            };

            if (! empty($regex) && preg_match($regex, $path)) {
                return true;
            }
        }

        return false;
    }

    public function getMaxDepth(): int
    {
        return $this->rules['maxDepth'] ?? PHP_INT_MAX;
    }

    public function getName(): string
    {
        return $this->rules['name'];
    }

    public function getDescription(): string
    {
        return $this->rules['description'] ?? '';
    }

    public static function createDefaultRuleset(): self
    {
        return new self(__DIR__.'/default-ruleset.json');
    }
}
