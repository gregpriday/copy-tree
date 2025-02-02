# Writing Rulesets for Ctree

Rulesets are the heart of Ctree's powerful file filtering capabilities. They allow you to precisely control which files and directories are included or excluded when copying a directory tree.

## Quick Start

Here's the fastest way to get started with Ctree rulesets:

```json
{
    "rules": [
        [
            ["extension", "oneOf", ["js", "ts"]]
        ]
    ]
}
```

Save this as `.ctree/ruleset.json` in your project to include all JavaScript and TypeScript files. That's it!

Common use cases:
- Include specific file types: `["extension", "oneOf", ["php", "js"]]`
- Filter by folder: `["folder", "startsWith", "src"]`
- Exclude patterns: `["basename", "startsWith", "."]`

Related documentation:
- [Ruleset Examples](./examples.md)
- [Fields and Operations Reference](./fields-and-operations.md)
- [Using Multiple Rulesets](./multiple-rulesets.md)

## Basic Structure

Rulesets are defined in JSON format. Here's the complete structure:

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

## Rules

The `rules` property defines an array of rule sets. Each rule set is an array of individual rules. For a file to be included, it must match all rules in at least one of the rule sets.

Each rule is an array with three elements:
1. `field`: The file attribute to check, such as `folder`, `basename`, `extension`, etc.
2. `operator`: The comparison operator, such as `=`, `startsWith`, `glob`, etc.
3. `value`: The value to compare against. Can be a string, number, or array depending on the operator.

Check [Fields and Operations](./fields-and-operations.md) for more details on what's available here.

### Rule Combinations

Rules can be combined using AND/OR logic:

```json
{
    "rules": [
        [
            ["folder", "startsWith", "src"],     // Rule 1   \
            ["extension", "oneOf", ["js", "ts"]] // Rule 2   } AND
        ],                                       //          /
        [                                       // New ruleset - OR
            ["folder", "startsWith", "tests"],   // Rule 3   \
            ["basename", "endsWith", "Test"]     // Rule 4   } AND
        ]
    ]
}
```

This ruleset includes files that are either:
- In a folder starting with "src" AND have a "js" or "ts" extension, OR
- In a folder starting with "tests" AND have a basename ending with "Test"

### How Rules Are Evaluated

1. Rules within a ruleset use AND logic:
   ```json
   [
     ["field1", "=", "value1"],     // Must match this AND
     ["field2", "=", "value2"]      // Must match this
   ]
   ```

2. Different rulesets use OR logic:
   ```json
   {
     "rules": [
       [["extension", "=", "js"]],     // Match this ruleset OR
       [["extension", "=", "ts"]]      // Match this ruleset
     ]
   }
   ```

3. Global exclude rules are always applied first:
   ```json
   {
     "globalExcludeRules": [
       ["folder", "contains", "node_modules"]  // Exclude these first
     ],
     "rules": [
       [["extension", "=", "js"]]             // Then apply these
     ]
   }
   ```

## Best Practices

### Organization
- Group related rules together in a ruleset
- Use descriptive comments to explain complex rules
- Keep rulesets focused on specific purposes
- Consider splitting complex rulesets into multiple files

### Performance
- Use path-based rules (folder, extension) before content-based rules
- Avoid complex regex patterns when simple string operations will do
- Put most common exclusions in globalExcludeRules
- List most likely matches first in OR conditions

### Maintainability
- Version control your rulesets alongside your code
- Document complex rule combinations
- Use consistent formatting and naming
- Break down complex rulesets into smaller, focused ones

## Troubleshooting

Common issues and solutions:

1. Files not being included:
    - Check path separators (use forward slashes)
    - Verify case sensitivity
    - Look for conflicting globalExcludeRules
    - Test rules individually

2. Performance issues:
    - Minimize use of content-based rules
    - Use specific path-based rules first
    - Avoid complex regex patterns
    - Keep rulesets focused and minimal

