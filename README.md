# Copy a directory and its files to your clipboard

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gregpriday/copy-tree.svg?style=flat-square)](https://packagist.org/packages/gregpriday/copy-tree)
[![Tests](https://img.shields.io/github/actions/workflow/status/gregpriday/copy-tree/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/gregpriday/copy-tree/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/gregpriday/copy-tree.svg?style=flat-square)](https://packagist.org/packages/gregpriday/copy-tree)

This command line tool allows you to copy the entire structure of a directory, including file contents, to your clipboard. This is particularly useful for quickly sharing the contents and structure of your files in a readable format, such as during code reviews or collaborative debugging sessions.

## Ruleset Usage

Copy-tree supports multiple rulesets to determine which files and directories to include or exclude. The ruleset system works in the following order:

1. Custom rulesets in the current directory
2. Predefined rulesets
3. Auto-detection
4. Default ruleset

### Custom Rulesets

You can create custom ruleset files in your project directory:

```
/your_project
    ├── .ctreeinclude          # Default custom ruleset
    ├── frontend.ctreeinclude  # Custom ruleset for frontend files
    ├── backend.ctreeinclude   # Custom ruleset for backend files
    └── ... (other project files and directories)
```

Use these custom rulesets like this:

```bash
ctree                     # Uses .ctreeinclude
ctree --ruleset frontend  # Uses frontend.ctreeinclude
ctree --ruleset backend   # Uses backend.ctreeinclude
```

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

### Examples:

```bash
# Use the default .ctreeinclude in the current directory
ctree

# Use a specific ruleset file
ctree --ruleset=alt

# This will look for 'alt.ctreeinclude' in the current directory
```

If you have multiple rulesets in your project, you might have a structure like this:

```
/your_project
    ├── .ctreeinclude          # Default ruleset
    ├── frontend.ctreeinclude  # Ruleset for frontend files
    ├── backend.ctreeinclude   # Ruleset for backend files
    └── ... (other project files and directories)
```

You can then use these rulesets like this:

```bash
ctree                     # Uses .ctreeinclude
ctree --ruleset frontend  # Uses frontend.ctreeinclude
ctree --ruleset backend   # Uses backend.ctreeinclude
```

## Ruleset Format

The ruleset format is as follows:

```
# Primary include rules (directories)
app/**
config/**

# Secondary include rules (file types)
~**/*.php
~**/*.json

# Force include specific files
+composer.json
+README.md

# Exclude rules
!vendor/
!node_modules/

# Force exclude specific files
-sensitive_file.txt
```

- Lines starting with `#` are comments.
- Lines without a prefix are primary include rules (usually directories).
- Lines starting with `~` are secondary include rules (usually file types).
- Lines starting with `+` are force include rules.
- Lines starting with `!` are exclude rules.
- Lines starting with `-` are force exclude rules.

By using multiple ruleset files, you can quickly switch between different configurations for various tasks or parts of your project.

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
