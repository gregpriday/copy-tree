<?php

namespace GregPriday\CopyTree\Filters\Git;

use GregPriday\CopyTree\Filters\FileFilterInterface;
use GregPriday\CopyTree\Utilities\Git\GitStatusChecker;
use RuntimeException;

/**
 * Filters files based on Git working directory modifications.
 *
 * This filter identifies files that have been modified since the last commit,
 * including staged changes, unstaged modifications, and new files.
 */
class ModifiedFilter implements FileFilterInterface
{
    private GitStatusChecker $gitChecker;

    private ?string $repoRoot = null;

    private ?array $modifiedFiles = null;

    /**
     * Create a new Git modified files filter.
     *
     * @param  string  $basePath  The base path of the repository
     *
     * @throws RuntimeException If path is not a Git repository
     */
    public function __construct(private readonly string $basePath)
    {
        $this->gitChecker = new GitStatusChecker;

        if (! $this->gitChecker->isGitRepository($this->basePath)) {
            throw new RuntimeException("Not a Git repository: {$this->basePath}");
        }

        $this->gitChecker->initRepository($this->basePath);
        $this->repoRoot = $this->gitChecker->getRepositoryRoot();
    }

    /**
     * Filter files based on Git modifications.
     *
     * {@inheritDoc}
     */
    public function filter(array $files, array $context = []): array
    {
        // Get the list of modified files if we haven't already
        if ($this->modifiedFiles === null) {
            try {
                $this->modifiedFiles = $this->gitChecker->getModifiedFiles();
            } catch (\Exception $e) {
                throw new RuntimeException(
                    'Failed to get modified files: '.$e->getMessage()
                );
            }
        }

        // If there are no modified files, return an empty array
        if (empty($this->modifiedFiles)) {
            return [];
        }

        // Filter the files array to only include modified files
        return array_filter($files, function ($file) {
            $relativePath = $this->getRelativePath($file['file']->getRealPath());

            return in_array($relativePath, $this->modifiedFiles);
        });
    }

    /**
     * Get a relative path from the repository root.
     */
    private function getRelativePath(string $absolutePath): string
    {
        return str_replace($this->repoRoot.'/', '', $absolutePath);
    }

    /**
     * Get description of the filter's current configuration.
     *
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        // Get count of modified files for more informative description
        if ($this->modifiedFiles === null) {
            $this->modifiedFiles = $this->gitChecker->getModifiedFiles();
        }

        $count = count($this->modifiedFiles);

        return sprintf(
            'Git modified files since last commit (%d file%s)',
            $count,
            $count === 1 ? '' : 's'
        );
    }

    /**
     * Determine if the filter should be applied.
     *
     * {@inheritDoc}
     */
    public function shouldApply(array $context = []): bool
    {
        try {
            // Only apply if there are actually modified files
            if ($this->modifiedFiles === null) {
                $this->modifiedFiles = $this->gitChecker->getModifiedFiles();
            }

            return ! empty($this->modifiedFiles);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get the list of modified files.
     *
     * @return array<string> Array of file paths relative to repository root
     */
    public function getModifiedFiles(): array
    {
        if ($this->modifiedFiles === null) {
            $this->modifiedFiles = $this->gitChecker->getModifiedFiles();
        }

        return $this->modifiedFiles;
    }

    /**
     * Check if the repository has any modifications.
     */
    public function hasModifications(): bool
    {
        return $this->gitChecker->hasChanges();
    }
}
