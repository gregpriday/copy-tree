<?php

namespace GregPriday\CopyTree\Tests\Integration;

use GregPriday\CopyTree\Tests\TestCase;
use GregPriday\CopyTree\Utilities\OpenAIFileFilter;
use SplFileInfo;

class OpenAIFileFilterTest extends TestCase
{
    private string $testDir;

    private OpenAIFileFilter $filter;

    private array $testFiles = [
        'app/Controllers/AuthController.php' => '<?php
            namespace App\Controllers;
            class AuthController {
                public function login() {
                    // Handle user login
                    $credentials = request()->only("email", "password");
                    Auth::attempt($credentials);
                }
                public function register() {
                    // Handle user registration
                }
            }',
        'app/Controllers/HomeController.php' => '<?php
            namespace App\Controllers;
            class HomeController {
                public function index() {
                    return view("home");
                }
                public function about() {
                    return view("about");
                }
            }',
        'app/Models/User.php' => '<?php
            namespace App\Models;
            class User extends Model {
                protected $fillable = ["name", "email", "password"];
                protected $hidden = ["password"];
            }',
        'README.md' => '# Project Documentation
            This is a sample project showcasing various features.
            ## Installation
            1. Clone the repository
            2. Run composer install
            3. Configure your .env file',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test environment path
        $homeDir = PHP_OS_FAMILY === 'Windows' ?
            getenv('USERPROFILE') :
            getenv('HOME');

        $envPath = $homeDir.'/.copytree/.env';

        // Ensure we have valid credentials before running tests
        if (! file_exists($envPath)) {
            $this->markTestSkipped('OpenAI credentials not found. Please set up ~/.copytree/.env first.');
        }

        // Create temporary directory and test files
        $this->testDir = sys_get_temp_dir().'/openai_filter_test_'.uniqid();
        mkdir($this->testDir, 0777, true);

        foreach ($this->testFiles as $path => $content) {
            $fullPath = $this->testDir.'/'.$path;
            $dirPath = dirname($fullPath);

            if (! is_dir($dirPath)) {
                mkdir($dirPath, 0777, true);
            }

            file_put_contents($fullPath, $content);
        }

        $this->filter = new OpenAIFileFilter;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeDirectory($this->testDir);
    }

    public function test_filter_authentication_related_files(): void
    {
        // Prepare files array in the format expected by the filter
        $files = [];
        foreach ($this->testFiles as $path => $content) {
            $fullPath = $this->testDir.'/'.$path;
            $files[] = [
                'path' => $path,
                'file' => new SplFileInfo($fullPath),
            ];
        }

        $result = $this->filter->filterFiles($files, 'Find all files related to user authentication');

        // Check that we get an explanation
        $this->assertNotEmpty($result['explanation']);
        $this->assertIsString($result['explanation']);

        // Verify filtered files
        $filteredPaths = array_map(function ($file) {
            return $file['path'];
        }, $result['files']);

        // Authentication-related files should be included
        $this->assertContains('app/Controllers/AuthController.php', $filteredPaths);
        $this->assertContains('app/Models/User.php', $filteredPaths);

        // Non-authentication files should be excluded
        $this->assertNotContains('app/Controllers/HomeController.php', $filteredPaths);
        $this->assertNotContains('README.md', $filteredPaths);
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
