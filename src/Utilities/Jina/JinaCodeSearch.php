<?php

namespace GregPriday\CopyTree\Utilities\Jina;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleRetry\GuzzleRetryMiddleware;
use RuntimeException;
use SplFileInfo;

class JinaCodeSearch extends BaseJinaService
{
    public function __construct(
        private readonly int $previewLength = 1000,
        private readonly int $chunkSize = 20,
        private readonly float $relevancyCutoff = 0.8
    ) {
        parent::__construct();

        // Add retry middleware to the client
        $stack = HandlerStack::create();
        $stack->push(GuzzleRetryMiddleware::factory([
            'max_retry_attempts' => 3,
            'retry_on_status' => [429, 503, 500],
            'default_retry_multiplier' => 2.0,
        ]));

        // Initialize client with retry middleware
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => $this->apiEndpoint,
            'handler' => $stack,
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Search through files based on a natural language query
     *
     * @param  array  $files  Array of files to search through, each containing 'path' and 'file' keys
     * @param  string  $query  Natural language search query
     * @return array{files: array, total_tokens: int} Ranked files and token usage info
     */
    public function searchFiles(array $files, string $query): array
    {
        if (empty($files)) {
            throw new RuntimeException('No files provided for searching');
        }

        // Prepare all documents
        $documents = array_map(function ($file) {
            return [
                'original' => $file,
                'content' => $this->getFileContent($file['file'], $file['path']),
            ];
        }, $files);

        // Split documents into chunks
        $chunks = array_chunk($documents, $this->chunkSize);
        $results = [];
        $totalTokens = 0;

        // Create requests for each chunk
        $requests = function () use ($chunks, $query) {
            foreach ($chunks as $chunk) {
                yield new Request(
                    'POST',
                    '',
                    ['Content-Type' => 'application/json'],
                    json_encode([
                        'model' => $this->model,
                        'query' => $query,
                        'documents' => array_column($chunk, 'content'),
                        'top_n' => count($chunk), // Get scores for all documents in chunk
                    ])
                );
            }
        };

        // Process chunks in parallel using Pool
        $pool = new Pool($this->client, $requests(), [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index) use (&$results, &$totalTokens, $chunks) {
                $data = json_decode($response->getBody()->getContents(), true);
                $totalTokens += $data['usage']['total_tokens'] ?? 0;

                // Map results back to original files
                foreach ($data['results'] as $result) {
                    $chunkIndex = $result['index'];
                    $originalFile = $chunks[$index][$chunkIndex]['original'];

                    $results[] = [
                        'file' => $originalFile,
                        'relevance_score' => $result['relevance_score'],
                    ];
                }
            },
            'rejected' => function ($reason) {
                throw new RuntimeException('Jina.ai API request failed: '.$reason->getMessage());
            },
        ]);

        // Wait for all requests to complete
        $pool->promise()->wait();

        // Sort results by relevance score
        usort($results, fn ($a, $b) => $b['relevance_score'] <=> $a['relevance_score']);

        $results = array_map(function ($result) {
            return [
                'file' => $result['file']['path'],
                'relevance_score' => $result['relevance_score'],
            ];
        }, $results);

        // Return all results with threshold indicator
        return [
            'files' => array_map(function ($result) {
                return [
                    'file' => $result['file'],
                    'relevance_score' => $result['relevance_score'],
                    'above_threshold' => $result['relevance_score'] >= $this->relevancyCutoff,
                ];
            }, $results),
            'total_tokens' => $totalTokens,
        ];
    }

    /**
     * Get file content with path as context
     *
     * @param  SplFileInfo  $file  The file to read
     * @param  string  $path  The relative path of the file
     * @return string The formatted content
     */
    private function getFileContent(SplFileInfo $file, string $path): string
    {
        try {
            $content = file_get_contents($file->getPathname());

            // Take a preview of the content to avoid too large requests
            $preview = mb_substr($content, 0, $this->previewLength);

            // Format the content with the path as context
            return "File Path: {$path}\n\nContent:\n{$preview}";

        } catch (\Exception $e) {
            throw new RuntimeException("Failed to read file {$path}: {$e->getMessage()}");
        }
    }
}
