<?php

namespace GregPriday\CopyTree\Utilities;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GitHubUrlHandler
{
    private string $repoUrl;

    private string $branch;

    private string $subPath;

    private string $repoDir;

    private string $cacheKey;

    /**
     * Create a new GitHub URL handler
     */
    public function __construct(private readonly string $url)
    {
        $this->parseUrl($url);
        $this->setupCacheDirectory();
    }

    /**
     * Parse the GitHub URL into its components
     */
    private function parseUrl(string $url): void
    {
        // Match GitHub URL pattern
        $pattern = '#^https://github\.com/([^/]+/[^/]+)(?:/tree/([^/]+))?(?:/(.+))?$#';

        if (! preg_match($pattern, $url, $matches)) {
            throw new InvalidArgumentException('Invalid GitHub URL format');
        }

        $this->repoUrl = 'https://github.com/'.$matches[1].'.git';
        $this->branch = $matches[2] ?? 'main';
        $this->subPath = $matches[3] ?? '';

        // Create a unique cache key for this repo/branch combination
        $this->cacheKey = md5($matches[1].'/'.$this->branch);
    }

    /**
     * Set up the cache directory structure
     */
    private function setupCacheDirectory(): void
    {
        // Get the system's cache directory
        if (PHP_OS_FAMILY === 'Windows') {
            $baseDir = getenv('LOCALAPPDATA').'/CopyTree';
        } else {
            $baseDir = getenv('XDG_CACHE_HOME');
            if (! $baseDir) {
                $baseDir = getenv('HOME').'/.cache';
            }
            $baseDir .= '/copytree';
        }

        // Create the repos directory if it doesn't exist
        $reposDir = $baseDir.'/repos';
        if (! is_dir($reposDir)) {
            if (! mkdir($reposDir, 0777, true)) {
                throw new RuntimeException("Failed to create cache directory: {$reposDir}");
            }
        }

        $this->repoDir = $reposDir.'/'.$this->cacheKey;
    }

    /**
     * Get the repository files, using cache when possible
     */
    public function getFiles(): string
    {
        $this->ensureGitIsInstalled();

        if (! is_dir($this->repoDir)) {
            $this->cloneRepository();
        } else {
            $this->updateRepository();
        }

        $targetPath = $this->repoDir;
        if ($this->subPath) {
            $targetPath .= '/'.$this->subPath;

            if (! is_dir($targetPath)) {
                throw new InvalidArgumentException("Specified path '{$this->subPath}' not found in repository");
            }
        }

        return $targetPath;
    }

    /**
     * Check if a URL is a valid GitHub URL
     */
    public static function isGitHubUrl(string $url): bool
    {
        return str_starts_with($url, 'https://github.com/');
    }

    /**
     * Ensure Git is installed on the system
     */
    private function ensureGitIsInstalled(): void
    {
        try {
            $this->executeCommand(['git', '--version']);
        } catch (ProcessFailedException $e) {
            throw new RuntimeException('Git is not installed on this system');
        }
    }

    /**
     * Clone the repository to the cache directory
     */
    private function cloneRepository(): void
    {
        // Clone specific branch
        $command = [
            'git', 'clone',
            '--branch', $this->branch,
            '--single-branch',
            $this->repoUrl,
            $this->repoDir,
        ];

        try {
            $this->executeCommand($command);
        } catch (ProcessFailedException $e) {
            // Clean up failed clone
            if (is_dir($this->repoDir)) {
                $this->executeCommand(['rm', '-rf', $this->repoDir]);
            }
            throw new RuntimeException('Failed to clone repository: '.$e->getMessage());
        }
    }

    /**
     * Update the repository if it already exists
     */
    private function updateRepository(): void
    {
        try {
            // Fetch the latest changes
            $this->executeCommand(['git', 'fetch'], $this->repoDir);

            // Check if we need to update
            $behindCount = $this->executeCommand(
                ['git', 'rev-list', 'HEAD..origin/'.$this->branch, '--count'],
                $this->repoDir
            );

            if ((int) $behindCount->getOutput() > 0) {
                // Reset any local changes and pull
                $this->executeCommand(['git', 'reset', '--hard', 'HEAD'], $this->repoDir);
                $this->executeCommand(['git', 'clean', '-fd'], $this->repoDir);
                $this->executeCommand(['git', 'pull', 'origin', $this->branch], $this->repoDir);
            }
        } catch (ProcessFailedException $e) {
            // If update fails, remove and re-clone
            $this->executeCommand(['rm', '-rf', $this->repoDir]);
            $this->cloneRepository();
        }
    }

    /**
     * Execute a system command
     */
    private function executeCommand(array $command, ?string $cwd = null): Process
    {
        $process = new Process($command, $cwd);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process;
    }

    /**
     * Clean the entire cache directory
     */
    public static function cleanCache(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $cacheDir = getenv('LOCALAPPDATA').'/CopyTree';
        } else {
            $cacheDir = getenv('XDG_CACHE_HOME');
            if (! $cacheDir) {
                $cacheDir = getenv('HOME').'/.cache';
            }
            $cacheDir .= '/copytree';
        }

        if (is_dir($cacheDir)) {
            $process = new Process(['rm', '-rf', $cacheDir]);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        }
    }
}
