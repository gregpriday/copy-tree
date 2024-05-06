<?php

namespace GregPriday\CopyTree;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Clipboard management class to copy text to the system clipboard.
 *
 * Inspired by and adapted from Ed Grosvenor's PHP Clipboard implementation.
 * Original repository: https://github.com/edgrosvenor/php-clipboard
 *
 * @package GregPriday\CopyTree
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
            $this->runCommand(['cmd', '/c', 'echo', $this->contents, '|', 'clip']);
        } elseif (stripos($this->os, 'Darwin') !== false) {
            $this->runCommand(['echo', $this->contents, '|', 'pbcopy']);
        } else {
            $this->runCommand(['echo', $this->contents, '|', 'xclip', '-selection', 'clipboard']);
        }
    }

    private function runCommand(array $command): void
    {
        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
