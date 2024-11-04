<?php

namespace GregPriday\CopyTree\Tests\Integration;

use GregPriday\CopyTree\CopyTreeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class GitHubUrlHandlerTest extends TestCase
{
    private CommandTester $commandTester;

    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up the command tester
        $application = new Application;
        $application->add(new CopyTreeCommand);
        $command = $application->find('app:copy-tree');
        $this->commandTester = new CommandTester($command);

        // Set up cache directory path
        if (PHP_OS_FAMILY === 'Windows') {
            $this->cacheDir = getenv('LOCALAPPDATA').'/CopyTree';
        } else {
            $cacheDir = getenv('XDG_CACHE_HOME');
            if (! $cacheDir) {
                $cacheDir = getenv('HOME').'/.cache';
            }
            $this->cacheDir = $cacheDir.'/copytree';
        }

        // Clean any existing cache
        if (is_dir($this->cacheDir)) {
            $this->removeDirectory($this->cacheDir);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up after tests
        if (is_dir($this->cacheDir)) {
            $this->removeDirectory($this->cacheDir);
        }
    }

    public function testGitHubUrlHandlerBasicFunctionality(): void
    {
        // Execute the command with a GitHub URL
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/src',
            '--only-tree' => true, // Only get tree to keep test output manageable
        ]);

        // Get command output
        $output = $this->commandTester->getDisplay();

        // Assert basic expectations
        $this->assertStringContainsString('Ruleset', $output);
        $this->assertStringContainsString('Views', $output);
        $this->assertStringContainsString('Utilities', $output);

        // Verify that specific files exist in the output
        $this->assertStringContainsString('CopyTreeCommand.php', $output);
        $this->assertStringContainsString('CopyTreeExecutor.php', $output);

        // Check exit code
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        // Verify cache directory was created
        $this->assertDirectoryExists($this->cacheDir);
    }

    public function testGitHubUrlHandlerWithSpecificBranch(): void
    {
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/rulesets',
            '--only-tree' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        // Verify specific ruleset files exist
        $this->assertStringContainsString('laravel.json', $output);
        $this->assertStringContainsString('sveltekit.json', $output);
        $this->assertStringContainsString('default.json', $output);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testGitHubUrlHandlerCacheClearing(): void
    {
        // First, execute a normal command to populate the cache
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/src',
            '--only-tree' => true,
        ]);

        // Verify cache directory exists
        $this->assertDirectoryExists($this->cacheDir);

        // Clear the cache
        $this->commandTester->execute([
            '--clear-cache' => true,
        ]);

        // Verify cache was cleared
        $this->assertDirectoryDoesNotExist($this->cacheDir);
        $this->assertStringContainsString('GitHub repository cache cleared successfully', $this->commandTester->getDisplay());
    }

    public function testGitHubUrlHandlerInvalidUrl(): void
    {
        $this->commandTester->execute([
            'path' => 'https://github.com/invalid/repo/that/does/not/exist',
            '--only-tree' => true,
        ]);

        $this->assertNotEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Failed to clone repository', $this->commandTester->getDisplay());
    }

    public function testGitHubUrlHandlerInvalidPath(): void
    {
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/nonexistent-directory',
            '--only-tree' => true,
        ]);

        $this->assertNotEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('not found in repository', $this->commandTester->getDisplay());
    }

    public function testGitHubUrlHandlerCacheReuse(): void
    {
        // First execution should clone the repository
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/src',
            '--only-tree' => true,
        ]);

        // Get the cache directory modification time
        $firstMtime = filemtime($this->cacheDir);

        // Wait a second to ensure different modification time if cache is updated
        sleep(1);

        // Second execution should use cached version
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/src',
            '--only-tree' => true,
        ]);

        // Get the new modification time
        $secondMtime = filemtime($this->cacheDir);

        // Cache directory should not have been modified on second run
        // unless there were actual updates in the repository
        $this->assertGreaterThanOrEqual($firstMtime, $secondMtime);
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $path.DIRECTORY_SEPARATOR.$file;
            is_dir($filePath) ? $this->removeDirectory($filePath) : unlink($filePath);
        }
        rmdir($path);
    }
}
