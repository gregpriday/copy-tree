<?php

namespace GregPriday\CopyTree\Utilities\Jina;

use GuzzleHttp\Client;
use RuntimeException;

/**
 * Base class for Jina.ai services providing common configuration and client initialization
 */
abstract class BaseJinaService
{
    protected ?Client $client = null;

    protected string $apiKey;

    protected string $model = 'jina-reranker-v2-base-multilingual';

    protected string $apiEndpoint = 'https://api.jina.ai/v1/rerank';

    public function __construct()
    {
        $this->loadConfiguration();
        $this->initializeClient();
    }

    protected function loadConfiguration(): void
    {
        $envPath = $this->getConfigPath();

        if (! file_exists($envPath)) {
            throw new RuntimeException("Jina.ai configuration file not found at {$envPath}");
        }

        $env = parse_ini_file($envPath);

        if (! isset($env['JINA_API_KEY'])) {
            throw new RuntimeException('Jina.ai API key not found in configuration');
        }

        $this->apiKey = $env['JINA_API_KEY'];
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
        $this->client = new Client([
            'base_uri' => $this->apiEndpoint,
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Creates a reranking request with standardized error handling
     *
     * @param  string  $query  The search query
     * @param  array  $documents  Array of documents to rerank
     * @param  int  $topN  Number of top results to return
     * @return array Decoded response content
     *
     * @throws RuntimeException If API call fails
     */
    protected function createRerankRequest(
        string $query,
        array $documents,
        int $topN = 3
    ): array {
        try {
            $response = $this->client->post('', [
                'json' => [
                    'model' => $this->model,
                    'query' => $query,
                    'top_n' => $topN,
                    'documents' => $documents,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (\Exception $e) {
            throw new RuntimeException("Jina.ai API call failed: {$e->getMessage()}");
        }
    }

    /**
     * Set the model to use for reranking
     *
     * @param  string  $model  The model identifier
     */
    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    /**
     * Set a custom API endpoint
     *
     * @param  string  $endpoint  The API endpoint URL
     */
    public function setApiEndpoint(string $endpoint): void
    {
        $this->apiEndpoint = $endpoint;
        // Reinitialize client with new endpoint
        $this->initializeClient();
    }
}
