<?php

declare(strict_types=1);

namespace GregPriday\CopyTree\Tests\Unit;

use GregPriday\CopyTree\Filters\Ruleset\LocalRulesetFilter;
use GregPriday\CopyTree\Filters\Ruleset\RulesetManager;
use GregPriday\CopyTree\Tests\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;

final class RulesetManagerTest extends TestCase
{
    private string $testDir;

    private RulesetManager $manager;

    private ?SymfonyStyle $io;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = sys_get_temp_dir().'/ruleset_test_'.uniqid();
        // Create the .ctree directory inside the temporary test directory.
        if (! mkdir($this->testDir.'/.ctree', 0777, true) && ! is_dir($this->testDir.'/.ctree')) {
            throw new \RuntimeException("Unable to create directory: {$this->testDir}/.ctree");
        }
        $this->io = $this->createMock(SymfonyStyle::class);
        $this->manager = new RulesetManager($this->testDir, $this->io);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeDirectory($this->testDir);
    }

    public function test_get_ruleset_with_workspace(): void
    {
        $workspaceConfig = [
            'workspaces' => [
                'test' => [
                    'rules' => [
                        [['path', 'startsWith', 'src']],
                    ],
                ],
            ],
        ];

        file_put_contents(
            $this->testDir.'/.ctree/workspaces.json',
            json_encode($workspaceConfig)
        );

        // Note: the second argument for workspace is no longer accepted;
        // so we simply call getRuleset('auto')
        $ruleset = $this->manager->getRuleset('auto');
        $this->assertInstanceOf(LocalRulesetFilter::class, $ruleset);
    }

    public function test_create_empty_ruleset(): void
    {
        $ruleset = $this->manager->createEmptyRuleset();
        $this->assertInstanceOf(LocalRulesetFilter::class, $ruleset);
    }

    public function test_create_ruleset_from_glob(): void
    {
        $ruleset = $this->manager->createRulesetFromGlob('*.php');
        $this->assertInstanceOf(LocalRulesetFilter::class, $ruleset);
    }

    public function test_create_ruleset_from_globs(): void
    {
        $ruleset = $this->manager->createRulesetFromGlobs(['*.php', '*.js']);
        $this->assertInstanceOf(LocalRulesetFilter::class, $ruleset);
    }

    public function test_get_ruleset_with_custom_ruleset(): void
    {
        $customRuleset = [
            'rules' => [
                [['path', 'startsWith', 'src']],
            ],
        ];

        file_put_contents(
            $this->testDir.'/.ctree/custom.json',
            json_encode($customRuleset)
        );

        $ruleset = $this->manager->getRuleset('custom');
        $this->assertInstanceOf(LocalRulesetFilter::class, $ruleset);
    }

    public function test_get_available_rulesets(): void
    {
        $rulesets = $this->manager->getAvailableRulesets();
        $this->assertIsArray($rulesets);
        $this->assertContains('auto', $rulesets);
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
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
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
