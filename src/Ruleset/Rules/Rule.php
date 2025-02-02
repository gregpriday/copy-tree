<?php

namespace GregPriday\CopyTree\Ruleset\Rules;

use InvalidArgumentException;

readonly class Rule
{
    /**
     * @param  RuleField  $field  The field to evaluate
     * @param  RuleOperator  $operator  The operator to apply
     * @param  string|int|array<string>|null  $value  The value to compare against
     */
    public function __construct(
        private RuleField $field,
        private RuleOperator $operator,
        private mixed $value
    ) {
        $this->validateValue();
    }

    /**
     * Create a Rule from an array representation
     *
     * @param  array{0: string, 1: string, 2: mixed}  $array
     */
    public static function fromArray(array $array): self
    {
        if (count($array) !== 3) {
            throw new InvalidArgumentException('Rule array must contain exactly 3 elements');
        }

        [$field, $operator, $value] = $array;

        return new self(
            RuleField::from($field),
            RuleOperator::from($operator),
            $value
        );
    }

    public function getField(): RuleField
    {
        return $this->field;
    }

    public function getOperator(): RuleOperator
    {
        return $this->operator;
    }

    /**
     * @return string|int|array<string>|null
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    private function validateValue(): void
    {
        // Validate value based on operator
        match ($this->operator) {
            RuleOperator::ONE_OF,
            RuleOperator::NOT_ONE_OF,
            RuleOperator::STARTS_WITH_ANY,
            RuleOperator::ENDS_WITH_ANY,
            RuleOperator::CONTAINS_ANY => $this->validateArrayValue(),

            RuleOperator::GREATER_THAN,
            RuleOperator::GREATER_THAN_EQUALS,
            RuleOperator::LESS_THAN,
            RuleOperator::LESS_THAN_EQUALS => $this->validateNumericValue(),

            RuleOperator::REGEX,
            RuleOperator::NOT_REGEX => $this->validateRegexValue(),

            default => $this->validateBasicValue()
        };

        // Additional validation for specific fields
        match ($this->field) {
            RuleField::SIZE => $this->validateSizeValue(),
            RuleField::MTIME => $this->validateTimeValue(),
            default => null
        };
    }

    private function validateArrayValue(): void
    {
        if (! is_array($this->value)) {
            throw new InvalidArgumentException(
                "Operator {$this->operator->value} requires an array value"
            );
        }

        if (empty($this->value)) {
            throw new InvalidArgumentException(
                "Array value for operator {$this->operator->value} cannot be empty"
            );
        }

        foreach ($this->value as $item) {
            if (! is_string($item)) {
                throw new InvalidArgumentException(
                    "All array items for operator {$this->operator->value} must be strings"
                );
            }
        }
    }

    private function validateNumericValue(): void
    {
        if (! is_numeric($this->value) && ! $this->isHumanReadableSize($this->value)) {
            throw new InvalidArgumentException(
                "Operator {$this->operator->value} requires a numeric value or human-readable size"
            );
        }
    }

    private function validateRegexValue(): void
    {
        if (! is_string($this->value)) {
            throw new InvalidArgumentException('Regex pattern must be a string');
        }

        if (@preg_match($this->value, '') === false) {
            throw new InvalidArgumentException('Invalid regex pattern: '.$this->value);
        }
    }

    private function validateBasicValue(): void
    {
        if (! is_string($this->value) && ! is_numeric($this->value) && ! is_null($this->value)) {
            throw new InvalidArgumentException(
                "Invalid value type for operator {$this->operator->value}"
            );
        }
    }

    private function validateSizeValue(): void
    {
        if (is_string($this->value) && ! $this->isHumanReadableSize($this->value)) {
            throw new InvalidArgumentException(
                'Size value must be numeric or a valid human-readable size (e.g., "5MB")'
            );
        }
    }

    private function validateTimeValue(): void
    {
        if (is_string($this->value) && strtotime($this->value) === false) {
            throw new InvalidArgumentException(
                'Time value must be numeric (timestamp) or a valid date string'
            );
        }
    }

    private function isHumanReadableSize(string $value): bool
    {
        return (bool) preg_match('/^\s*(?:\d+\.?\d*|\.\d+)\s*(?:[kmgt]i?b?)?\s*$/i', $value);
    }

    /**
     * Convert the rule back to its array representation
     *
     * @return array{0: string, 1: string, 2: mixed}
     */
    public function toArray(): array
    {
        return [
            $this->field->value,
            $this->operator->value,
            $this->value,
        ];
    }
}
