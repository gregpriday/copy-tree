<?php

declare(strict_types=1);

namespace GregPriday\CopyTree\Tests\Unit;

use GregPriday\CopyTree\Tests\FilesystemHelperTrait;
use GregPriday\CopyTree\Tests\TestCase;

final class CopyTreeCommandTest extends TestCase
{
    use FilesystemHelperTrait;

    /**
     * @var string Path to the temporary test directory
     */
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary directory for testing
        $this->testDir = sys_get_temp_dir().'/ctree_test_dir_'.uniqid();
        if (! mkdir($this->testDir, 0777, true) && ! is_dir($this->testDir)) {
            throw new \RuntimeException("Unable to create test directory: {$this->testDir}");
        }

        // Create subdirectories
        mkdir($this->testDir.'/subfolder1', 0777, true);
        mkdir($this->testDir.'/subfolder2', 0777, true);

        // Create sample files
        file_put_contents($this->testDir.'/subfolder1/test1.txt', 'Test content 1');
        file_put_contents($this->testDir.'/subfolder1/readme.md', 'Test readme');
        file_put_contents($this->testDir.'/subfolder2/test2.txt', 'Test content 2');
        file_put_contents($this->testDir.'/subfolder2/notes.md', 'Test notes');
        file_put_contents($this->testDir.'/test.php', '<?php echo "test";');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->testDir);
        parent::tearDown();
    }

    public function test_basic_directory_copy(): void
    {
        $this->commandTester->execute([
            'path' => $this->testDir,
            '--display' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        // Verify that the output contains the XML project wrapper tags
        $this->assertStringContainsString('<ct:project>', $output, 'Output should begin with <ct:project> tag');
        $this->assertStringContainsString('<ct:tree>', $output, 'Output should include a <ct:tree> element');
        $this->assertStringContainsString('</ct:project>', $output, 'Output should end with </ct:project> tag');

        // Check that the expected file names appear in the tree
        $this->assertStringContainsString('test1.txt', $output);
        $this->assertStringContainsString('test2.txt', $output);
        $this->assertStringContainsString('readme.md', $output);
        $this->assertStringContainsString('notes.md', $output);
        $this->assertStringContainsString('test.php', $output);

        // Check that the directory structure appears
        $this->assertStringContainsString('subfolder1', $output);
        $this->assertStringContainsString('subfolder2', $output);

        // Since this is the full copy, also verify that file contents are included
        $this->assertStringContainsString('Test content 1', $output);
        $this->assertStringContainsString('Test content 2', $output);
        $this->assertStringContainsString('Test readme', $output);
        $this->assertStringContainsString('Test notes', $output);
    }

    public function test_display_tree_only(): void
    {
        $this->commandTester->execute([
            'path' => $this->testDir,
            '--only-tree' => true,
            '--display' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        // Check that the output contains the tree view but no file content section
        $this->assertStringContainsString('<ct:tree>', $output, 'Tree view should be present');
        $this->assertStringNotContainsString('<ct:project_files>', $output, 'File contents should not be displayed in tree-only mode');

        // Check that directory names and file names are present
        $this->assertStringContainsString('subfolder1', $output);
        $this->assertStringContainsString('subfolder2', $output);
        $this->assertStringContainsString('test1.txt', $output);
        $this->assertStringContainsString('test2.txt', $output);
        $this->assertStringContainsString('readme.md', $output);
        $this->assertStringContainsString('notes.md', $output);
        $this->assertStringContainsString('test.php', $output);

        // Verify that file contents are not included
        $this->assertStringNotContainsString('Test content 1', $output);
        $this->assertStringNotContainsString('Test content 2', $output);
        $this->assertStringNotContainsString('Test readme', $output);
        $this->assertStringNotContainsString('Test notes', $output);
    }
}
