<?php

namespace GregPriday\CopyTree\Utilities;

use DirectoryIterator;

class TempFileManager
{
    private const MAX_AGE_MINUTES = 15;

    private const PREFIX = 'ctree_output_';

    public static function getTempDir(): string
    {
        $baseDir = sys_get_temp_dir();
        $ctreeDir = $baseDir.DIRECTORY_SEPARATOR.'ctree';

        if (! is_dir($ctreeDir)) {
            mkdir($ctreeDir, 0777, true);
        }

        return $ctreeDir;
    }

    public static function createTempFile(string $content): string
    {
        $tempDir = self::getTempDir();
        $timestamp = date('Y-m-d_H-i-s');
        $hash = substr(hash('sha256', $content.uniqid()), 0, 16);
        $filename = self::PREFIX.$timestamp.'_'.$hash.'.txt';
        $filepath = $tempDir.DIRECTORY_SEPARATOR.$filename;

        file_put_contents($filepath, $content);

        return $filepath;
    }

    public static function cleanOldFiles(): void
    {
        $tempDir = self::getTempDir();
        $maxAge = time() - (self::MAX_AGE_MINUTES * 60);

        foreach (new DirectoryIterator($tempDir) as $fileInfo) {
            if ($fileInfo->isFile() &&
                str_starts_with($fileInfo->getFilename(), self::PREFIX) &&
                $fileInfo->getMTime() < $maxAge) {
                // Unlink the file if it's our file and older than the max age
                unlink($fileInfo->getPathname());
            }
        }
    }
}
