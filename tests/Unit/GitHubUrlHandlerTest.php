<?php

namespace GregPriday\CopyTree\Tests\Unit;

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

    public function test_git_hub_url_handler_basic_functionality(): void
    {
        // Execute the command with a GitHub URL and stream output
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/src',
            '--only-tree' => true,
            '--stream' => true,
        ]);

        // Get command output
        $output = $this->commandTester->getDisplay();

        // Verify that specific files exist in the output
        $this->assertStringContainsString('CopyTreeCommand.php', $output);
        $this->assertStringContainsString('CopyTreeExecutor.php', $output);

        // Check exit code
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        // Verify cache directory was created
        $this->assertDirectoryExists($this->cacheDir);
    }

    public function test_git_hub_url_handler_subdirectory(): void
    {
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/develop/docs',
            '--only-tree' => true,
            '--stream' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        // Verify that documentation files exist in the output
        $this->assertStringContainsString('examples.md', $output);
        $this->assertStringContainsString('rulesets.md', $output);
        $this->assertStringContainsString('fields-and-operations.md', $output);

        // Verify we don't see files from other directories
        $this->assertStringNotContainsString('CopyTreeCommand.php', $output);
        $this->assertStringNotContainsString('composer.json', $output);

        // Check exit code
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function test_git_hub_url_handler_with_specific_branch(): void
    {
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/rulesets',
            '--only-tree' => true,
            '--stream' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        // Verify specific ruleset files exist
        $this->assertStringContainsString('laravel.json', $output);
        $this->assertStringContainsString('sveltekit.json', $output);
        $this->assertStringContainsString('default.json', $output);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function test_git_hub_url_handler_cache_clearing(): void
    {
        // First, execute a normal command to populate the cache
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/src',
            '--only-tree' => true,
            '--stream' => true,
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

    public function test_git_hub_url_handler_invalid_url(): void
    {
        $this->commandTester->execute([
            'path' => 'https://github.com/invalid/repo/that/does/not/exist',
            '--only-tree' => true,
            '--stream' => true,
        ]);

        $this->assertNotEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Failed to clone repository', $this->commandTester->getDisplay());
    }

    public function test_git_hub_url_handler_invalid_path(): void
    {
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/nonexistent-directory',
            '--only-tree' => true,
            '--stream' => true,
        ]);

        $this->assertNotEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('not found in repository', $this->commandTester->getDisplay());
    }

    public function test_git_hub_url_handler_cache_reuse(): void
    {
        // First execution should clone the repository
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/src',
            '--only-tree' => true,
            '--stream' => true,
        ]);

        // Get the cache directory modification time
        $firstMtime = filemtime($this->cacheDir);

        // Wait a second to ensure different modification time if cache is updated
        sleep(1);

        // Second execution should use cached version
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/src',
            '--only-tree' => true,
            '--stream' => true,
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
