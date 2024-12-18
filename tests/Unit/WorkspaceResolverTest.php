<?php

namespace GregPriday\CopyTree\Tests\Unit;

use GregPriday\CopyTree\Ruleset\RulesetFilter;
use GregPriday\CopyTree\Ruleset\RulesetManager;
use GregPriday\CopyTree\Tests\TestCase;
use GregPriday\CopyTree\Workspace\WorkspaceResolver;

class WorkspaceResolverTest extends TestCase
{
    private WorkspaceResolver $resolver;

    private RulesetManager $rulesetManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rulesetManager = $this->createMock(RulesetManager::class);
        $this->resolver = new WorkspaceResolver($this->rulesetManager);
    }

    public function test_resolve_workspace_with_extension(): void
    {
        $baseRuleset = $this->createMock(RulesetFilter::class);
        $baseRuleset->expects($this->once())
            ->method('addIncludeRuleSet');

        $this->rulesetManager->expects($this->once())
            ->method('getRuleset')
            ->with('sveltekit')
            ->willReturn($baseRuleset);

        $workspace = [
            'extends' => 'sveltekit',
            'rules' => [
                [
                    ['folder', 'startsWith', 'src/components'],
                ],
            ],
        ];

        $result = $this->resolver->resolveWorkspace($workspace);
        $this->assertInstanceOf(RulesetFilter::class, $result);
    }

    public function test_resolve_workspace_without_extension(): void
    {
        $emptyRuleset = $this->createMock(RulesetFilter::class);

        $this->rulesetManager->expects($this->once())
            ->method('createEmptyRuleset')
            ->willReturn($emptyRuleset);

        $workspace = [
            'rules' => [
                [
                    ['folder', 'startsWith', 'src/components'],
                ],
            ],
        ];

        $result = $this->resolver->resolveWorkspace($workspace);
        $this->assertInstanceOf(RulesetFilter::class, $result);
    }
}
