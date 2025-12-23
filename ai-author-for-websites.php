<?php
/**
 * Plugin Name: AI Author for Websites
 * Plugin URI: https://github.com/rajanvijayan/ai-author-for-websites
 * Description: AI-powered blog post generator. Train the AI with your knowledge base and create high-quality content.
 * Version: 1.0.0
 * Author: Rajan Vijayan
 * Author URI: https://rajanvijayan.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-author-for-websites
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 8.0
 *
 * @package AI_Author_For_Websites
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'AIAUTHOR_VERSION', '1.0.0' );
define( 'AIAUTHOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AIAUTHOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AIAUTHOR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'AIAUTHOR_PLUGIN_FILE', __FILE__ );

// Load Composer autoloader.
require_once AIAUTHOR_PLUGIN_DIR . 'vendor/autoload.php';

// Load plugin classes.
require_once AIAUTHOR_PLUGIN_DIR . 'includes/Core/class-plugin.php';
require_once AIAUTHOR_PLUGIN_DIR . 'includes/Admin/class-settings.php';
require_once AIAUTHOR_PLUGIN_DIR . 'includes/Knowledge/class-manager.php';
require_once AIAUTHOR_PLUGIN_DIR . 'includes/API/class-rest.php';

// Load Integrations framework.
require_once AIAUTHOR_PLUGIN_DIR . 'includes/Integrations/class-integration-interface.php';
require_once AIAUTHOR_PLUGIN_DIR . 'includes/Integrations/class-integration-base.php';
require_once AIAUTHOR_PLUGIN_DIR . 'includes/Integrations/class-manager.php';

/**
 * Initialize plugin.
 *
 * @return \AIAuthor\Core\Plugin
 */
function aiauthor_init() {
	return \AIAuthor\Core\Plugin::get_instance();
}

// Start the plugin.
add_action( 'plugins_loaded', 'aiauthor_init' );

// Add settings link on plugins page.
add_filter(
	'plugin_action_links_' . AIAUTHOR_PLUGIN_BASENAME,
	function ( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=ai-author-settings' ) . '">' . __( 'Settings', 'ai-author-for-websites' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
);

/**
 * Backward compatibility class wrapper.
 */
class AI_Author_For_Websites {

	/**
	 * Get plugin settings.
	 *
	 * @return array Plugin settings.
	 */
	public static function get_settings() {
		return \AIAuthor\Core\Plugin::get_settings();
	}

	/**
	 * Update plugin settings.
	 *
	 * @param array $settings New settings to save.
	 * @return bool True if updated successfully.
	 */
	public static function update_settings( $settings ) {
		return \AIAuthor\Core\Plugin::update_settings( $settings );
	}
}
