<?php

namespace GregPriday\CopyTree\Utilities\OpenAI;

use InvalidArgumentException;
use OpenAI\Client;
use RuntimeException;

class OpenAIFilenameGenerator
{
    private ?Client $client = null;

    private string $apiKey;

    private string $organization;

    private string $model;

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

    public function generateFilename(array $files, ?string $outputDirectory = null): string
    {
        if (empty($files)) {
            throw new InvalidArgumentException('No files provided for filename generation');
        }

        // Only take the first 200 files
        $files = array_slice($files, 0, 200);

        // Convert file paths to a formatted string for the API request
        $filesList = implode("\n", array_map(function ($file) {
            return '- '.$file['path'];
        }, $files));

        try {
            $system = file_get_contents(__DIR__.'/../../../prompts/filename-generator/system.txt');
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => "Generate a descriptive filename for the following set of files:\n\n{$filesList}"],
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
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
                    ],
                ],
                'temperature' => 0.1,
                'max_tokens' => 120,
            ]);

            $suggestedName = $response->choices[0]->message->content;
            $decodedResponse = json_decode($suggestedName, true);

            return $this->sanitizeFilename($decodedResponse['filename'], $outputDirectory);

        } catch (\Exception $e) {
            throw new RuntimeException('Failed to generate filename: '.$e->getMessage());
        }
    }

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
        $filename = substr($filename, 0, 90);

        // Add .txt extension
        $baseFilename = $filename;
        $filename = $filename.'.txt';

        // Check for existing files and add suffix if needed
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
