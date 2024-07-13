<?php

namespace GregPriday\CopyTree\Ruleset;

class RulesetGuesser
{
    private string $projectPath;

    private array $availableRulesets;

    public function __construct(string $projectPath, array $availableRulesets)
    {
        $this->projectPath = $projectPath;
        $this->availableRulesets = $availableRulesets;
    }

    public function guess(): string
    {
        if ($this->isLaravelProject()) {
            return 'laravel';
        }

        if ($this->isSvelteKitProject()) {
            return 'sveltekit';
        }

        // Add more project type checks here

        return 'default';
    }

    private function isLaravelProject(): bool
    {
        return file_exists($this->projectPath.'/artisan') &&
            is_dir($this->projectPath.'/app') &&
            is_dir($this->projectPath.'/bootstrap') &&
            is_dir($this->projectPath.'/config') &&
            is_dir($this->projectPath.'/database');
    }

    private function isSvelteKitProject(): bool
    {
        if (! file_exists($this->projectPath.'/package.json')) {
            return false;
        }

        $packageJson = json_decode(file_get_contents($this->projectPath.'/package.json'), true);

        return isset($packageJson['dependencies']['@sveltejs/kit']) ||
            isset($packageJson['devDependencies']['@sveltejs/kit']);
    }

    public function getRulesetPath(string $rulesetName): ?string
    {
        $rulesetFileName = $rulesetName.'.json';
        if (in_array($rulesetFileName, $this->availableRulesets)) {
            return realpath(__DIR__.'/../../rulesets/'.$rulesetFileName);
        }

        return null;
    }
}
