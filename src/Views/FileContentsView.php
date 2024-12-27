<?php

namespace GregPriday\CopyTree\Views;

use DateTime;

/**
 * Renders file contents for the copy tree output.
 *
 * Generates a formatted view of file contents, including file paths, MIME types,
 * file sizes, line counts, and modification times.
 */
class FileContentsView
{
    public static function render(array $files, int $maxLines = 0): string
    {
        $output = [];

        foreach ($files as $file) {
            $output[] = '';
            $relativePath = $file['path'];
            $fileInfo = $file['file'];

            // Get file metadata
            $mimeType = mime_content_type($fileInfo->getPathname());
            $size = self::formatFileSize($fileInfo->getSize());
            $lines = count(file($fileInfo->getPathname()));
            $modifiedTime = (new DateTime)->setTimestamp($fileInfo->getMTime())->format('Y-m-d H:i:s');

            // Build tag with metadata
            $output[] = sprintf(
                '<file_contents path="%s" mime-type="%s" size="%s" lines="%d" modified="%s">',
                $relativePath,
                $mimeType,
                $size,
                $lines,
                $modifiedTime
            );

            try {
                $content = file_get_contents($fileInfo->getPathname());

                // If maxLines is set and greater than 0, limit the number of lines
                if ($maxLines > 0) {
                    $contentLines = explode("\n", $content);
                    if (count($contentLines) > $maxLines) {
                        $content = implode("\n", array_slice($contentLines, 0, $maxLines));
                        $content .= "\n\n... [truncated after {$maxLines} lines] ...";
                    }
                }

                $output[] = $content;
            } catch (\Exception $e) {
                $output[] = $e->getMessage();
            }

            $output[] = '</file_contents> '.sprintf('<!-- End of file: %s -->', $relativePath);
            $output[] = '';
        }

        return implode("\n", $output);
    }

    /**
     * Formats a file size into a human-readable string.
     *
     * @param  int  $bytes  The size in bytes
     * @return string The formatted size
     */
    private static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 1).' '.$units[$pow];
    }
}
