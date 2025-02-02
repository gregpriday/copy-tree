<?php

namespace GregPriday\CopyTree\Utilities\OpenAI;

use OpenAI\Client;
use RuntimeException;

/**
 * Base class for OpenAI services providing common configuration and client initialization
 */
abstract class BaseOpenAIService
{
    protected ?Client $client = null;

    protected string $apiKey;

    protected string $organization;

    protected string $model;

    public function __construct()
    {
        $this->loadConfiguration();
        $this->initializeClient();
    }

    protected function loadConfiguration(): void
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

    protected function getConfigPath(): string
    {
        $homeDir = PHP_OS_FAMILY === 'Windows' ?
            getenv('USERPROFILE') :
            getenv('HOME');

        return $homeDir.DIRECTORY_SEPARATOR.'.copytree'.DIRECTORY_SEPARATOR.'.env';
    }

    protected function initializeClient(): void
    {
        $this->client = \OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withOrganization($this->organization)
            ->make();
    }

    /**
     * Creates a chat completion with standardized error handling
     *
     * @param  array  $messages  Chat messages to send
     * @param  array  $schema  JSON schema for response validation
     * @param  float  $temperature  Temperature for response generation
     * @param  int  $maxTokens  Maximum tokens in response
     * @return array Decoded response content
     *
     * @throws RuntimeException If API call fails
     */
    protected function createChatCompletion(
        array $messages,
        array $schema,
        float $temperature = 0.3,
        int $maxTokens = 1000
    ): array {
        try {
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => $messages,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => $schema,
                ],
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);

            return json_decode($response->choices[0]->message->content, true);

        } catch (\Exception $e) {
            throw new RuntimeException("OpenAI API call failed: {$e->getMessage()}");
        }
    }
}
