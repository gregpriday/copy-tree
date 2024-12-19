<?php

namespace GregPriday\CopyTree;

use GregPriday\CopyTree\Utilities\Clipboard;
use GregPriday\CopyTree\Utilities\OpenAI\OpenAIFilenameGenerator;
use GregPriday\CopyTree\Utilities\TempFileManager;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

/**
 * Manages the output of the copy tree operation.
 *
 * Handles saving to file, copying to clipboard, streaming to output, and displaying in console based on user options.
 */
class OutputManager
{
    private Clipboard $clipboard;

    private string $homeDir;

    private ?OpenAIFilenameGenerator $filenameGenerator = null;

    public function __construct(
        private bool $displayOutput,
        private ?string $outputFile,
        private bool $streamOutput = false,
        private bool $copyAsFile = false
    ) {
        $this->clipboard = new Clipboard;
        $this->homeDir = $this->getHomeDirectory();
        $this->ensureCopyTreeDirectory();
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

        if ($this->outputFile !== null) {
            try {
                // If outputFile is an empty string (flag used without value), generate a name
                $filePath = $this->outputFile === '' ?
                    $this->createOutputPath($result) :
                    $this->outputFile;

                $this->saveToFile($result['output'], $filePath);
                $io->writeln(sprintf('<info>✓ Saved %d files to %s</info>', $result['fileCount'], $filePath));

                // If on macOS and using generated name, reveal in Finder
                if ($this->isMacOS() && $this->outputFile === '') {
                    $this->revealInFinder($filePath);
                }
            } catch (\Exception $e) {
                // If OpenAI filename generation fails, fallback to timestamp
                $filePath = $this->createTimestampPath();
                $this->saveToFile($result['output'], $filePath);
                $io->writeln(sprintf('<info>✓ Saved %d files to %s</info>', $result['fileCount'], $filePath));
                $io->writeln('<comment>Note: Used timestamp for filename as AI generation failed: '.$e->getMessage().'</comment>');

                if ($this->isMacOS()) {
                    $this->revealInFinder($filePath);
                }
            }
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

    private function createOutputPath(array $result): string
    {
        $outputDir = sprintf(
            '%s%s.copytree%sfiles',
            $this->homeDir,
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR
        );

        try {
            if ($this->filenameGenerator === null) {
                $this->filenameGenerator = new OpenAIFilenameGenerator;
            }

            $filename = $this->filenameGenerator->generateFilename($result['files'], $outputDir);

            return $outputDir.DIRECTORY_SEPARATOR.$filename;
        } catch (\Exception $e) {
            // If OpenAI generation fails, throw the error to be handled by caller
            throw new \RuntimeException('Failed to generate AI filename: '.$e->getMessage());
        }
    }

    private function createTimestampPath(): string
    {
        $timestamp = date('Y-m-d_H-i-s');

        return sprintf(
            '%s%s.copytree%sfiles%soutput_%s.txt',
            $this->homeDir,
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR,
            $timestamp
        );
    }

    private function saveToFile(string $content, string $filename): void
    {
        file_put_contents($filename, $content);
    }

    private function getHomeDirectory(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return getenv('USERPROFILE');
        }

        return getenv('HOME');
    }

    private function ensureCopyTreeDirectory(): void
    {
        $copyTreeDir = $this->homeDir.DIRECTORY_SEPARATOR.'.copytree';
        $filesDir = $copyTreeDir.DIRECTORY_SEPARATOR.'files';

        if (! is_dir($filesDir)) {
            mkdir($filesDir, 0755, true);
        }
    }

    private function isMacOS(): bool
    {
        return PHP_OS_FAMILY === 'Darwin';
    }

    private function revealInFinder(string $filePath): void
    {
        // Attempt to reveal the file in Finder using AppleScript
        $script = sprintf(
            'tell application "Finder" to reveal POSIX file "%s"
            tell application "Finder" to activate',
            str_replace('"', '\"', $filePath)
        );

        $process = new Process(['osascript', '-e', $script]);
        $process->run();
    }
}
