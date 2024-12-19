<?php

namespace GregPriday\CopyTree\Utilities;

use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Clipboard management class to copy text to the system clipboard.
 * MacOS-only implementation using pbcopy and osascript.
 */
class Clipboard
{
    private string $contents;

    private bool $isFilePath = false;

    public function __construct()
    {
        if (stripos(php_uname(), 'Darwin') === false) {
            throw new RuntimeException('This package only supports MacOS.');
        }
    }

    public function copy(string $contents, bool $isFilePath = false): void
    {
        $this->contents = $contents;
        $this->isFilePath = $isFilePath;

        if ($this->isFilePath) {
            $this->copyFileReference();
        } else {
            $this->copyTextContent();
        }
    }

    private function copyFileReference(): void
    {
        $command = sprintf('osascript -e \'
        set aFile to POSIX file "%s"
        tell app "Finder" to set the clipboard to aFile\'',
            str_replace('"', '\"', $this->contents)
        );

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function copyTextContent(): void
    {
        $process = Process::fromShellCommandline('pbcopy');
        $process->setInput($this->contents);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
