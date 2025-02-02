<?php

namespace GregPriday\CopyTree\Utilities\Git;

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
        if (PHP_OS_FAMILY !== 'Darwin') {
            throw new RuntimeException('This package only supports MacOS.');
        }

        $this->parseUrl($url);
        $this->setupCacheDirectory();
    }

    /**
     * Parse the GitHub URL into its components
     */
    private function parseUrl(string $url): void
    {
        $pattern = '#^https://github\.com/([^/]+/[^/]+)(?:/tree/([^/]+))?(?:/(.+))?$#';

        if (! preg_match($pattern, $url, $matches)) {
            throw new InvalidArgumentException('Invalid GitHub URL format');
        }

        $this->repoUrl = 'https://github.com/'.$matches[1].'.git';
        $this->branch = $matches[2] ?? 'main';
        $this->subPath = $matches[3] ?? '';
        $this->cacheKey = md5($matches[1].'/'.$this->branch);
    }

    /**
     * Set up the cache directory structure
     */
    private function setupCacheDirectory(): void
    {
        // Use consistent location under ~/.copytree
        $baseDir = getenv('HOME').'/.copytree/cache';

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
            $this->executeCommand(['git', 'fetch'], $this->repoDir);

            $behindCount = $this->executeCommand(
                ['git', 'rev-list', 'HEAD..origin/'.$this->branch, '--count'],
                $this->repoDir
            );

            if ((int) $behindCount->getOutput() > 0) {
                $this->executeCommand(['git', 'reset', '--hard', 'HEAD'], $this->repoDir);
                $this->executeCommand(['git', 'clean', '-fd'], $this->repoDir);
                $this->executeCommand(['git', 'pull', 'origin', $this->branch], $this->repoDir);
            }
        } catch (ProcessFailedException $e) {
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
        $cacheDir = getenv('HOME').'/.copytree/cache';

        if (is_dir($cacheDir)) {
            $process = new Process(['rm', '-rf', $cacheDir]);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        }
    }

    /**
     * Clean up this specific repository
     */
    public function cleanup(): void
    {
        if (is_dir($this->repoDir)) {
            $process = new Process(['rm', '-rf', $this->repoDir]);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        }
    }
}
