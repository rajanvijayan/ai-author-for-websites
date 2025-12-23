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

/**
 * Main Plugin Class
 */
class AI_Author_For_Websites {

	/**
	 * Single instance of the class.
	 *
	 * @var AI_Author_For_Websites|null
	 */
	private static $instance = null;

	/**
	 * Get single instance.
	 *
	 * @return AI_Author_For_Websites
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required files.
	 */
	private function load_dependencies() {
		// Load AI Engine library.
		require_once AIAUTHOR_PLUGIN_DIR . 'vendor/autoload.php';

		// Load plugin classes.
		require_once AIAUTHOR_PLUGIN_DIR . 'includes/class-admin-settings.php';
		require_once AIAUTHOR_PLUGIN_DIR . 'includes/class-knowledge-manager.php';
		require_once AIAUTHOR_PLUGIN_DIR . 'includes/class-rest-api.php';
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Activation/Deactivation hooks.
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );

		// Initialize components.
		add_action( 'init', [ $this, 'init' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		// Set default options.
		$defaults = [
			'api_key'            => '',
			'provider'           => 'groq',
			'model'              => 'llama-3.3-70b-versatile',
			'system_instruction' => 'You are an expert blog writer. Create engaging, well-structured blog posts based on the knowledge base provided. Use a professional yet conversational tone. Include proper headings, paragraphs, and formatting.',
			'default_word_count' => 1000,
			'enabled'            => false,
		];

		if ( ! get_option( 'aiauthor_settings' ) ) {
			add_option( 'aiauthor_settings', $defaults );
		} else {
			// Merge new defaults with existing settings.
			$existing = get_option( 'aiauthor_settings' );
			$merged   = array_merge( $defaults, $existing );
			update_option( 'aiauthor_settings', $merged );
		}

		// Create knowledge base storage directory.
		$upload_dir = wp_upload_dir();
		$kb_dir     = $upload_dir['basedir'] . '/ai-author-knowledge';
		if ( ! file_exists( $kb_dir ) ) {
			wp_mkdir_p( $kb_dir );
		}

		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Initialize plugin.
	 */
	public function init() {
		// Load text domain.
		load_plugin_textdomain( 'ai-author-for-websites', false, dirname( AIAUTHOR_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'AI Author', 'ai-author-for-websites' ),
			__( 'AI Author', 'ai-author-for-websites' ),
			'manage_options',
			'ai-author-settings',
			[ $this, 'render_admin_page' ],
			'dashicons-edit-page',
			30
		);

		add_submenu_page(
			'ai-author-settings',
			__( 'Settings', 'ai-author-for-websites' ),
			__( 'Settings', 'ai-author-for-websites' ),
			'manage_options',
			'ai-author-settings',
			[ $this, 'render_admin_page' ]
		);

		add_submenu_page(
			'ai-author-settings',
			__( 'Knowledge Base', 'ai-author-for-websites' ),
			__( 'Knowledge Base', 'ai-author-for-websites' ),
			'manage_options',
			'ai-author-knowledge',
			[ $this, 'render_knowledge_page' ]
		);

		add_submenu_page(
			'ai-author-settings',
			__( 'Generate Post', 'ai-author-for-websites' ),
			__( 'Generate Post', 'ai-author-for-websites' ),
			'manage_options',
			'ai-author-generate',
			[ $this, 'render_generate_page' ]
		);
	}

	/**
	 * Render admin settings page.
	 */
	public function render_admin_page() {
		$admin = new AIAUTHOR_Admin_Settings();
		$admin->render();
	}

	/**
	 * Render knowledge base page.
	 */
	public function render_knowledge_page() {
		$knowledge = new AIAUTHOR_Knowledge_Manager();
		$knowledge->render_admin_page();
	}

	/**
	 * Render generate post page.
	 */
	public function render_generate_page() {
		include AIAUTHOR_PLUGIN_DIR . 'includes/views/generate-post.php';
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		$api = new AIAUTHOR_REST_API();
		$api->register_routes();
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function admin_scripts( $hook ) {
		if ( strpos( $hook, 'ai-author' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'aiauthor-admin',
			AIAUTHOR_PLUGIN_URL . 'assets/css/admin.css',
			[],
			AIAUTHOR_VERSION
		);

		wp_enqueue_script(
			'aiauthor-admin',
			AIAUTHOR_PLUGIN_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			AIAUTHOR_VERSION,
			true
		);

		wp_localize_script(
			'aiauthor-admin',
			'aiauthorAdmin',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'restUrl' => rest_url( 'ai-author/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	/**
	 * Get plugin settings.
	 *
	 * @return array Plugin settings.
	 */
	public static function get_settings() {
		return get_option( 'aiauthor_settings', [] );
	}

	/**
	 * Update plugin settings.
	 *
	 * @param array $settings New settings to save.
	 * @return bool True if updated successfully.
	 */
	public static function update_settings( $settings ) {
		return update_option( 'aiauthor_settings', $settings );
	}
}

// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed -- Main plugin file requires function for plugins_loaded hook.

/**
 * Initialize plugin.
 *
 * @return AI_Author_For_Websites
 */
function aiauthor_init() {
	return AI_Author_For_Websites::get_instance();
}

// phpcs:enable Universal.Files.SeparateFunctionsFromOO.Mixed

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

