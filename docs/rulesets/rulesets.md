# Writing Rulesets for Ctree

Rulesets are the heart of Ctree's powerful file filtering capabilities. They allow you to precisely control which files and directories are included or excluded when copying a directory tree.

Related documentation:
- [Ruleset Examples](./examples.md)
- [Fields and Operations Reference](./fields-and-operations.md)
- [Using Multiple Rulesets](./multiple-rulesets.md)

Here are some [ruleset examples](./examples.md) to help you understand the overall system.

Rulesets are defined in JSON format. Here's the basic structure:

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

Example:
```json
"rules": [
  [
    ["folder", "startsWith", "src"],
    ["extension", "oneOf", ["js", "ts"]]
  ],
  [
    ["folder", "startsWith", "tests"],
    ["basename", "endsWith", "Test"]
  ]
]
```

This ruleset includes files that are either:
- In a folder starting with "src" AND have a "js" or "ts" extension, OR
- In a folder starting with "tests" AND have a basename ending with "Test"

### Understanding the `rules` Field

The `rules` field in a Ctree ruleset is an array of rule sets. Each rule set is an array of individual rules. For a file to be included in the output, it must satisfy all the rules in at least one of the rule sets.

Here's the general structure:

```json
{
    "rules": [
        [
            ["field1", "operator1", "value1"],
            ["field2", "operator2", "value2"]
        ],
        [
            ["field3", "operator3", "value3"],
            ["field4", "operator4", "value4"]
        ]
    ]
}
```

In this example, the `rules` array contains two rule sets:

1. The first rule set:
   ```json
   [
     ["field1", "operator1", "value1"],
     ["field2", "operator2", "value2"]
   ]
   ```

2. The second rule set:
   ```json
   [
     ["field3", "operator3", "value3"], 
     ["field4", "operator4", "value4"]
   ]
   ```

For a file to be included, it must satisfy either:
- All the rules in the first rule set (field1 operator1 value1 AND field2 operator2 value2)
- OR all the rules in the second rule set (field3 operator3 value3 AND field4 operator4 value4)

In other words:
- The rules within each rule set are combined with a logical AND
- The rule sets themselves are combined with a logical OR

This allows for powerful and flexible filtering. You can define multiple criteria that a file must meet (within a rule set), while also providing alternate paths for inclusion (via multiple rule sets).

For example:
```json
{
    "rules": [
        [
            ["folder", "startsWith", "src"],
            ["extension", "oneOf", ["js", "ts"]]
        ],
        [
            ["basename", "equals", "README.md"]
        ]
    ]
}
```

In this ruleset, a file will be included if it is either:
- Inside a folder that starts with "src" AND has a ".js" or ".ts" extension
- OR has the exact basename "README.md"

This combination of AND within rule sets and OR between rule sets enables you to create rulesets that precisely target the files you want to include, while keeping the logic clear and maintainable.

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
