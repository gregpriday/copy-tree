<?php

namespace GregPriday\CopyTree\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use GregPriday\CopyTree\Clipboard;
use DirectoryIterator;
use Exception;

class CopyTreeCommand extends Command
{
    protected static $defaultName = 'app:copy-tree';

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
            ->addOption('display', null, InputOption::VALUE_NONE, 'Display the output in the console.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = $input->getArgument('path');
        $filter = $input->getOption('filter');
        $depth = $input->getOption('depth');
        $noClipboard = $input->getOption('no-clipboard');
        $displayOutput = $input->getOption('display');

        [$treeOutput, $fileContentsOutput] = $this->displayTree($path, $filter, $depth);
        $combinedOutput = array_merge($treeOutput, ["", '---', ""], $fileContentsOutput);
        $formattedOutput = implode("\n", $combinedOutput);

        $fileCount = count($fileContentsOutput) / 6; // Count the number of file contents

        if (!$noClipboard) {
            $clip = new Clipboard();
            $clip->copy($formattedOutput);
            $io->success(sprintf('%d file contents have been copied to the clipboard.', $fileCount));
        }

        if ($displayOutput) {
            $io->text($formattedOutput);
        }

        return Command::SUCCESS;
    }

    private function displayTree($directory, $fileFilter, $depth, $prefix = ''): array
    {
        $treeOutput = [];
        $fileContentsOutput = [];

        if ($depth == 0) {
            return [$treeOutput, $fileContentsOutput];
        }

        foreach (new DirectoryIterator($directory) as $fileInfo) {
            if ($fileInfo->isDot()) continue;

            $path = $fileInfo->getPathname();
            if ($fileInfo->isDir()) {
                $treeOutput[] = $prefix . $fileInfo->getFilename();
                [$subTreeOutput, $subFileContentsOutput] = $this->displayTree($path, $fileFilter, $depth - 1, $prefix . "│   ");
                $treeOutput = array_merge($treeOutput, $subTreeOutput);
                $fileContentsOutput = array_merge($fileContentsOutput, $subFileContentsOutput);
            } else {
                if (fnmatch($fileFilter, $fileInfo->getFilename())) {
                    $treeOutput[] = $prefix . $fileInfo->getFilename();
                    $fileContentsOutput[] = "";
                    $fileContentsOutput[] = "> " . $path;
                    $fileContentsOutput[] = "```";
                    try {
                        $content = file_get_contents($path);
                        $fileContentsOutput[] = $content;
                    } catch (Exception $e) {
                        $fileContentsOutput[] = $e->getMessage();
                    }
                    $fileContentsOutput[] = "```";
                    $fileContentsOutput[] = "";
                }
            }
        }

        return [$treeOutput, $fileContentsOutput];
    }
}
