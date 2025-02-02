<?php

namespace GregPriday\CopyTree\Utilities\OpenAI;

use InvalidArgumentException;
use RuntimeException;

class OpenAIFilenameGenerator extends BaseOpenAIService
{
    /**
     * Maximum number of files to consider for filename generation
     */
    private const MAX_FILES = 200;

    /**
     * Maximum length for generated filenames (before extension)
     */
    private const MAX_FILENAME_LENGTH = 90;

    /**
     * Generate a descriptive filename based on a collection of files
     *
     * @param  array  $files  Array of files to analyze, each containing a 'path' key
     * @param  string|null  $outputDirectory  Directory where the file will be saved (for uniqueness check)
     * @return string Generated filename with .txt extension
     *
     * @throws InvalidArgumentException When no files are provided
     * @throws RuntimeException When filename generation fails
     */
    public function generateFilename(array $files, ?string $outputDirectory = null): string
    {
        if (empty($files)) {
            throw new InvalidArgumentException('No files provided for filename generation');
        }

        // Only take the first N files to avoid token limits
        $files = array_slice($files, 0, self::MAX_FILES);

        // Convert file paths to a formatted string for the API request
        $filesList = implode("\n", array_map(function ($file) {
            return '- '.$file['path'];
        }, $files));

        $schema = [
            'name' => 'generate_filename',
            'description' => 'Generates a descriptive filename based on the contents of files',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'filename' => [
                        'type' => 'string',
                        'description' => 'The suggested filename for the text file, without extension. Use short English words separated by `-`.',
                    ],
                ],
                'required' => ['filename'],
                'additionalProperties' => false,
            ],
        ];

        $system = file_get_contents(__DIR__.'/../../../prompts/filename-generator/system.txt');
        $result = $this->createChatCompletion(
            [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => "Generate a descriptive filename for the following set of files:\n\n{$filesList}"],
            ],
            $schema,
            0.1,
            120
        );

        return $this->sanitizeFilename($result['filename'], $outputDirectory);
    }

    /**
     * Sanitize and format the generated filename
     *
     * @param  string  $filename  Raw filename to sanitize
     * @param  string|null  $directory  Directory to check for filename uniqueness
     * @return string Sanitized filename with .txt extension
     */
    private function sanitizeFilename(string $filename, ?string $directory = null): string
    {
        // Convert everything that's not alphanumeric to dashes
        $filename = preg_replace('/[^a-zA-Z0-9]+/', '-', $filename);

        // Convert to lowercase
        $filename = strtolower($filename);

        // Replace multiple dashes with single dash
        $filename = preg_replace('/-+/', '-', $filename);

        // Trim dashes from start and end
        $filename = trim($filename, '-');

        // Ensure reasonable length (leaving room for suffix)
        $filename = substr($filename, 0, self::MAX_FILENAME_LENGTH);

        // Add .txt extension
        $baseFilename = $filename;
        $filename = $filename.'.txt';

        // Ensure filename uniqueness if directory is provided
        if ($directory) {
            $counter = 2;
            while (file_exists($directory.DIRECTORY_SEPARATOR.$filename)) {
                $filename = $baseFilename.'-'.$counter.'.txt';
                $counter++;
            }
        }

        return $filename;
    }
}
