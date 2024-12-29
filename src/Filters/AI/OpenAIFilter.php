<?php

namespace GregPriday\CopyTree\Filters\AI;

use GregPriday\CopyTree\Filters\FileFilterInterface;
use GregPriday\CopyTree\Utilities\OpenAI\OpenAIFileFilter;
use RuntimeException;

/**
 * Filters files using OpenAI's language model based on natural language descriptions.
 *
 * This filter uses OpenAI to analyze file paths and content previews to determine
 * which files best match a given natural language description.
 */
class OpenAIFilter implements FileFilterInterface
{
    private OpenAIFileFilter $aiFilter;

    private ?string $explanation = null;

    /**
     * Create a new OpenAI filter.
     *
     * @param  string  $description  Natural language description of files to include
     * @param  int  $previewLength  Maximum length of file content preview sent to OpenAI
     *
     * @throws RuntimeException If OpenAI configuration is missing or invalid
     */
    public function __construct(
        private readonly string $description,
        int $previewLength = 450
    ) {
        try {
            $this->aiFilter = new OpenAIFileFilter;
            $this->aiFilter->setPreviewLength($previewLength);
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to initialize OpenAI filter: '.$e->getMessage());
        }
    }

    /**
     * Filter files based on AI analysis.
     *
     * {@inheritDoc}
     */
    public function filter(array $files, array $context = []): array
    {
        if (empty($files)) {
            return [];
        }

        try {
            $result = $this->aiFilter->filterFiles($files, $this->description);
            $this->explanation = $result['explanation'];

            return $result['files'];
        } catch (\Exception $e) {
            throw new RuntimeException('OpenAI filtering failed: '.$e->getMessage());
        }
    }

    /**
     * Get description of the filter's current configuration.
     *
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        $baseDescription = sprintf('OpenAI filter: "%s"', $this->description);

        if ($this->explanation !== null) {
            return $baseDescription."\nAI Explanation: ".$this->explanation;
        }

        return $baseDescription;
    }

    /**
     * Determine if the filter should be applied.
     *
     * {@inheritDoc}
     */
    public function shouldApply(array $context = []): bool
    {
        // OpenAI filter should always apply if it was successfully constructed
        // since it requires a description and valid configuration
        return true;
    }

    /**
     * Get the AI's explanation for its filtering decisions.
     *
     * @return string|null The explanation, or null if filter hasn't been run yet
     */
    public function getExplanation(): ?string
    {
        return $this->explanation;
    }

    /**
     * Set a new preview length for file content analysis.
     *
     * @param  int  $length  Maximum length of file content preview
     * @return self For method chaining
     */
    public function setPreviewLength(int $length): self
    {
        $this->aiFilter->setPreviewLength($length);

        return $this;
    }

    /**
     * Get the natural language description being used for filtering.
     */
    public function getFilterDescription(): string
    {
        return $this->description;
    }
}
