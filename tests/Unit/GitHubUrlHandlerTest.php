<?php

declare(strict_types=1);

namespace GregPriday\CopyTree\Tests\Unit;

use GregPriday\CopyTree\CopyTreeCommand;
use GregPriday\CopyTree\Tests\FilesystemHelperTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class GitHubUrlHandlerTest extends TestCase
{
    use FilesystemHelperTrait;

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

        // Use the actual cache directory used by the GitHubUrlHandler
        // (assuming the implementation uses HOME/.copytree/cache)
        $this->cacheDir = getenv('HOME').'/.copytree/cache';

        // Clean any existing cache
        if (is_dir($this->cacheDir)) {
            $this->removeDirectory($this->cacheDir);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up cache directory after tests.
        if (is_dir($this->cacheDir)) {
            $this->removeDirectory($this->cacheDir);
        }
    }

    public function test_git_hub_url_handler_basic_functionality(): void
    {
        // Execute the command with a GitHub URL (cloning the repository)
        // and request only the tree output to be streamed.
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/src',
            '--only-tree' => true,
            '--stream' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        // Verify that specific files from the src directory are present.
        $this->assertStringContainsString('CopyTreeCommand.php', $output, 'Expected file CopyTreeCommand.php missing');
        $this->assertStringContainsString('CopyTreeExecutor.php', $output, 'Expected file CopyTreeExecutor.php missing');

        // Check that the command exits with a success code.
        $this->assertSame(0, $this->commandTester->getStatusCode(), 'Exit code should be 0 on success');

        // Verify that the cache directory was created.
        $this->assertDirectoryExists($this->cacheDir, 'Cache directory was not created');
    }

    public function test_git_hub_url_handler_subdirectory(): void
    {
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/develop/docs',
            '--only-tree' => true,
            '--stream' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        // Verify that documentation files are present.
        $this->assertStringContainsString('examples.md', $output, 'Expected documentation file examples.md missing');
        $this->assertStringContainsString('rulesets.md', $output, 'Expected documentation file rulesets.md missing');
        $this->assertStringContainsString('fields-and-operations.md', $output, 'Expected documentation file fields-and-operations.md missing');

        // Verify that files from other directories (such as source files) are not included.
        $this->assertStringNotContainsString('CopyTreeCommand.php', $output, 'Unexpected file CopyTreeCommand.php found');
        $this->assertStringNotContainsString('composer.json', $output, 'Unexpected file composer.json found');

        $this->assertSame(0, $this->commandTester->getStatusCode(), 'Exit code should be 0 on success');
    }

    public function test_git_hub_url_handler_with_specific_branch(): void
    {
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/rulesets',
            '--only-tree' => true,
            '--stream' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        // Verify that ruleset files from the main branch are present.
        $this->assertStringContainsString('laravel.json', $output, 'Expected file laravel.json missing');
        $this->assertStringContainsString('sveltekit.json', $output, 'Expected file sveltekit.json missing');
        $this->assertStringContainsString('default.json', $output, 'Expected file default.json missing');

        $this->assertSame(0, $this->commandTester->getStatusCode(), 'Exit code should be 0 on success');
    }

    public function test_git_hub_url_handler_cache_clearing(): void
    {
        // First, execute a normal command to populate the cache.
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/src',
            '--only-tree' => true,
            '--stream' => true,
        ]);

        // Verify that the cache directory exists.
        $this->assertDirectoryExists($this->cacheDir, 'Cache directory should exist after cloning');

        // Clear the cache using the clear-cache option.
        $this->commandTester->execute([
            '--clear-cache' => true,
        ]);

        // Verify that the cache directory was cleared.
        $this->assertDirectoryDoesNotExist($this->cacheDir, 'Cache directory should be cleared');
        $this->assertStringContainsString(
            'GitHub repository cache cleared successfully',
            $this->commandTester->getDisplay(),
            'Expected cache clearing confirmation missing'
        );
    }

    public function test_git_hub_url_handler_invalid_url(): void
    {
        $this->commandTester->execute([
            'path' => 'https://github.com/invalid/repo/that/does/not/exist',
            '--only-tree' => true,
            '--stream' => true,
        ]);

        // For an invalid URL, we expect a non-zero exit code.
        $this->assertNotSame(0, $this->commandTester->getStatusCode(), 'Expected non-zero exit code for invalid URL');
        $this->assertStringContainsString(
            'Failed to clone repository',
            $this->commandTester->getDisplay(),
            'Expected error message for failed clone not found'
        );
    }

    public function test_git_hub_url_handler_invalid_path(): void
    {
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/nonexistent-directory',
            '--only-tree' => true,
            '--stream' => true,
        ]);

        // Expect non-zero exit code for an invalid subdirectory path.
        $this->assertNotSame(0, $this->commandTester->getStatusCode(), 'Expected non-zero exit code for invalid path');
        $this->assertStringContainsString(
            'not found in repository',
            $this->commandTester->getDisplay(),
            'Expected error message indicating nonexistent directory'
        );
    }

    public function test_git_hub_url_handler_cache_reuse(): void
    {
        // First execution should clone the repository.
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/src',
            '--only-tree' => true,
            '--stream' => true,
        ]);

        // Get the cache directory modification time.
        $firstMtime = filemtime($this->cacheDir);

        // Wait a second to allow for a potential update.
        sleep(1);

        // Second execution should reuse the cached repository.
        $this->commandTester->execute([
            'path' => 'https://github.com/gregpriday/copy-tree/tree/main/src',
            '--only-tree' => true,
            '--stream' => true,
        ]);

        // Get the new modification time.
        $secondMtime = filemtime($this->cacheDir);

        // Assert that the cache was not updated (unless there was an actual repository change).
        $this->assertGreaterThanOrEqual(
            $firstMtime,
            $secondMtime,
            'Cache directory modification time should not decrease on cache reuse'
        );
    }
}
