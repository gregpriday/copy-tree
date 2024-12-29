<?php

namespace GregPriday\CopyTree\Filters\Git;

use GregPriday\CopyTree\Filters\FileFilterInterface;
use GregPriday\CopyTree\Utilities\Git\GitStatusChecker;
use RuntimeException;

/**
 * Filters files based on changes between two Git commits.
 *
 * This filter identifies files that have been modified between two specified Git commits,
 * allowing for focused review of changes within specific commit ranges.
 */
class ChangeFilter implements FileFilterInterface
{
    private GitStatusChecker $gitChecker;

    private string $fromCommit;

    private string $toCommit;

    private ?string $repoRoot = null;

    private ?array $changedFiles = null;

    /**
     * Create a new Git change filter.
     *
     * @param  string  $basePath  The base path of the repository
     * @param  string  $fromCommit  Starting commit hash
     * @param  string  $toCommit  Ending commit hash (defaults to HEAD)
     *
     * @throws RuntimeException If path is not a Git repository
     */
    public function __construct(
        private readonly string $basePath,
        string $fromCommit,
        ?string $toCommit = 'HEAD'
    ) {
        $this->gitChecker = new GitStatusChecker;

        if (! $this->gitChecker->isGitRepository($this->basePath)) {
            throw new RuntimeException("Not a Git repository: {$this->basePath}");
        }

        $this->gitChecker->initRepository($this->basePath);
        $this->fromCommit = $fromCommit;
        $this->toCommit = $toCommit ?? 'HEAD';
        $this->repoRoot = $this->gitChecker->getRepositoryRoot();
    }

    /**
     * Filter files based on Git changes between commits.
     *
     * {@inheritDoc}
     */
    public function filter(array $files, array $context = []): array
    {
        // Get the list of changed files if we haven't already
        if ($this->changedFiles === null) {
            try {
                $this->changedFiles = $this->gitChecker->getChangedFilesBetweenCommits(
                    $this->fromCommit,
                    $this->toCommit
                );
            } catch (\Exception $e) {
                throw new RuntimeException(
                    "Failed to get changed files between {$this->fromCommit} and {$this->toCommit}: ".
                    $e->getMessage()
                );
            }
        }

        // Filter the files array to only include changed files
        return array_filter($files, function ($file) {
            $relativePath = $this->getRelativePath($file['file']->getRealPath());

            return in_array($relativePath, $this->changedFiles);
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
        return sprintf(
            'Git changes between %s and %s',
            substr($this->fromCommit, 0, 8),  // Show abbreviated commit hashes
            substr($this->toCommit, 0, 8)
        );
    }

    /**
     * Determine if the filter should be applied.
     *
     * {@inheritDoc}
     */
    public function shouldApply(array $context = []): bool
    {
        // This filter should always apply if it was instantiated,
        // as the constructor validates the repository and commits
        return true;
    }

    /**
     * Get the list of changed files.
     *
     * @return array<string> Array of file paths relative to repository root
     */
    public function getChangedFiles(): array
    {
        if ($this->changedFiles === null) {
            $this->changedFiles = $this->gitChecker->getChangedFilesBetweenCommits(
                $this->fromCommit,
                $this->toCommit
            );
        }

        return $this->changedFiles;
    }
}
