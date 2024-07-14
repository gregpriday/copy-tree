<?php

namespace GregPriday\CopyTree\Views;

/**
 * Renders file contents for the copy tree output.
 *
 * Generates a formatted view of file contents, including file paths and MIME types.
 */
class FileContentsView
{
    public static function render(array $files): string
    {
        $output = [];

        foreach ($files as $file) {
            $output[] = '';
            $relativePath = $file['path'];
            $mimeType = mime_content_type($file['file']->getPathname());
            $output[] = sprintf('<file_contents path="%s" mime-type="%s">', $relativePath, $mimeType);

            try {
                $content = file_get_contents($file['file']->getPathname());
                $output[] = $content;
            } catch (\Exception $e) {
                $output[] = $e->getMessage();
            }

            $output[] = '</file_contents> '.sprintf('<!-- End of file: %s -->', $relativePath);
            $output[] = '';
        }

        return implode("\n", $output);
    }
}
