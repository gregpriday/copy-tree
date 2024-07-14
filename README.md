# Copy a directory and its files to your clipboard

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gregpriday/copy-tree.svg?style=flat-square)](https://packagist.org/packages/gregpriday/copy-tree)
[![Tests](https://img.shields.io/github/actions/workflow/status/gregpriday/copy-tree/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/gregpriday/copy-tree/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/gregpriday/copy-tree.svg?style=flat-square)](https://packagist.org/packages/gregpriday/copy-tree)

This command line tool allows you to copy the entire structure of a directory, including file contents, to your clipboard. This is particularly useful for quickly sharing the contents and structure of your files in a readable format, such as during code reviews or collaborative debugging sessions.

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

Copy-tree uses a flexible ruleset system to determine which files and directories to include or exclude. The ruleset system works in the following order:

1. Custom rulesets in the current directory
2. Predefined rulesets
3. Auto-detection
4. Default ruleset

### Custom Rulesets

You can create a custom ruleset file named `ctree.json` in your project directory. If this file exists, it will be used instead of any predefined or default rulesets.

### Predefined Rulesets

Copy-tree comes with predefined rulesets for common project types. Use them like this:

```bash
ctree --ruleset laravel   # Uses the predefined Laravel ruleset
ctree --ruleset sveltekit # Uses the predefined SvelteKit ruleset
```

### Auto-detection

If no ruleset is specified, copy-tree will attempt to auto-detect the project type and use an appropriate ruleset:

```bash
ctree  # Auto-detects project type and uses the most suitable ruleset
```

### Default Ruleset

If no custom ruleset is found, no predefined ruleset is specified, and auto-detection fails, copy-tree will use the default ruleset.

## Ruleset Format

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

For a complete reference of the ruleset schema, see the `schema.json` file in the project repository.

## Examples

Here are some example rulesets:

### Laravel Ruleset

```json
{
    "rules": [
        [
            ["folder", "startsWithAny", ["app", "config", "database/migrations", "resources/views", "routes", "tests"]],
            ["extension", "oneOf", ["php", "blade.php"]]
        ]
    ],
    "globalExcludeRules": [
        ["folder", "startsWithAny", ["vendor", "node_modules", "storage"]],
        ["extension", "oneOf", ["log", "lock"]],
        ["basename", "startsWith", ".env"]
    ],
    "always": {
        "include": [
            "composer.json",
            "README.md",
            ".env.example"
        ],
        "exclude": [
            "composer.lock",
            "package-lock.json"
        ]
    }
}
```

## Testing

Run the tests with:

```bash
composer test
```

## Changelog

For details on recent changes, check out the [CHANGELOG](CHANGELOG.md).

## Contributing

Contributions are welcome! Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email greg@siteorigin.com instead of using the issue tracker.

## Credits

- [Greg Priday](https://github.com/gregpriday)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
