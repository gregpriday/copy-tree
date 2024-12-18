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

    public function test_execute_with_workspace(): void
    {
        // Create a temporary workspace configuration
        $rulesetDir = self::PROJECT_ROOT.'.ctree';
        if (! is_dir($rulesetDir)) {
            mkdir($rulesetDir);
        }

        $workspaceFile = $rulesetDir.'/workspaces.json';
        $workspaceContent = json_encode([
            'workspaces' => [
                'test-workspace' => [
                    'rules' => [
                        [
                            ['folder', 'startsWith', 'subfolder1'],
                        ],
                    ],
                ],
            ],
        ]);

        file_put_contents($workspaceFile, $workspaceContent);

        $this->commandTester->execute([
            'path' => self::TEST_DIR,
            '--workspace' => 'test-workspace',
            '--display' => true,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('subfolder1', $output);
        $this->assertStringNotContainsString('subfolder2', $output);

        // Clean up
        unlink($workspaceFile);
        rmdir($rulesetDir);
    }

    public function test_execute_with_workspace_and_format(): void
    {
        // Create a temporary workspace configuration
        $rulesetDir = self::PROJECT_ROOT.'.ctree';
        if (! is_dir($rulesetDir)) {
            mkdir($rulesetDir);
        }

        $workspaceFile = $rulesetDir.'/workspaces.json';
        $workspaceContent = json_encode([
            'workspaces' => [
                'test-workspace' => [
                    'rules' => [
                        [
                            ['extension', '=', 'txt'],
                        ],
                    ],
                ],
            ],
        ]);

        file_put_contents($workspaceFile, $workspaceContent);

        $this->commandTester->execute([
            'path' => self::TEST_DIR,
            '--workspace' => 'test-workspace',
            '--format' => 'gpt',
            '--display' => true,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('```', $output);
        $this->assertStringContainsString('test1.txt', $output);
        $this->assertStringContainsString('test2.txt', $output);
        $this->assertStringNotContainsString('readme.md', $output);

        // Clean up
        unlink($workspaceFile);
        rmdir($rulesetDir);
    }

    public function test_execute_with_nonexistent_workspace(): void
    {
        $this->commandTester->execute([
            'path' => self::TEST_DIR,
            '--workspace' => 'nonexistent-workspace',
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Workspace "nonexistent-workspace" not found', $output);
    }
}
