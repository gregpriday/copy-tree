<?php

namespace GregPriday\CopyTree\Utilities;

use GregPriday\CopyTree\Filters\Ruleset\RulesetFilter;
use GregPriday\CopyTree\Utilities\Git\GitHubUrlHandler;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExternalSourceProcessor
{
    private array $externalItems;

    private string $basePath;

    private ?SymfonyStyle $io;

    /**
     * Constructor.
     *
     * @param  array  $externalItems  Array of external items (from the "external" key)
     * @param  string  $basePath  The base path of the main project (used to resolve relative paths)
     * @param  SymfonyStyle|null  $io  Optional IO interface for logging/warnings
     */
    public function __construct(array $externalItems, string $basePath, ?SymfonyStyle $io = null)
    {
        $this->externalItems = $externalItems;
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->io = $io;
    }

    /**
     * Process all external items and return a merged list of files with remapped paths.
     *
     * @return array Array of files in the format: ['path' => string, 'file' => SplFileInfo]
     */
    public function process(): array
    {
        $mergedFiles = [];

        foreach ($this->externalItems as $item) {
            // Validate that the required keys exist.
            if (! isset($item['source'], $item['destination'])) {
                if ($this->io) {
                    $this->io->warning('Skipping external item: "source" or "destination" not specified.');
                }

                continue;
            }

            $source = $item['source'];
            $destination = $item['destination'];
            $rules = $item['rules'] ?? null;

            // Resolve the external source path.
            $resolvedSource = $this->resolveSource($source);
            if ($resolvedSource === null) {
                if ($this->io) {
                    $this->io->warning("Skipping external item: unable to resolve source: {$source}");
                }

                continue;
            }

            // Scan the external source for files.
            $files = $this->scanDirectory($resolvedSource);
            if ($this->io) {
                $this->io->writeln('Found '.count($files)." files in external source: {$resolvedSource}", SymfonyStyle::VERBOSITY_VERBOSE);
            }

            // Apply optional filtering if "rules" are provided.
            if (is_array($rules)) {
                try {
                    $externalRuleset = RulesetFilter::fromArray(['rules' => $rules], $resolvedSource);
                    $files = $externalRuleset->filter($files);
                } catch (\Exception $e) {
                    if ($this->io) {
                        $this->io->warning("Failed to apply filtering rules for external source {$source}: ".$e->getMessage());
                    }
                    // Decide whether to continue without filtering or skip the item.
                    // Here we continue without filtering.
                }
            }

            // Remap each file's relative path by prefixing it with the destination.
            $files = $this->remapPaths($files, $destination);

            // Merge the processed external files.
            $mergedFiles = array_merge($mergedFiles, $files);
        }

        return $mergedFiles;
    }

    /**
     * Resolve the source path.
     *
     * If the source is a GitHub URL, uses GitHubUrlHandler.
     * If the source is a relative path, resolves it relative to the main basePath.
     * If absolute, uses it as given.
     *
     * @return string|null Resolved absolute path or null if resolution fails.
     */
    private function resolveSource(string $source): ?string
    {
        // Check if the source is a GitHub URL.
        if (str_starts_with($source, 'https://github.com/')) {
            try {
                $handler = new GitHubUrlHandler($source);

                // getFiles() returns the local directory path of the cloned/fetched repository or subdirectory.
                return $handler->getFiles();
            } catch (\Exception $e) {
                if ($this->io) {
                    $this->io->warning("GitHub URL resolution failed for {$source}: ".$e->getMessage());
                }

                return null;
            }
        }

        // Check if the source is an absolute path.
        if ($this->isAbsolutePath($source)) {
            return is_dir($source) ? realpath($source) : null;
        }

        // Otherwise, treat as a relative path from the main basePath.
        $resolved = realpath($this->basePath.DIRECTORY_SEPARATOR.$source);

        return ($resolved !== false && is_dir($resolved)) ? $resolved : null;
    }

    /**
     * Determine if a given path is absolute.
     */
    private function isAbsolutePath(string $path): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return preg_match('/^[a-zA-Z]:\\\\/', $path) === 1;
        }

        return str_starts_with($path, '/');
    }

    /**
     * Recursively scan a directory and return an array of files.
     *
     * Each file is returned as an associative array with keys:
     * - 'path': The file path relative to the external source root.
     * - 'file': The SplFileInfo object.
     */
    private function scanDirectory(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if ($file->isFile()) {
                // Calculate relative path from the external source root.
                $relativePath = ltrim(str_replace(realpath($directory), '', $file->getRealPath()), DIRECTORY_SEPARATOR);
                $files[] = [
                    'path' => $relativePath,
                    'file' => $file,
                ];
            }
        }

        return $files;
    }

    /**
     * Remap file paths by prefixing each with the destination path.
     *
     * For example, if a file's relative path is "subfolder/file.txt" and the destination is
     * "path/to/destination/directory", the new relative path becomes:
     * "path/to/destination/directory/subfolder/file.txt"
     *
     * @param  array  $files  Array of files with a 'path' key.
     * @param  string  $destination  The destination prefix.
     * @return array The array with remapped file paths.
     */
    private function remapPaths(array $files, string $destination): array
    {
        $destination = rtrim($destination, '/');
        foreach ($files as &$file) {
            $file['path'] = $destination.'/'.ltrim($file['path'], '/');
        }

        return $files;
    }
}
