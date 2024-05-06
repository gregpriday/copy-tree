<?php

namespace GregPriday\CopyTree\Tests;

use GregPriday\CopyTree\Command\CopyTreeCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Output\OutputInterface;
use PHPUnit\Framework\TestCase;

class CopyTreeCommandTest extends TestCase
{
    private $commandTester;

    protected function setUp(): void
    {
        $application = new Application();
        $application->add(new CopyTreeCommand());

        $command = $application->find('app:copy-tree');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteWithDefaultOptions(): void
    {
        $this->commandTester->execute([
            'path' => __DIR__ . '/dir'
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('file contents have been copied to the clipboard.', $output);
        $this->assertMatchesRegularExpression('/[0-9]+ file contents have been copied to the clipboard./', $output);
    }

    public function testNoClipboardOption(): void
    {
        $this->commandTester->execute([
            'path'          => __DIR__ . '/dir',
            '--no-clipboard' => true
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringNotContainsString('have been copied to the clipboard', $output);
    }

    public function testDisplayOption(): void
    {
        $this->commandTester->execute([
            'path'    => __DIR__ . '/dir',
            '--display' => true
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('>', $output); // Check for part of the output format
    }
}

