<?php

namespace GregPriday\CopyTree\Command;

use Exception;
use GregPriday\CopyTree\Clipboard;
use GregPriday\CopyTree\Ruleset\RulesetInterface;
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
            ->addOption('ruleset', 'r', InputOption::VALUE_OPTIONAL, 'Ruleset to apply (laravel, sveltekit)', 'default');
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

        // If output is specified as a flag but no value is given, set the default filename
        if ($input->hasParameterOption(['--output', '-o']) && is_null($outputFile)) {
            $outputFile = $this->generateDefaultOutputFilename($path);
        }

        $ruleset = $this->getRulesetInstance($input->getOption('ruleset'));
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

    private function getRulesetInstance(string $ruleset): ?RulesetInterface
    {
        $rulesetClassName = $this->findRulesetClass($ruleset);
        if ($rulesetClassName) {
            return new $rulesetClassName();
        }

        return null;
    }

    private function findRulesetClass(string $ruleset): ?string
    {
        $rulesetPath = __DIR__ . '/../Ruleset';
        $rulesetFiles = scandir($rulesetPath);

        $rulesetClassName = null;
        $rulesetPattern = strtolower($ruleset) . 'ruleset';

        foreach ($rulesetFiles as $file) {
            if (preg_match('/^[^.].*\.php$/', $file)) {
                $className = basename($file, '.php');
                if (strtolower($className) === $rulesetPattern) {
                    $rulesetClassName = 'GregPriday\\CopyTree\\Ruleset\\' . $className;
                    break;
                }
            }
        }

        return $rulesetClassName;
    }

    private function displayTree($directory, $fileFilter, $depth, ?RulesetInterface $ruleset, $prefix = '', $baseDir = ''): array
    {
        $treeOutput = [];
        $fileContentsOutput = [];

        if ($depth == 0) {
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
            $relativePath = $baseDir ? $baseDir . '/' . $filename : $filename;

            if ($fileInfo->isDir()) {
                if ($ruleset && !$ruleset->shouldIncludeDirectory($relativePath)) {
                    continue;
                }
                $treeOutput[] = $prefix . $filename;
                [$subTreeOutput, $subFileContentsOutput] = $this->displayTree($path, $fileFilter, $depth - 1, $ruleset, $prefix . '│   ', $relativePath);
                $treeOutput = array_merge($treeOutput, $subTreeOutput);
                $fileContentsOutput = array_merge($fileContentsOutput, $subFileContentsOutput);
            } else {
                if (fnmatch($fileFilter, $filename) && (!$ruleset || $ruleset->shouldIncludeFile($relativePath))) {
                    $treeOutput[] = $prefix . $filename;
                    $fileContentsOutput[] = '';
                    $fileContentsOutput[] = '> ' . $relativePath;
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

    /**
     * Generate a default output filename based on the directory name and current date-time.
     *
     * @param string $path The directory path.
     * @return string The generated output filename.
     */
    private function generateDefaultOutputFilename($path): string
    {
        $directoryName = basename($path);
        $timestamp = date('Y-m-d_H-i-s');

        return sprintf('%s_tree_%s.txt', $directoryName, $timestamp);
    }
}
