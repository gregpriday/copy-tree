<?php

namespace GregPriday\CopyTree\Workspace;

use GregPriday\CopyTree\Ruleset\RulesetFilter;
use GregPriday\CopyTree\Ruleset\RulesetManager;

class WorkspaceResolver
{
    private RulesetManager $rulesetManager;

    public function __construct(RulesetManager $rulesetManager)
    {
        $this->rulesetManager = $rulesetManager;
    }

    public function resolveWorkspace(array $workspace): RulesetFilter
    {
        $ruleset = null;
        if (isset($workspace['extends'])) {
            $ruleset = $this->rulesetManager->getRuleset($workspace['extends']);
        } else {
            $ruleset = $this->rulesetManager->createEmptyRuleset();
        }

        if (isset($workspace['rules'])) {
            foreach ($workspace['rules'] as $ruleSet) {
                $ruleset->addIncludeRuleSet($ruleSet);
            }
        }

        if (isset($workspace['globalExcludeRules'])) {
            foreach ($workspace['globalExcludeRules'] as $rule) {
                $ruleset->addGlobalExcludeRule($rule);
            }
        }

        if (isset($workspace['always'])) {
            if (isset($workspace['always']['include'])) {
                $ruleset->addAlwaysIncludeFiles($workspace['always']['include']);
            }
            if (isset($workspace['always']['exclude'])) {
                $ruleset->addAlwaysExcludeFiles($workspace['always']['exclude']);
            }
        }

        return $ruleset;
    }
}
