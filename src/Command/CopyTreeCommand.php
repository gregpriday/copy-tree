<?php

namespace GregPriday\CopyTree\Command;

use Exception;
use GregPriday\CopyTree\Clipboard;
use GregPriday\CopyTree\Ruleset\Ruleset;
use GregPriday\CopyTree\Views\FileContentsView;
use GregPriday\CopyTree\Views\FileTreeView;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CopyTreeCommand extends Command
{
    protected static string $defaultName = 'app:copy-tree';

    protected function configure(): void
    {
        $availableRulesets = $this->getAvailableRulesets();
        $rulesetDescription = 'Ruleset to apply (auto, '.implode(', ', $availableRulesets).')';

        $this
            ->setName('app:copy-tree')
            ->setDescription('Copies the directory tree to the clipboard and optionally displays it.')
            ->setHelp('This command copies the directory tree to the clipboard by default. You can also display the tree in the console or skip copying to the clipboard.')
            ->addArgument('path', InputArgument::OPTIONAL, 'The directory path', getcwd())
            ->addOption('depth', 'd', InputOption::VALUE_OPTIONAL, 'Maximum depth of the tree', 10)
            ->addOption('no-clipboard', null, InputOption::VALUE_NONE, 'Do not copy the output to the clipboard')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Outputs to a file instead of the clipboard')
            ->addOption('display', null, InputOption::VALUE_NONE, 'Display the output in the console.')
            ->addOption('ruleset', 'r', InputOption::VALUE_OPTIONAL, $rulesetDescription, 'auto');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->setVerbosity($output->getVerbosity());

        $path = $input->getArgument('path');
        $depth = $input->getOption('depth');
        $noClipboard = $input->getOption('no-clipboard');
        $displayOutput = $input->getOption('display');
        $outputFile = $input->getOption('output');
        $rulesetOption = $input->getOption('ruleset');

        if ($input->hasParameterOption(['--output', '-o']) && is_null($outputFile)) {
            $outputFile = $this->generateDefaultOutputFilename($path);
        }

        try {
            $ruleset = $this->getRuleset($path, $rulesetOption, $io);

            $filteredFiles = iterator_to_array($ruleset->getFilteredFiles());

            $treeOutput = FileTreeView::render($filteredFiles);
            $fileContentsOutput = FileContentsView::render($filteredFiles);

            $combinedOutput = $treeOutput."\n\n---\n\n".$fileContentsOutput;

            $this->handleOutput($combinedOutput, count($filteredFiles), $noClipboard, $outputFile, $displayOutput, $io);

            $io->writeln(sprintf('Used maximum depth of %d', $depth), OutputInterface::VERBOSITY_VERBOSE);

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    private function getRuleset(string $path, string $rulesetOption, SymfonyStyle $io): Ruleset
    {
        $customRulesetPath = $path.'/ctree.json';
        if (file_exists($customRulesetPath)) {
            $io->writeln(sprintf('Using custom ruleset: %s', $customRulesetPath), OutputInterface::VERBOSITY_VERBOSE);

            return Ruleset::fromJson(file_get_contents($customRulesetPath), $path);
        }

        if ($rulesetOption !== 'auto') {
            $predefinedRulesetPath = $this->getPredefinedRulesetPath($rulesetOption);
            if ($predefinedRulesetPath) {
                $io->writeln(sprintf('Using predefined ruleset: %s', $rulesetOption), OutputInterface::VERBOSITY_VERBOSE);

                return Ruleset::fromJson(file_get_contents($predefinedRulesetPath), $path);
            }
        }

        if ($rulesetOption === 'auto') {
            $guessedRuleset = $this->guessRuleset($path);
            if ($guessedRuleset !== 'default') {
                $io->writeln(sprintf('Auto-detected ruleset: %s', $guessedRuleset), OutputInterface::VERBOSITY_VERBOSE);

                return Ruleset::fromJson(file_get_contents($this->getPredefinedRulesetPath($guessedRuleset)), $path);
            }
        }

        $defaultRulesetPath = $this->getDefaultRulesetPath();
        $io->writeln('Using default ruleset', OutputInterface::VERBOSITY_VERBOSE);

        return Ruleset::fromJson(file_get_contents($defaultRulesetPath), $path);
    }

    private function handleOutput(string $output, int $fileCount, bool $noClipboard, ?string $outputFile, bool $displayOutput, SymfonyStyle $io): void
    {
        if ($outputFile) {
            file_put_contents($outputFile, $output);
            $io->writeln(sprintf('<info>✓ Saved %d files to %s</info>', $fileCount, $outputFile));
            $io->writeln(sprintf('Output file size: %s bytes', filesize($outputFile)), OutputInterface::VERBOSITY_VERBOSE);
        } elseif (! $noClipboard) {
            $clip = new Clipboard();
            $clip->copy($output);
            $io->writeln(sprintf('<info>✓ Copied %d files to clipboard</info>', $fileCount));
            $io->writeln(sprintf('Clipboard content size: %d characters', strlen($output)), OutputInterface::VERBOSITY_VERBOSE);
        }

        if ($displayOutput) {
            $io->writeln('Displaying output in console:', OutputInterface::VERBOSITY_VERBOSE);
            $io->text($output);
        }

        $io->writeln(sprintf('Total output size: %d characters', strlen($output)), OutputInterface::VERBOSITY_VERBOSE);
    }

    private function getPredefinedRulesetPath(string $rulesetName): ?string
    {
        $rulesetPath = realpath(__DIR__.'/../../rulesets/'.$rulesetName.'.json');

        return $rulesetPath && file_exists($rulesetPath) ? $rulesetPath : null;
    }

    private function getDefaultRulesetPath(): string
    {
        return realpath(__DIR__.'/../../rulesets/default.json');
    }

    private function getAvailableRulesets(): array
    {
        $rulesetDir = realpath(__DIR__.'/../../rulesets');
        $rulesets = glob($rulesetDir.'/*.json');

        return array_map(function ($path) {
            return basename($path, '.json');
        }, $rulesets);
    }

    private function generateDefaultOutputFilename($path): string
    {
        $directoryName = basename($path);
        $timestamp = date('Y-m-d_H-i-s');

        return sprintf('%s_tree_%s.txt', $directoryName, $timestamp);
    }

    private function guessRuleset(string $path): string
    {
        // Implement ruleset guessing logic here
        // For now, we'll just return 'default'
        return 'default';
    }
}
