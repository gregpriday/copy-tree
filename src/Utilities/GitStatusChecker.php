<?php

namespace GregPriday\CopyTree\Utilities;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitRepository;
use RuntimeException;

class GitStatusChecker
{
    private ?GitRepository $repository = null;

    private Git $git;

    public function __construct()
    {
        $this->git = new Git;
    }

    /**
     * Initialize the repository for the given path
     */
    public function initRepository(string $path): void
    {
        try {
            $this->repository = $this->git->open($path);
        } catch (\Exception $e) {
            throw new RuntimeException("Not a git repository: {$path}");
        }
    }

    /**
     * Check if the given path is a Git repository
     */
    public function isGitRepository(string $path): bool
    {
        try {
            $this->git->open($path);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get list of modified files since last commit
     *
     * @return array<string> Array of modified file paths relative to repository root
     */
    public function getModifiedFiles(): array
    {
        if (! $this->repository) {
            throw new RuntimeException('Repository not initialized');
        }

        try {
            // Get the repository status
            // Note: execute returns array of strings for output lines
            $output = $this->repository->execute('status', '--porcelain');

            // Parse the porcelain output
            $files = [];

            // Handle the case where output is an array (multiple lines) or string (single line)
            $lines = is_array($output) ? $output : explode("\n", trim($output));

            foreach ($lines as $line) {
                if (empty($line)) {
                    continue;
                }

                // Parse porcelain status format
                // XY PATH
                // X = status of index, Y = status of working tree
                $status = substr($line, 0, 2);
                $path = substr($line, 3);

                // Added, modified, or renamed in index
                if (in_array($status[0], ['A', 'M', 'R'])) {
                    $files[] = $path;
                }
                // Modified in working tree
                elseif ($status[1] === 'M') {
                    $files[] = $path;
                }
            }

            return array_unique($files);

        } catch (\Exception $e) {
            throw new RuntimeException('Failed to get modified files: '.$e->getMessage());
        }
    }

    /**
     * Get list of files that have changed between two commits
     *
     * @return array<string> Array of changed file paths
     */
    public function getChangedFilesBetweenCommits(string $fromCommit, string $toCommit = 'HEAD'): array
    {
        if (! $this->repository) {
            throw new RuntimeException('Repository not initialized');
        }

        try {
            // Get the diff between commits
            $output = $this->repository->execute('diff', '--name-only', $fromCommit, $toCommit);

            if (is_array($output)) {
                return array_filter($output);
            }

            return array_filter(explode("\n", trim($output)));

        } catch (\Exception $e) {
            throw new RuntimeException("Failed to get changed files between {$fromCommit} and {$toCommit}: ".$e->getMessage());
        }
    }

    /**
     * Get list of files changed in the last commit
     *
     * @return array<string> Array of changed file paths
     */
    public function getFilesInLastCommit(): array
    {
        if (! $this->repository) {
            throw new RuntimeException('Repository not initialized');
        }

        try {
            $output = $this->repository->execute('diff-tree', '--no-commit-id', '--name-only', '-r', 'HEAD');

            if (is_array($output)) {
                return array_filter($output);
            }

            return array_filter(explode("\n", trim($output)));

        } catch (\Exception $e) {
            throw new RuntimeException('Failed to get files in last commit: '.$e->getMessage());
        }
    }

    /**
     * Get the repository root directory
     */
    public function getRepositoryRoot(): string
    {
        if (! $this->repository) {
            throw new RuntimeException('Repository not initialized');
        }

        return $this->repository->getRepositoryPath();
    }

    /**
     * Check if the repository has any changes
     */
    public function hasChanges(): bool
    {
        if (! $this->repository) {
            throw new RuntimeException('Repository not initialized');
        }

        return $this->repository->hasChanges();
    }
}
