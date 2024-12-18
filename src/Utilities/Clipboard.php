<?php

namespace GregPriday\CopyTree\Utilities;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Clipboard management class to copy text to the system clipboard.
 *
 * Inspired by and adapted from Ed Grosvenor's PHP Clipboard implementation.
 * Original repository: https://github.com/edgrosvenor/php-clipboard
 */
class Clipboard
{
    private string $contents;

    private string $os;

    private bool $isFilePath = false;

    public function __construct()
    {
        $this->os = php_uname();
    }

    public function copy(string $contents, bool $isFilePath = false): void
    {
        $this->contents = $contents;
        $this->isFilePath = $isFilePath;

        if (stripos($this->os, 'Windows') !== false) {
            $this->runWindowsCommand();
        } elseif (stripos($this->os, 'Darwin') !== false) {
            $this->runMacCommand();
        } else {
            $this->runLinuxCommand();
        }
    }

    private function runWindowsCommand(): void
    {
        if ($this->isFilePath) {
            // For file paths, we want to maintain Windows backslashes
            $path = str_replace('/', '\\', $this->contents);
            $command = sprintf('echo %s| clip', escapeshellarg($path));
        } else {
            // Create a temporary file for content
            $tempFile = tempnam(sys_get_temp_dir(), 'ctree_');
            file_put_contents($tempFile, $this->contents);
            $command = sprintf('powershell.exe -Command "Get-Content -Path \'%s\' -Raw | Set-Clipboard"', $tempFile);
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (isset($tempFile)) {
            unlink($tempFile);
        }

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function runMacCommand(): void
    {
        if ($this->isFilePath) {
            // For file paths, use osascript to copy the file reference
            $command = sprintf('osascript -e \'
            set aFile to POSIX file "%s"
            tell app "Finder" to set the clipboard to aFile\'',
                str_replace('"', '\"', $this->contents)
            );
            $process = Process::fromShellCommandline($command);
        } else {
            // For regular text content, use pbcopy
            $process = Process::fromShellCommandline('pbcopy');
            $process->setInput($this->contents);
        }

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function runLinuxCommand(): void
    {
        if (! getenv('DISPLAY')) {
            $this->simulateClipboard();

            return;
        }

        $process = Process::fromShellCommandline('xclip -selection clipboard');
        $process->setInput($this->contents);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function simulateClipboard(): void
    {
        $clipboardFile = sys_get_temp_dir().'/clipboard_contents.txt';
        file_put_contents($clipboardFile, $this->contents);
        putenv('SIMULATED_CLIPBOARD='.base64_encode($this->contents));
    }
}
