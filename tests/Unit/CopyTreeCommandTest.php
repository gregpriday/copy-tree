<?php

namespace GregPriday\CopyTree\Tests\Unit;

use GregPriday\CopyTree\Tests\TestCase;

class CopyTreeCommandTest extends TestCase
{
    private const TEST_DIR = __DIR__.'/dir';

    private const PROJECT_ROOT = __DIR__.'/../';

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure test directory structure exists
        if (! is_dir(self::TEST_DIR)) {
            mkdir(self::TEST_DIR);
            mkdir(self::TEST_DIR.'/subfolder1');
            mkdir(self::TEST_DIR.'/subfolder2');

            file_put_contents(self::TEST_DIR.'/subfolder1/test1.txt', 'Test content 1');
            file_put_contents(self::TEST_DIR.'/subfolder1/readme.md', 'Test readme');
            file_put_contents(self::TEST_DIR.'/subfolder2/test2.txt', 'Test content 2');
            file_put_contents(self::TEST_DIR.'/subfolder2/notes.md', 'Test notes');
            file_put_contents(self::TEST_DIR.'/test.php', '<?php echo "test";');
        }
    }

    protected function tearDown(): void
    {
        // Clean up test directory structure
        if (is_dir(self::TEST_DIR)) {
            array_map('unlink', glob(self::TEST_DIR.'/subfolder1/*'));
            array_map('unlink', glob(self::TEST_DIR.'/subfolder2/*'));
            @unlink(self::TEST_DIR.'/test.php');
            rmdir(self::TEST_DIR.'/subfolder1');
            rmdir(self::TEST_DIR.'/subfolder2');
            rmdir(self::TEST_DIR);
        }

        parent::tearDown();
    }

    public function test_basic_directory_copy(): void
    {
        $this->commandTester->execute([
            'path' => self::TEST_DIR,
            '--display' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        // Check all expected files are present
        $this->assertStringContainsString('test1.txt', $output);
        $this->assertStringContainsString('test2.txt', $output);
        $this->assertStringContainsString('readme.md', $output);
        $this->assertStringContainsString('notes.md', $output);
        $this->assertStringContainsString('test.php', $output);

        // Check directory structure
        $this->assertStringContainsString('subfolder1', $output);
        $this->assertStringContainsString('subfolder2', $output);
    }

    public function test_display_tree_only(): void
    {
        $this->commandTester->execute([
            'path' => self::TEST_DIR,
            '--only-tree' => true,
            '--display' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        // Check directory structure is present (without caring about prefix)
        $this->assertStringContainsString('subfolder1', $output);
        $this->assertStringContainsString('subfolder2', $output);

        // Check file names are present
        $this->assertStringContainsString('test1.txt', $output);
        $this->assertStringContainsString('test2.txt', $output);
        $this->assertStringContainsString('readme.md', $output);
        $this->assertStringContainsString('notes.md', $output);
        $this->assertStringContainsString('test.php', $output);

        // Check content is not included
        $this->assertStringNotContainsString('Test content 1', $output);
        $this->assertStringNotContainsString('Test content 2', $output);
        $this->assertStringNotContainsString('Test readme', $output);
        $this->assertStringNotContainsString('Test notes', $output);
    }
}