3. Cross-platform issues:
    - Always use forward slashes (/) in paths
    - Be explicit about case sensitivity
    - Test on all target platforms
    - Use relative paths

## Global Exclude Rules

The `globalExcludeRules` property defines an array of rules. If a file matches any of these rules, it will always be excluded, regardless of the `rules` section.

Example:
```json
{
    "globalExcludeRules": [
        ["folder", "startsWith", "node_modules"],
        ["basename", "equals", "package-lock.json"]
    ]
}
```

This will exclude all files in "node_modules" directories and any file named "package-lock.json".

## Always Include/Exclude

The `always` property allows you to explicitly include or exclude specific files by their relative paths, regardless of other rules.

Example:
```json
{
    "always": {
        "include": [".gitignore", "README.md"],
        "exclude": ["temp.log"]
    }
}
```

This will always include ".gitignore" and "README.md", and always exclude "temp.log".

## Operators

Ctree supports a wide variety of operators for flexible rule definition:

- Comparison: `=`, `!=`, `>`, `>=`, `<`, `<=`
- String: `startsWith`, `endsWith`, `contains`
- Array: `oneOf`
- Glob: `glob` (shell-style wildcards)
- Regex: `regex`
- File Type: `isAscii`, `isUrl`, `isJson`, etc.

You can negate any operator by prefixing it with "not", e.g., `notStartsWith`.

## Numeric Operations

When using comparison operators with numeric fields like `size` or `mtime`, the value can be a number:

```json
["size", ">", 1024]
```

For `size`, you can also use human-readable strings with units like "KB", "MB", "GB":

```json 
["size", "<", "5 MB"]
```

## Directory Filtering

To include or exclude entire directories, use the `folder` field with string operators:

```json
[
  ["folder", "startsWith", "src"],
  ["folder", "notStartsWith", "src/tests"]
]
```

This includes all files in "src/" but excludes "src/tests/".

## Examples

Here are some examples demonstrating various ruleset techniques:

1. Include only JavaScript and TypeScript files in "src/":
   ```json
   {
     "rules": [
       [
         ["folder", "startsWith", "src"],
         ["extension", "oneOf", ["js", "ts"]]
       ]
     ]
   }
   ```

2. Exclude "node_modules" and hidden files (starting with "."):
    ```json
    {
      "globalExcludeRules": [
        ["folder", "contains", "node_modules"],
        ["basename", "startsWith", "."]
      ]
    }
    ```

3. Always include key files, exclude large files:
   ```json
   {
     "always": {
       "include": [
         "package.json", 
         "README.md",
         "LICENSE"
       ],
       "exclude": [
         "images/large_photo.jpg"
       ]
     }
   }
   ```

4. Include recent and small files:
   ```json
   {
     "rules": [
       [
         ["mtime", ">", "1 week ago"],
         ["size", "<", "100 KB"] 
       ]
     ]
   }
   ```

5. Exclude files that are not PHP or JavaScript:
   ```json
   {
     "globalExcludeRules": [
       ["extension", "notOneOf", ["php", "js"]]
     ]
   }
   ```

   This ruleset uses `globalExcludeRules` with the `notOneOf` operator to exclude any file whose extension is not ".php" or ".js". This applies globally, to all sets of rules.

   The `notOneOf` operator checks if the file's extension is not in the provided array of allowed extensions. This effectively filters out all files except those with the specified extensions.

   You can easily adapt this to allow different file types by modifying the array:
   ```json
   ["extension", "notOneOf", ["java", "py", "rb"]]
   ```

   This would exclude files that are not Java (".java"), Python (".py"), or Ruby (".rb") files.

   Using `globalExcludeRules` with `notOneOf` is a concise way to limit the copied files to a whitelist of allowed extensions. This technique is useful when you want to share only specific types of source files from your project.

With its flexible ruleset system, Ctree makes it easy to precisely control which parts of your project are shared. Experiment with different rule combinations to craft the perfect output for your needs!
