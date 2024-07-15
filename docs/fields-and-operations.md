# Ctree Fields and Operations Reference

This page documents all the available fields and operations that can be used in Ctree rulesets to filter files and directories.

For a complete guide on writing rulesets, see the [Writing Rulesets](./rulesets.md) documentation.

## Fields

Ctree rulesets allow you to match against various attributes of files and directories. Here are the available fields:

- `folder`: The directory path relative to the base directory.
    - Example: `src/components`

- `path`: The full file path relative to the base directory.
    - Example: `src/components/Button.js`

- `dirname`: The directory name.
    - Example: `components`

- `basename`: The file name including extension.
    - Example: `Button.js`

- `extension`: The file extension.
    - Example: `js`

- `filename`: The file name without extension.
    - Example: `Button`

- `contents`: The full contents of the file.
    - Allows filtering based on file content.

- `contents_slice`: The first 256 characters of the file contents.
    - Useful for efficiently checking file headers or initial content without loading the full file.
    - Example: Check if a file is ASCII text:
      ```json
      ["contents_slice", "isAscii"]
      ```

- `size`: The size of the file in bytes.
    - Allows filtering based on file size thresholds.
    - Example: Include files less than 1MB:
      ```json
      ["size", "<", "1 MB"]    
      ```

- `mtime`: The modification time of the file as a Unix timestamp.
    - Allows filtering based on when files were last modified.
    - Example: Include files modified in the last week:
      ```json
      ["mtime", ">", "1 week ago"]
      ```

- `mimeType`: The MIME type of the file.
    - Allows filtering based on file type as determined by the content.
    - Example: Exclude image files:
      ```json  
      ["mimeType", "notStartsWith", "image/"]
      ```

## Operations

Operations are used to compare fields against values. Ctree supports a wide variety of operations for powerful filtering:

### Comparison
- `=`, `!=`: Check for equality/inequality
- `>`, `>=`, `<`, `<=`: Compare ordered values
    - Example: Include files larger than 10KB but less than 1MB:
      ```json
      [
        ["size", ">", "10 KB"],
        ["size", "<", "1 MB"]
      ]
      ```

### String
- `startsWith`, `endsWith`, `contains`: Check if a string starts with, ends with, or contains a substring
    - Example: Include JS and TS files in src directory:
      ```json
      [
        ["folder", "startsWith", "src"], 
        ["extension", "oneOf", ["js", "ts"]]
      ]  
      ```
- Regex: `regex`: Match a regular expression
    - Example: Exclude minified files:
      ```json
      ["basename", "notRegex", "\\.min\\.(js|css)$"]   
      ```
- Glob: `glob`: Match a glob pattern
    - Example: Include Markdown files in docs:
      ```json
      ["path", "glob", "docs/**/*.md"]
      ```

### Array
- `oneOf`: Check if a value is one of an array
    - Example: Include common web image formats:
      ```json
      ["extension", "oneOf", ["jpg", "png", "gif", "webp"]]
      ```
- `notOneOf`: Check if a value is not in an array
    - Example: Exclude certain file extensions:
      ```json
      ["basename", "notOneOf", ["py", "rb", "java"]]
      ```

### File Type Checks
- `isAscii`, `isJson`, `isUrl`, `isUuid`, `isUlid`: Check if a string is valid ASCII, JSON, URL, UUID, or ULID

### Negation
Any operation can be negated by prefixing with `not`:
- `notContains`, `notEndsWith`, `notRegex`, `notIsAscii` etc.

## String Operations
Ctree leverages Laravel's `Str` class, providing a huge number of convenient string manipulation functions. All are available, but you'll mostly want to use boolean operations like `startsWith`, `contains`, etc.

See the [Laravel String documentation](https://laravel.com/docs/11.x/strings#strings-method-list) for a full list of available string operations.
