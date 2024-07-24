<?php

namespace GregPriday\CopyTree\Ruleset;

class FilteredDirIterator extends \RecursiveDirectoryIterator
{
    private static $ignoredDirs = [
        '.',
        '..',
        '.git',
        '.svn',
        '.hg',
        '.idea',
        '.vscode',
        '__pycache__',
        'node_modules',
        'bower_components',
        '.npm',
        '.yarn',
    ];

    private $isSkippedDir = false;

    public function __construct($directory, $flags = \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO)
    {
        parent::__construct($directory, $flags);
        $this->skipUnwantedFiles();
    }

    public function hasChildren($allowLinks = false): bool
    {
        return ! $this->isSkippedDir && parent::hasChildren($allowLinks) && ! $this->shouldSkip($this->getPathname());
    }

    public function getChildren(): \RecursiveDirectoryIterator
    {
        if ($this->hasChildren()) {
            return new self($this->getPathname(), $this->getFlags());
        }
        $this->isSkippedDir = true;

        return $this;
    }

    public function next(): void
    {
        do {
            parent::next();
            $this->skipUnwantedFiles();
        } while ($this->valid() && $this->shouldSkip($this->getPathname()));
    }

    public function valid(): bool
    {
        return ! $this->isSkippedDir && parent::valid() && ! $this->shouldSkip($this->getPathname());
    }

    public function rewind(): void
    {
        parent::rewind();
        $this->skipUnwantedFiles();
    }

    public function current(): \SplFileInfo
    {
        return parent::current();
    }

    public function key(): string
    {
        return parent::key();
    }

    private function shouldSkip(string $path): bool
    {
        $basename = basename($path);

        return in_array($basename, self::$ignoredDirs) || str_starts_with($basename, '.');
    }

    private function skipUnwantedFiles(): void
    {
        while (parent::valid() && $this->shouldSkip($this->getPathname())) {
            parent::next();
        }
    }
}
