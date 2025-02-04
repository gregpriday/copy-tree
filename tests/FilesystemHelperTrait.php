<?php

namespace GregPriday\CopyTree\Tests;

trait FilesystemHelperTrait
{
    /**
     * Recursively remove a directory and all its contents.
     *
     * This method scans the given directory recursively and deletes every file and subdirectory.
     * It uses PHP’s built-in RecursiveDirectoryIterator and RecursiveIteratorIterator.
     *
     * @param  string  $dir  The directory path to remove.
     */
    protected function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }
}
