# AI Features in Ctree

Ctree incorporates advanced AI capabilities to enhance your development workflow. These features leverage the power of OpenAI's language models to provide intelligent file filtering and smart output naming, making it easier to share and work with your codebase.

## Available AI Features

Ctree currently offers two main AI-powered features:

1. **Intelligent File Filtering:** Use natural language to describe the files you want to include, and let the AI find them for you.
2. **Smart Filename Generation:** When saving output to a file, Ctree can automatically generate a descriptive filename based on the content using AI.

## Setting Up AI Features

To use Ctree's AI features, you need to set up your OpenAI API credentials. Here's how:

1. **Obtain an OpenAI API Key:**
    *   If you don't already have an OpenAI account, sign up at [openai.com](https://openai.com/).
    *   Once logged in, go to the API section and create a new API key.
    *   Copy your API key.

2. **Find your OpenAI Organization ID:**
    *   In the OpenAI dashboard, navigate to the **Settings** or **Organization** section.
    *   Copy your Organization ID.

3. **Create the `.env` configuration file:**
    *   In your home directory, create a folder named `.copytree`.
    *   Inside this folder, create a file named `.env`.
    *   Add the following lines to the `.env` file, replacing `your-api-key` and `your-org-id` with your actual credentials:

    ```
    OPENAI_API_KEY=your-api-key
    OPENAI_API_ORG=your-org-id
    ```

   Your `.copytree` directory structure should look like this:

    ```
    ~/.copytree/
    └── .env           # OpenAI configuration
    ```

   **Important:** Keep your `.env` file secure and do not share it publicly.

## Using AI Features

### Intelligent File Filtering

The AI-powered file filtering feature allows you to use natural language to describe the files you're looking for. Ctree will then use OpenAI's language models to understand your request and select the relevant files.

**How to use:**

*   Use the `--ai-filter` option followed by your description:

    ```bash
    ctree --ai-filter="Find all authentication related files"
    ```

*   Or, use the `--ai-filter` option without a description to enter interactive mode:

    ```bash
    ctree --ai-filter
    ```

    Ctree will then prompt you to enter your filtering description.

**Examples:**

*   `ctree --ai-filter="Show me all the test files for the authentication system"`
*   `ctree --ai-filter="Find files related to database migrations and models"`
*   `ctree --ai-filter="What files deal with user profile management?"`

**How it works:**

1. Ctree sends the list of file paths and their content previews (first 250 characters) to the OpenAI API.
2. The API analyzes the file paths, directory structure, and content previews, considering common software development conventions.
3. The AI determines which files best match your description, considering both explicit terms and related concepts.
4. Ctree receives the filtered list of files and uses it for the copy operation.

**Benefits:**

*   **Intuitive filtering:** No need to remember complex glob patterns or write intricate rulesets.
*   **Time-saving:** Quickly find the files you need without manual searching.
*   **Context-aware:** The AI considers the overall project structure and file relationships.

### Smart Filename Generation

When saving the output to a file using the `--output` or `-o` option, Ctree can automatically generate a descriptive filename based on the content of the included files.

**How to use:**

*   Use the `--output` option without specifying a filename:

    ```bash
    ctree --output
    ```

*   Or, use the short version:

    ```bash
    ctree -o
    ```

**How it works:**

1. Ctree sends a list of the included file paths to the OpenAI API.
2. The AI analyzes the file paths to understand the overall purpose and context of the files.
3. The AI generates a concise, descriptive filename in lowercase, hyphen-separated format (e.g., `user-authentication-system.txt`).
4. Ctree saves the output to a file with the generated name in the `~/.copytree/files/` directory.
5. If you're on macOS, Ctree will automatically open the folder containing the saved file in Finder.

**Benefits:**

*   **Meaningful filenames:** No more guessing what's inside a file based on a generic name.
*   **Improved organization:** Makes it easier to manage and find your saved code snippets.
*   **Automated workflow:** Saves you the effort of manually naming files.

**Example:**

If you copy files related to user authentication, the AI might generate a filename like `user-authentication-logic.txt`.

## Troubleshooting

*   **AI filtering or filename generation fails:**
    *   Ensure your OpenAI API key and organization ID are correct in the `~/.copytree/.env` file.
    *   Check your OpenAI API usage and billing.
    *   If the AI features consistently fail, you can still use Ctree's ruleset-based filtering and manual filename specification.
*   **Error: "OpenAI configuration file not found":**
    *   Make sure you've created the `.env` file in the correct location (`~/.copytree/.env`).
