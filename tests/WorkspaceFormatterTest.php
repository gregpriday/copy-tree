<?php

namespace GregPriday\CopyTree\Tests\Workspace;

use GregPriday\CopyTree\Tests\TestCase;
use GregPriday\CopyTree\Workspace\WorkspaceFormatter;

class WorkspaceFormatterTest extends TestCase
{
    private WorkspaceFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new WorkspaceFormatter();
    }

    public function testFormatOutputForClaude(): void
    {
        $input = "test content";
        $expected = "<document>\ntest content\n</document>";

        $output = $this->formatter->formatOutput($input, 'claude');
        $this->assertEquals($expected, $output);
    }

    public function testFormatOutputForGPT(): void
    {
        $input = "test content";
        $expected = "```\ntest content\n```";

        $output = $this->formatter->formatOutput($input, 'gpt');
        $this->assertEquals($expected, $output);
    }

    public function testFormatOutputForDefault(): void
    {
        $input = "test content";
        $output = $this->formatter->formatOutput($input, 'standard');
        $this->assertEquals($input, $output);
    }

    public function testFormatOutputWithMultilineContent(): void
    {
        $input = "line1\nline2\nline3";

        $claudeOutput = $this->formatter->formatOutput($input, 'claude');
        $gptOutput = $this->formatter->formatOutput($input, 'gpt');

        $this->assertStringContainsString($input, $claudeOutput);
        $this->assertStringContainsString($input, $gptOutput);
    }
}
