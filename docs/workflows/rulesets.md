# AI-Assisted Ruleset Creation Workflow with `ctree`

## 1. Introduction

This document outlines a process for creating and refining `ctree` rulesets with the assistance of AI. Rulesets are essential for defining which files and directories are included or excluded when copying parts of your project. This workflow leverages `ctree`’s capability to interact with codebases and the large context windows of modern AI assistants (such as Claude, Gemini, or ChatGPT-4) to generate and fine-tune rulesets based on your project structure and requirements.

**Guiding Principles:**

- **AI-Augmented Ruleset Creation:** AI assists in generating and refining rulesets tailored to your project’s structure and user needs.
- **Iterative Refinement:** The creation of rulesets is an iterative process involving AI suggestions, testing, and continuous feedback.
- **Modular Rulesets:** Develop multiple, focused rulesets for different parts of your project or for distinct use cases.
- **Full Context Sharing:** Provide your AI assistant with comprehensive project context using `ctree`’s output to maximize accuracy.
- **Test-Driven Approach:** Verify the effectiveness of your rulesets with `ctree`’s output and adjust based on real results.

---

## 2. Tools and Technologies

- **AI Assistant:** The primary tool for generating, refining, and testing rulesets (e.g., Claude, Gemini, ChatGPT-4).
- **`ctree` Command:** A command-line utility for copying and filtering project files and directories.
- **Text Editor:** For editing and saving ruleset JSON files.

---

## 3. Ruleset Creation Workflow

### 3.1. Initial Project Analysis

1. **Provide Project Context to AI:**

    - Use `ctree` to capture a broad overview of your project structure along with file previews:

      ```bash
      # Get structure and file previews (first 20 lines of each file)
      ctree --max-lines=20
      ```

    - Also, retrieve `ctree`’s ruleset documentation:

      ```bash
      # Get ruleset documentation
      ctree --ruleset-docs
      ```

    - Paste both outputs into your AI assistant’s chat along with a brief description of your project’s purpose and what you aim to achieve with the ruleset.

   **Example Prompt:**

    ```text
    Here’s an overview of my project’s structure and a preview of each file:
    [Paste output from `ctree --max-lines=20`]
    
    And here’s the documentation for ctree’s ruleset system:
    [Paste output from `ctree --ruleset-docs`]
    
    I’m building a [Project Description, e.g., web application for online courses using Laravel]. I want to create a ruleset that includes all files related to the user authentication system—covering controllers, models, views, and tests.
    ```

### 3.2. AI-Assisted Ruleset Generation

1. **Request an Initial Ruleset:**

    - Ask the AI to generate a starting ruleset based on your provided context.

   **Example Prompt:**

    ```text
    Based on the project overview and ruleset documentation provided, could you generate a ctree ruleset that includes all files related to user authentication? Please provide the complete JSON for the ruleset, including any necessary global exclude rules.
    ```

2. **Review and Save:**

    - Carefully review the AI-generated ruleset.
    - Save the resulting JSON file in your project’s `.ctree/rulesets/` directory using a descriptive name (e.g., `authentication.json`).

### 3.3. Iterative Ruleset Refinement

1. **Test the Ruleset:**

    - Use `ctree` with the `--only-tree` and `--display` options to test your new ruleset without copying file contents. Optionally, use the `--output` option to save the result to a file for easier review:

      ```bash
      ctree --ruleset=authentication --only-tree --display
      ```

    - Examine the output carefully. Verify that the expected files and directories are included and that any irrelevant files are excluded.

2. **Provide Feedback to AI:**

    - Share the `ctree` output with your AI assistant and describe any issues:

      **Example Feedback:**

      ```text
      Here is the output of `ctree --ruleset=authentication --only-tree`:
      [Paste output here]
      
      The ruleset is mostly correct, but it’s missing files related to password reset and tests. Additionally, it includes some files from the `storage/logs` directory that should be excluded. Could you refine the ruleset based on this feedback?
      ```

3. **Iterate:**

    - Repeat the testing and feedback steps until the ruleset accurately captures the desired files. Use specific examples in your feedback to guide the AI.

### 3.4. Creating Multiple Rulesets

1. **Identify Different Project Areas:**

    - Determine distinct sections of your project that might benefit from separate rulesets (e.g., `frontend`, `backend`, `api`, `docs`, `tests`, `database`).

2. **Repeat the Process:**

    - Follow steps 3.1 to 3.3 for each identified area.
    - Save each ruleset with a descriptive name (e.g., `frontend.json`, `backend.json`, `docs.json`).

### 3.5. Advanced Ruleset Techniques

1. **Leverage `always` Include/Exclude:**

    - Use the `always` section to explicitly include or exclude specific files or directories regardless of other rules.

      ```json
      "always": {
          "include": ["README.md", "config/app.php"],
          "exclude": ["storage/logs"]
      }
      ```

2. **Use `globalExcludeRules` Effectively:**

    - Define common exclusion patterns in `globalExcludeRules` to avoid redundancy across rulesets.

      ```json
      "globalExcludeRules": [
          ["folder", "startsWithAny", ["node_modules", "vendor", "storage/framework"]],
          ["basename", "startsWith", "."]
      ]
      ```

3. **Experiment with Different Operators:**

    - Explore various operators available in `ctree` rulesets (e.g., `regex`, `glob`, `containsAny`, `notOneOf`).
    - Refer to the [Fields and Operations Reference](../rulesets/fields-and-operations.md) for details.

4. **Combine Rulesets:**

    - Combine multiple rulesets by using a `workspaces.json` file, enabling even more targeted file selections. See [Using Multiple Rulesets](../rulesets/multiple-rulesets.md) for more information.

---

## 4. Best Practices

- **Start Broad, Then Narrow:** Begin with a general ruleset and refine it iteratively.
- **Test Frequently:** Use commands like `ctree --ruleset=<name> --only-tree --display` after each change to verify behavior.
- **Provide Specific Feedback:** Clearly describe issues and desired changes to your AI assistant.
- **Document Your Rulesets:** Include comments in your JSON files to explain the purpose of each rule.
- **Version Control:** Store your `.ctree` directory under version control to track and manage changes.
- **Use a Default Ruleset:** Maintain a `ruleset.json` file in your `.ctree` directory as the default when no specific ruleset is provided.

---

## 5. Conclusion

This workflow provides a structured, AI-assisted approach to creating and refining `ctree` rulesets. By leveraging AI’s ability to understand project structure and user intent—combined with `ctree`’s powerful filtering capabilities—you can develop precise, maintainable rulesets that streamline your development process. Remember that continuous iteration and testing are key to achieving optimal results. With regular feedback and refinement, you can develop a set of rulesets that perfectly suit your project’s needs and significantly enhance your workflow.
