# AI WordPress Genius

AI WordPress Genius is a powerful WordPress plugin that uses the Google Gemini AI to streamline your development workflow. It can generate child themes, create entire block themes from a description, build functional plugins from your ideas, scan your existing code for issues, and even help you modify your code.

## Configuration

Before you can use the AI-powered features, you must add your Google Gemini API key.

1.  Go to **AI Genius > Settings** in your WordPress dashboard.
2.  Enter your Gemini API key into the field and click "Save Settings".

The plugin will not be able to perform any AI-related tasks without a valid API key.

## Features

### 1. Child Theme Generator
- **What it does:** Instantly creates a child theme from any parent theme installed on your site. This feature does not require an API key.
- **How to use:** Go to the **AI Genius > Dashboard**, select a parent theme from the dropdown, and click "Create Child Theme."

### 2. AI-Powered Theme Generator
- **What it does:** Generates a complete, modern WordPress block theme based on a natural language description.
- **How to use:** On the dashboard, provide a name for your new theme and describe its look and feel (e.g., "A minimalist, single-column theme with a dark background and white text"). Click "Generate Theme," and the AI will create a new theme and add it to your `Appearance > Themes` menu.

### 3. AI-Powered Plugin Generator
- **What it does:** Generates a functional WordPress plugin from a description of what it should do.
- **How to use:** On the dashboard, give your new plugin a name and describe its functionality (e.g., "A simple plugin that creates a shortcode [year] to display the current year"). Click "Generate Plugin," and the AI will create a new plugin and add it to your `Plugins` page.

### 4. Agentic Bug Finder (Beta)
- **What it does:** Acts as an AI agent to diagnose and propose fixes for bugs in your plugins and themes.
- **How it works:**
    1.  On the dashboard, describe the bug you are experiencing in detail (e.g., "I get a 500 error when I try to save a new post").
    2.  Select the plugin or theme you suspect is causing the problem.
    3.  Click "Let Agent Diagnose & Fix."
    4.  **Diagnosis (AI Call #1):** The agent analyzes your report and your site's environment to create a plan, identifying the most likely files to inspect.
    5.  **Analysis & Fix (AI Call #2):** The agent then reads the content of the identified files and sends them to the AI, asking for a code fix.
    6.  **Approval:** If the AI proposes a fix, you will be shown a "diff" view comparing the original code with the AI's proposed changes. **You must review these changes carefully.**
    7.  If you approve, click "Approve & Apply Changes" to overwrite the file.

### 5. AI Code Editor (Beta)
- **What it does:** Modifies the code of your existing plugins and themes based on your instructions. This is a powerful, experimental feature.
- **How it works:**
    1.  On the dashboard, select a plugin or theme to modify and provide a clear instruction (e.g., "Change the version number to 2.0.0").
    2.  Click "Generate Proposed Changes." The AI will analyze your request and rewrite the file.
    3.  You will be shown a "diff" view comparing the original code with the AI's proposed changes.
    4.  **You must review these changes carefully.**
    5.  If you approve, click "Approve & Apply Changes" to overwrite the file.
- **Limitation:** In this version, this feature will only modify the main plugin file for plugins or the `functions.php` file for themes.

## Installation

1. Download the `ai-wordpress-genius` directory as a ZIP file.
2. In your WordPress dashboard, go to `Plugins > Add New > Upload Plugin`.
3. Upload the ZIP file and activate the plugin.
4. The "AI Genius" menu will appear in your WordPress dashboard.
