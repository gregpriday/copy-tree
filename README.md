# Copy a directory and its files to your clipboard with Ctree

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gregpriday/copy-tree.svg?style=flat-square)](https://packagist.org/packages/gregpriday/copy-tree)
[![Tests](https://img.shields.io/github/actions/workflow/status/gregpriday/copy-tree/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/gregpriday/copy-tree/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/gregpriday/copy-tree.svg?style=flat-square)](https://packagist.org/packages/gregpriday/copy-tree)

> **Note**: This tool is designed exclusively for MacOS and is not compatible with other operating systems.

Ctree is a command-line tool designed to easily copy the structure and contents of a directory or GitHub repository to your clipboard, specifically formatted for interaction with AI assistants like [Claude](https://claude.ai/), [ChatGPT](https://chatgpt.com/), and [Gemini](https://gemini.google.com/). It provides a quick way to get your code and content into these platforms for analysis, code generation, or any other tasks they can perform.

## Features

-   **MacOS Integration**: Seamless clipboard integration using native MacOS tools (`pbcopy` and `osascript`).
-   **GitHub Integration**: Copy directly from GitHub repositories using URLs. Includes smart caching for improved performance.
-   **[AI-Powered Features](docs/ai-features.md)**:
    -   **Intelligent File Filtering**: Use natural language to describe which files to include.
    -   **Smart Filename Generation**: Automatically generates descriptive filenames when saving output based on content analysis.
-   **Smart Directory Management**:
    -   **Flexible Ruleset System**: Define rules for including/excluding files using a powerful JSON-based system.
    -   **Multiple Rulesets**: Create different rulesets (e.g., `frontend.json`, `backend.json`) to target specific parts of your project.
    -   **Project Auto-detection**: Automatically detects and uses appropriate rulesets for common project types (Laravel, SvelteKit, etc.).
-   **Versatile Output Options**:
    -   Copy directly to clipboard.
    -   Save to a file (with AI-generated names if desired).
    -   Display the output in the console.
    -   Stream output for piping to other commands.
-   **Reference File Copying:** Copy a reference to a temporary file instead of the content itself, to work around clipboard limitations.

## Prerequisites

Before installing, ensure you have:

-   MacOS
-   PHP 8.2 or higher
-   Git (required for GitHub repository support)
-   Composer

## Installation

1. Install via Composer:

    ```bash
    composer global require gregpriday/copy-tree
    ```

2. Add Composer's global bin to your PATH. Edit `~/.zshrc` or `~/.bashrc` and add:

    ```bash
    export PATH="$PATH:$HOME/.composer/vendor/bin"
    ```

3. **Configure OpenAI (Optional, for AI features):**
    -   Create a `.env` file in `~/.copytree/` with your OpenAI credentials:

    ```
    OPENAI_API_KEY=your-api-key
    OPENAI_API_ORG=your-org-id
    ```

    Replace `your-api-key` and `your-org-id` with your actual OpenAI API key and organization ID.

## Quick Start

**Copy the current directory:**

```bash
ctree
```

**Copy from a GitHub repository:**

```bash
ctree https://github.com/username/repo/tree/main/src
```

**Use AI to filter files:**

```bash
ctree --ai-filter="Find all authentication related files"
```

## Advanced Usage

### GitHub Integration

Ctree maintains a local cache at `~/.copytree/cache` to optimize performance when working with GitHub repositories.

```bash
# Copy a specific branch/directory from GitHub
ctree https://github.com/username/repo/tree/develop/src

# Clear the cache
ctree --clear-cache

# Bypass the cache
ctree https://github.com/username/repo --no-cache
```

### AI Features

#### Intelligent Filtering

Use natural language to filter files:

```bash
# Interactive mode (prompts for description)
ctree --ai-filter

# Direct description
ctree --ai-filter="Show me all the test files for the authentication system"
```

Learn more in the [AI Features documentation](docs/ai-features.md).

#### Smart Filename Generation

When saving output to a file, Ctree can automatically generate a descriptive filename:

```bash
# Saves to ~/.copytree/files with an AI-generated name
ctree --output
```

### Output Options

```bash
# Display output in the console
ctree --display

# Save to a file with automatic naming (using AI)
ctree --output

# Save to a specific file
ctree --output=my-output-file.txt

# Stream output (useful for piping)
ctree --stream

# Copy a reference to a temporary file instead of the content
ctree --as-reference
```

### Ruleset System

Ctree uses a powerful and flexible ruleset system to determine which files to include or exclude.

1. **Configuration Directory**: Ctree automatically creates a `.ctree` directory in your project for storing ruleset configurations.
2. **Multiple Named Rulesets**: Define multiple rulesets for different purposes (e.g., `frontend.json`, `backend.json`, `docs.json`).
3. **Project Auto-detection**: Ctree automatically detects and applies appropriate rulesets for known project types (currently Laravel and SvelteKit).
4. **Workspaces:** Define workspaces to combine rulesets and add additional rules.

**Example ruleset (`.ctree/my-ruleset.json`):**

```json
{
    "rules": [
        [
            ["folder", "startsWith", "src"],
            ["extension", "oneOf", ["js", "ts"]]
        ]
    ],
    "globalExcludeRules": [
        ["folder", "contains", "node_modules"]
    ],
    "always": {
        "include": ["README.md"],
        "exclude": ["secrets.json"]
    }
}
```

**Using a custom ruleset:**

```bash
ctree --ruleset=my-ruleset
```

**For detailed ruleset documentation, see:**

-   [Ruleset Examples](docs/rulesets/examples.md)
-   [Writing Rulesets](docs/rulesets/rulesets.md)
-   [Fields and Operations Reference](docs/rulesets/fields-and-operations.md)
-   [Using Multiple Rulesets](docs/rulesets/multiple-rulesets.md)

## Directory Structure

Ctree uses the following directory structure:

```
~/.copytree/
├── .env           # OpenAI configuration (if using AI features)
├── cache/         # GitHub repository cache
│   └── repos/
└── files/         # Generated output files (when using --output)
```

## Contributing

Contributions are welcome! Please note that this project is MacOS-only, and we currently do not plan to add support for other operating systems.

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## Testing

Run the tests with:

```bash
composer test
```

## Security

If you discover security issues, please email [greg@siteorigin.com](mailto:greg@siteorigin.com) rather than using the issue tracker.

## License

The MIT License (MIT). See [License File](LICENSE.md) for details.
