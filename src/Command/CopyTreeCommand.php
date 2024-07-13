<?php

namespace GregPriday\CopyTree\Command;

use Exception;
use GregPriday\CopyTree\Clipboard;
use GregPriday\CopyTree\Ruleset\IncludeRuleset;
use GregPriday\CopyTree\Ruleset\RulesetGuesser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

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
            ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL, 'File pattern filter', '*')
            ->addOption('depth', 'd', InputOption::VALUE_OPTIONAL, 'Maximum depth of the tree', 10)
            ->addOption('no-clipboard', null, InputOption::VALUE_NONE, 'Do not copy the output to the clipboard')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Outputs to a file instead of the clipboard')
            ->addOption('display', null, InputOption::VALUE_NONE, 'Display the output in the console.')
            ->addOption('ruleset', 'r', InputOption::VALUE_OPTIONAL, $rulesetDescription, 'auto');
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

        if ($input->hasParameterOption(['--output', '-o']) && is_null($outputFile)) {
            $outputFile = $this->generateDefaultOutputFilename($path);
        }

        try {
            $ruleset = $this->getRuleset($path, $rulesetOption, $io);
            $allFiles = $this->getAllFiles($path, $depth);
            $filteredFiles = $this->filterFiles($allFiles, $ruleset, $filter);
            [$treeOutput, $fileContentsOutput] = $this->generateTree($filteredFiles, $path);

            $combinedOutput = array_merge($treeOutput, ['', '---', ''], $fileContentsOutput);
            $formattedOutput = implode("\n", $combinedOutput);

            $this->handleOutput($formattedOutput, $noClipboard, $outputFile, $displayOutput, $io);

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    private function getRuleset(string $path, string $rulesetOption, SymfonyStyle $io): IncludeRuleset
    {
        // Check for custom ruleset in the current directory
        $customRulesetPath = $this->findCustomRuleset($path, $rulesetOption);
        if ($customRulesetPath) {
            $io->note(sprintf('Using custom ruleset: %s', $customRulesetPath));

            return new IncludeRuleset($customRulesetPath);
        }

        // If no custom ruleset found, check for predefined rulesets
        $predefinedRulesetPath = $this->getPredefinedRulesetPath($rulesetOption);
        if ($predefinedRulesetPath) {
            $io->note(sprintf('Using predefined ruleset: %s', $rulesetOption));

            return new IncludeRuleset($predefinedRulesetPath);
        }

        // If still no ruleset found, use auto-detection or default
        $availableRulesets = $this->getAvailableRulesets();
        $guesser = new RulesetGuesser($path, $availableRulesets);

        if ($rulesetOption === 'auto') {
            $rulesetOption = $guesser->guess();
            if ($rulesetOption !== 'default') {
                $io->note(sprintf('Auto-detected ruleset: %s', $rulesetOption));

                return new IncludeRuleset($this->getPredefinedRulesetPath($rulesetOption));
            }
        }

        $defaultRulesetPath = $this->getDefaultRulesetPath();
        if (file_exists($defaultRulesetPath)) {
            $io->note('Using default ruleset');

            return new IncludeRuleset($defaultRulesetPath);
        }

        throw new Exception('No suitable ruleset found. Please ensure a valid ruleset is available.');
    }

    private function findCustomRuleset(string $path, string $rulesetOption): ?string
    {
        if ($rulesetOption !== 'auto') {
            $customRulesetPath = $path.'/'.$rulesetOption.'.ctreeinclude';
            if (file_exists($customRulesetPath)) {
                return $customRulesetPath;
            }
        }

        $defaultCustomRulesetPath = $path.'/.ctreeinclude';
        if (file_exists($defaultCustomRulesetPath)) {
            return $defaultCustomRulesetPath;
        }

        return null;
    }

    private function getPredefinedRulesetPath(string $rulesetName): ?string
    {
        $rulesetPath = realpath(__DIR__.'/../../rulesets/'.$rulesetName.'.ctreeinclude');

        return $rulesetPath && file_exists($rulesetPath) ? $rulesetPath : null;
    }

    private function getAllFiles(string $directory, int $depth): array
    {
        $finder = new Finder();
        $finder->in($directory)->depth('< '.$depth);

        $files = [];
        foreach ($finder as $file) {
            if ($file->isFile()) {
                $files[] = $file->getRelativePathname();
            }
        }

        return $files;
    }

    private function filterFiles(array $files, IncludeRuleset $ruleset, string $filter): array
    {
        return array_filter($files, function ($file) use ($ruleset, $filter) {
            return fnmatch($filter, basename($file)) && $ruleset->shouldInclude($file);
        });
    }

    private function generateTree(array $files, string $basePath): array
    {
        $treeOutput = [];
        $fileContentsOutput = [];
        $tree = [];

        foreach ($files as $file) {
            $parts = explode('/', $file);
            $current = &$tree;
            foreach ($parts as $part) {
                if (! isset($current[$part])) {
                    $current[$part] = [];
                }
                $current = &$current[$part];
            }
        }

        $this->renderTree($tree, $treeOutput, $fileContentsOutput, $basePath);

        return [$treeOutput, $fileContentsOutput];
    }

    private function renderTree(array $tree, array &$treeOutput, array &$fileContentsOutput, string $basePath, string $prefix = ''): void
    {
        foreach ($tree as $name => $subtree) {
            $treeOutput[] = $prefix.$name;
            $path = $basePath.'/'.$name;

            if (empty($subtree) && is_file($path)) {
                $fileContentsOutput[] = '';
                $fileContentsOutput[] = '> '.$name;
                $fileContentsOutput[] = '```';
                try {
                    $content = file_get_contents($path);
                    $fileContentsOutput[] = $content;
                } catch (Exception $e) {
                    $fileContentsOutput[] = $e->getMessage();
                }
                $fileContentsOutput[] = '```';
                $fileContentsOutput[] = '';
            } else {
                $this->renderTree($subtree, $treeOutput, $fileContentsOutput, $path, $prefix.'│   ');
            }
        }
    }

    private function handleOutput(string $output, bool $noClipboard, ?string $outputFile, bool $displayOutput, SymfonyStyle $io): void
    {
        $fileCount = substr_count($output, '> ');

        if ($outputFile) {
            file_put_contents($outputFile, $output);
            $io->success(sprintf('%d file contents have been saved to %s.', $fileCount, $outputFile));
        } elseif (! $noClipboard) {
            $clip = new Clipboard();
            $clip->copy($output);
            $io->success(sprintf('%d file contents have been copied to the clipboard.', $fileCount));
        }

        if ($displayOutput) {
            $io->text($output);
        }
    }

    private function getDefaultRulesetPath(): string
    {
        return realpath(__DIR__.'/../../rulesets/.ctreeinclude');
    }

    private function getRulesetPath(string $rulesetOption): ?string
    {
        $rulesetDir = realpath(__DIR__.'/../../rulesets');
        $rulesetPath = $rulesetDir.'/'.$rulesetOption.'.ctreeinclude';

        return file_exists($rulesetPath) ? $rulesetPath : null;
    }

    private function getAvailableRulesets(): array
    {
        $rulesetDir = realpath(__DIR__.'/../../rulesets');
        $rulesets = glob($rulesetDir.'/*.ctreeinclude');

        return array_map(function ($path) {
            return basename($path, '.ctreeinclude');
        }, $rulesets);
    }

    private function generateDefaultOutputFilename($path): string
    {
        $directoryName = basename($path);
        $timestamp = date('Y-m-d_H-i-s');

        return sprintf('%s_tree_%s.txt', $directoryName, $timestamp);
    }
}
