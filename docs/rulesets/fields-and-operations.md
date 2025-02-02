# Ctree Fields and Operations Reference

This page documents all available fields and operations that can be used in Ctree rulesets to filter files and directories.

For a complete guide on writing rulesets, see the [Writing Rulesets](./rulesets.md) documentation.

## Fields

Ctree rulesets allow you to match against various attributes of files and directories:

### Path-based Fields

- `folder`: The directory path relative to the base directory
    - Examples: `src/components`, `tests/unit`
    - Useful for filtering entire directories

- `path`: The complete file path relative to the base directory
    - Examples: `src/components/Button.js`, `tests/unit/ButtonTest.php`
    - Best for exact file matching or complex patterns

- `dirname`: The immediate parent directory name
    - Examples: `components`, `unit`
    - Good for matching files in specific directories regardless of depth

- `basename`: The file name including extension
    - Examples: `Button.js`, `UserTest.php`
    - Perfect for filtering by full filename

- `extension`: The file extension without the dot
    - Examples: `js`, `php`, `md`
    - Ideal for filtering by file type

- `filename`: The file name without extension
    - Examples: `Button`, `UserTest`
    - Useful when extension doesn't matter

### Content-based Fields

- `contents`: The complete file contents
    - Usage: Full text search and pattern matching
    - Note: Can be slow for large files
    - Example: Find files containing copyright notices
      ```json
      ["contents", "contains", "Copyright (c)"]
      ```

- `contents_slice`: First 256 characters of the file
    - Usage: Quick header checks or file type detection
    - More efficient than full contents search
    - Example: Check if file is ASCII text
      ```json
      ["contents_slice", "isAscii"]
      ```

### Metadata Fields

- `size`: File size in bytes
    - Supports human-readable formats
    - Examples:
      ```json
      ["size", "<", "1 MB"]
      ["size", ">", "500 KB"]
      ["size", "<=", "2.5 GB"]
      ```
    - Supported units: B, KB, MB, GB, TB
    - Also supports binary units: KiB, MiB, GiB, TiB

- `mtime`: File modification time as Unix timestamp
    - Supports human-readable date strings
    - Examples:
      ```json
      ["mtime", ">", "1 week ago"]
      ["mtime", "<", "2024-01-01"]
      ["mtime", ">=", "last month"]
      ```
    - Uses Laravel's Carbon for date parsing

- `mimeType`: File MIME type detected from content
    - Examples:
      ```json
      ["mimeType", "startsWith", "text/"]
      ["mimeType", "=", "application/json"]
      ["mimeType", "notStartsWith", "image/"]
      ```

## Operations

### Comparison Operations

- Basic Comparisons
    - `=`: Exact equality
    - `!=`: Inequality
    - `>`: Greater than
    - `>=`: Greater than or equal
    - `<`: Less than
    - `<=`: Less than or equal

- Array Operations
    - `oneOf`: Value matches any array element
      ```json
      ["extension", "oneOf", ["js", "ts", "jsx", "tsx"]]
      ```
    - `notOneOf`: Value matches no array elements
      ```json
      ["extension", "notOneOf", ["exe", "dll", "so"]]
      ```

### String Operations

- Basic String Matching
    - `startsWith`: String starts with prefix
    - `endsWith`: String ends with suffix
    - `contains`: String contains substring
    - `notStartsWith`: String doesn't start with prefix
    - `notEndsWith`: String doesn't end with suffix
    - `notContains`: String doesn't contain substring

- Pattern Matching
    - `regex`: Matches regular expression
      ```json
      ["basename", "regex", "^test.*\\.js$"]
      ```
    - `notRegex`: Doesn't match regular expression
    - `glob`: Matches glob pattern
      ```json
      ["path", "glob", "src/**/*.{js,ts}"]
      ```
    - `fnmatch`: Matches shell-style wildcards

### File Type Checks

- Content Validation
    - `isAscii`: File contains only ASCII characters
    - `isJson`: File contains valid JSON
    - `isUrl`: String is a valid URL
    - `isUuid`: String is a valid UUID
    - `isUlid`: String is a valid ULID

### Compound Operations

- Multiple conditions use AND logic within a ruleset:
  ```json
  [
    ["folder", "startsWith", "src"],
    ["extension", "oneOf", ["js", "ts"]],
    ["size", "<", "1 MB"]
  ]
  ```

- Different rulesets use OR logic:
  ```json
  {
    "rules": [
      [
        ["extension", "=", "js"]
      ],
      [
        ["extension", "=", "ts"]
      ]
    ]
  }
  ```

### Operation Modifiers

- Any operation can be negated with `not` prefix:
    - `notContains`
    - `notStartsWith`
    - `notEndsWith`
    - `notRegex`
    - `notIsAscii`

- Plural variants for multiple values:
    - `startsWithAny`: Matches any prefix
    - `endsWithAny`: Matches any suffix
    - `containsAny`: Contains any substring
    - All support array input:
      ```json
      ["folder", "startsWithAny", ["src/", "test/", "docs/"]]
      ```

### String Operations

Ctree leverages Laravel's `Str` class for string operations. These operations are used for filtering files based on true/false conditions.

See the [Laravel String documentation](https://laravel.com/docs/11.x/strings#strings-method-list) for a complete list of available string operations.
