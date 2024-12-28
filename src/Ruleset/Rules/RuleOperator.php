<?php

namespace GregPriday\CopyTree\Ruleset\Rules;

enum RuleOperator: string
{
    case EQUALS = '=';
    case NOT_EQUALS = '!=';
    case GREATER_THAN = '>';
    case GREATER_THAN_EQUALS = '>=';
    case LESS_THAN = '<';
    case LESS_THAN_EQUALS = '<=';
    case ONE_OF = 'oneOf';
    case NOT_ONE_OF = 'notOneOf';
    case REGEX = 'regex';
    case NOT_REGEX = 'notRegex';
    case GLOB = 'glob';
    case FNMATCH = 'fnmatch';
    case CONTAINS = 'contains';
    case NOT_CONTAINS = 'notContains';
    case STARTS_WITH = 'startsWith';
    case NOT_STARTS_WITH = 'notStartsWith';
    case ENDS_WITH = 'endsWith';
    case NOT_ENDS_WITH = 'notEndsWith';
    case STARTS_WITH_ANY = 'startsWithAny';
    case ENDS_WITH_ANY = 'endsWithAny';
    case CONTAINS_ANY = 'containsAny';
    case IS_ASCII = 'isAscii';
    case IS_JSON = 'isJson';
    case IS_ULID = 'isUlid';
    case IS_URL = 'isUrl';
    case IS_UUID = 'isUuid';

    public function isNegation(): bool
    {
        return str_starts_with($this->value, 'not');
    }

    public function getBaseOperator(): self
    {
        if (! $this->isNegation()) {
            return $this;
        }

        // Convert notStartsWith to startsWith, etc.
        $baseOperatorName = lcfirst(substr($this->value, 3));

        return self::from($baseOperatorName);
    }
}
