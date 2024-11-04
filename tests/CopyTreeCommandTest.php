<?php

namespace GregPriday\CopyTree\Tests;

class CopyTreeCommandTest extends TestCase
{
    private const TEST_DIR = __DIR__.'/dir';
    private const PROJECT_ROOT = __DIR__.'/../';

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure test directory structure exists
        if (!is_dir(self::TEST_DIR)) {
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
            array_map('unlink', glob(self::TEST_DIR."/subfolder1/*"));
            array_map('unlink', glob(self::TEST_DIR."/subfolder2/*"));
            @unlink(self::TEST_DIR."/test.php");
            rmdir(self::TEST_DIR."/subfolder1");
            rmdir(self::TEST_DIR."/subfolder2");
            rmdir(self::TEST_DIR);
        }

        parent::tearDown();
    }

    public function testExecuteWithThisProject()
    {
        $this->commandTester->execute([
            'path' => self::PROJECT_ROOT,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Copied', $output);

        // Make sure some files were copied, without specifying an exact number
        if (preg_match('/Copied (\d+) files to clipboard/', $output, $matches)) {
            $this->assertGreaterThan(0, (int) $matches[1], "Expected some files to be copied");
        } else {
            $this->fail("Could not find the number of files copied in the output: $output");
        }
    }

    public function testExecuteWithFilter()
    {
        $this->commandTester->execute([
            'path' => self::TEST_DIR,
            '--filter' => '*/*.txt',
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Copied', $output);
        $this->assertFilesCopied($output, 2); // Will match test1.txt and test2.txt
    }

    public function testExecuteWithMultipleFilters()
    {
        $this->commandTester->execute([
            'path' => self::TEST_DIR,
            '--filter' => [
                '*/*.txt',  // Should match test1.txt and test2.txt
                '*/*.md'    // Should match readme.md and notes.md
            ],
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Copied', $output);
        $this->assertFilesCopied($output, 4); // Will match two .txt files and two .md files
    }

    public function testExecuteWithInvalidFilter()
    {
        $this->commandTester->execute([
            'path' => self::TEST_DIR,
            '--filter' => '[invalid',  // Invalid glob pattern
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('copied 0 files', strtolower($output), 'Invalid glob pattern should result in no files copied');
    }

    public function testExecuteWithLaravelProject()
    {
        $laravelPath = self::PROJECT_ROOT.'vendor/laravel/laravel';
        $this->commandTester->execute([
            'path' => $laravelPath,
            '--ruleset' => 'laravel',
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Copied', $output);

        // Make sure a reasonable number of Laravel files were copied
        if (preg_match('/Copied (\d+) files to clipboard/', $output, $matches)) {
            $numFiles = (int) $matches[1];
            $this->assertGreaterThan(10, $numFiles, "Expected a substantial number of Laravel files to be copied");
            $this->assertLessThan(50, $numFiles, "Unexpectedly large number of Laravel files copied");
        } else {
            $this->fail("Could not find the number of files copied in the output: $output");
        }
    }

    public function testExecuteWithDisplayOption()
    {
        $this->commandTester->execute([
            'path' => self::PROJECT_ROOT,
            '--display' => true,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('.', $output); // Root directory
        $this->assertStringContainsString('src', $output); // src directory
        $this->assertStringContainsString('<file_contents', $output); // File contents
    }

    public function testExecuteWithOutputOption()
    {
        $outputFile = sys_get_temp_dir().'/copy-tree-output.txt';
        $this->commandTester->execute([
            'path' => self::PROJECT_ROOT,
            '--output' => $outputFile,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Saved', $output);

        // Make sure some files were saved, without specifying an exact number
        if (preg_match('/Saved (\d+) files to/', $output, $matches)) {
            $this->assertGreaterThan(0, (int) $matches[1], "Expected some files to be saved");
        } else {
            $this->fail("Could not find the number of files saved in the output: $output");
        }

        $this->assertFileExists($outputFile);
        $fileContents = file_get_contents($outputFile);
        $this->assertStringContainsString('.', $fileContents); // Root directory
        $this->assertStringContainsString('src', $fileContents); // src directory
        $this->assertStringContainsString('<file_contents', $fileContents); // File contents

        // Clean up
        unlink($outputFile);
    }

    public function testExecuteWithOnlyTreeOption()
    {
        $this->commandTester->execute([
            'path' => self::PROJECT_ROOT,
            '--only-tree' => true,
            '--display' => true,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('.', $output); // Root directory
        $this->assertStringContainsString('src', $output); // src directory
        $this->assertStringNotContainsString('<file_contents', $output); // No file contents
    }

    public function testExecuteWithCustomRuleset()
    {
        // Create a temporary custom ruleset file in the project root
        $rulesetDir = self::PROJECT_ROOT.'.ctree';
        if (!is_dir($rulesetDir)) {
            mkdir($rulesetDir);
        }
        $rulesetFile = $rulesetDir.'/test-ruleset.json';

        $rulesetContent = json_encode([
            'rules' => [
                [
                    ['path', 'startsWith', 'src'],
                    ['extension', '=', 'php'],
                ],
            ],
        ]);

        file_put_contents($rulesetFile, $rulesetContent);

        $this->commandTester->execute([
            'path' => self::PROJECT_ROOT,
            '--ruleset' => 'test-ruleset',
            '--only-tree' => true,
            '--display' => true,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('.php', $output);
        $this->assertStringNotContainsString('.json', $output);

        // Clean up
        unlink($rulesetFile);
        rmdir($rulesetDir);
    }
}
