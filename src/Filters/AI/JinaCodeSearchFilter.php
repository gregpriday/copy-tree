<?php

namespace GregPriday\CopyTree\Filters\AI;

use GregPriday\CopyTree\Filters\FileFilterInterface;
use GregPriday\CopyTree\Utilities\Jina\JinaCodeSearch;
use RuntimeException;

/**
 * Filters files using Jina AI's semantic code search capabilities.
 *
 * This filter uses Jina's language models to perform semantic code search,
 * finding files that match a given query based on their content and context.
 */
class JinaCodeSearchFilter implements FileFilterInterface
{
    private JinaCodeSearch $jinaSearch;

    private array $searchResults = [];

    private int $totalTokens = 0;

    private float $relevancyThreshold;

    /**
     * Create a new Jina code search filter.
     *
     * @param  string  $query  Search query to find relevant code
     * @param  float  $relevancyThreshold  Minimum relevancy score (0.0 to 1.0) for including files
     * @param  int  $previewLength  Maximum length of file content preview for analysis
     * @param  int  $chunkSize  Number of files to process in each batch
     *
     * @throws RuntimeException If Jina configuration is missing or invalid
     */
    public function __construct(
        private readonly string $query,
        float $relevancyThreshold = 0.35,
        int $previewLength = 8192,
        int $chunkSize = 20
    ) {
        if ($relevancyThreshold < 0.0 || $relevancyThreshold > 1.0) {
            throw new RuntimeException('Relevancy threshold must be between 0.0 and 1.0');
        }

        try {
            $this->jinaSearch = new JinaCodeSearch($previewLength, $chunkSize);
            $this->relevancyThreshold = $relevancyThreshold;
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to initialize Jina search: '.$e->getMessage());
        }
    }

    /**
     * Filter files based on semantic code search.
     *
     * {@inheritDoc}
     */
    public function filter(array $files, array $context = []): array
    {
        if (empty($files)) {
            return [];
        }

        try {
            // Perform semantic search
            $result = $this->jinaSearch->searchFiles($files, $this->query);

            // Store results for later analysis
            $this->searchResults = $result['files'];
            $this->totalTokens = $result['total_tokens'];

            // Filter files based on relevancy threshold
            return array_filter($files, function ($file) {
                // Find this file's result
                $result = $this->findFileResult($file);

                return $result !== null &&
                    $result['relevance_score'] >= $this->relevancyThreshold;
            });
        } catch (\Exception $e) {
            throw new RuntimeException('Jina search failed: '.$e->getMessage());
        }
    }

    /**
     * Find search result for a specific file.
     */
    private function findFileResult(array $file): ?array
    {
        foreach ($this->searchResults as $result) {
            if ($result['file'] === $file['path']) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Get description of the filter's current configuration.
     *
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        $description = sprintf(
            'Jina semantic search: "%s" (threshold: %.2f)',
            $this->query,
            $this->relevancyThreshold
        );

        if (! empty($this->searchResults)) {
            $description .= sprintf(
                "\nProcessed %d files using %d tokens",
                count($this->searchResults),
                $this->totalTokens
            );

            $averageScore = array_reduce(
                $this->searchResults,
                fn ($carry, $item) => $carry + $item['relevance_score'],
                0
            ) / count($this->searchResults);

            $description .= sprintf(
                "\nAverage relevance score: %.3f",
                $averageScore
            );
        }

        return $description;
    }

    /**
     * Determine if the filter should be applied.
     *
     * {@inheritDoc}
     */
    public function shouldApply(array $context = []): bool
    {
        // Jina filter should always apply if it was successfully constructed
        // since it requires a query and valid configuration
        return true;
    }

    /**
     * Get detailed search results including relevancy scores.
     *
     * @return array Array of search results with file information and scores
     */
    public function getSearchResults(): array
    {
        return $this->searchResults;
    }

    /**
     * Get the total number of tokens used in the last search.
     */
    public function getTotalTokens(): int
    {
        return $this->totalTokens;
    }

    /**
     * Get the current relevancy threshold.
     */
    public function getRelevancyThreshold(): float
    {
        return $this->relevancyThreshold;
    }

    /**
     * Set a new relevancy threshold.
     *
     * @param  float  $threshold  New threshold value between 0.0 and 1.0
     * @return self For method chaining
     *
     * @throws RuntimeException If threshold is invalid
     */
    public function setRelevancyThreshold(float $threshold): self
    {
        if ($threshold < 0.0 || $threshold > 1.0) {
            throw new RuntimeException('Relevancy threshold must be between 0.0 and 1.0');
        }

        $this->relevancyThreshold = $threshold;

        return $this;
    }

    /**
     * Get the search query being used.
     */
    public function getQuery(): string
    {
        return $this->query;
    }
}
