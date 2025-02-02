<?php

namespace GregPriday\CopyTree\Utilities\OpenAI;

use InvalidArgumentException;
use RuntimeException;

class OpenAIFileFilter extends BaseOpenAIService
{
    /**
     * Maximum length of file content preview to send to OpenAI
     */
    private int $previewLength = 450;

    /**
     * Filter files based on a natural language description
     *
     * @param  array  $files  Array of files to filter, each containing 'path' and 'file' keys
     * @param  string  $filterDescription  Natural language description of files to include
     * @return array{files: array, explanation: string} Filtered files and explanation
     *
     * @throws InvalidArgumentException When no files are provided
     * @throws RuntimeException When filtering fails
     */
    public function filterFiles(array $files, string $filterDescription): array
    {
        if (empty($files)) {
            throw new InvalidArgumentException('No files provided for filtering');
        }

        // Prepare files data for the API
        $filesData = array_map(function ($file) {
            return [
                'path' => $file['path'],
                'preview' => $this->getFilePreview($file['file']->getPathname()),
            ];
        }, $files);

        $schema = [
            'name' => 'filter_files',
            'description' => 'Filters files based on paths and content according to user description',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'files' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                            'description' => 'Paths of files to include in the filtered results',
                        ],
                        'description' => 'Array of file paths that match the filtering criteria',
                    ],
                    'explanation' => [
                        'type' => 'string',
                        'description' => 'Brief explanation of why these files were selected',
                    ],
                ],
                'required' => ['files', 'explanation'],
                'additionalProperties' => false,
            ],
        ];

        $system = file_get_contents(__DIR__.'/../../../prompts/file-filter/system.txt');
        $result = $this->createChatCompletion(
            [
                ['role' => 'system', 'content' => $system],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'description' => $filterDescription,
                        'files' => $filesData,
                    ]),
                ],
            ],
            $schema
        );

        // Filter the original files array based on the returned paths
        return [
            'files' => array_filter($files, function ($file) use ($result) {
                return in_array($file['path'], $result['files']);
            }),
            'explanation' => $result['explanation'],
        ];
    }

    /**
     * Get a preview of file contents
     *
     * @param  string  $filepath  Path to the file
     * @return string Preview of file contents or error message
     */
    private function getFilePreview(string $filepath): string
    {
        try {
            $content = file_get_contents($filepath);

            return mb_substr($content, 0, $this->previewLength);
        } catch (\Exception) {
            return '[Error reading file content]';
        }
    }

    /**
     * Set the maximum length of file content previews
     *
     * @param  int  $length  Maximum preview length in characters
     *
     * @throws InvalidArgumentException When length is less than 1
     */
    public function setPreviewLength(int $length): void
    {
        if ($length < 1) {
            throw new InvalidArgumentException('Preview length must be greater than 0');
        }
        $this->previewLength = $length;
    }
}
