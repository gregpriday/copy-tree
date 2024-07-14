<?php

namespace GregPriday\CopyTree\Tests;

use GregPriday\CopyTree\CopyTree;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CopyTreeCommandTest extends TestCase
{
    private $commandTester;

    protected function setUp(): void
    {
        $application = new Application();
        $application->add(new CopyTree());

        $command = $application->find('app:copy-tree');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteWithThisProject()
    {
        $this->commandTester->execute([
            'path' => __DIR__.'/../',
        ]);

        $output = $this->commandTester->getDisplay();
        dd($output);
    }

    public function testExecuteWithDefaultOptions(): void
    {
        $this->commandTester->execute([
            'path' => __DIR__.'/dir',
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('file contents have been copied to the clipboard.', $output);
        $this->assertMatchesRegularExpression('/[0-9]+ file contents have been copied to the clipboard./', $output);
    }

    public function testNoClipboardOption(): void
    {
        $this->commandTester->execute([
            'path' => __DIR__.'/dir',
            '--no-clipboard' => true,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringNotContainsString('have been copied to the clipboard', $output);
    }

    public function testDisplayOption(): void
    {
        $this->commandTester->execute([
            'path' => __DIR__.'/dir',
            '--display' => true,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('>', $output); // Check for part of the output format
    }

    public function testCopySveltekitFolder()
    {
        $this->commandTester->execute([
            'path' => '/Users/gpriday/Sites/vectorlens-sveltekit',
            '--ruleset' => 'sveltekit',
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('file contents have been copied to the clipboard.', $output);
    }

    public function testCopyLaravelFolder()
    {
        $this->commandTester->execute([
            'path' => '/Users/gpriday/Sites/vectorlens',
            '--ruleset' => 'laravel',
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('file contents have been copied to the clipboard.', $output);
    }

    public function testCopyBaseFolder()
    {
        // Set the current working directory to ../
        chdir(__DIR__.'/..');

        // Now run the command
        $this->commandTester->execute([
            'path' => '.',
        ]);
    }
}
