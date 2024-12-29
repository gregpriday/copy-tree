<?php

namespace GregPriday\CopyTree\Filters;

/**
 * Interface for file filtering components in the CopyTree pipeline.
 *
 * Each filter in the pipeline should implement this interface to provide
 * consistent filtering behavior. Filters can be chained together to create
 * a complete filtering pipeline.
 *
 * Expected file array format:
 * [
 *     [
 *         'path' => string,      // Relative path to the file
 *         'file' => SplFileInfo  // File information object
 *     ],
 *     ...
 * ]
 */
interface FileFilterInterface
{
    /**
     * Filter an array of files based on specific criteria.
     *
     * @param  array  $files  Array of file information arrays
     * @param  array  $context  Optional context data that might be needed by filters
     * @return array Filtered array of files in the same format as input
     *
     * @throws \RuntimeException If filtering operation fails
     */
    public function filter(array $files, array $context = []): array;

    /**
     * Get a human-readable description of what this filter does.
     *
     * This description is used for logging and debugging purposes to understand
     * how the filter was configured and what it's meant to do.
     *
     * @return string Description of the filter's purpose and configuration
     */
    public function getDescription(): string;

    /**
     * Determine if the filter should be applied based on current context.
     *
     * This allows filters to be conditionally skipped based on their
     * configuration or context, without removing them from the pipeline.
     *
     * @param  array  $context  Context data that might affect whether filter should run
     * @return bool True if filter should be applied, false to skip
     */
    public function shouldApply(array $context = []): bool;
}
