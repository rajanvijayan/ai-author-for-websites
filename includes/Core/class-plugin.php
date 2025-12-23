<?php
/**
 * Main Plugin Core Class
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 *
 * Main plugin class that handles initialization and setup.
 */
class Plugin {

	/**
	 * Single instance of the class.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Get single instance.
	 *
	 * @return Plugin
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
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Activation/Deactivation hooks.
		register_activation_hook( AIAUTHOR_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( AIAUTHOR_PLUGIN_FILE, array( $this, 'deactivate' ) );

		// Initialize components.
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Initialize integrations.
		add_action( 'plugins_loaded', array( $this, 'init_integrations' ), 20 );
	}

	/**
	 * Initialize integrations framework.
	 */
	public function init_integrations() {
		\AIAuthor\Integrations\Manager::get_instance();
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		// Set default options.
		$defaults = array(
			'api_key'            => '',
			'provider'           => 'groq',
			'model'              => 'llama-3.3-70b-versatile',
			'system_instruction' => 'You are an expert blog writer. Create engaging, well-structured blog posts based on the knowledge base provided. Use a professional yet conversational tone. Include proper headings, paragraphs, and formatting.',
			'default_word_count' => 1000,
			'enabled'            => false,
		);

		if ( ! get_option( 'aiauthor_settings' ) ) {
			add_option( 'aiauthor_settings', $defaults );
		} else {
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
			array( $this, 'render_admin_page' ),
			'dashicons-edit-page',
			30
		);

		add_submenu_page(
			'ai-author-settings',
			__( 'Settings', 'ai-author-for-websites' ),
			__( 'Settings', 'ai-author-for-websites' ),
			'manage_options',
			'ai-author-settings',
			array( $this, 'render_admin_page' )
		);

		add_submenu_page(
			'ai-author-settings',
			__( 'Knowledge Base', 'ai-author-for-websites' ),
			__( 'Knowledge Base', 'ai-author-for-websites' ),
			'manage_options',
			'ai-author-knowledge',
			array( $this, 'render_knowledge_page' )
		);

		add_submenu_page(
			'ai-author-settings',
			__( 'Generate Post', 'ai-author-for-websites' ),
			__( 'Generate Post', 'ai-author-for-websites' ),
			'manage_options',
			'ai-author-generate',
			array( $this, 'render_generate_page' )
		);

		add_submenu_page(
			'ai-author-settings',
			__( 'Integrations', 'ai-author-for-websites' ),
			__( 'Integrations', 'ai-author-for-websites' ),
			'manage_options',
			'ai-author-integrations',
			array( $this, 'render_integrations_page' )
		);
	}

	/**
	 * Render integrations page.
	 */
	public function render_integrations_page() {
		$manager = \AIAuthor\Integrations\Manager::get_instance();
		$manager->render_admin_page();
	}

	/**
	 * Render admin settings page.
	 */
	public function render_admin_page() {
		$admin = new \AIAuthor\Admin\Settings();
		$admin->render();
	}

	/**
	 * Render knowledge base page.
	 */
	public function render_knowledge_page() {
		$knowledge = new \AIAuthor\Knowledge\Manager();
		$knowledge->render_admin_page();
	}

	/**
	 * Render generate post page.
	 */
	public function render_generate_page() {
		include AIAUTHOR_PLUGIN_DIR . 'includes/Views/generate-post.php';
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		$api = new \AIAuthor\API\REST();
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
			array(),
			self::VERSION
		);

		wp_enqueue_script(
			'aiauthor-admin',
			AIAUTHOR_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			self::VERSION,
			true
		);

		wp_localize_script(
			'aiauthor-admin',
			'aiauthorAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'restUrl' => rest_url( 'ai-author/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Get plugin settings.
	 *
	 * @return array Plugin settings.
	 */
	public static function get_settings() {
		$defaults = array(
			'api_key'            => '',
			'provider'           => 'groq',
			'model'              => 'llama-3.3-70b-versatile',
			'system_instruction' => '',
			'default_word_count' => 1000,
			'enabled'            => false,
		);
		$settings = get_option( 'aiauthor_settings', array() );
		return array_merge( $defaults, $settings );
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
