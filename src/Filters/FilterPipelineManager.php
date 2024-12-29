<?php

namespace GregPriday\CopyTree\Filters;

use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Manages the execution of file filters in a pipeline.
 *
 * This class coordinates the execution of multiple FileFilterInterface implementations
 * in sequence, handling logging, errors, and maintaining the pipeline state.
 */
class FilterPipelineManager
{
    /** @var array<FileFilterInterface> */
    private array $filters = [];

    private array $context = [];

    public function __construct(
        private readonly ?SymfonyStyle $io = null
    ) {}

    /**
     * Add a filter to the pipeline.
     *
     * @param  FileFilterInterface  $filter  The filter to add
     * @return self For method chaining
     */
    public function addFilter(FileFilterInterface $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Set context data for the pipeline execution.
     *
     * @param  array  $context  Context data accessible to all filters
     * @return self For method chaining
     */
    public function setContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Execute all filters in the pipeline.
     *
     * @param  array  $files  Initial array of files to filter
     * @return array The filtered files array
     *
     * @throws RuntimeException If any filter fails
     */
    public function execute(array $files): array
    {
        $filteredFiles = $files;
        $activeFilters = $this->getActiveFilters();

        if (empty($activeFilters)) {
            $this->log('No active filters in pipeline', 'comment');

            return $files;
        }

        foreach ($activeFilters as $filter) {
            $this->logFilterStart($filter);

            try {
                $initialCount = count($filteredFiles);
                $filteredFiles = $filter->filter($filteredFiles, $this->context);
                $finalCount = count($filteredFiles);

                $this->logFilterResults($filter, $initialCount, $finalCount);

            } catch (\Exception $e) {
                $this->handleFilterError($filter, $e);
            }

            // Stop processing if no files remain
            if (empty($filteredFiles)) {
                $this->log('No files remaining after filter, stopping pipeline', 'comment');
                break;
            }
        }

        return $filteredFiles;
    }

    /**
     * Get the IO interface used by this pipeline manager.
     */
    public function getIo(): ?SymfonyStyle
    {
        return $this->io;
    }

    /**
     * Get array of filters that should be applied based on current context.
     *
     * @return array<FileFilterInterface>
     */
    private function getActiveFilters(): array
    {
        return array_filter($this->filters, fn ($filter) => $filter->shouldApply($this->context));
    }

    /**
     * Get descriptions of all filters in the pipeline.
     *
     * @return array<string> Array of filter descriptions
     */
    public function getFilterDescriptions(): array
    {
        return array_map(fn ($filter) => $filter->getDescription(), $this->filters);
    }

    /**
     * Check if the pipeline has any filters.
     */
    public function hasFilters(): bool
    {
        return ! empty($this->filters);
    }

    /**
     * Log a message if IO is available.
     *
     * @param  string  $message  The message to log
     * @param  string  $type  The type of message (info, comment, warning, error)
     */
    private function log(string $message, string $type = 'info'): void
    {
        if (! $this->io) {
            return;
        }

        match ($type) {
            'info' => $this->io->writeln($message),
            'comment' => $this->io->writeln("<comment>{$message}</comment>"),
            'warning' => $this->io->warning($message),
            'error' => $this->io->error($message),
            default => $this->io->writeln($message)
        };
    }

    /**
     * Log the start of a filter's execution.
     */
    private function logFilterStart(FileFilterInterface $filter): void
    {
        $this->log(
            "Applying filter: {$filter->getDescription()}",
            'info'
        );
    }

    /**
     * Log the results of a filter's execution.
     */
    private function logFilterResults(
        FileFilterInterface $filter,
        int $initialCount,
        int $finalCount
    ): void {
        $difference = $initialCount - $finalCount;
        if ($difference > 0) {
            $this->log(
                "Filter removed {$difference} files, {$finalCount} remaining",
                'comment'
            );
        }
    }

    /**
     * Handle an error from a filter.
     *
     * @throws RuntimeException If no IO is available to handle the error gracefully
     */
    private function handleFilterError(FileFilterInterface $filter, \Exception $error): void
    {
        $message = "Filter failed: {$filter->getDescription()}\nError: {$error->getMessage()}";

        if ($this->io) {
            $this->log($message, 'error');
            $this->log('Continuing with unfiltered results...', 'comment');
        } else {
            throw new RuntimeException($message);
        }
    }
}
