<?php

namespace GregPriday\CopyTree\Tests\Unit;

use GregPriday\CopyTree\Filters\Ruleset\RulesetFilter;
use GregPriday\CopyTree\Ruleset\RulesetManager;
use GregPriday\CopyTree\Tests\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;

class RulesetManagerTest extends TestCase
{
    private string $testDir;

    private RulesetManager $manager;

    private ?SymfonyStyle $io;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = sys_get_temp_dir().'/ruleset_test_'.uniqid();
        mkdir($this->testDir.'/.ctree', 0777, true);
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

        $ruleset = $this->manager->getRuleset('auto', 'test');
        $this->assertInstanceOf(RulesetFilter::class, $ruleset);
    }

    public function test_create_empty_ruleset(): void
    {
        $ruleset = $this->manager->createEmptyRuleset();
        $this->assertInstanceOf(RulesetFilter::class, $ruleset);
    }

    public function test_create_ruleset_from_glob(): void
    {
        $ruleset = $this->manager->createRulesetFromGlob('*.php');
        $this->assertInstanceOf(RulesetFilter::class, $ruleset);
    }

    public function test_create_ruleset_from_globs(): void
    {
        $ruleset = $this->manager->createRulesetFromGlobs(['*.php', '*.js']);
        $this->assertInstanceOf(RulesetFilter::class, $ruleset);
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
        $this->assertInstanceOf(RulesetFilter::class, $ruleset);
    }

    public function test_get_available_rulesets(): void
    {
        $rulesets = $this->manager->getAvailableRulesets();
        $this->assertIsArray($rulesets);
        $this->assertContains('auto', $rulesets);
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
