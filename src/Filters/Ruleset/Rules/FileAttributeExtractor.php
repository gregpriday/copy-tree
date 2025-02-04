<?php

namespace GregPriday\CopyTree\Filters\Ruleset\Rules;

use finfo;
use InvalidArgumentException;
use RuntimeException;
use SplFileInfo;

class FileAttributeExtractor
{
    private string $basePath;

    private ?finfo $finfoInstance = null;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim(realpath($basePath), '/');
    }

    /**
     * Get the value of a specific field from a file
     *
     * @param  RuleField  $field  The field to extract
     * @param  SplFileInfo  $file  The file to extract from
     * @return mixed The extracted value
     *
     * @throws InvalidArgumentException If the field is invalid
     * @throws RuntimeException If there's an error reading the file
     */
    public function getFieldValue(RuleField $field, SplFileInfo $file): mixed
    {
        $relativePath = $this->getRelativePath($file);
        $pathInfo = pathinfo($relativePath);

        return match ($field) {
            RuleField::FOLDER => $this->getFolder($relativePath),
            RuleField::PATH => $relativePath,
            RuleField::DIRNAME => $pathInfo['dirname'],
            RuleField::BASENAME => $pathInfo['basename'],
            RuleField::EXTENSION => $pathInfo['extension'] ?? '',
            RuleField::FILENAME => $pathInfo['filename'],
            RuleField::CONTENTS => $this->getContents($file),
            RuleField::CONTENTS_SLICE => $this->getContentsSlice($file, 256),
            RuleField::SIZE => $this->getSize($file),
            RuleField::MTIME => $this->getMTime($file),
            RuleField::MIME_TYPE => $this->getMimeType($file),
        };
    }

    /**
     * Get the folder path relative to base path
     */
    private function getFolder(string $relativePath): string
    {
        $dirname = dirname($relativePath);

        return $dirname === '.' ? '' : $dirname;
    }

    /**
     * Get the entire contents of a file
     *
     * @throws RuntimeException If file cannot be read
     */
    private function getContents(SplFileInfo $file): string
    {
        $contents = @file_get_contents($file->getPathname());
        if ($contents === false) {
            throw new RuntimeException(
                "Failed to read contents of file: {$file->getPathname()}"
            );
        }

        return $contents;
    }

    /**
     * Get a slice of the file contents
     *
     * @throws RuntimeException If file cannot be read
     */
    private function getContentsSlice(SplFileInfo $file, int $length): string
    {
        $handle = @fopen($file->getPathname(), 'r');
        if ($handle === false) {
            throw new RuntimeException(
                "Failed to open file: {$file->getPathname()}"
            );
        }

        $slice = @fread($handle, $length);
        fclose($handle);

        if ($slice === false) {
            throw new RuntimeException(
                "Failed to read from file: {$file->getPathname()}"
            );
        }

        return $slice;
    }

    /**
     * Get the file size
     *
     * @throws RuntimeException If file size cannot be determined
     */
    private function getSize(SplFileInfo $file): int
    {
        $size = $file->getSize();
        if ($size === false) {
            throw new RuntimeException(
                "Failed to get size of file: {$file->getPathname()}"
            );
        }

        return $size;
    }

    /**
     * Get the file modification time
     *
     * @throws RuntimeException If modification time cannot be determined
     */
    private function getMTime(SplFileInfo $file): int
    {
        $mtime = $file->getMTime();
        if ($mtime === false) {
            throw new RuntimeException(
                "Failed to get modification time of file: {$file->getPathname()}"
            );
        }

        return $mtime;
    }

    /**
     * Get the MIME type of a file
     *
     * @throws RuntimeException If MIME type cannot be determined
     */
    private function getMimeType(SplFileInfo $file): string
    {
        if ($this->finfoInstance === null) {
            $this->finfoInstance = new finfo(FILEINFO_MIME_TYPE);
        }

        $mimeType = $this->finfoInstance->file($file->getPathname());
        if ($mimeType === false) {
            throw new RuntimeException(
                "Failed to determine MIME type of file: {$file->getPathname()}"
            );
        }

        return $mimeType;
    }

    /**
     * Get path relative to base path
     */
    private function getRelativePath(SplFileInfo $file): string
    {
        $realPath = $file->getRealPath();
        if ($realPath === false) {
            throw new RuntimeException(
                "Failed to resolve real path for file: {$file->getPathname()}"
            );
        }

        return str_replace($this->basePath.'/', '', $realPath);
    }

    /**
     * Check if a file is an image based on its extension
     */
    public function isImage(SplFileInfo $file): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'tiff', 'ico'];

        return in_array(strtolower($file->getExtension()), $imageExtensions);
    }

    /**
     * Convert a size in bytes to a human-readable string
     */
    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 1).' '.$units[$pow];
    }
}
