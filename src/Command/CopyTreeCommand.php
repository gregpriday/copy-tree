<?php

namespace GregPriday\CopyTree\Command;

use Exception;
use GregPriday\CopyTree\Clipboard;
use GregPriday\CopyTree\Ruleset\JsonRuleset;
use GregPriday\CopyTree\Ruleset\RulesetGuesser;
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
        $this
            ->setName('app:copy-tree')
            ->setDescription('Copies the directory tree to the clipboard and optionally displays it.')
            ->setHelp('This command copies the directory tree to the clipboard by default. You can also display the tree in the console or skip copying to the clipboard.')
            ->addArgument('path', InputArgument::OPTIONAL, 'The directory path', getcwd())
            ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL, 'File pattern filter', '*')
            ->addOption('depth', 'd', InputOption::VALUE_OPTIONAL, 'Maximum depth of the tree', 10)
            ->addOption('no-clipboard', null, InputOption::VALUE_NONE, 'Do not copy the output to the clipboard')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Outputs to a file instead of the clipboard')
            ->addOption('display', null, InputOption::VALUE_NONE, 'Display the output in the console.')
            ->addOption('ruleset', 'r', InputOption::VALUE_OPTIONAL, 'Ruleset to apply (laravel, sveltekit)', 'auto');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = $input->getArgument('path');
        $filter = $input->getOption('filter');
        $depth = $input->getOption('depth');
        $noClipboard = $input->getOption('no-clipboard');
        $displayOutput = $input->getOption('display');
        $outputFile = $input->getOption('output');
        $rulesetOption = $input->getOption('ruleset');

        // If output is specified as a flag but no value is given, set the default filename
        if ($input->hasParameterOption(['--output', '-o']) && is_null($outputFile)) {
            $outputFile = $this->generateDefaultOutputFilename($path);
        }

        try {
            $ruleset = $this->getRuleset($path, $rulesetOption, $io);
        } catch (Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        [$treeOutput, $fileContentsOutput] = $this->displayTree($path, $filter, $depth, $ruleset);

        $combinedOutput = array_merge($treeOutput, ['', '---', ''], $fileContentsOutput);
        $formattedOutput = implode("\n", $combinedOutput);

        $fileCount = count($fileContentsOutput) / 6; // Count the number of file contents

        if ($outputFile) {
            try {
                file_put_contents($outputFile, $formattedOutput);
                $io->success(sprintf('%d file contents have been saved to %s.', $fileCount, $outputFile));
            } catch (Exception $e) {
                $io->error(sprintf('Failed to save output to file: %s', $e->getMessage()));

                return Command::FAILURE;
            }
        } elseif (! $noClipboard) {
            $clip = new Clipboard();
            $clip->copy($formattedOutput);
            $io->success(sprintf('%d file contents have been copied to the clipboard.', $fileCount));
        }

        if ($displayOutput) {
            $io->text($formattedOutput);
        }

        return Command::SUCCESS;
    }

    private function getRuleset(string $path, string $rulesetOption, SymfonyStyle $io): JsonRuleset
    {
        // Check for custom ruleset in the project directory
        $customRulesetPaths = [
            $path.'/ctree.json',
            $path.'/.ctree/ruleset.json',
        ];

        foreach ($customRulesetPaths as $customRulesetPath) {
            if (file_exists($customRulesetPath)) {
                $io->note('Using custom ruleset from '.basename(dirname($customRulesetPath)).'/'.basename($customRulesetPath));

                return new JsonRuleset($customRulesetPath, $path);
            }
        }

        $availableRulesets = $this->getAvailableRulesets();
        $guesser = new RulesetGuesser($path, $availableRulesets);

        // If 'auto' is selected, try to guess the ruleset
        if ($rulesetOption === 'auto') {
            $rulesetOption = $guesser->guess();
            if ($rulesetOption !== 'default') {
                $io->note(sprintf('Auto-detected ruleset: %s', $rulesetOption));
            }
        }

        // Try to get the specified or guessed ruleset
        $rulesetPath = $guesser->getRulesetPath($rulesetOption);
        if ($rulesetPath) {
            return new JsonRuleset($rulesetPath, $path);
        }

        // If no suitable ruleset found, fall back to the default ruleset
        $defaultRulesetPath = $this->getDefaultRulesetPath();
        if (file_exists($defaultRulesetPath)) {
            $io->note('Using default ruleset');

            return new JsonRuleset($defaultRulesetPath, $path);
        }

        // If even the default ruleset is not found, throw an exception
        throw new Exception('Default ruleset not found. Please ensure default.json is present in the rulesets directory.');
    }

    private function getDefaultRulesetPath(): string
    {
        return realpath(__DIR__.'/../../rulesets/default.json');
    }

    private function getAvailableRulesets(): array
    {
        $rulesetDir = realpath(__DIR__.'/../../rulesets');

        return array_map('basename', glob($rulesetDir.'/*.json'));
    }

    private function displayTree($directory, $fileFilter, $depth, JsonRuleset $ruleset, $prefix = '', $baseDir = ''): array
    {
        $treeOutput = [];
        $fileContentsOutput = [];

        if ($depth == 0 || $depth > $ruleset->getMaxDepth()) {
            return [$treeOutput, $fileContentsOutput];
        }

        foreach (new \DirectoryIterator($directory) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }
            $filename = $fileInfo->getFilename();
            if (str_starts_with($filename, '.')) {
                continue;
            }

            $path = $fileInfo->getPathname();
            $relativePath = $baseDir ? $baseDir.'/'.$filename : $filename;

            if ($fileInfo->isDir()) {
                if (! $ruleset->shouldIncludeDirectory($relativePath)) {
                    continue;
                }
                $treeOutput[] = $prefix.$filename;
                [$subTreeOutput, $subFileContentsOutput] = $this->displayTree($path, $fileFilter, $depth - 1, $ruleset, $prefix.'│   ', $relativePath);
                $treeOutput = array_merge($treeOutput, $subTreeOutput);
                $fileContentsOutput = array_merge($fileContentsOutput, $subFileContentsOutput);
            } else {
                if (fnmatch($fileFilter, $filename) && $ruleset->shouldIncludeFile($relativePath)) {
                    $treeOutput[] = $prefix.$filename;
                    $fileContentsOutput[] = '';
                    $fileContentsOutput[] = '> '.$relativePath;
                    $fileContentsOutput[] = '```';
                    try {
                        $content = file_get_contents($path);
                        $fileContentsOutput[] = $content;
                    } catch (Exception $e) {
                        $fileContentsOutput[] = $e->getMessage();
                    }
                    $fileContentsOutput[] = '```';
                    $fileContentsOutput[] = '';
                }
            }
        }

        return [$treeOutput, $fileContentsOutput];
    }

    private function generateDefaultOutputFilename($path): string
    {
        $directoryName = basename($path);
        $timestamp = date('Y-m-d_H-i-s');

        return sprintf('%s_tree_%s.txt', $directoryName, $timestamp);
    }
}
