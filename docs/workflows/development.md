# AI-Assisted Development Workflow with `ctree` and LLMs

This workflow integrates copytree with modern LLMs (such as ChatGPT) to enable rapid, context-rich, and iterative software development. By extracting and sharing precise code context using copytree and then feeding that context into an LLM, teams can accelerate feature development, generate high-quality tests, and continuously refine both code and documentation.

---

## 1. Introduction

This document describes an AI-integrated development process that blends copytree’s powerful context extraction with LLMs’ (e.g., ChatGPT’s) code generation, testing, debugging, and documentation capabilities. The approach is language-agnostic, emphasizing modularity, iterative refinement, and full-context collaboration between human developers and AI.

**Key Principles:**

- **Deep Context Provisioning:** Use copytree to generate comprehensive code snapshots (directory structure, file previews, metadata) so that LLMs receive rich context.
- **Dynamic Prompt Engineering:** Craft and refine prompts to LLMs (via ChatGPT or similar) that clearly articulate requirements, constraints, and examples.
- **Iterative, Feedback-Driven Development:** Develop in short cycles—generate code, test it, review AI suggestions, and adjust prompts to refine the outcome.
- **Modularity and Focus:** Leverage copytree’s ruleset system to isolate relevant code modules, reducing noise and improving the precision of LLM outputs.
- **Test-Driven and Secure:** Generate tests and verify AI output rigorously, ensuring that automated code meets quality, security, and performance standards.
- **Seamless Integration:** Connect the iterative outputs (code, tests, documentation) with your CI/CD pipelines and version control for a streamlined release process.

---

## 2. Tools and Technologies

- **LLMs & ChatGPT:** Use ChatGPT (or similar) for generating, reviewing, and refining code, tests, and documentation.
- **copytree Command:** Extract targeted code context and file structures using customizable JSON rulesets.
- **GitHub:** For version control, issue tracking, pull requests, and collaboration.
- **Testing Frameworks:** Such as PHPUnit, pytest, Jest, etc., for validating new features.
- **CI/CD Systems:** Your existing integration and deployment tools.
- **Prompt Management Tools:** (Optional) Use dedicated prompt libraries or wrappers to manage iterative LLM interactions.

---

## 3. Development Workflow

### 3.1. Feature Definition and Planning

1. **Feature Identification & Issue Tracking:**
   - Define new features based on user feedback, market analysis, or strategic priorities.
   - Log the feature as a GitHub issue, including detailed requirements and acceptance criteria.

2. **Context Extraction via copytree:**
   - **Full Context Extraction:**  
     If the project is small, run:
     ```bash
     ctree
     ```
   - **Targeted Context with Rulesets:**  
     For larger projects, create and use a ruleset:
     ```bash
     ctree --max-lines=20
     ctree --ruleset-docs
     ```
     *Example Prompt to AI:*  
     > “Based on the project overview and sample file previews provided, generate a ruleset that captures all files related to [feature area] (including tests and supporting modules). Aim to err on the side of inclusion.”
   - Save the resulting ruleset (e.g., `.ctree/rulesets/semantic-search.json`) and verify:
     ```bash
     ctree --ruleset=semantic-search --only-tree
     ```
   - Retrieve full context:
     ```bash
     ctree --ruleset=semantic-search
     ```

3. **Implementation Planning with AI:**
   - Provide the AI with the extracted context (via copytree output) plus the feature requirements.
   - **Prompt Example:**
     > “Here is our project structure and file previews:
     > [Paste copytree output]
     >
     > We need to implement semantic search using the Jina AI API. Please propose an implementation plan detailing which files to modify or add, suggested function signatures, data structures, and a testing strategy. Also, flag any potential challenges.”
   - Use AI’s output to create a roadmap of changes, design decisions, and test cases.

### 3.2. AI-Assisted Code Generation

1. **Context Provisioning & Prompting:**
   - Re-run copytree with the appropriate ruleset to capture the latest context.
   - Combine the current implementation plan with up-to-date code excerpts.
   - **Prompt Example:**
     > “Using the current context and our implementation plan below, please generate the updated code for the semantic search feature. Focus on the `JinaCodeSearch` class and its integration with our FilterPipeline. Ensure PSR-12 compliance and include detailed comments.”
   - Adjust parameters (temperature, max tokens) if needed to balance creativity and consistency.

2. **Iterative Refinement:**
   - Review the AI-generated code in your code editor.
   - Provide specific feedback (“Refactor this function for clarity”, “Improve error handling”, etc.).
   - Use targeted copytree snapshots to supply fresh context when re-prompting the LLM.
   - Iterate until the code meets functional and stylistic requirements.

### 3.3. AI-Assisted Test Creation

