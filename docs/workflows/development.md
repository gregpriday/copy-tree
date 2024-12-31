# AI-Assisted Development Workflow with `ctree`

## 1. Introduction

This document outlines a process for developing software features using AI assistance throughout the development lifecycle. It leverages the `ctree` command to interact with codebases efficiently and Claude's large context window for comprehensive code understanding and generation. This process is language-agnostic and adaptable to various project structures.

**Guiding Principles:**

* **AI-Augmented Development:** AI is a core component of the development process, not just a supplementary tool
* **Test-Driven Approach:** Automated tests are prioritized and written alongside or even before feature code
* **Iterative Refinement:** Development is an iterative process of AI-assisted code generation, testing, and refinement
* **Modular Design:** Codebases should be structured to facilitate targeted code interaction using `ctree` and rulesets
* **Efficiency and Speed:** The process aims to maximize development speed and efficiency while maintaining code quality
* **Maximize AI Context:** Always aim to provide AI assistants with as much relevant code as possible within their context limits

## 2. Tools and Technologies

* **AI Assistant:** Primary tool for code generation, testing, debugging, documentation, and planning (e.g., Claude)
* **`ctree` Command:** Command-line tool for copying and filtering project files and directories
* **GitHub:** Version control, issue tracking, pull requests, and project management
* **Testing Framework:** Any suitable framework for your project's language (PHPUnit, pytest, Jest, etc.)
* **CI/CD:** Your preferred continuous integration and deployment tools

## 3. Development Workflow

### 3.1. Feature Definition and Planning

1. **Identify Feature:** Define the new feature based on user feedback, market analysis, or strategic goals. Create a GitHub issue using your project's feature request template.

2. **Initial Codebase Analysis with AI:**
    * Determine if the entire codebase can fit into the AI's context window:
        * If **YES**: Use `ctree` to copy the entire codebase:
          ```bash
          ctree
          ```
        * If **NO**: (Create a ruleset)[./rulesets.md] for this part of the codebase:
          ```bash
          # Get structure and file previews
          ctree --max-lines=20
          
          # Get ruleset documentation
          ctree --ruleset-docs
          ```

    * Create a targeted ruleset for the feature:
      ```
      Example Prompt:
      "Here is the project structure and the beginning of each file, along with the ruleset documentation.
      We need to add support for filtering files using AI-based semantic search. Which files and folders 
      would be most relevant? Please create a ruleset that includes all necessary files, including test 
      files. Err on the side of including more files rather than fewer."
      ```

    * Save the ruleset (e.g., `.ctree/rulesets/semantic-search.json`) and verify:
      ```bash
      ctree --ruleset=semantic-search --only-tree
      ```

    * Get the full context:
      ```bash
      ctree --ruleset=semantic-search
      ```

3. **Implementation Planning:**
    * Provide the AI with code context and feature requirements
    * Request a detailed implementation plan including:
        * Files to create/modify
        * Function/method signatures
        * Data structures
        * Test case outlines
        * Potential challenges

   Example prompt:
   ```
   "Here is the relevant code for implementing semantic search:
   [Paste code from ctree]
   
   We need to add support for filtering files based on semantic similarity using the Jina AI API. 
   Please generate a detailed implementation plan. Include specific files to modify, function 
   signatures, and required changes. Also outline a testing strategy and potential challenges."
   ```

### 3.2. AI-Assisted Code Generation

1. **Provide Context:**
    * Use `ctree` with appropriate ruleset/filters
    * Include implementation plan and requirements
    * Add relevant documentation/examples

2. **Request Code Generation:**
   ```
   "Using the implementation plan and code context, please generate the code for the 
   semantic search feature. Focus on the JinaCodeSearch class and related components. 
   Ensure PSR-12 compliance and include detailed comments."
   ```

3. **Iterative Refinement:**
    * Review generated code
    * Provide feedback to AI
    * Use `ctree` for targeted refinements
    * Repeat until quality targets are met

### 3.3. AI-Assisted Test Creation

1. **Generate Test Cases:**
    * Provide feature code and test outlines
    * Request comprehensive test coverage
   ```
   "Here's the semantic search implementation. Please generate PHPUnit tests covering:
   - Basic functionality
   - Edge cases
   - Error handling
   - API interaction
   Include both unit and integration tests."
   ```

2. **Review and Refine:**
    * Verify test coverage
    * Add missing test cases
    * Ensure test organization follows project standards

### 3.4. Iterative Development

1. **Code Implementation:**
    * Integrate AI-generated code
    * Follow project structure and standards
    * Document key decisions

2. **Testing:**
    * Run test suite
    * Debug failures with AI assistance
   ```
   "The following tests are failing:
   [Paste test output]
   
   Here's the relevant code:
   [Paste code from ctree]
   
   Can you identify the issue and suggest a fix?"
   ```

3. **Refinement:**
    * Address test failures
    * Optimize performance
    * Improve error handling

### 3.5. Documentation and Review

1. **AI-Assisted Documentation:**
    * Generate initial documentation
    * Include API references
    * Provide usage examples
    * Add integration guides

2. **Review:**
    * Technical accuracy
    * Code quality
    * Security considerations
    * Project standards compliance

### 3.6. Deployment

1. **Preparation:**
    * Final testing
    * Documentation review
    * Change log updates

2. **Release:**
    * Version tagging
    * Package publication
    * Announcement/changelog

## 4. Example: Adding Semantic Search to ctree

Let's walk through implementing semantic search filtering:

1. **Planning:**
   ```bash
   # Get project overview
   ctree --max-lines=20
   ctree --ruleset-docs
   ```

   Create ruleset for semantic search:
   ```json
   {
     "rules": [
       [
         ["folder", "startsWith", "src/Filters"],
         ["extension", "=", "php"]
       ],
       [
         ["folder", "startsWith", "tests"],
         ["basename", "contains", "Filter"]
       ]
     ],
     "always": {
       "include": [
         "composer.json",
         "docs/ai-features.md"
       ]
     }
   }
   ```

2. **Code Generation:**
   ```bash
   ctree --ruleset=semantic-search
   ```

   Generate implementation plan and code for:
    * JinaCodeSearch class
    * Integration with FilterPipeline
    * Configuration handling
    * Error management

3. **Test Creation:**
    * Unit tests for JinaCodeSearch
    * Integration tests with FilterPipeline
    * Mock API responses
    * Error case testing

4. **Refinement:**
    * Implement feedback
    * Optimize performance
    * Improve error handling
    * Update documentation

## 5. Best Practices

* **Context Management:**
    * Provide complete, relevant code context
    * Use (rulesets)[./rulesets.md] for large codebases
    * Include documentation and examples

* **Iterative Development:**
    * Start with basic functionality
    * Add features incrementally
    * Test throughout development

* **Security:**
    * Review AI-generated code carefully
    * Validate inputs and outputs
    * Handle errors gracefully

* **Documentation:**
    * Document as you develop
    * Include examples
    * Explain key decisions

## 6. Conclusion

This AI-integrated development process, leveraging `ctree` and AI capabilities, offers a streamlined approach to software development. By combining AI assistance with human expertise and a well-defined workflow, teams can accelerate development while maintaining high quality standards. The process is adaptable and can be refined based on project needs and team feedback.

The emphasis on structured, test-driven development, combined with AI assistance, enables rapid and reliable feature development. Using `ctree` to manage code context ensures AI tools have the information they need while keeping the process efficient and organized.
