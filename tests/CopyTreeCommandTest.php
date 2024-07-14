<?php

namespace GregPriday\CopyTree\Tests;

class CopyTreeCommandTest extends TestCase
{
    public function testExecuteWithThisProject()
    {
        $this->commandTester->execute([
            'path' => __DIR__.'/../',
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Copied', $output);
        $this->assertFilesCopied($output, 15); // Adjust this number as needed
    }

    public function testExecuteWithLaravelProject()
    {
        $laravelPath = __DIR__.'/../vendor/laravel/laravel';
        $this->commandTester->execute([
            'path' => $laravelPath,
            '--ruleset' => 'laravel',
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Copied', $output);
        $this->assertFilesCopied($output, 25); // Adjust this number as needed
    }

    public function testExecuteWithDisplayOption()
    {
        $this->commandTester->execute([
            'path' => __DIR__.'/../',
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
            'path' => __DIR__.'/../',
            '--output' => $outputFile,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Saved', $output);
        $this->assertFilesCopied($output, 15); // Adjust this number as needed
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
            'path' => __DIR__.'/../',
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
        $projectRoot = dirname(__DIR__); // Go up one level from the tests directory
        $rulesetDir = $projectRoot.'/.ctree';
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
            'path' => $projectRoot,
            '--ruleset' => 'test-ruleset',
            '--only-tree' => true,
            '--display' => true,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('.php', $output);
        $this->assertStringNotContainsString('.json', $output);

        // Clean up
        unlink($rulesetFile);
    }
}
