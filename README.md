# Copy a directory and its files to your clipboard

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gregpriday/copy-tree.svg?style=flat-square)](https://packagist.org/packages/gregpriday/copy-tree)
[![Tests](https://img.shields.io/github/actions/workflow/status/gregpriday/copy-tree/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/gregpriday/copy-tree/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/gregpriday/copy-tree.svg?style=flat-square)](https://packagist.org/packages/gregpriday/copy-tree)

This command line tool allows you to copy the entire structure of a directory, including file contents, to your clipboard. This is particularly useful for quickly sharing the contents and structure of your files in a readable format, such as during code reviews or collaborative debugging sessions.

## Features

- Copy directory structure and file contents to clipboard, ready to paste into chatbots like [Claude](https://claude.ai/) or [ChatGPT](https://chatgpt.com/).
- Flexible ruleset system for including/excluding files.
- Support for multiple rulesets to target different parts of your project.
- Support for custom, predefined, and auto-detected rulesets.
- Output to clipboard, console, or file.
- Cross-platform support (Linux, macOS, Windows).

## Quick Start

After installation, you can quickly copy the current directory structure to your clipboard:

```bash
ctree
```

You can get command help with 

```bash
ctree --help
```

If you're in a Laravel or Sveltekit project, the automatic rules will work out the box. Otherwise you'll need to specify a custom ruleset.

## Documentation

For more detailed information on using Ctree, please refer to the following documentation:

- [Ruleset Examples](docs/examples.md): Various examples of rulesets for different project types.
- [Writing Rulesets](docs/rulesets.md): Detailed guide on how to write and structure rulesets.
- [Fields and Operations Reference](docs/fields-and-operations.md): Complete list of available fields and operations for rulesets.
- [Using Multiple Rulesets](docs/multiple-rulesets.md): Guide on using multiple rulesets in a single project.

For a quick overview of the ruleset system, see the [Ruleset System](#ruleset-system) section below.

## Prerequisites

Before installing and using `copy-tree`, make sure to have the necessary clipboard utilities installed on your system:

- **Linux**: Install `xclip` which is used by the tool to access the clipboard.
  ```bash
  sudo apt-get update && sudo apt-get install -y xclip
  ```
- **macOS**: macOS comes with `pbcopy` preinstalled, so no additional installation is necessary.
- **Windows**: Windows has the `clip` command available by default, so no additional installation is required.

## Installation

You can install the package via Composer:

```bash
composer require gregpriday/copy-tree
```

## Usage

After installation, you can run the `copy-tree` command directly from your terminal. Here's how you can use the command:

```bash
# Display the help information
./vendor/bin/ctree --help

# Copy current directory to clipboard and optionally display the output
./vendor/bin/ctree --display

# Specify a directory path
./vendor/bin/ctree --path=/path/to/directory

# Avoid copying to clipboard
./vendor/bin/ctree --no-clipboard

# Specify depth of directory tree
./vendor/bin/ctree --depth=3

# Use a specific ruleset
./vendor/bin/ctree --ruleset=laravel

# Output to a file instead of clipboard
./vendor/bin/ctree --output=output.txt
```

### Global Installation and Usage

Install `copy-tree` globally with Composer to use the `ctree` command from anywhere in your terminal:

```bash
composer global require gregpriday/copy-tree
```

Run the same command to upgrade to the latest version.

Ensure the Composer global bin directory is in your `PATH`. Typically, this is `~/.composer/vendor/bin` or `~/.config/composer/vendor/bin` for Unix systems. Add this to your `.bashrc` or `.zshrc`:

```bash
export PATH="$PATH:$HOME/.composer/vendor/bin"
```

Reload your configuration:

```bash
source ~/.bashrc
# Or, if using zsh
source ~/.zshrc
```

Now, you can use `ctree` from any directory:

```bash
# Copy the current directory to the clipboard
ctree

# Copy with specific depth and display output
ctree --path=/path/to/directory --depth=2 --display
```

## Ruleset System

Ctree uses a flexible ruleset system to determine which files and directories to include or exclude. The ruleset system works in the following order:

1. Custom rulesets in the current directory
2. Predefined rulesets
3. Auto-detection
4. Default ruleset

See the [Laravel Ruleset](./rulesets/laravel.json) for an example.

For a complete guide on writing rulesets, see the [Writing Rulesets](docs/rulesets.md) documentation.

For examples of rulesets for various project types, check out our [Ruleset Examples](docs/examples.md).

### Ruleset Format

Rulesets are defined in JSON format. Here's an overview of the structure:

```json
{
    "rules": [
        [
            ["field", "operator", "value"],
            ["field", "operator", "value"]
        ]
    ],
    "globalExcludeRules": [
        ["field", "operator", "value"]
    ],
    "always": {
        "include": ["file1", "file2"],
        "exclude": ["file3", "file4"]
    }
}
```

- `rules`: An array of rule sets. Each rule set is an array of rules that must all be true for a file to be included.
- `globalExcludeRules`: An array of rules that, if any are true, will exclude a file.
- `always`: Specifies files to always include or exclude, regardless of other rules.

### Fields

Available fields include:
- `folder`, `path`, `dirname`, `basename`, `extension`, `filename`, `contents`, `contents_slice`, `size`, `mtime`, `mimeType`

### Operators

Available operators include:

- `>`, `>=`, `<`, `<=`, `=`, `!=`, `oneOf`, `regex`, `glob`, `fnmatch`, `contains`, `startsWith`, `endsWith`, `length`, `isAscii`, `isJson`, `isUlid`, `isUrl`, `isUuid`

For a complete reference of the ruleset schema, see the [`schema.json`](./rulesets/schema.json) file in the project repository.

All operators can be negated by prefixing them with 'not', e.g., `notOneOf`, `notRegex`, `notStartsWith`. This allows for more flexible exclusion rules.

### Custom Rulesets

You can create a custom ruleset file named `/.ctree/ruleset.json` in your project directory. If this file exists, it will be used instead of any predefined or default rulesets.

You can also create named rulesets at `/.ctree/example.json`, which will be used for `ctree -r example`.

### Multiple Rulesets

Ctree supports the use of multiple rulesets within a single project, allowing you to selectively share different parts of your codebase. This is particularly useful for large projects with distinct sections or modules.

To use multiple rulesets:

1. Create separate JSON files for each ruleset in the `/.ctree` directory of your project.
2. Name each file according to the desired ruleset name (e.g., `/.ctree/frontend.json`, `/.ctree/backend.json`).
3. Use the `--ruleset` or `-r` option to specify which ruleset to apply:

```bash
ctree --ruleset frontend
ctree --ruleset backend
```

This feature enables you to easily share specific parts of your project, such as only the frontend code or only the backend code, without having to modify your ruleset each time.

For more detailed information on using multiple rulesets, refer to the [Using Multiple Rulesets](docs/multiple-rulesets.md) documentation.

### Predefined Rulesets

Ctree comes with predefined rulesets for common project types. Use them like this:

```bash
ctree --ruleset laravel   # Uses the predefined Laravel ruleset
ctree --ruleset sveltekit # Uses the predefined SvelteKit ruleset
```

### Auto-detection

If no ruleset is specified, Ctree will attempt to auto-detect the project type and use an appropriate ruleset:

```bash
ctree  # Auto-detects project type and uses the most suitable ruleset
```

### Default Ruleset

If no custom ruleset is found, no predefined ruleset is specified, and auto-detection fails, Ctree will use the default ruleset.

## Troubleshooting

If you encounter issues with clipboard functionality:

- **Linux**: Ensure `xclip` is installed and running.
- **macOS**: Try running the command with `sudo` if you get permission errors.
- **Windows**: Make sure you're running the command prompt as an administrator if you encounter permission issues.

If the output is truncated, try using the `--output` option to save to a file instead of copying to the clipboard.

## Contributing

Contributions are welcome! Here's how you can contribute:

1. Fork the repository
2. Create a new branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Commit your changes (`git commit -m 'Add some amazing feature'`)
5. Push to the branch (`git push origin feature/amazing-feature`)
6. Open a Pull Request

Please make sure to update tests as appropriate. For more details, see the [CONTRIBUTING](CONTRIBUTING.md) file.

## Testing

Run the tests with:

```bash
composer test
```

## Changelog

For details on recent changes, check out the [CHANGELOG](CHANGELOG.md).

## Security

If you discover any security related issues, please email [greg@siteorigin.com](mailto:greg@siteorigin.com) instead of using the issue tracker.

## Credits

- [Greg Priday](https://github.com/gregpriday)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
