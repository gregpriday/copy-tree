<?php

namespace GregPriday\CopyTree\Ruleset;

use RecursiveDirectoryIterator;
use TOGoS_GitIgnore_Ruleset;

class FilteredDirIterator extends RecursiveDirectoryIterator
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
        'venv',
    ];

    private $isSkippedDir = false;

    private ?TOGoS_GitIgnore_Ruleset $ignoreRuleset = null;

    private string $baseDirectory;

    public function __construct($directory, $flags = \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO)
    {
        parent::__construct($directory, $flags);
        $this->baseDirectory = $directory;
        $this->initGitIgnore();
        $this->skipUnwantedFiles();
    }

    private function initGitIgnore(): void
    {
        $gitignorePath = $this->baseDirectory.'/.gitignore';
        if (file_exists($gitignorePath)) {
            try {
                $content = file_get_contents($gitignorePath);
                if ($content !== false) {
                    $this->ignoreRuleset = TOGoS_GitIgnore_Ruleset::loadFromString($content);
                }
            } catch (\Exception $e) {
                // If there's any error loading the gitignore, just continue without it
                $this->ignoreRuleset = null;
            }
        }
    }

    public function hasChildren($allowLinks = false): bool
    {
        return ! $this->isSkippedDir && parent::hasChildren($allowLinks) && ! $this->shouldSkip($this->getPathname());
    }

    public function getChildren(): RecursiveDirectoryIterator
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

        // First check our static ignore list
        if (in_array($basename, self::$ignoredDirs) || str_starts_with($basename, '.')) {
            return true;
        }

        // Then check gitignore patterns if we have them
        if ($this->ignoreRuleset !== null) {
            // Get path relative to the base directory
            $relativePath = substr($path, strlen($this->baseDirectory) + 1);
            if ($relativePath !== false) {
                try {
                    $result = $this->ignoreRuleset->match($relativePath);
                    if ($result === true) {
                        return true;
                    }
                } catch (\Exception $e) {
                    // If there's an error matching the pattern, accept the file
                    return false;
                }
            }
        }

        return false;
    }

    private function skipUnwantedFiles(): void
    {
        while (parent::valid() && $this->shouldSkip($this->getPathname())) {
            parent::next();
        }
    }
}
