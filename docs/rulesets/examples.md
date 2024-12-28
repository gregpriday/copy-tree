# Ctree Ruleset Examples

This document provides a variety of ruleset examples for different types of code projects. Each example includes a description and a JSON ruleset that can be used with Ctree. Remember that Ctree performs file-by-file filtering, so each rule should be applicable to individual files rather than entire directories.

For a complete guide on writing rulesets, see the [Writing Rulesets](./rulesets.md) documentation.

## Table of Contents

1. [Basic Web Project](#1-basic-web-project)
2. [Python Data Science Project](#2-python-data-science-project)
3. [Java Maven Project](#3-java-maven-project)
4. [Node.js Express API](#4-nodejs-express-api)
5. [React Native Mobile App](#5-react-native-mobile-app)
6. [Django Web Application](#6-django-web-application)
7. [Golang Microservice](#7-golang-microservice)
8. [Unity Game Project](#8-unity-game-project)
9. [Ruby on Rails Application](#9-ruby-on-rails-application)
10. [Flutter Mobile App](#10-flutter-mobile-app)
11. [Common Patterns](#11-common-patterns)

## 1. Basic Web Project

Description: A simple ruleset for a typical web project, including HTML, CSS, and JavaScript files.

```json
{
  "rules": [
    [
      ["extension", "oneOf", ["html", "css", "js"]]
    ]
  ],
  "globalExcludeRules": [
    ["folder", "contains", "node_modules"],
    ["basename", "oneOf", ["package-lock.json", "yarn.lock"]]
  ],
  "always": {
    "include": ["index.html", "style.css", "script.js", "README.md"]
  }
}
```

## 2. Python Data Science Project

Description: A ruleset for a Python data science project, focusing on Python files, Jupyter notebooks, and data files, while ensuring sensitive files are excluded.

```json
{
    "rules": [
        [
            ["extension", "oneOf", ["py", "ipynb", "csv", "json"]]
        ],
        [
            ["folder", "startsWith", "data"],
            ["extension", "oneOf", ["csv", "json", "xlsx"]]
        ]
    ],
    "globalExcludeRules": [
        ["folder", "containsAny", ["__pycache__", ".ipynb_checkpoints"]],
        ["basename", "startsWith", "."],
        ["basename", "=", "large_dataset.csv"]
    ],
    "always": {
        "include": ["requirements.txt", "README.md", "setup.py"],
        "exclude": ["config.ini", "secrets.yaml"]
    }
}
```

## 3. Java Maven Project

Description: A ruleset for a Java project using Maven, focusing on Java source files and important Maven configuration files.

```json
{
  "rules": [
    [
      ["folder", "startsWith", "src"],
      ["extension", "=", "java"]
    ]
  ],
  "globalExcludeRules": [
    ["folder", "startsWithAny", ["target", ".idea", ".settings"]],
    ["extension", "oneOf", ["class", "jar"]],
    ["basename", "=", "large-test-data.xml"]
  ],
  "always": {
    "include": ["pom.xml", "README.md", "src/main/resources/application.properties"]
  }
}
```

## 4. Node.js Express API

Description: A ruleset for a Node.js Express API project, focusing on JavaScript files and API-related configurations, while excluding a specific large data file.

```json
{
    "rules": [
        [
            ["extension", "oneOf", ["js", "json"]]
        ],
        [
            ["folder", "startsWith", "routes"]
        ],
        [
            ["folder", "startsWith", "middlewares"]
        ]
    ],
    "globalExcludeRules": [
        ["folder", "startsWithAny", ["node_modules", "logs", "coverage"]],
        ["basename", "oneOf", [".env", "npm-debug.log", "secrets.js", "large-data-file.json"]]
    ],
    "always": {
        "include": ["package.json", "app.js", "config/database.js", "README.md"]
    }
}
```

## 5. React Native Mobile App

Description: A ruleset for a React Native mobile app project, including JavaScript/TypeScript files and mobile-specific configurations.

```json
{
  "rules": [
    [
      ["extension", "oneOf", ["js", "jsx", "ts", "tsx"]]
    ],
    [
      ["folder", "startsWith", "src"]
    ],
    [
      ["folder", "startsWithAny", ["ios", "android"]],
      ["extension", "oneOf", ["swift", "kotlin", "gradle", "pbxproj", "plist", "xml"]]
    ]
  ],
  "globalExcludeRules": [
    ["folder", "startsWithAny", ["node_modules", "build", ".gradle", "ios/Pods", "android/.idea"]],
    ["extension", "oneOf", ["apk", "ipa"]]
  ],
  "always": {
    "include": ["App.js", "package.json", "metro.config.js", "babel.config.js", "README.md"]
  }
}
```

## 6. Django Web Application

Description: A ruleset for a Django web application, focusing on Python files, templates, and static assets.

```json
{
  "rules": [
    [
      ["extension", "oneOf", ["py", "html", "css", "js"]]
    ],
    [
      ["folder", "startsWith", "templates"]
    ],
    [
      ["folder", "startsWith", "static"]
    ]
  ],
  "globalExcludeRules": [
    ["folder", "startsWithAny", ["__pycache__", "migrations", "venv", "media/large_uploads"]],
    ["basename", "oneOf", ["db.sqlite3", "local_settings.py"]]
  ],
  "always": {
    "include": ["manage.py", "requirements.txt", "README.md"]
  }
}
```

## 7. Golang Microservice

Description: A ruleset for a Golang microservice project, focusing on Go source files and configuration files.

```json
{
  "rules": [
    [
      ["extension", "=", "go"]
    ],
    [
      ["folder", "startsWithAny", ["cmd", "internal", "pkg"]]
    ],
    [
      ["extension", "oneOf", ["yaml", "toml", "json"]]
    ]
  ],
  "globalExcludeRules": [
    ["folder", "startsWithAny", ["vendor", "bin"]],
    ["extension", "=", "exe"],
    ["basename", "=", "secrets.yaml"]
  ],
  "always": {
    "include": ["go.mod", "go.sum", "Dockerfile", "Makefile", "README.md"]
  }
}
```

## 8. Unity Game Project

Description: A ruleset for a Unity game project, focusing on C# scripts, Unity asset files, and scene files.

```json
{
  "rules": [
    [
      ["extension", "oneOf", ["cs", "unity", "prefab", "mat", "asset"]]
    ],
    [
      ["folder", "startsWith", "Assets"]
    ]
  ],
  "globalExcludeRules": [
    ["folder", "startsWithAny", ["Library", "Temp", "obj", "Builds", "Assets/Plugins/Paid"]],
    ["extension", "oneOf", ["meta", "log"]]
  ],
  "always": {
    "include": ["ProjectSettings/ProjectSettings.asset", "Assets/Scenes/MainScene.unity", "README.md"]
  }
}
```

## 9. Ruby on Rails Application

Description: A ruleset for a Ruby on Rails application, focusing on Ruby files, views, and important configuration files.

```json
{
  "rules": [
    [
      ["extension", "oneOf", ["rb", "erb", "html", "scss", "coffee"]]
    ],
    [
      ["folder", "startsWithAny", ["app", "config", "db", "lib", "test"]]
    ]
  ],
  "globalExcludeRules": [
    ["folder", "startsWithAny", ["tmp", "log", "public/assets"]],
    ["basename", "oneOf", ["schema.rb", "routes.rb", "master.key", "credentials.yml.enc"]]
  ],
  "always": {
    "include": ["Gemfile", "config/database.yml", "README.md"]
  }
}
```

## 10. Flutter Mobile App

Description: A ruleset for a Flutter mobile app project, focusing on Dart files and Flutter-specific configurations.

```json
{
  "rules": [
    [
      ["extension", "=", "dart"]
    ],
    [
      ["folder", "startsWith", "lib"]
    ],
    [
      ["folder", "startsWithAny", ["ios", "android"]],
      ["extension", "oneOf", ["swift", "kotlin", "gradle", "plist", "xml"]]
    ]
  ],
  "globalExcludeRules": [
    ["folder", "startsWithAny", ["build", ".dart_tool", ".pub-cache"]],
    ["extension", "oneOf", ["apk", "ipa", "jar"]],
    ["basename", "=", "flutter_export_environment.sh"]
  ],
  "always": {
    "include": ["pubspec.yaml", "lib/main.dart", "README.md"]
  }
}
```

## 11. Common Patterns

Here are some frequently used patterns that you can incorporate into any ruleset:

### Cross-Platform Path Handling
```json
{
  "globalExcludeRules": [
    ["folder", "containsAny", ["bin", "obj", "out", "build", "dist"]],
    ["extension", "oneOf", ["exe", "dll", "so", "dylib"]]
  ]
}
```

### Security-Focused Exclusions
```json
{
  "globalExcludeRules": [
    ["basename", "oneOf", [".env", "secrets.json", "credentials.json"]],
    ["extension", "oneOf", ["pem", "key", "pfx", "p12"]],
    ["folder", "contains", "secrets"]
  ]
}
```

### Development Files Only
```json
{
  "rules": [
    [
      ["extension", "oneOf", ["js", "ts", "jsx", "tsx", "vue"]],
      ["folder", "startsWith", "src"]
    ]
  ],
  "globalExcludeRules": [
    ["folder", "contains", "test"],
    ["extension", "oneOf", ["min.js", "bundle.js"]]
  ]
}
```

### Documentation Files Only
```json
{
  "rules": [
    [
      ["extension", "oneOf", ["md", "rst", "txt"]],
      ["folder", "startsWith", "docs"]
    ],
    [
      ["basename", "=", "README.md"]
    ]
  ],
  "globalExcludeRules": [
    ["folder", "contains", "draft"],
    ["basename", "startsWith", "_"]
  ]
}
```

These examples cover a wide range of project types and demonstrate various ways to use Ctree's ruleset system. Users can adapt these examples to fit their specific project needs or use them as a starting point for creating their own custom rulesets. Remember that each rule is applied on a file-by-file basis, so rules should be designed to match individual files rather than entire directories.
