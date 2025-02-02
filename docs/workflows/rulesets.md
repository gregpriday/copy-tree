# AI-Assisted Ruleset Creation Workflow with `ctree`

## 1. Introduction

This document outlines a process for creating and refining `ctree` rulesets using AI assistance. Rulesets are crucial for defining which files and directories are included or excluded when copying parts of your project. This workflow leverages `ctree`'s ability to interact with codebases and AI's large context window for comprehensive code understanding and ruleset generation.

**Guiding Principles:**

*   **AI-Augmented Ruleset Creation:** AI assists in generating and refining rulesets based on project structure and user needs.
*   **Iterative Refinement:** Ruleset creation is an iterative process of AI suggestion, testing, and feedback.
*   **Modular Rulesets:** Create multiple, focused rulesets for different parts of your project or different use cases.
*   **Full Context Sharing:** Provide AI assistants with comprehensive project context using `ctree`'s output to maximize accuracy.
*   **Test-Driven Approach:** Verify rulesets with `ctree`'s output and adjust based on actual results.

## 2. Tools and Technologies

*   **AI Assistant:** Primary tool for ruleset generation, refinement, and testing suggestions (e.g., Claude, Gemini, ChatGPT-4).
*   **`ctree` Command:** Command-line tool for copying and filtering project files and directories.
*   **Text Editor:** For editing and saving ruleset JSON files.

## 3. Ruleset Creation Workflow

### 3.1. Initial Project Analysis

1. **Provide Project Context to AI:**

    *   Use `ctree` to capture a broad overview of your project structure:

        ```bash
        # Get structure and file previews (first 20 lines of each file)
        ctree --max-lines=20
        ```

    *   Also, provide `ctree`'s ruleset documentation to the AI:

        ```bash
        # Get ruleset documentation
        ctree --ruleset-docs
        ```

    *   Paste both outputs into your AI assistant's chat, along with a description of your project's purpose and what you aim to achieve with the ruleset.

   **Example Prompt:**

    ```text
    Here's an overview of my project's structure and the beginning of each file:
    [Paste output from `ctree --max-lines=20`]

    And here's the documentation for ctree's ruleset system:
    [Paste output from `ctree --ruleset-docs`]

    I'm building a [Project Description, e.g., web application for online courses using Laravel]. 
    I want to create a ruleset that includes all files related to the user authentication system, 
    including controllers, models, views, and tests.
    ```

### 3.2. AI-Assisted Ruleset Generation

1. **Request Initial Ruleset:**

    *   Ask the AI to generate an initial ruleset based on your description and the provided project context.

   **Example Prompt:**

    ```text
    Based on the project overview and ruleset documentation, could you generate a ctree ruleset 
    that includes all files related to user authentication? Please provide the complete JSON 
    for the ruleset, including any necessary global exclude rules.
    ```

2. **Review and Save:**

    *   Carefully review the AI-generated ruleset.
    *   Save the ruleset to a `.json` file within your project's `.ctree/rulesets/` directory. Use a descriptive name (e.g., `authentication.json`).

### 3.3. Iterative Ruleset Refinement

1. **Test the Ruleset:**

    *   Use `ctree` with the `--only-tree` and `--display` options to test your new ruleset without copying any content to the clipboard. You can also add the `--output` option to save the output to a file for easier review:

        ```bash
        ctree --ruleset=authentication --only-tree --display
        ```

    *   Carefully examine the output. Are the expected files and directories included? Are any irrelevant files included? Are any important files missing?

2. **Provide Feedback to AI:**

    *   Share the `ctree` output with your AI assistant.
    *   Describe any issues you found:
        *   "The ruleset is missing files related to password reset."
        *   "It's including files from the `vendor` directory, which should be excluded."
        *   "The `User` model is included, but the related `UserTest` is missing."

   **Example Prompt:**

    ```text
    Here's the output of `ctree --ruleset=authentication --only-tree`:
    [Paste ctree output]

    The ruleset is mostly correct, but it's missing the password reset controller and tests. 
    It's also including some files from the `storage/logs` directory which should be excluded. 
    Could you refine the ruleset based on this feedback?
    ```

3. **Iterate:**

    *   Repeat steps 1-2, providing feedback and refining the ruleset until it accurately captures the desired files.
    *   Use specific examples in your feedback to guide the AI towards the correct solution.

### 3.4. Creating Multiple Rulesets

1. **Identify Different Project Areas:**

    *   Determine distinct parts of your project that might require separate rulesets (e.g., `frontend`, `backend`, `api`, `docs`, `tests`, `database`).

2. **Repeat the Process:**

    *   Follow steps 3.1 to 3.3 to create and refine rulesets for each identified area.
    *   Use descriptive names for your ruleset files (e.g., `frontend.json`, `backend.json`, `docs.json`).

### 3.5. Advanced Ruleset Techniques

1. **Leverage `always` Include/Exclude:**

    *   Use the `always` section in your rulesets to explicitly include or exclude specific files or directories, regardless of other rules.

    ```json
    "always": {
        "include": ["README.md", "config/app.php"],
        "exclude": ["storage/logs"]
    }
    ```

2. **Use `globalExcludeRules` Effectively:**

    *   Define common exclusion patterns in `globalExcludeRules` to avoid redundancy across multiple rulesets.

    ```json
    "globalExcludeRules": [
        ["folder", "startsWithAny", ["node_modules", "vendor", "storage/framework"]],
        ["basename", "startsWith", "."]
    ]
    ```

3. **Experiment with Different Operators:**

    *   Explore the full range of operators available in `ctree` rulesets, including `regex`, `glob`, `containsAny`, `notOneOf`, etc.
    *   Refer to the [Fields and Operations Reference](../rulesets/fields-and-operations.md) for detailed documentation.

4. **Combine Rulesets:**
    *   You can combine multiple rulesets by using a `workspaces.json` file. This allows you to create even more targeted selections of files for copying. See [Using Multiple Rulesets](../rulesets/multiple-rulesets.md) for more information.

## 4. Best Practices

*   **Start Broad, Then Narrow:** Begin with a general ruleset, then refine it iteratively.
*   **Test Frequently:** Use `ctree --ruleset=<name> --only-tree --display` after each change to verify the ruleset's behavior.
*   **Provide Specific Feedback:** Clearly articulate issues and desired changes to the AI assistant.
*   **Document Your Rulesets:** Add comments within your ruleset JSON files to explain the purpose of each rule.
*   **Version Control:** Store your `.ctree` directory under version control to track changes to your rulesets.
*   **Use a Default Ruleset:** Create a `ruleset.json` file in your `.ctree` directory to serve as the default when no specific ruleset is specified.

## 5. Conclusion

This workflow provides a structured approach to creating and refining `ctree` rulesets with the assistance of AI. By leveraging AI's ability to understand project structure and user intent, combined with `ctree`'s powerful filtering capabilities, you can create precise and maintainable rulesets that streamline your development process. Remember that iteration and testing are key to achieving the desired results. Through continuous feedback and refinement, you can develop a set of rulesets that perfectly suit your project's needs and enhance your workflow.
