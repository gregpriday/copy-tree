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

    public function getChildren(): \RecursiveDirectoryIterator
    {
        try {
            $children = parent::getChildren();
            if ($children->isDir() && $this->shouldSkip($children->getPathname())) {
                $children->next();
            }

            return $children;
        } catch (\UnexpectedValueException $e) {
            return new self($this->getPath(), $this->getFlags());
        }
    }

    public function next(): void
    {
        parent::next();
        while ($this->valid() && $this->shouldSkip($this->getPathname())) {
            parent::next();
        }
    }

    private function shouldSkip(string $path): bool
    {
        $basename = basename($path);

        return in_array($basename, self::$ignoredDirs) || str_starts_with($basename, '.');
    }
}
