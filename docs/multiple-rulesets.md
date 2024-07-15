# Using Multiple Rulesets in Ctree

Ctree allows you to define multiple rulesets within a single project to selectively copy different parts of your codebase. This is useful when you want to share only specific sections of your project with others, such as templates, admin pages, or settings-related files.

Related documentation:
- [Writing Rulesets](./rulesets.md)
- [Ruleset Examples](./examples.md)
- [Fields and Operations Reference](./fields-and-operations.md)

## Why Use Multiple Rulesets?

Large projects often have distinct sections or modules that serve different purposes. When collaborating with others or seeking help, you may want to share only the relevant parts of your codebase rather than the entire project. By defining multiple rulesets, you can create targeted subsets of your project files that can be easily shared using Ctree.

Examples of when multiple rulesets can be helpful:

1. **Template System**: If you need help with your project's templating, you can define a `templates` ruleset that includes only the files related to your templates, such as HTML, CSS, and template-specific JavaScript files.

2. **Admin Section**: When working on the admin section of your application, you can create an `admin` ruleset that captures all the files related to the admin functionality, such as admin controllers, views, and models.

3. **Settings Pages**: If you're tweaking the settings or configuration of your application, you can use a `settings` ruleset to include only the files related to your application's settings, such as configuration files, settings views, and related controllers.

## Defining Multiple Rulesets

To define multiple rulesets in your project, create separate JSON files for each ruleset in the `/.ctree` directory of your project. The file names should match the desired ruleset names.

For example, to create `templates`, `admin`, and `settings` rulesets, you would have the following files:

```
/.ctree/templates.json
/.ctree/admin.json
/.ctree/settings.json
```

Each JSON file should contain the ruleset definition using the standard Ctree ruleset format. Here's an example of what the `templates.json` ruleset might look like:

```json
{
  "rules": [
    [
      ["folder", "startsWith", "resources/views"]
    ],
    [
      ["extension", "oneOf", ["html", "blade.php", "twig", "css", "js"]]
    ]
  ],
  "globalExcludeRules": [
    ["folder", "contains", "node_modules"],
    ["basename", "endsWith", ".min.css"]
  ],
  "always": {
    "include": ["resources/lang"]
  }
}
```

This ruleset includes all files in the `resources/views` directory, as well as any files with `.html`, `.blade.php`, `.twig`, `.css`, or `.js` extensions. It excludes `node_modules` directories and minified CSS files, and always includes the `resources/lang` directory.

## Using Multiple Rulesets

Once you have defined your rulesets, you can use them with Ctree by specifying the ruleset name using the `--ruleset` or `-r` option.

For example, to copy files using the `templates` ruleset:

```bash
ctree --ruleset templates
```

This command will apply the rules defined in the `/.ctree/templates.json` file and copy the matching files to the clipboard.

Similarly, you can use the `admin` and `settings` rulesets:

```bash
ctree --ruleset admin
ctree --ruleset settings
```

Each command will copy the files specified by the corresponding ruleset, allowing you to selectively share different parts of your project.

## Conclusion

Using multiple rulesets in Ctree allows you to create targeted subsets of your project files for easier sharing and collaboration. By defining rulesets for specific sections or modules of your project, you can quickly copy and share only the relevant parts of your codebase with others.

Remember to create separate JSON files for each ruleset in the `/.ctree` directory and use the `--ruleset` option to specify which ruleset to apply when running the `ctree` command.
