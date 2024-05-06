# Copy a directory and its files to your clipboard

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gregpriday/copy-tree.svg?style=flat-square)](https://packagist.org/packages/gregpriday/copy-tree)
[![Tests](https://img.shields.io/github/actions/workflow/status/gregpriday/copy-tree/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/gregpriday/copy-tree/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/gregpriday/copy-tree.svg?style=flat-square)](https://packagist.org/packages/gregpriday/copy-tree)

This command line tool allows you to copy the entire structure of a directory, including file contents, to your clipboard. This is particularly useful for quickly sharing the contents and structure of your files in a readable format, such as during code reviews or collaborative debugging sessions.

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
```

### Global Installation and Usage

Install `copy-tree` globally with Composer to use the `ctree` command from anywhere in your terminal:

```bash
composer global require gregpriday/copy-tree
```

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

This setup streamlines the installation and usage process, allowing quick and flexible use of `ctree` across your system.

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
