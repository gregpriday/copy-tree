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
    private $contents;

    private $os;

    public function __construct()
    {
        $this->os = php_uname();
    }

    public function copy(string $contents): void
    {
        $this->contents = $contents;

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
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'ctree_');
        file_put_contents($tempFile, $this->contents);

        // Use PowerShell to read the file and pipe it to clip.exe
        $command = sprintf('powershell.exe -Command "Get-Content -Path \'%s\' -Raw | Set-Clipboard"', $tempFile);

        $process = Process::fromShellCommandline($command);
        $process->run();

        // Clean up the temporary file
        unlink($tempFile);

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function runMacCommand(): void
    {
        $process = Process::fromShellCommandline('pbcopy');
        $process->setInput($this->contents);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function runLinuxCommand(): void
    {
        // Check if DISPLAY is set (X server is available)
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
        // Simulate clipboard by writing to a file or environment variable
        $clipboardFile = sys_get_temp_dir().'/clipboard_contents.txt';
        file_put_contents($clipboardFile, $this->contents);

        // Set an environment variable
        putenv('SIMULATED_CLIPBOARD='.base64_encode($this->contents));
    }
}
