<?php

namespace GregPriday\CopyTree\Tests\Integration;

use GregPriday\CopyTree\Tests\TestCase;
use GregPriday\CopyTree\Utilities\OpenAI\OpenAIFilenameGenerator;

class OpenAIFilenameGeneratorTest extends TestCase
{
    private OpenAIFilenameGenerator $generator;

    private string $originalEnvPath;

    private string $testEnvPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test environment path
        $homeDir = PHP_OS_FAMILY === 'Windows' ?
            getenv('USERPROFILE') :
            getenv('HOME');

        $this->originalEnvPath = $homeDir.DIRECTORY_SEPARATOR.'.copytree'.DIRECTORY_SEPARATOR.'.env';

        // Ensure we have valid credentials before running tests
        if (! file_exists($this->originalEnvPath)) {
            $this->markTestSkipped('OpenAI credentials not found. Please set up ~/.copytree/.env first.');
        }

        $this->generator = new OpenAIFilenameGenerator;
    }

    public function test_generator_creates_valid_filename_for_php_project(): void
    {
        $files = [
            ['path' => 'src/Controller/UserController.php'],
            ['path' => 'src/Model/User.php'],
            ['path' => 'tests/UserTest.php'],
            ['path' => 'composer.json'],
        ];

        $filename = $this->generator->generateFilename($files);

        $this->assertIsString($filename);
        $this->assertStringEndsWith('.txt', $filename);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_-]+\.txt$/', $filename);
        $this->assertLessThanOrEqual(104, strlen($filename)); // 100 chars + '.txt'
    }

    public function test_generator_creates_valid_filename_for_web_project(): void
    {
        $files = [
            ['path' => 'index.html'],
            ['path' => 'css/styles.css'],
            ['path' => 'js/main.js'],
            ['path' => 'images/logo.svg'],
        ];

        $filename = $this->generator->generateFilename($files);

        $this->assertIsString($filename);
        $this->assertStringEndsWith('.txt', $filename);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_-]+\.txt$/', $filename);
    }

    public function test_generator_handles_empty_file_list(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No files provided for filename generation');

        $this->generator->generateFilename([]);
    }

    public function test_generator_handles_deep_directory_structure(): void
    {
        $files = [
            ['path' => 'frontend/src/components/Header/Navigation/MenuItem.tsx'],
            ['path' => 'frontend/src/components/Header/Navigation/SubMenu.tsx'],
            ['path' => 'frontend/src/components/Header/Navigation/index.tsx'],
            ['path' => 'frontend/src/styles/navigation.scss'],
        ];

        $filename = $this->generator->generateFilename($files);

        $this->assertIsString($filename);
        $this->assertStringEndsWith('.txt', $filename);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_-]+\.txt$/', $filename);
    }

    public function test_generator_handles_mixed_file_types(): void
    {
        $files = [
            ['path' => 'docker-compose.yml'],
            ['path' => 'Dockerfile'],
            ['path' => 'README.md'],
            ['path' => 'src/index.js'],
            ['path' => '.env.example'],
        ];

        $filename = $this->generator->generateFilename($files);

        $this->assertIsString($filename);
        $this->assertStringEndsWith('.txt', $filename);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_-]+\.txt$/', $filename);
    }

    public function test_generator_sanitizes_special_characters(): void
    {
        // Create the generator with a mock that would return a filename with special characters
        $files = [
            ['path' => 'test.php'],
        ];

        // Generate multiple filenames to test sanitization
        $unsafeChars = ['/', '\\', ':', '*', '?', '"', '<', '>', '|', '..', '  ', '__'];

        foreach ($unsafeChars as $char) {
            // Add the unsafe char to the test file to influence the generation
            $testFiles = array_merge($files, [['path' => "test{$char}file.php"]]);

            $filename = $this->generator->generateFilename($testFiles);

            $this->assertIsString($filename);
            $this->assertStringEndsWith('.txt', $filename);
            $this->assertDoesNotMatchRegularExpression('/[\/\\:*?"<>|]/', $filename);
            $this->assertDoesNotMatchRegularExpression('/\.\./', $filename);
            $this->assertDoesNotMatchRegularExpression('/\s{2,}/', $filename);
            $this->assertDoesNotMatchRegularExpression('/__{2,}/', $filename);
        }
    }

    /**
     * @group slow
     */
    public function test_generator_creates_different_names_for_different_projects(): void
    {
        $phpProject = [
            ['path' => 'src/Controller/UserController.php'],
            ['path' => 'src/Model/User.php'],
            ['path' => 'tests/UserTest.php'],
        ];

        $webProject = [
            ['path' => 'index.html'],
            ['path' => 'css/styles.css'],
            ['path' => 'js/main.js'],
        ];

        $phpFilename = $this->generator->generateFilename($phpProject);
        $webFilename = $this->generator->generateFilename($webProject);

        $this->assertNotEquals($phpFilename, $webFilename);
    }

    /**
     * @group slow
     */
    public function test_generator_creates_consistent_filenames_for_similar_projects(): void
    {
        $project1 = [
            ['path' => 'src/UserController.php'],
            ['path' => 'src/User.php'],
            ['path' => 'tests/UserTest.php'],
        ];

        $project2 = [
            ['path' => 'src/UserController.php'],
            ['path' => 'src/User.php'],
            ['path' => 'tests/UserTest.php'],
        ];

        $filename1 = $this->generator->generateFilename($project1);
        $filename2 = $this->generator->generateFilename($project2);

        // The filenames might not be exactly the same due to the nature of AI generation,
        // but they should at least contain similar keywords
        $this->assertStringContainsString('user', strtolower($filename1));
        $this->assertStringContainsString('user', strtolower($filename2));
    }
}
