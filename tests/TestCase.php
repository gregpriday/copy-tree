<?php

namespace GregPriday\CopyTree\Tests;

use GregPriday\CopyTree\CopyTreeCommand;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class TestCase extends BaseTestCase
{
    protected CommandTester $commandTester;

    protected function setUp(): void
    {
        // Define PROJECT_ROOT constant if not already defined
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', realpath(__DIR__ . '/..'));
        }

        $application = new Application;
        $application->add(new CopyTreeCommand);

        $command = $application->find('app:copy-tree');
        $this->commandTester = new CommandTester($command);

        parent::setUp();
    }

    protected function assertFilesCopied(string $output, int $expectedCount): void
    {
        if (preg_match('/Copied (\d+) files to clipboard/', $output, $matches)) {
            $actualCount = (int) $matches[1];
            $this->assertEquals($expectedCount, $actualCount, "Expected $expectedCount files to be copied, but $actualCount were copied.");
        } elseif (preg_match('/Saved (\d+) files to/', $output, $matches)) {
            $actualCount = (int) $matches[1];
            $this->assertEquals($expectedCount, $actualCount, "Expected $expectedCount files to be saved, but $actualCount were saved.");
        } else {
            $this->fail("Could not find the number of files copied or saved in the output: $output");
        }
    }
}
