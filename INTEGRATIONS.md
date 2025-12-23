# AI Author Integrations Developer Guide

This guide explains how to create custom integrations for the AI Author for Websites plugin.

## Overview

AI Author provides an extensible integrations framework that allows developers to add new functionality through standalone plugins or custom code. Integrations can add features like social media auto-posting, SEO optimization, analytics tracking, and more.

## Quick Start

### Creating a Simple Integration Plugin

Create a new WordPress plugin with the following structure:

```
my-ai-author-integration/
├── my-ai-author-integration.php
└── includes/
    └── class-my-integration.php
```

**my-ai-author-integration.php:**

```php
<?php
/**
 * Plugin Name: My AI Author Integration
 * Description: Custom integration for AI Author
 * Version: 1.0.0
 * Requires Plugins: ai-author-for-websites
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register the integration when AI Author loads integrations.
add_action( 'aiauthor_register_integrations', function( $manager ) {
    require_once __DIR__ . '/includes/class-my-integration.php';
    $manager->register( new MyIntegration() );
});
```

## Integration Interface

All integrations must implement the `AIAuthor\Integrations\IntegrationInterface` interface. For convenience, you can extend the `AIAuthor\Integrations\IntegrationBase` abstract class which provides default implementations.

### Required Methods

| Method | Return Type | Description |
|--------|-------------|-------------|
| `get_id()` | `string` | Unique identifier (e.g., 'my-integration') |
| `get_name()` | `string` | Display name |
| `get_description()` | `string` | Brief description |
| `get_version()` | `string` | Version number |
| `get_author()` | `string` | Author name |
| `get_icon()` | `string` | Dashicon class or icon URL |
| `get_category()` | `string` | Category: 'automation', 'publishing', 'analytics', 'seo', 'social', 'other' |
| `is_enabled()` | `bool` | Whether integration is active |
| `enable()` | `bool` | Activate the integration |
| `disable()` | `bool` | Deactivate the integration |
| `init()` | `void` | Initialize hooks when enabled |
| `get_settings()` | `array` | Get integration settings |
| `update_settings(array)` | `bool` | Save settings |
| `has_settings_page()` | `bool` | Whether to show settings page |
| `render_settings_page()` | `void` | Render the settings UI |
| `on_activate()` | `void` | Called when integration is enabled |
| `on_deactivate()` | `void` | Called when integration is disabled |
| `is_builtin()` | `bool` | Return `false` for external integrations |

## Complete Example

```php
<?php
/**
 * My Custom Integration
 */

namespace MyPlugin;

use AIAuthor\Integrations\IntegrationBase;

class MyIntegration extends IntegrationBase {

    /**
     * Default settings for this integration.
     */
    protected $default_settings = [
        'enabled'     => false,
        'api_key'     => '',
        'auto_share'  => true,
    ];

    /**
     * Get unique identifier.
     */
    public function get_id(): string {
        return 'my-integration';
    }

    /**
     * Get display name.
     */
    public function get_name(): string {
        return __( 'My Integration', 'my-plugin' );
    }

    /**
     * Get description.
     */
    public function get_description(): string {
        return __( 'Adds custom functionality to AI Author.', 'my-plugin' );
    }

    /**
     * Get version.
     */
    public function get_version(): string {
        return '1.0.0';
    }

    /**
     * Get author.
     */
    public function get_author(): string {
        return 'Your Name';
    }

    /**
     * Get icon (Dashicon class).
     */
    public function get_icon(): string {
        return 'dashicons-admin-plugins';
    }

    /**
     * Get category.
     */
    public function get_category(): string {
        return 'other';
    }

    /**
     * Initialize the integration.
     * Called when the integration is loaded and enabled.
     */
    public function init(): void {
        // Add hooks here
        add_action( 'publish_post', [ $this, 'on_post_publish' ], 10, 2 );
    }

    /**
     * Called when integration is enabled.
     */
    public function on_activate(): void {
        // Set up cron jobs, create database tables, etc.
    }

    /**
     * Called when integration is disabled.
     */
    public function on_deactivate(): void {
        // Clean up cron jobs, etc.
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page(): void {
        $settings = $this->get_settings();
        
        // Handle form submission
        if ( isset( $_POST['my_integration_save'] ) && check_admin_referer( 'my_integration_nonce' ) ) {
            $settings['api_key'] = sanitize_text_field( $_POST['api_key'] ?? '' );
            $settings['auto_share'] = ! empty( $_POST['auto_share'] );
            $this->update_settings( $settings );
        }
        
        ?>
        <div class="wrap aiauthor-admin">
            <h1>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-author-integrations' ) ); ?>">
                    ← Back
                </a>
                <?php echo esc_html( $this->get_name() ); ?>
            </h1>
            
            <form method="post">
                <?php wp_nonce_field( 'my_integration_nonce' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="api_key">API Key</label></th>
                        <td>
                            <input type="text" 
                                   id="api_key" 
                                   name="api_key" 
                                   value="<?php echo esc_attr( $settings['api_key'] ); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="auto_share">Auto Share</label></th>
                        <td>
                            <input type="checkbox" 
                                   id="auto_share" 
                                   name="auto_share" 
                                   value="1" 
                                   <?php checked( $settings['auto_share'] ); ?>>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" 
                           name="my_integration_save" 
                           class="button button-primary" 
                           value="Save Settings">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Hook callback - runs when a post is published.
     */
    public function on_post_publish( $post_id, $post ): void {
        $settings = $this->get_settings();
        
        if ( ! $settings['auto_share'] ) {
            return;
        }
        
        // Your custom logic here
    }
}
```

