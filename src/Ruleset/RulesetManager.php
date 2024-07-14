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
        $customRulesetPath = $this->basePath.'/.ctree/ruleset.json';
        if (file_exists($customRulesetPath)) {
            if ($this->io) {
                $this->io->writeln(sprintf('Using custom ruleset: %s', $customRulesetPath), SymfonyStyle::VERBOSITY_VERBOSE);
            }

            return RulesetFilter::fromJson(file_get_contents($customRulesetPath), $this->basePath);
        }

        if ($rulesetOption !== 'auto') {
            $customRulesetPath = $this->basePath.'/.ctree/'.$rulesetOption.'.json';
            if (file_exists($customRulesetPath)) {
                if ($this->io) {
                    $this->io->writeln(sprintf('Using custom ruleset: %s', $customRulesetPath), SymfonyStyle::VERBOSITY_VERBOSE);
                }

                return RulesetFilter::fromJson(file_get_contents($customRulesetPath), $this->basePath);
            }

            $predefinedRulesetPath = $this->getPredefinedRulesetPath($rulesetOption);
            if ($predefinedRulesetPath) {
                if ($this->io) {
                    $this->io->writeln(sprintf('Using predefined ruleset: %s', $rulesetOption), SymfonyStyle::VERBOSITY_VERBOSE);
                }

                return RulesetFilter::fromJson(file_get_contents($predefinedRulesetPath), $this->basePath);
            }
        }

        if ($rulesetOption === 'auto') {
            $guessedRuleset = $this->guessRuleset();
            if ($guessedRuleset !== 'default') {
                if ($this->io) {
                    $this->io->writeln(sprintf('Auto-detected ruleset: %s', $guessedRuleset), SymfonyStyle::VERBOSITY_VERBOSE);
                }

                return RulesetFilter::fromJson(file_get_contents($this->getPredefinedRulesetPath($guessedRuleset)), $this->basePath);
            }
        }

        $defaultRulesetPath = $this->getDefaultRulesetPath();
        $this->io->writeln('Using default ruleset', SymfonyStyle::VERBOSITY_VERBOSE);

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
}
