<?php

namespace GregPriday\CopyTree\Ruleset;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Manages ruleset selection and loading for the copy tree operation.
 *
 * Handles custom, predefined, and auto-detected rulesets, prioritizing them appropriately.
 */
class RulesetManager
{
    private string $basePath;

    private ?SymfonyStyle $io;

    public function __construct(string $basePath, ?SymfonyStyle $io = null)
    {
        $this->basePath = $basePath;
        $this->io = $io;
    }

    public function getRuleset(string $rulesetOption): RulesetFilter
    {
        if ($rulesetOption === 'none') {
            if ($this->io) {
                $this->io->writeln('Using no ruleset', SymfonyStyle::VERBOSITY_VERBOSE);
            }

            return $this->createEmptyRuleset();
        }
        if ($rulesetOption !== 'auto') {
            // Look for custom ruleset in project directory
            $customRulesetPath = $this->basePath.'/.ctree/'.$rulesetOption.'.json';
            if (file_exists($customRulesetPath)) {
                if ($this->io) {
                    $this->io->writeln(sprintf('Using custom ruleset: %s', $customRulesetPath), SymfonyStyle::VERBOSITY_VERBOSE);
                }

                return RulesetFilter::fromJson(file_get_contents($customRulesetPath), $this->basePath);
            }

            // Look for predefined ruleset in PROJECT_ROOT/rulesets/
            $predefinedRulesetPath = PROJECT_ROOT.'/rulesets/'.$rulesetOption.'.json';
            if (file_exists($predefinedRulesetPath)) {
                if ($this->io) {
                    $this->io->writeln(sprintf('Using predefined ruleset: %s', $rulesetOption), SymfonyStyle::VERBOSITY_VERBOSE);
                }

                return RulesetFilter::fromJson(file_get_contents($predefinedRulesetPath), $this->basePath);
            }

            // If ruleset is not found, throw an error
            throw new \InvalidArgumentException(sprintf('Ruleset "%s" not found.', $rulesetOption));
        }

        // Check for custom default ruleset
        $customDefaultRulesetPath = $this->basePath.'/.ctree/ruleset.json';
        if (file_exists($customDefaultRulesetPath)) {
            if ($this->io) {
                $this->io->writeln(sprintf('Using custom default ruleset: %s', $customDefaultRulesetPath), SymfonyStyle::VERBOSITY_VERBOSE);
            }

            return RulesetFilter::fromJson(file_get_contents($customDefaultRulesetPath), $this->basePath);
        }

        // Auto-detect ruleset
        $guessedRuleset = $this->guessRuleset();
        if ($guessedRuleset !== 'default') {
            $guessedRulesetPath = PROJECT_ROOT.'/rulesets/'.$guessedRuleset.'.json';
            if (file_exists($guessedRulesetPath)) {
                if ($this->io) {
                    $this->io->writeln(sprintf('Auto-detected ruleset: %s', $guessedRuleset), SymfonyStyle::VERBOSITY_VERBOSE);
                }

                return RulesetFilter::fromJson(file_get_contents($guessedRulesetPath), $this->basePath);
            }
        }

        // Use default ruleset
        $defaultRulesetPath = PROJECT_ROOT.'/rulesets/default.json';
        if ($this->io) {
            $this->io->writeln('Using default ruleset', SymfonyStyle::VERBOSITY_VERBOSE);
        }

        return RulesetFilter::fromJson(file_get_contents($defaultRulesetPath), $this->basePath);
    }

    private function getPredefinedRulesetPath(string $rulesetName): ?string
    {
        $rulesetPath = realpath(PROJECT_ROOT.'/rulesets/'.$rulesetName.'.json');

        return $rulesetPath && file_exists($rulesetPath) ? $rulesetPath : null;
    }

    private function getDefaultRulesetPath(): string
    {
        return realpath(PROJECT_ROOT.'/rulesets/default.json');
    }

    private function guessRuleset(): string
    {
        $guesser = new RulesetGuesser($this->basePath, $this->getAvailableRulesets());

        return $guesser->guess();
    }

    public function getAvailableRulesets(): array
    {
        // Get predefined rulesets
        $rulesetDir = realpath(PROJECT_ROOT.'/rulesets');
        $predefinedRulesets = glob($rulesetDir.'/*.json');
        $rulesets = array_map(function ($path) {
            return basename($path, '.json');
        }, $predefinedRulesets);

        // Get custom rulesets
        $customRulesetDir = $this->basePath.'/.ctree';
        if (is_dir($customRulesetDir)) {
            $customRulesets = glob($customRulesetDir.'/*.json');
            $customRulesets = array_map(function ($path) {
                return basename($path, '.json');
            }, $customRulesets);
            $rulesets = array_merge($rulesets, $customRulesets);
        }

        // Remove 'default' from the list if it exists
        $rulesets = array_diff($rulesets, ['default', 'ruleset', 'schema']);

        // Add 'auto' option
        array_unshift($rulesets, 'auto');

        return array_unique($rulesets);
    }

    public function createRulesetFromGlob(string $glob): RulesetFilter
    {
        return RulesetFilter::fromArray([
            'rules' => [
                [['path', 'glob', $glob]],
            ],
        ], $this->basePath);
    }

    public function createEmptyRuleset(): RulesetFilter
    {
        return RulesetFilter::fromArray([
            'rules' => [],
            'globalExcludeRules' => [],
            'always' => [
                'include' => [],
                'exclude' => [],
            ],
        ], $this->basePath);
    }
}
