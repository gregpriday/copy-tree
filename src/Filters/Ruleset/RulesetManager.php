<?php

namespace GregPriday\CopyTree\Filters\Ruleset;

use InvalidArgumentException;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Manages ruleset selection and loading for the copy tree operation.
 *
 * Handles custom, predefined, and auto-detected rulesets, prioritizing them appropriately.
 *
 * Note: This manager now uses LocalRulesetFilter which focuses solely on local file filtering.
 */
class RulesetManager
{
    public function __construct(
        private string $basePath,
        private ?SymfonyStyle $io = null
    ) {}

    /**
     * Get the ruleset for the given option.
     *
     * @param  string  $rulesetOption  The name of the ruleset, or special values ('none', 'auto')
     * @return LocalRulesetFilter The loaded local ruleset filter.
     *
     * @throws InvalidArgumentException if the specified ruleset is not found.
     */
    public function getRuleset(string $rulesetOption): LocalRulesetFilter
    {
        if ($rulesetOption === 'none') {
            if ($this->io) {
                $this->io->writeln('Using no ruleset', SymfonyStyle::VERBOSITY_VERBOSE);
            }

            return $this->createEmptyRuleset();
        }

        if ($rulesetOption !== 'auto') {
            // Look for a custom ruleset in the project directory (.ctree)
            $customRulesetPath = $this->basePath.'/.ctree/'.$rulesetOption.'.json';
            if (file_exists($customRulesetPath)) {
                if ($this->io) {
                    $this->io->writeln(sprintf('Using custom ruleset: %s', $customRulesetPath), SymfonyStyle::VERBOSITY_VERBOSE);
                }

                return LocalRulesetFilter::fromJson(file_get_contents($customRulesetPath), $this->basePath);
            }

            // Look for a predefined ruleset in the PROJECT_ROOT/rulesets/ directory
            $predefinedRulesetPath = PROJECT_ROOT.'/rulesets/'.$rulesetOption.'.json';
            if (file_exists($predefinedRulesetPath)) {
                if ($this->io) {
                    $this->io->writeln(sprintf('Using predefined ruleset: %s', $rulesetOption), SymfonyStyle::VERBOSITY_VERBOSE);
                }

                return LocalRulesetFilter::fromJson(file_get_contents($predefinedRulesetPath), $this->basePath);
            }

            // If the ruleset is not found, throw an error
            throw new InvalidArgumentException(sprintf('Ruleset "%s" not found.', $rulesetOption));
        }

        // Check for a custom default ruleset
        $customDefaultRulesetPath = $this->basePath.'/.ctree/ruleset.json';
        if (file_exists($customDefaultRulesetPath)) {
            if ($this->io) {
                $this->io->writeln(sprintf('Using custom default ruleset: %s', $customDefaultRulesetPath), SymfonyStyle::VERBOSITY_VERBOSE);
            }

            return LocalRulesetFilter::fromJson(file_get_contents($customDefaultRulesetPath), $this->basePath);
        }

        // Auto-detect ruleset
        $guessedRuleset = $this->guessRuleset();
        if ($guessedRuleset !== 'default') {
            $guessedRulesetPath = PROJECT_ROOT.'/rulesets/'.$guessedRuleset.'.json';
            if (file_exists($guessedRulesetPath)) {
                if ($this->io) {
                    $this->io->writeln(sprintf('Auto-detected ruleset: %s', $guessedRuleset), SymfonyStyle::VERBOSITY_VERBOSE);
                }

                return LocalRulesetFilter::fromJson(file_get_contents($guessedRulesetPath), $this->basePath);
            }
        }

        // Use the default ruleset
        $defaultRulesetPath = PROJECT_ROOT.'/rulesets/default.json';
        if ($this->io) {
            $this->io->writeln('Using default ruleset', SymfonyStyle::VERBOSITY_VERBOSE);
        }

        return LocalRulesetFilter::fromJson(file_get_contents($defaultRulesetPath), $this->basePath);
    }

    /**
     * Retrieve an array of available rulesets.
     *
     * This includes both predefined rulesets and any custom rulesets found in the project.
     *
     * @return array Array of ruleset names.
     */
    public function getAvailableRulesets(): array
    {
        // Get predefined rulesets from PROJECT_ROOT/rulesets/
        $rulesetDir = realpath(PROJECT_ROOT.'/rulesets');
        $predefinedRulesets = glob($rulesetDir.'/*.json');
        $rulesets = array_map(function ($path) {
            return basename($path, '.json');
        }, $predefinedRulesets);

        // Get custom rulesets from the project .ctree directory
        $customRulesetDir = $this->basePath.'/.ctree';
        if (is_dir($customRulesetDir)) {
            $customRulesets = glob($customRulesetDir.'/*.json');
            $customRulesets = array_map(function ($path) {
                return basename($path, '.json');
            }, $customRulesets);
            $rulesets = array_merge($rulesets, $customRulesets);
        }

        // Remove 'default', 'ruleset', and 'schema' from the list if present
        $rulesets = array_diff($rulesets, ['default', 'ruleset', 'schema']);

        // Add the 'auto' option at the beginning
        array_unshift($rulesets, 'auto');

        return array_unique($rulesets);
    }

    /**
     * Create a local ruleset from an array of glob patterns.
     *
     * @param  array  $globs  Array of glob patterns.
     */
    public function createRulesetFromGlobs(array $globs): LocalRulesetFilter
    {
        $rules = array_map(function ($glob) {
            return [['path', 'glob', $glob]];
        }, $globs);

        return LocalRulesetFilter::fromArray([
            'rules' => $rules,
        ], $this->basePath);
    }

    /**
     * Create a local ruleset from a single glob pattern.
     *
     * @param  string  $glob  A glob pattern.
     */
    public function createRulesetFromGlob(string $glob): LocalRulesetFilter
    {
        return $this->createRulesetFromGlobs([$glob]);
    }

    /**
     * Create an empty local ruleset.
     */
    public function createEmptyRuleset(): LocalRulesetFilter
    {
        return LocalRulesetFilter::fromArray([
            'rules' => [],
            'globalExcludeRules' => [],
            'always' => [
                'include' => [],
                'exclude' => [],
            ],
        ], $this->basePath);
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

    /**
     * Auto-detect an appropriate ruleset using the RulesetGuesser.
     *
     * @return string The guessed ruleset name.
     */
    private function guessRuleset(): string
    {
        $guesser = new RulesetGuesser($this->basePath, $this->getAvailableRulesets());

        return $guesser->guess();
    }
}
