# AI-Driven Design Workflow with SvelteKit, Tailwind CSS, and Claude

This document outlines a process for designing web applications using an iterative, AI-assisted approach. It leverages SvelteKit for project structure, Tailwind CSS for styling, and Claude as the AI design partner. This workflow focuses on organic design evolution, starting from broad strokes and refining individual components through continuous feedback.

## 1. Introduction

This workflow embraces the strengths of AI in generating design variations and refining concepts based on user feedback. It's particularly well-suited for designers who are comfortable working with code and enjoy a collaborative approach with AI tools.

**Guiding Principles:**

*   **AI as a Design Partner:**  Claude acts as a design collaborator, generating ideas, iterating on designs, and providing feedback.
*   **Iterative Refinement:** The design evolves through continuous feedback loops, starting with general layouts and moving to detailed component design.
*   **Component-Based Design:** SvelteKit's component structure is used to isolate and refine design elements individually.
*   **Visual Feedback Driven:** Design decisions are made based on visual output and user preferences, allowing the design to emerge organically.
*   **Code-Centric Approach:** Design is expressed and manipulated directly through Svelte and Tailwind code.
*   **Full Context Design:** Use `ctree` to give the AI assistant full context on the project, even on small changes.

## 2. Tools and Technologies

*   **[SvelteKit](https://svelte.dev/docs/kit/introduction):** A framework for building web applications with a focus on developer experience and performance. Its component-based structure aligns well with this iterative design workflow.
*   **[Tailwind CSS](https://tailwindcss.com/):** A utility-first CSS framework that enables rapid styling through composable utility classes. Its design system is highly customizable and well-suited for AI-assisted design.
*   **[Claude](https://claude.ai/):** The chosen AI assistant for this workflow due to its large context window, ability to understand code, and strong visual reasoning capabilities.
*   **[ctree](https://github.com/gregpriday/copy-tree):** Command-line tool for copying project files and directories, used to provide Claude with full project context.

**Note:** This document assumes familiarity with SvelteKit and Tailwind CSS. Please refer to their official documentation for setup and usage instructions.

## 3. Design Workflow

### 3.1. Initial Project Setup

1. **Create a new SvelteKit project:**

    ```bash
    npm create svelte@latest my-design-project
    cd my-design-project
    npm install
    ```

2. **Install Tailwind CSS:**

   Follow the official Tailwind CSS installation guide for SvelteKit: [Install Tailwind CSS with SvelteKit](https://tailwindcss.com/docs/installation/framework-guides).

3. **Set up a basic project structure:**

   Create initial routes (`+page.svelte` files) for your main pages (e.g., Home, About, Contact).

### 3.2. Initial Design Concept

1. **Describe your project to Claude and provide initial project context:**

   Provide a detailed description of your project's purpose, target audience, desired features, and overall aesthetic. Even though your project is in its early stages, it's beneficial to give Claude the full context using `ctree`.

    ```bash
    # Copy the entire project to your clipboard
    ctree
    ```

    ```text
    Here is my current project code:
    [Paste ctree output here]

    I'm building a website for a modern, minimalist online portfolio showcasing my photography. 
    It should have a clean, spacious layout with a focus on large, high-quality images. 
    The color scheme should be predominantly monochrome with subtle accent colors. 
    Key pages include a Home page with a hero image and project grid, an About page, 
    and a Contact page. I want the design to feel elegant, professional, and visually engaging.
    ```

2. **Request initial design for core components one at a time:**

   Ask Claude to generate Svelte components one by one, starting with the most important, usually the `Header`, `Footer`, or a key element of your `Home` page. Even though you've already provided the full project context, it can be helpful to reiterate the project description when requesting each component.

   **Example Prompts:**

    ```text
    Using SvelteKit and Tailwind CSS, can you design a Header component based on the project description? Please provide the complete code for the component.
    ```

    ```text
    Using SvelteKit and Tailwind CSS, can you design a Footer component for the website? Please provide the complete code.
    ```

    ```text
    Using SvelteKit and Tailwind CSS, can you design a Hero component for the Home page? It should include a large background image and a call to action.
    ```

3. **Implement the initial design:**

   Create the suggested Svelte components (`+page.svelte` files) and paste in Claude's code.

### 3.3. Iterative Refinement with `ctree`

1. **Copy the entire project using `ctree`:**

   Each time you want to iterate on the design, use `ctree` to copy the entire project's current state into your clipboard:

    ```bash
    # Copy the entire project to your clipboard
    ctree
    ```

2. **Provide full context to Claude:**

   Paste the `ctree` output into a new Claude chat. This gives Claude the complete context of your current code, including all components, styles, and configurations.

    ```
    Here is my current project code:
    [Paste ctree output here]
    ```

3. **Request specific changes or refinements:**

   Ask Claude for specific modifications to the design. Focus on one component or aspect at a time.

   **Example Prompts:**

    ```text
    Can you update the Header to use a sticky navigation bar that remains fixed at the top as the user scrolls?
    ```

    ```text
    I'd like the Footer to have a darker background and include social media icons.
    ```

    ```text
    Let's change the Home page grid to use a masonry layout for the project images.
    ```

4. **Implement and review changes:**

    *   Replace the relevant component code with Claude's updated version.
    *   Visually inspect the changes in your browser.

5. **Iterate:**

   Repeat steps 1-4, providing feedback to Claude after each iteration. Use `ctree` each time to ensure Claude has the latest context.

   **Example Feedback:**

    ```text
    The sticky header works great, but could we make the background slightly transparent?
    ```

    ```text
    The masonry layout looks good, but the gaps between images are a bit too large.
    ```

### 3.4. Component-Focused Refinement

1. **Isolate a component:**

   Once you have a basic design direction established, focus on refining individual components in isolation.

2. **Provide component-specific context:**

   When requesting changes to a specific component, it can be helpful to provide its code to Claude again, even though it's already in the full `ctree` output. This reinforces the context.

    ```text
    Here's the current code for the Header component again:
    [Paste Header component code]

    Could you redesign it to include a logo on the left and a hamburger menu for mobile navigation?
    ```

3. **Iterate on the component:**

   Request multiple design variations for the component, providing specific feedback on each iteration.

   **Example Feedback:**

    ```text
    I like the layout of version 2, but could we use a different font for the navigation links?
    ```

    ```text
    Can you show me a variation of the hamburger menu with rounded corners?
    ```

4. **Integrate the refined component:**

   Once you're satisfied with the component's design, integrate it back into your main layout.

### 3.5. Tailwind CSS Configuration Refinement

1. **Provide Tailwind config context:**

   Share your `tailwind.config.js` file with Claude, either by pasting it directly or using `ctree` to copy the entire project.

    ```text
    Here's my current tailwind.config.js:
    [Paste tailwind.config.js content]

    I'd like to refine the color palette. Could you suggest a more cohesive set of colors that align with the minimalist, photography-focused aesthetic?
    ```

2. **Iterate on design system elements:**

   Ask Claude to suggest changes to your Tailwind configuration to adjust colors, typography, spacing, and other design system elements.

   **Example Prompts:**

    ```text
    Can you generate a set of complementary font pairings that would work well for this design?
    ```

    ```text
    I want to adjust the spacing scale to create a more consistent visual rhythm. Could you propose a new spacing scale based on a 8px baseline grid?
    ```

    ```text
    Let's explore a different approach to shadows and elevation. Can you create a custom shadow system that feels more modern and subtle?
    ```

3. **Apply and evaluate changes:**

   Update your `tailwind.config.js` with Claude's suggestions and observe the impact on your overall design.

### 3.6. Backend Integration with +server.js

1. **Define API requirements:**

   Clearly describe the data needs and API interactions for your dynamic components.

    ```text
    I need to create an API endpoint to fetch a list of projects for the Home page grid. 
    The endpoint should accept optional query parameters for filtering and sorting. 
    Each project object should include the title, description, an array of image URLs, and a link to the project details page.
    ```

2. **Generate +server.js files:**

   Ask Claude to generate the SvelteKit `+server.js` files for your API endpoints, including data fetching logic, error handling, and response formatting.

    ```text
    Can you create a +server.js file for the /api/projects endpoint that implements the 
    functionality we discussed? Assume we're fetching data from a hypothetical CMS or database.
    ```

3. **Integrate with components:**

   Update your Svelte components to fetch data from the new API endpoints using SvelteKit's `load` function.

    ```svelte
    <script>
      export let data;

      async function load({ fetch }) {
        const res = await fetch('/api/projects');
        const projects = await res.json();
        return { projects };
      }
    </script>
    ```

4. **Iterate on API design:**

   Refine your API endpoints and data structures based on your evolving design needs and Claude's suggestions.

## 3.7. Creating rulesets for AI assistants

If your project gets too big for the context window of Claude or the AI assistant you're using, you can (create rulesets)[./rulesets.md] to split your project into different sections.

## 4. Example: Refining the Header Component

Let's say you want to refine the Header component after a few initial design iterations.

1. **New Claude Chat:** Start a new conversation with Claude to focus specifically on the Header.
2. **Provide Context:**

    ```text
    Here's my entire project code:
    [Paste output from `ctree`]

    And here's the current Header component code:
    [Paste Header.svelte code]

    I'd like to explore some alternative designs for the Header. Could you generate 3 variations that:
    1. Use a different font for the navigation links.
    2. Include a site logo on the left side.
    3. Implement a visually distinct hamburger menu for mobile responsiveness.
    ```

3. **Review Variations:** Claude provides 3 different Header designs. Analyze them and provide feedback.
4. **Iterate:**

    ```text
    I like the logo placement in variation 2, but the font in variation 1 is more readable. 
    Could you combine those elements? Also, let's make the hamburger menu icon a bit larger and 
    use a darker shade of gray for better contrast.
    ```

5. **Repeat:** Continue iterating with Claude until you're satisfied with the Header design.
6. **Integrate:** Replace your existing `Header.svelte` code with the final version generated by Claude.

## 5. Best Practices

*   **Start Broad, Then Narrow:** Begin with overall layout and design direction before focusing on individual components.
*   **Use `ctree` liberally:** Provide Claude with complete project context as often as possible, especially when requesting changes.
*   **Isolate Components:** Refine components in isolation to maintain focus and avoid unintended side effects.
*   **Iterate Frequently:** Embrace short feedback loops to guide the design process effectively.
*   **Experiment with Variations:** Encourage Claude to generate multiple design options to explore different possibilities.
*   **Provide Specific Feedback:** Clearly articulate your preferences and critiques to guide Claude's refinements.
*   **Maintain a Design System:** Use Tailwind's configuration to establish a consistent design language and make global changes efficiently.
*   **Version Control:** Commit your changes regularly using Git to track design evolution and revert if necessary.

## 6. Conclusion

This AI-driven design workflow offers a dynamic and collaborative approach to web development. By leveraging SvelteKit's component model, Tailwind CSS's utility-first system, and Claude's generative capabilities, you can create visually appealing and functional designs through an iterative process. The key is to embrace the AI as a design partner, provide clear direction, and continuously refine the output based on your visual preferences and project requirements. This workflow empowers you to explore a wider range of design possibilities and accelerate your design process while maintaining a high degree of control over the final product.
