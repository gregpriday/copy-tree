<?php

namespace GregPriday\CopyTree\Filters\Ruleset\Rules;

use Carbon\Carbon;
use finfo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SplFileInfo;
use Symfony\Component\Finder\Glob;

class RuleEvaluator
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim(realpath($basePath), '/');
    }

    public function evaluateRule(Rule $rule, SplFileInfo $file): bool
    {
        $fieldValue = $this->getFieldValue($file, $rule->getField());
        $operator = $rule->getOperator();
        $value = $rule->getValue();

        $baseOperator = $operator->getBaseOperator();
        $result = $this->compareValues($fieldValue, $value, $baseOperator);

        $comparisonResult = match ($baseOperator) {
            RuleOperator::EQUALS => $result === 0,
            RuleOperator::NOT_EQUALS => $result !== 0,
            RuleOperator::GREATER_THAN => $result > 0,
            RuleOperator::GREATER_THAN_EQUALS => $result >= 0,
            RuleOperator::LESS_THAN => $result < 0,
            RuleOperator::LESS_THAN_EQUALS => $result <= 0,
            RuleOperator::ONE_OF => is_array($value) && in_array($fieldValue, $value),
            RuleOperator::REGEX => is_string($value) && preg_match($value, $fieldValue) === 1,
            RuleOperator::GLOB => is_string($value) && preg_match(Glob::toRegex($value), $fieldValue) === 1,
            RuleOperator::FNMATCH => is_string($value) && fnmatch($value, $fieldValue),
            RuleOperator::IS_ASCII => ctype_print($fieldValue),
            RuleOperator::IS_JSON => $this->isValidJson($fieldValue),
            RuleOperator::IS_URL => filter_var($fieldValue, FILTER_VALIDATE_URL) !== false,
            RuleOperator::IS_UUID => preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $fieldValue) === 1,
            RuleOperator::IS_ULID => preg_match('/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/', $fieldValue) === 1,
            default => $this->handleStrOperations($baseOperator, $fieldValue, $value),
        };

        return $operator->isNegation() ? ! $comparisonResult : $comparisonResult;
    }

    private function getFieldValue(SplFileInfo $file, RuleField $field): mixed
    {
        $relativePath = $this->getRelativePath($file);
        $pathInfo = pathinfo($relativePath);

        return match ($field) {
            RuleField::FOLDER => dirname($relativePath),
            RuleField::PATH => $relativePath,
            RuleField::DIRNAME => $pathInfo['dirname'],
            RuleField::BASENAME => $pathInfo['basename'],
            RuleField::EXTENSION => $pathInfo['extension'] ?? '',
            RuleField::FILENAME => $pathInfo['filename'],
            RuleField::CONTENTS => file_get_contents($file->getPathname()),
            RuleField::CONTENTS_SLICE => substr(file_get_contents($file->getPathname()), 0, 256),
            RuleField::SIZE => $file->getSize(),
            RuleField::MTIME => $file->getMTime(),
            RuleField::MIME_TYPE => $this->getMimeType($file),
        };
    }

    private function handleStrOperations(RuleOperator $operator, mixed $fieldValue, mixed $value): bool
    {
        // Get the method name based on the operator
        $method = match ($operator) {
            RuleOperator::CONTAINS => 'contains',
            RuleOperator::STARTS_WITH => 'startsWith',
            RuleOperator::ENDS_WITH => 'endsWith',
            RuleOperator::CONTAINS_ANY => 'containsAny',
            RuleOperator::STARTS_WITH_ANY => 'startsWithAny',
            RuleOperator::ENDS_WITH_ANY => 'endsWithAny',
            default => throw new InvalidArgumentException("Unsupported string operator: {$operator->value}")
        };

        // Handle array-based operations
        if (Str::endsWith($method, 'Any')) {
            if (! is_array($value)) {
                throw new InvalidArgumentException("Value must be an array for {$operator->value} operation");
            }

            $baseMethod = Str::substr($method, 0, -3);
            $collection = new Collection($value);

            return $collection->contains(function ($v) use ($baseMethod, $fieldValue) {
                return Str::$baseMethod($fieldValue, $v);
            });
        }

        // Handle simple string operations
        return Str::$method($fieldValue, $value);
    }

    private function compareValues($a, $b, RuleOperator $operator): int
    {
        // If comparing dates
        if (is_numeric($a) && is_string($b) && strtotime($b) !== false) {
            $dateA = Carbon::createFromTimestamp($a);
            $dateB = Carbon::parse($b);

            return $dateA->compare($dateB);
        }

        // If comparing file sizes
        if (is_numeric($a) && is_string($b) && preg_match('/^\s*((?:\d+\.?\d*|\.\d+))\s*([kmg]i?b?)?\s*$/i', $b, $matches)) {
            $size = (float) $matches[1];
            $unit = $matches[2] ?? '';
            $sizeInBytes = $this->convertToBytes($size, $unit);

            return $a <=> $sizeInBytes;
        }

        // Default comparison
        return $a <=> $b;
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

    private function isValidJson(string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        @json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
