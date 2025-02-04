<?php

declare(strict_types=1);

namespace GregPriday\CopyTree\Tests\Integration;

use GregPriday\CopyTree\Tests\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

final class ExternalSourceIntegrationTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary directory for our test project
        $this->tempDir = sys_get_temp_dir().'/external_test_'.uniqid();
        if (! mkdir($this->tempDir, 0777, true) && ! is_dir($this->tempDir)) {
            throw new RuntimeException("Failed to create temp dir: {$this->tempDir}");
        }

        // Create a "src" folder with a couple of local text files
        mkdir($this->tempDir.'/src', 0777, true);
        file_put_contents($this->tempDir.'/src/local1.txt', 'Local content 1');
        file_put_contents($this->tempDir.'/src/local2.txt', 'Local content 2');

        // Create a .ctree folder to hold our custom ruleset JSON
        $ctreeDir = $this->tempDir.'/.ctree';
        if (! mkdir($ctreeDir, 0777, true) && ! is_dir($ctreeDir)) {
            throw new RuntimeException("Failed to create .ctree dir: {$ctreeDir}");
        }

        // Create a custom ruleset that:
        // - Includes local files under "src" with extension "txt"
        // - Specifies an external source using the GitHub URL for the docs folder
        $ruleset = [
            'rules' => [
                [
                    ['folder', 'startsWith', 'src'],
                    ['extension', '=', 'txt'],
                ],
            ],
            'globalExcludeRules' => [],
            'always' => [
                'include' => [],
                'exclude' => [],
            ],
            'external' => [
                [
                    'source' => 'https://github.com/gregpriday/copy-tree/tree/develop/docs',
                    'destination' => 'external_docs',
                ],
            ],
        ];
        file_put_contents($ctreeDir.'/custom.json', json_encode($ruleset));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    public function test_external_source_integration(): void
    {
        // Execute the command using our temporary project and our custom ruleset
        $this->commandTester->execute([
            'path' => $this->tempDir,
            '--ruleset' => 'custom',
            '--display' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        // Assert that the local files are present in the output
        $this->assertStringContainsString('local1.txt', $output);
        $this->assertStringContainsString('local2.txt', $output);

        // Assert that external files are included.
        // For example, one of the docs files such as "ai-features.md" should appear,
        // and the destination path "external_docs" should be visible.
        $this->assertStringContainsString('ai-features.md', $output);
        $this->assertStringContainsString('external_docs', $output);
    }

    public function test_external_source_integration_excludes_specific_file(): void
    {
        // Create a custom ruleset that:
        // - Includes local files under "src" with extension "txt"
        // - Specifies an external source using the GitHub URL for the docs folder
        // - Excludes a specific external file, e.g., "github-urls.md"
        $ruleset = [
            'rules' => [
                [
                    ['folder', 'startsWith', 'src'],
                    ['extension', '=', 'txt'],
                ],
            ],
            'globalExcludeRules' => [],
            'always' => [
                'include' => [],
                'exclude' => [],
            ],
            'external' => [
                [
                    'source' => 'https://github.com/gregpriday/copy-tree/tree/develop/docs',
                    'destination' => 'external_docs',
                    'rules' => [
                        [
                            ['extension', '=', 'md'],
                            ['basename', '!=', 'github-urls.md'],
                        ],
                    ],
                ],
            ],
        ];
        $ctreeDir = $this->tempDir.'/.ctree';
        file_put_contents($ctreeDir.'/custom.json', json_encode($ruleset));

        // Execute the command using our temporary project and our custom ruleset
        $this->commandTester->execute([
            'path' => $this->tempDir,
            '--ruleset' => 'custom',
            '--display' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        // Assert that the local files are present in the output
        $this->assertStringContainsString('local1.txt', $output);
        $this->assertStringContainsString('local2.txt', $output);

        // Assert that external files are included.
        $this->assertStringContainsString('ai-features.md', $output);
        $this->assertStringContainsString('external_docs', $output);

        // Assert that the excluded external file "github-urls.md" is NOT present in the output
        $this->assertStringNotContainsString('github-urls.md', $output);
    }

    /**
     * Recursively remove a directory and its contents.
     *
     * @param  string  $dir  The directory to remove.
     */
    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}
