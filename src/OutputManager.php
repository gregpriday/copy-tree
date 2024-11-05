<?php

namespace GregPriday\CopyTree;

use GregPriday\CopyTree\Utilities\Clipboard;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Manages the output of the copy tree operation.
 *
 * Handles saving to file, copying to clipboard, streaming to output, and displaying in console based on user options.
 */
class OutputManager
{
    private Clipboard $clipboard;

    public function __construct(
        private bool $displayOutput,
        private ?string $outputFile,
        private bool $streamOutput = false
    ) {
        $this->clipboard = new Clipboard;
    }

    public function handleOutput(array $result, SymfonyStyle $io): void
    {
        // If streaming is enabled, write directly to output
        if ($this->streamOutput) {
            $io->write($result['output'], false);

            return;
        }

        if ($this->outputFile) {
            $this->saveToFile($result['output'], $this->outputFile);
            $io->writeln(sprintf('<info>✓ Saved %d files to %s</info>', $result['fileCount'], $this->outputFile));
        } elseif (! $this->displayOutput) {
            $this->clipboard->copy($result['output']);
            $io->writeln(sprintf('<info>✓ Copied %d files to clipboard</info>', $result['fileCount']));
        }

        if ($this->displayOutput) {
            $io->writeln('Displaying output in console:', OutputInterface::VERBOSITY_VERBOSE);
            $io->write($result['output']);
        }

        $io->writeln(sprintf('Total output size: %d characters', strlen($result['output'])), OutputInterface::VERBOSITY_VERBOSE);
    }

    private function saveToFile(string $content, string $filename): void
    {
        file_put_contents($filename, $content);
    }
}
