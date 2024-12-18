<?php

namespace GregPriday\CopyTree;

use GregPriday\CopyTree\Utilities\Clipboard;
use GregPriday\CopyTree\Utilities\TempFileManager;
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

    private bool $useTemporaryFile;

    public function __construct(
        private bool $displayOutput,
        private ?string $outputFile,
        private bool $streamOutput = false,
        private bool $copyAsFile = false
    ) {
        $this->clipboard = new Clipboard;
    }

    public function handleOutput(array $result, SymfonyStyle $io): void
    {
        // Clean old temporary files first
        TempFileManager::cleanOldFiles();

        // If streaming is enabled, write directly to output
        if ($this->streamOutput) {
            $io->write($result['output'], false);

            return;
        }

        if ($this->outputFile) {
            $this->saveToFile($result['output'], $this->outputFile);
            $io->writeln(sprintf('<info>✓ Saved %d files to %s</info>', $result['fileCount'], $this->outputFile));
        } elseif (! $this->displayOutput) {
            if ($this->copyAsFile) {
                // Create temporary file and copy its path
                $tempFile = TempFileManager::createTempFile($result['output']);
                $this->clipboard->copy($tempFile, true);
                $io->writeln(sprintf('<info>✓ Created and copied reference to temporary file: %s</info>', $tempFile));
            } else {
                // Copy content directly to clipboard
                $this->clipboard->copy($result['output']);
                $io->writeln(sprintf('<info>✓ Copied %d files to clipboard</info>', $result['fileCount']));
            }
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
