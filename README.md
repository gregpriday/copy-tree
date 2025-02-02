# Copy a Directory and Its Files to Your Clipboard with Ctree

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gregpriday/copy-tree.svg?style=flat-square)](https://packagist.org/packages/gregpriday/copy-tree)
[![Tests](https://img.shields.io/github/actions/workflow/status/gregpriday/copy-tree/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/gregpriday/copy-tree/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/gregpriday/copy-tree.svg?style=flat-square)](https://packagist.org/packages/gregpriday/copy-tree)

> **Note:** This tool is designed exclusively for MacOS and is not compatible with other operating systems.

Ctree is a command-line utility that effortlessly copies the structure and contents of a local directory or GitHub repository to your clipboard. It formats the output specifically for use with AI assistants such as [Claude](https://claude.ai/), [ChatGPT](https://chatgpt.com/), and [Gemini](https://gemini.google.com/), making it ideal for code analysis, generation, and sharing.

## Features

- **MacOS Integration:**  
  Seamless clipboard operations using native tools (`pbcopy` and `osascript`).

- **GitHub Integration:**  
  Clone and cache GitHub repositories via URL for fast, offline access and updates.

- **AI-Powered Capabilities:**
    - **Intelligent File Filtering:**  
      Use natural language descriptions to select relevant files.
    - **Smart Filename Generation:**  
      Automatically generate clear, hyphenated filenames for saved output based on content analysis.

- **Flexible Directory Management:**
    - **Dynamic Ruleset System:**  
      Configure inclusion/exclusion rules with a powerful JSON format.
    - **Multiple Rulesets & Auto-detection:**  
      Define custom rulesets (e.g., `frontend.json`, `backend.json`) or let Ctree auto-detect settings for common project types (Laravel, SvelteKit, etc.).

- **Versatile Output Options:**
    - Copy directly to the clipboard.
    - Save output to a file (with AI-generated names if desired).
    - Display output in the console.
    - Stream output for piping.
    - Copy a reference to a temporary file (to work around clipboard limitations).

## Prerequisites

- **MacOS**
- **PHP 8.2 or higher**
- **Git** (required for GitHub repository support)
- **Composer**

## Installation

1. **Install via Composer (globally):**

   ```bash
   composer global require gregpriday/copy-tree
   ```

2. **Add Composer’s Global Bin to Your PATH:**  
   Edit your `~/.zshrc` or `~/.bashrc` and add:

   ```bash
   export PATH="$PATH:$HOME/.composer/vendor/bin"
   ```

3. **(Optional) Configure AI Features:**  
   Create a `.env` file in `~/.copytree/` and add your OpenAI credentials:

   ```ini
   OPENAI_API_KEY=your-api-key
   OPENAI_API_ORG=your-org-id
   ```

   Replace `your-api-key` and `your-org-id` with your actual values.

## Quick Start

- **Copy the Current Directory:**

  ```bash
  ctree
  ```

- **Copy from a GitHub Repository:**

  ```bash
  ctree https://github.com/username/repo/tree/main/src
  ```

- **Use AI to Filter Files:**

  ```bash
  ctree --ai-filter="Find all authentication related files"
  ```

## Workflows

Ctree is designed to integrate into various development processes. Detailed workflows are available for:

- **[AI-Driven Design Workflow](docs/workflows/design.md):**  
  Iterate on web designs with SvelteKit, Tailwind CSS, and Claude.

- **[AI-Assisted Development Workflow](docs/workflows/development.md):**  
  Leverage AI for test-driven development, code generation, and managing large codebases.

- **[AI-Assisted Ruleset Creation Workflow](docs/workflows/rulesets.md):**  
  Create and refine JSON-based rulesets with AI support.

- **[AI-Powered Business Plan Development](docs/workflows/business-plans.md):**  
  Develop comprehensive business plans with in-depth market research and operational documentation.

## Advanced Usage

### GitHub Integration

Ctree caches GitHub repositories in `~/.copytree/cache` for improved performance.

```bash
# Copy a specific branch or directory from GitHub
ctree https://github.com/username/repo/tree/develop/src

# Clear the cache
ctree --clear-cache

# Bypass the cache
ctree https://github.com/username/repo --no-cache
```

### AI Features

#### Intelligent File Filtering

Filter files using natural language:

```bash
# Interactive mode (prompts for a description)
ctree --ai-filter

# Provide a filtering description directly
ctree --ai-filter="Show me all test files for the authentication system"
```

For more details, see [AI Features Documentation](docs/ai-features.md).

#### Smart Filename Generation

Automatically generate descriptive filenames when saving output:

```bash
ctree --output
```

### Output Options

- **Display in Console:**  
  `ctree --display`

- **Save to File (AI-generated name):**  
  `ctree --output`

- **Specify Output File:**  
  `ctree --output=my-output-file.txt`

- **Stream Output (for piping):**  
  `ctree --stream`

- **Copy as Reference (temporary file):**  
  `ctree --as-reference`

### Git Filtering

Focus on changes with Git-based options:

```bash
# Only include files modified since the last commit
ctree --modified

# Filter files changed between specific commits
ctree --changes=abc123:def456
```

These options are ideal for sharing recent changes or reviewing specific updates.

### Ruleset System

Ctree’s flexible ruleset system lets you precisely control file selection:

1. **Configuration:**  
   Ctree creates a `.ctree` directory in your project to store ruleset configurations.

2. **Multiple Named Rulesets:**  
   Define and use custom rulesets (e.g., `frontend.json`, `backend.json`).

3. **Auto-detection:**  
   Ctree auto-selects appropriate rulesets for recognized project types (e.g., Laravel, SvelteKit).

To use a custom ruleset, run:

```bash
ctree --ruleset=my-ruleset
```

For more details, see:
- [Ruleset Examples](docs/rulesets/examples.md)
- [Writing Rulesets](docs/rulesets/rulesets.md)
- [Fields and Operations Reference](docs/rulesets/fields-and-operations.md)
- [Using Multiple Rulesets](docs/rulesets/multiple-rulesets.md)

## Directory Structure

Ctree uses the following directory structure for configuration and output:

```
~/.copytree/
├── .env           # OpenAI configuration (if using AI features)
├── cache/         # GitHub repository cache
│   └── repos/
└── files/         # Generated output files (when using --output)
```

## Contributing

Contributions are welcome! Please note that this project is MacOS-only. To contribute:

1. Fork the repository.
2. Create a feature branch.
3. Make your changes.
4. Submit a pull request.

## Testing

Run the tests with:

```bash
composer test
```

## Security

If you discover any security issues, please email [greg@siteorigin.com](mailto:greg@siteorigin.com) rather than using the issue tracker.

## License

This project is licensed under the MIT License. See [LICENSE.md](LICENSE.md) for details.
