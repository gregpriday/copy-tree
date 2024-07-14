<?php

namespace GregPriday\CopyTree;

use GregPriday\CopyTree\Utilities\Clipboard;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Manages the output of the copy tree operation.
 *
 * Handles saving to file, copying to clipboard, and displaying in console based on user options.
 */
class OutputManager
{
    private Clipboard $clipboard;

    public function __construct(
        private bool $displayOutput,
        private ?string $outputFile
    ) {
        $this->clipboard = new Clipboard();
    }

    public function handleOutput(array $result, SymfonyStyle $io): void
    {
        if ($this->outputFile) {
            $this->saveToFile($result['output'], $this->outputFile);
            $io->writeln(sprintf('<info>✓ Saved %d files to %s</info>', $result['fileCount'], $this->outputFile));
        } elseif (! $this->displayOutput) {
            $this->clipboard->copy($result['output']);
            $io->writeln(sprintf('<info>✓ Copied %d files to clipboard</info>', $result['fileCount']));
        }

        if ($this->displayOutput) {
            $io->writeln('Displaying output in console:', OutputInterface::VERBOSITY_VERBOSE);
            $io->text($result['output']);
        }

        $io->writeln(sprintf('Total output size: %d characters', strlen($result['output'])), OutputInterface::VERBOSITY_VERBOSE);
    }

    private function saveToFile(string $content, string $filename): void
    {
        file_put_contents($filename, $content);
    }
}