## Available Categories

When defining your integration's category, use one of these values:

| Category | Description |
|----------|-------------|
| `automation` | Scheduled tasks, automated workflows |
| `publishing` | Content publishing, distribution |
| `analytics` | Tracking, reporting, insights |
| `seo` | Search engine optimization |
| `social` | Social media integrations |
| `other` | Other functionality |

## Hooks & Filters

### Actions

```php
// Fires when integrations should be registered
do_action( 'aiauthor_register_integrations', $manager );

// Example: Register your integration
add_action( 'aiauthor_register_integrations', function( $manager ) {
    $manager->register( new MyIntegration() );
});
```

### Accessing the Integration Manager

```php
// Get the integrations manager instance
$manager = \AIAuthor\Integrations\Manager::get_instance();

// Get all registered integrations
$integrations = $manager->get_all();

// Get a specific integration
$scheduler = $manager->get( 'auto-scheduler' );

// Get only enabled integrations
$enabled = $manager->get_enabled();

// Get integrations by category
$automation = $manager->get_by_category( 'automation' );
```

## Using AI Author's AI Engine

You can leverage AI Author's configured AI provider in your integration:

```php
use AIAuthor\Core\Plugin;
use AIEngine\AIEngine;

$settings = Plugin::get_settings();

if ( empty( $settings['api_key'] ) ) {
    // Handle missing API key
    return;
}

$ai = new AIEngine(
    $settings['api_key'],
    [
        'provider' => $settings['provider'] ?? 'groq',
        'model'    => $settings['model'] ?? 'llama-3.3-70b-versatile',
        'timeout'  => 60,
    ]
);

// Generate content
$response = $ai->generateContent( 'Your prompt here' );

// Chat with context
$ai->setSystemInstruction( 'You are a helpful assistant.' );
$response = $ai->chat( 'User message' );
```

## Accessing the Knowledge Base

```php
use AIAuthor\Knowledge\Manager as KnowledgeManager;

$knowledge_manager = new KnowledgeManager();

// Get knowledge base context as text
$context = $knowledge_manager->get_knowledge_context();

// Get the knowledge base instance
$kb = $knowledge_manager->get_knowledge_base();
$summary = $kb->getSummary();
```

## Best Practices

1. **Unique IDs**: Use a unique, descriptive ID for your integration (e.g., 'social-twitter', 'seo-yoast').

2. **Namespace**: Use PHP namespaces to avoid conflicts with other plugins.

3. **Settings Storage**: Use the built-in `get_settings()` and `update_settings()` methods. They automatically handle option storage with the key `aiauthor_integration_{id}`.

4. **Cleanup**: Always clean up cron jobs and temporary data in `on_deactivate()`.

5. **Error Handling**: Wrap API calls in try-catch blocks and log errors appropriately.

6. **Internationalization**: Use WordPress i18n functions (`__()`, `esc_html_e()`, etc.).

7. **Security**: 
   - Validate and sanitize all input
   - Use nonces for form submissions
   - Check user capabilities

8. **Dependencies**: Ensure AI Author is active before loading your integration:

```php
add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'AIAuthor\Integrations\IntegrationBase' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo 'My Integration requires AI Author for Websites to be installed and active.';
            echo '</p></div>';
        });
        return;
    }
    
    // Safe to load integration
});
```

## Testing Your Integration

1. Enable your integration from **AI Author → Integrations**
2. Configure settings on your integration's settings page
3. Test all functionality with various scenarios
4. Verify cleanup when disabling the integration

## Support

For questions or issues with the integrations API, please open an issue on the [GitHub repository](https://github.com/rajanvijayan/ai-author-for-websites).