1. **Test Planning and Generation:**
   - Provide the feature code and context to the LLM, along with desired test scenarios.
   - **Prompt Example:**
     > “Below is the new semantic search implementation:
     > [Paste code excerpt]
     >
     > Please generate PHPUnit tests covering basic functionality, edge cases, error handling, and API interactions. Include both unit and integration tests.”
   - Validate that the generated tests conform to your project’s structure.

2. **Test Review and Iteration:**
   - Run the tests, identify failures, and capture output.
   - Ask the LLM for clarifications or fixes using targeted prompts (“The following test is failing – please adjust the error handling in function X”).
   - Refine until tests pass and cover all critical cases.

### 3.4. Iterative Development and Debugging

1. **Integration and Code Review:**
   - Merge the AI-generated code into your branch.
   - Conduct peer reviews using both traditional methods and by leveraging LLM-generated code summaries.
   - Document major architectural decisions.

2. **Testing and Debugging:**
   - Run the full test suite; use CI/CD tools to catch regressions.
   - For failures, supply error outputs and relevant code excerpts to the LLM:
     > “The following tests are failing:
     > [Paste error output]
     >
     > Here is the relevant code snippet:
     > [Paste code]
     >
     > Can you analyze the issue and suggest corrective changes?”
   - Iterate with rapid feedback loops until the code is stable.

3. **Performance and Security Optimization:**
   - Use the LLM to suggest performance improvements or security hardening measures.
   - Validate suggestions with profiling tools and security audits.

### 3.5. Documentation and Knowledge Transfer

1. **AI-Generated Documentation:**
   - Use LLMs to create or refine API documentation, inline comments, and user guides.
   - **Prompt Example:**
     > “Based on the updated semantic search feature code, generate comprehensive documentation including usage examples, API reference details, and integration guidelines.”
   - Ensure documentation is reviewed by team members for technical accuracy.

2. **Knowledge Base Updates:**
   - Integrate documentation updates into your project’s wiki or knowledge base.
   - Record lessons learned and best practices for future reference.

### 3.6. Deployment and Release

1. **Final Preparations:**
   - Conduct a final round of testing and code reviews.
   - Update change logs, version numbers, and prepare release notes.
   - Ensure that all AI-assisted modifications are clearly documented for future audits.

2. **Release Execution:**
   - Tag the release in Git.
   - Deploy using your CI/CD pipeline.
   - Announce the release via appropriate channels and update public documentation.

---

## 4. Example: Implementing Semantic Search in ctree

### 4.1. Planning

- **Extract Context:**
  ```bash
  ctree --max-lines=20
  ctree --ruleset-docs
  ```
- **Create a Semantic Search Ruleset:**
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

### 4.2. Code Generation & Testing

- **Generate Code:**
  ```bash
  ctree --ruleset=semantic-search
  ```
    - Request AI-generated revisions for:
        - The `JinaCodeSearch` class.
        - Integration with the FilterPipeline.
        - Robust configuration and error handling.
- **Generate Tests:**
    - Ask for comprehensive unit and integration tests that simulate API responses and cover all error paths.
    - Validate and refine tests until they pass.

### 4.3. Iteration and Deployment

- **Iterate:**
    - Use the iterative cycle described above until the feature is robust.
    - Incorporate peer feedback and LLM suggestions.
- **Deploy:**
    - Merge changes into the main branch, tag the release, and deploy via your CI/CD system.
    - Update all relevant documentation and announce the release.

---

## 5. Best Practices and Considerations

- **Maximize Context Quality:**  
  Always run copytree with a targeted ruleset so that the AI receives relevant, concise context.
- **Prompt Engineering:**  
  Craft clear and specific prompts. Provide examples, and use follow-up prompts to refine the output.
- **Iterative Feedback Loops:**  
  Use short cycles of AI generation, review, and refinement. Document what worked (and what didn’t) for future iterations.
- **Security & Validation:**  
  Review every AI-generated snippet for security vulnerabilities and logic errors.
- **Documentation:**  
  Update documentation alongside code. Use AI to generate drafts but ensure human review.
- **Integration:**  
  Integrate AI suggestions with your CI/CD pipeline to automatically run tests and perform code analysis.
- **Experimentation:**  
  Test different LLM configurations (temperature, prompt structure) to balance creativity with consistency.

---

## 6. Conclusion

By combining copytree’s efficient context extraction with LLMs’ powerful code-generation and analysis capabilities, this workflow transforms feature development into a highly iterative, feedback-driven process. Embrace dynamic prompt engineering and maintain rigorous testing and documentation practices to ensure that AI assistance leads to higher-quality, more secure, and rapidly delivered software. This process not only accelerates development but also builds a knowledge base for continuous improvement and future innovation.

```
