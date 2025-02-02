# Best Practices for Ctree Rulesets

This guide outlines best practices for creating, maintaining, and organizing Ctree rulesets. Following these recommendations will help you create more efficient, maintainable, and reliable rulesets.

## Organization

### Directory Structure
```
.ctree/
├── rulesets/
│   ├── frontend.json
│   ├── backend.json
│   └── docs.json
├── workspaces.json
└── ruleset.json
```

- Keep related rulesets in the `.ctree/rulesets/` directory
- Use `ruleset.json` as your default ruleset
- Split complex rulesets into smaller, focused files
- Use descriptive filenames that reflect the ruleset's purpose

### Rule Organization

1. Order rules by specificity:
```json
{
  "rules": [
    [
      ["folder", "startsWith", "src"],        // Most specific first
      ["extension", "oneOf", ["js", "ts"]]    // More general second
    ]
  ]
}
```

2. Group related rules together:
```json
{
  "rules": [
    [
      // Frontend files
      ["folder", "startsWith", "src/components"],
      ["extension", "oneOf", ["jsx", "tsx"]]
    ],
    [
      // Backend files
      ["folder", "startsWith", "src/api"],
      ["extension", "=", "ts"]
    ]
  ]
}
```

## Performance

### Rule Ordering
1. Put fastest checks first:
    - Path-based rules (`folder`, `extension`, `basename`)
    - Simple string operations (`startsWith`, `endsWith`)
    - Array checks (`oneOf`)
    - Regular expressions (slower)
    - Content-based rules (slowest)

```json
{
  "rules": [
    [
      ["folder", "startsWith", "src"],           // Fast path check
      ["extension", "oneOf", ["js", "ts"]],      // Fast array check
      ["contents", "contains", "copyright"]       // Slow content check
    ]
  ]
}
```

### Optimize Global Excludes
1. Put common exclusions in `globalExcludeRules`:
```json
{
  "globalExcludeRules": [
    ["folder", "containsAny", ["node_modules", "dist", "build"]],
    ["extension", "oneOf", ["log", "tmp", "bak"]]
  ]
}
```

2. Use efficient patterns:
```json
{
  "globalExcludeRules": [
    // Better: Single rule with multiple values
    ["folder", "startsWithAny", ["temp", "tmp", ".git"]],
    
    // Worse: Multiple separate rules
    ["folder", "startsWith", "temp"],
    ["folder", "startsWith", "tmp"],
    ["folder", "startsWith", ".git"]
  ]
}
```

## Security

### Sensitive File Exclusions
1. Always exclude sensitive files:
```json
{
  "globalExcludeRules": [
    ["basename", "oneOf", [
      ".env",
      "secrets.json",
      "credentials.yml",
      "id_rsa",
      "private.key"
    ]],
    ["extension", "oneOf", ["pem", "key", "pfx", "p12"]],
    ["folder", "contains", "secrets"]
  ]
}
```

2. Use pattern matching for variations:
```json
{
  "globalExcludeRules": [
    ["basename", "regex", "\\.(env|key|secret)(\\..*)?$"],
    ["basename", "regex", "^(dev|prod|staging)\\-secrets\\."]
  ]
}
```

## Maintainability

### Documentation
1. Add comments in separate documentation files:
```markdown
# Frontend Ruleset

This ruleset is used for collecting frontend-related files:
- React components from src/components
- Style files from src/styles
- Configuration files from the root directory
```

2. Explain complex rules:
```json
{
  "rules": [
    [
      // Matches source files but excludes test files
      ["folder", "startsWith", "src"],
      ["folder", "notContains", "/__tests__"],
      ["extension", "oneOf", ["js", "ts"]]
    ]
  ]
}
```

### Version Control
1. Track rulesets in version control:
```gitignore
# .gitignore
!.ctree/
!.ctree/**
```

2. Use semantic versioning for ruleset changes
3. Include changelog comments for significant changes

### Cross-Platform Compatibility
1. Use forward slashes for paths:
```json
{
  "rules": [
    // Good - works on all platforms
    ["folder", "startsWith", "src/components"],
    
    // Bad - Windows-specific
    ["folder", "startsWith", "src\\components"]
  ]
}
```

2. Handle case sensitivity:
```json
{
  "rules": [
    // Use lowercase for consistent matching
    ["extension", "oneOf", ["js", "ts", "jsx", "tsx"]]
  ]
}
```

## Testing

### Ruleset Validation
1. Test with sample directories:
```bash
# Test ruleset with different paths
ctree path/to/test/dir --ruleset=frontend
ctree another/test/dir --ruleset=frontend
```

2. Verify exclusions:
```bash
# Check that sensitive files are excluded
ctree . --ruleset=frontend --display
```

### Common Testing Patterns
1. Test edge cases:
    - Empty directories
    - Deep directory structures
    - Files with special characters
    - Very large files
    - Binary files

2. Test platform differences:
    - Different path separators
    - Case sensitivity
    - Line endings
    - File permissions

## Rule Composition

### Reusable Patterns
1. Create common exclusion patterns:
```json
{
  "globalExcludeRules": [
    // Development files
    ["folder", "containsAny", [
      "node_modules",
      "vendor",
      "dist",
      "build"
    ]],
    
    // Temporary files
    ["extension", "oneOf", [
      "log",
      "tmp",
      "temp",
      "swp"
    ]],
    
    // System files
    ["basename", "oneOf", [
      ".DS_Store",
      "Thumbs.db",
      ".gitignore"
    ]]
  ]
}
```

2. Define focused rule sets:
```json
{
  "rules": [
    // JavaScript source files
    [
      ["folder", "startsWith", "src"],
      ["extension", "oneOf", ["js", "jsx"]]
    ],
    
    // TypeScript source files
    [
      ["folder", "startsWith", "src"],
      ["extension", "oneOf", ["ts", "tsx"]]
    ],
    
    // Style files
    [
      ["folder", "startsWith", "src/styles"],
      ["extension", "oneOf", ["css", "scss", "less"]]
    ]
  ]
}
```

## Error Handling

### Common Pitfalls
1. Avoid overlapping rules:
```json
{
  "rules": [
    // Problematic: Rules might conflict
    [
      ["folder", "startsWith", "src"],
      ["extension", "oneOf", ["js", "ts"]]
    ],
    [
      ["folder", "startsWith", "src/lib"],
      ["extension", "=", "js"]
    ]
  ]
}
```

2. Handle edge cases:
```json
{
  "rules": [
    [
      // Handle both lowercase and uppercase extensions
      ["extension", "oneOf", ["md", "MD", "markdown"]]
    ]
  ]
}
```

### Debugging Tips
1. Use verbose output:
```bash
ctree --ruleset=frontend --display
```

2. Test rules individually:
```bash
# Test single rule patterns
ctree --filter="src/**/*.js"
```

## Conclusion

Following these best practices will help you create more maintainable and efficient rulesets. Remember to:
1. Organize rules logically
2. Optimize for performance
3. Ensure security
4. Maintain cross-platform compatibility
5. Test thoroughly
6. Document clearly
7. Handle errors gracefully

For more detailed information, refer to:
- [Writing Rulesets](./rulesets.md)
- [Ruleset Examples](./examples.md)
- [Fields and Operations Reference](./fields-and-operations.md)
