<?php

namespace GregPriday\CopyTree\Ruleset;

use JsonSchema\Validator;
use Symfony\Component\Finder\Glob;

class JsonRuleset
{
    private array $rules;

    public function __construct(string $jsonFilePath)
    {
        $this->loadAndValidateJson($jsonFilePath);
    }

    private function loadAndValidateJson(string $jsonFilePath): void
    {
        if (! file_exists($jsonFilePath)) {
            throw new \RuntimeException("JSON file not found: $jsonFilePath");
        }

        $json = json_decode(file_get_contents($jsonFilePath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON: '.json_last_error_msg());
        }

        $validator = new Validator();
        $validator->validate($json, (object) ['$ref' => 'file://'.realpath(__DIR__.'/ruleset-schema.json')]);

        if (! $validator->isValid()) {
            $errors = array_map(function ($error) {
                return sprintf('[%s] %s', $error['property'], $error['message']);
            }, $validator->getErrors());

            throw new \RuntimeException("JSON does not validate against the schema:\n".implode("\n", $errors));
        }

        $this->rules = $json;
    }

    private function loadAlwaysRuleset(): void
    {
        $alwaysPath = realpath(__DIR__.'/../../rulesets/always.json');
        if (file_exists($alwaysPath)) {
            $this->alwaysRules = json_decode(file_get_contents($alwaysPath), true);
        } else {
            $this->alwaysRules = ['exclude' => ['directories' => [], 'files' => []]];
        }
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
            if ($pattern['type'] === 'glob') {
                if (Glob::match($path, $pattern['pattern'])) {
                    return true;
                }
            } elseif ($pattern['type'] === 'regex') {
                if (preg_match('/'.str_replace('/', '\/', $pattern['pattern']).'/', $path)) {
                    return true;
                }
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
