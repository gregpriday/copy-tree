<?php

namespace GregPriday\CopyTree\Tests\Workspace;

use GregPriday\CopyTree\Tests\TestCase;
use GregPriday\CopyTree\Workspace\WorkspaceManager;
use InvalidArgumentException;

class WorkspaceManagerTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = sys_get_temp_dir().'/workspace_test_'.uniqid();
        mkdir($this->testDir.'/.ctree', 0777, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeDirectory($this->testDir);
    }

    public function testLoadValidWorkspaceConfiguration(): void
    {
        $config = [
            'workspaces' => [
                'frontend' => [
                    'extends' => 'sveltekit',
                    'rules' => [
                        [
                            ['folder', 'startsWith', 'src/components'],
                        ],
                    ],
                ],
            ],
        ];

        file_put_contents(
            $this->testDir.'/.ctree/workspaces.json',
            json_encode($config)
        );

        $manager = new WorkspaceManager($this->testDir);

        $this->assertTrue($manager->hasWorkspace('frontend'));
        $this->assertNotNull($manager->getWorkspace('frontend'));
        $this->assertEquals(['frontend'], $manager->getAvailableWorkspaces());
    }

    public function testLoadInvalidWorkspaceConfiguration(): void
    {
        file_put_contents(
            $this->testDir.'/.ctree/workspaces.json',
            'invalid json'
        );

        $this->expectException(InvalidArgumentException::class);
        new WorkspaceManager($this->testDir);
    }

    public function testNonExistentWorkspace(): void
    {
        $manager = new WorkspaceManager($this->testDir);

        $this->assertFalse($manager->hasWorkspace('nonexistent'));
        $this->assertNull($manager->getWorkspace('nonexistent'));
        $this->assertEquals([], $manager->getAvailableWorkspaces());
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
