# AI Author for Websites

An AI-powered WordPress plugin that helps create blog posts for your website using a knowledge base to train the AI.

## Description

AI Author for Websites leverages artificial intelligence to help you create high-quality blog content. Train the AI with your website's knowledge base, and it will generate relevant, on-brand blog posts.

## Features

- **AI-Powered Content Generation**: Use advanced AI models (Groq, Gemini, Meta Llama) to generate blog posts
- **Knowledge Base Training**: Train the AI with your existing content, URLs, and custom text
- **Easy Configuration**: Simple settings page to configure API keys and preferences
- **WordPress Integration**: Seamlessly integrates with WordPress post editor

## Requirements

- WordPress 5.8 or higher
- PHP 8.0 or higher
- Composer for dependency management

## Installation

1. Clone or download this plugin to your `/wp-content/plugins/` directory
2. Run `composer install` to install dependencies
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to **AI Author** in the admin menu to configure settings

## Configuration

1. Go to **AI Author > Settings** in your WordPress admin
2. Enter your AI provider API key (Groq recommended)
3. Configure your preferences
4. Add content to your knowledge base via **AI Author > Knowledge Base**

## Getting API Keys

### Groq (Recommended)
1. Visit [console.groq.com](https://console.groq.com)
2. Sign up for a free account
3. Create an API key
4. Copy and paste it into the plugin settings

### Google Gemini
1. Visit [aistudio.google.com](https://aistudio.google.com)
2. Create or sign in to your Google account
3. Generate an API key

## Usage

1. Train the AI by adding content to the Knowledge Base:
   - Add URLs from your website
   - Upload documents (PDF, DOC, TXT)
   - Add custom text entries

2. Generate blog posts:
   - Go to **AI Author > Generate Post**
   - Enter a topic or title
   - Click "Generate" to create a draft

## Development

### Setup

```bash
# Install PHP dependencies
composer install

# Run code style checks
composer phpcs

# Auto-fix code style issues
composer phpcbf
```

### Directory Structure

```
ai-author-for-websites/
├── ai-author-for-websites.php  # Main plugin file
├── includes/                    # PHP classes
│   ├── class-admin-settings.php
│   ├── class-knowledge-manager.php
│   └── class-rest-api.php
├── assets/                      # CSS and JS files
│   ├── css/
│   └── js/
├── vendor/                      # Composer dependencies
├── composer.json
└── README.md
```

## Changelog

### 1.0.0
- Initial release
- AI content generation
- Knowledge base management
- Settings page with API configuration

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Author

Rajan Vijayan - [rajanvijayan.com](https://rajanvijayan.com)

## Credits

This plugin uses the [AI Engine](https://github.com/rajanvijayan/ai-engine) library for AI provider integrations.

