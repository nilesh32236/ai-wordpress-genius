# AI WordPress Genius

AI WordPress Genius is a powerful WordPress plugin that uses AI to streamline your development workflow. It can generate child themes, create entire block themes from a description, build functional plugins from your ideas, and scan your existing themes and plugins for potential issues.

## Features

### 1. Child Theme Generator
- **What it does:** Instantly creates a child theme from any parent theme installed on your site.
- **How to use:** Go to the "AI Genius" dashboard, select a parent theme from the dropdown, and click "Create Child Theme." The generated child theme will be available in your `Appearance > Themes` menu, ready for activation and customization.

### 2. AI-Powered Theme Generator
- **What it does:** Generates a complete, modern WordPress block theme based on a natural language description.
- **How to use:** On the "AI Genius" dashboard, provide a name for your new theme and describe its look and feel (e.g., "A minimalist, single-column theme with a dark background and white text"). Click "Generate Theme," and the new theme will be created and added to your `Appearance > Themes` menu.
- **Note:** This feature currently uses a simulated AI response to generate a standard "dark mode" theme as a proof-of-concept.

### 3. AI-Powered Plugin Generator
- **What it does:** Generates a functional WordPress plugin from a description of what it should do.
- **How to use:** On the "AI Genius" dashboard, give your new plugin a name and describe its functionality (e.g., "A simple plugin that creates a shortcode [year] to display the current year"). Click "Generate Plugin," and the new plugin will be created and available on your `Plugins` page for activation.
- **Note:** This feature currently uses a simulated AI response to generate a simple shortcode plugin.

### 4. AI Bug Finder
- **What it does:** Scans your installed plugins and themes for common issues, such as the use of deprecated WordPress functions.
- **How to use:** On the "AI Genius" dashboard, select a plugin or theme to scan and click the "Scan" button. The results will be displayed on the same page, showing the file, line number, the issue found, and an AI-powered suggestion for how to fix it.
- **Note:** This feature currently uses a simulated scanner that looks for a specific deprecated function (`get_bloginfo('siteurl')`) as a proof-of-concept.

## Installation

1. Download the `ai-wordpress-genius` directory as a ZIP file.
2. In your WordPress dashboard, go to `Plugins > Add New > Upload Plugin`.
3. Upload the ZIP file and activate the plugin.
4. The "AI Genius" menu will appear in your WordPress dashboard.

## Future Development

This plugin is designed to be extensible. Future versions could include:
- Integration with real AI services like OpenAI's GPT or Google's Gemini.
- A more comprehensive set of rules for the bug finder.
- The ability to add new features to existing themes/plugins.
- AI-powered content generation as originally described in the `prd.md`.
