<?php

namespace GregPriday\CopyTree\Utilities\OpenAI;

use InvalidArgumentException;
use OpenAI\Client;
use RuntimeException;

class OpenAIFileFilter
{
    private ?Client $client = null;

    private string $apiKey;

    private string $organization;

    private string $model;

    private int $previewLength = 450;

    public function __construct()
    {
        $this->loadConfiguration();
        $this->initializeClient();
    }

    private function loadConfiguration(): void
    {
        $envPath = $this->getConfigPath();

        if (! file_exists($envPath)) {
            throw new RuntimeException("OpenAI configuration file not found at {$envPath}");
        }

        $env = parse_ini_file($envPath);

        if (! isset($env['OPENAI_API_KEY']) || ! isset($env['OPENAI_API_ORG'])) {
            throw new RuntimeException('OpenAI API key or organization not found in configuration');
        }

        $this->apiKey = $env['OPENAI_API_KEY'];
        $this->organization = $env['OPENAI_API_ORG'];
        $this->model = $env['OPENAI_MODEL'] ?? 'gpt-4o';
    }

    private function getConfigPath(): string
    {
        $homeDir = PHP_OS_FAMILY === 'Windows' ?
            getenv('USERPROFILE') :
            getenv('HOME');

        return $homeDir.DIRECTORY_SEPARATOR.'.copytree'.DIRECTORY_SEPARATOR.'.env';
    }

    private function initializeClient(): void
    {
        $this->client = \OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withOrganization($this->organization)
            ->make();
    }

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

        try {
            $system = file_get_contents(__DIR__.'/../../../prompts/file-filter/system.txt');
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    [
                        'role' => 'user',
                        'content' => json_encode([
                            'description' => $filterDescription,
                            'files' => $filesData,
                        ]),
                    ],
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
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
                    ],
                ],
                'temperature' => 0.3,
                'max_tokens' => 1000,
            ]);

            $result = json_decode($response->choices[0]->message->content, true);

            // Filter the original files array based on the returned paths
            return [
                'files' => array_filter($files, function ($file) use ($result) {
                    return in_array($file['path'], $result['files']);
                }),
                'explanation' => $result['explanation'],
            ];

        } catch (\Exception $e) {
            throw new RuntimeException('Failed to filter files: '.$e->getMessage());
        }
    }

    private function getFilePreview(string $filepath): string
    {
        try {
            $content = file_get_contents($filepath);

            return mb_substr($content, 0, $this->previewLength);
        } catch (\Exception $e) {
            return '[Error reading file content]';
        }
    }

    public function setPreviewLength(int $length): void
    {
        if ($length < 1) {
            throw new InvalidArgumentException('Preview length must be greater than 0');
        }
        $this->previewLength = $length;
    }
}
