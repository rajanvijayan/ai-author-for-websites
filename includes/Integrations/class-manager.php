<?php
/**
 * Integrations Manager Class
 *
 * Handles registration and management of all integrations.
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Manager
 *
 * Manages all plugin integrations.
 */
class Manager {

	/**
	 * Single instance of the class.
	 *
	 * @var Manager|null
	 */
	private static $instance = null;

	/**
	 * Registered integrations.
	 *
	 * @var IntegrationInterface[]
	 */
	private $integrations = array();

	/**
	 * Get single instance.
	 *
	 * @return Manager
	 */
	public static function get_instance(): Manager {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_builtin_integrations();
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks(): void {
		// Allow external plugins to register integrations.
		add_action( 'aiauthor_register_integrations', array( $this, 'do_register_integrations' ), 10 );
		add_action( 'init', array( $this, 'init_enabled_integrations' ), 20 );
	}

	/**
	 * Load built-in integrations.
	 */
	private function load_builtin_integrations(): void {
		// Load Auto Scheduler integration.
		require_once AIAUTHOR_PLUGIN_DIR . 'includes/Integrations/AutoScheduler/class-auto-scheduler.php';
		$this->register( new AutoScheduler\AutoScheduler() );
	}

	/**
	 * Fire action to allow external plugins to register integrations.
	 */
	public function do_register_integrations(): void {
		/**
		 * Fires when integrations should be registered.
		 *
		 * @param Manager $manager The integrations manager instance.
		 */
		do_action( 'aiauthor_register_integrations', $this );
	}

	/**
	 * Register an integration.
	 *
	 * @param IntegrationInterface $integration The integration to register.
	 * @return bool True if registered successfully.
	 */
	public function register( IntegrationInterface $integration ): bool {
		$id = $integration->get_id();

		if ( isset( $this->integrations[ $id ] ) ) {
			return false;
		}

		$this->integrations[ $id ] = $integration;
		return true;
	}

	/**
	 * Unregister an integration.
	 *
	 * @param string $id Integration ID.
	 * @return bool True if unregistered successfully.
	 */
	public function unregister( string $id ): bool {
		if ( ! isset( $this->integrations[ $id ] ) ) {
			return false;
		}

		unset( $this->integrations[ $id ] );
		return true;
	}

	/**
	 * Get an integration by ID.
	 *
	 * @param string $id Integration ID.
	 * @return IntegrationInterface|null The integration or null if not found.
	 */
	public function get( string $id ): ?IntegrationInterface {
		return $this->integrations[ $id ] ?? null;
	}

	/**
	 * Get all registered integrations.
	 *
	 * @return IntegrationInterface[] Array of integrations.
	 */
	public function get_all(): array {
		return $this->integrations;
	}

	/**
	 * Get all enabled integrations.
	 *
	 * @return IntegrationInterface[] Array of enabled integrations.
	 */
	public function get_enabled(): array {
		return array_filter(
			$this->integrations,
			fn( $integration ) => $integration->is_enabled()
		);
	}

	/**
	 * Get integrations by category.
	 *
	 * @param string $category Category name.
	 * @return IntegrationInterface[] Array of integrations in the category.
	 */
	public function get_by_category( string $category ): array {
		return array_filter(
			$this->integrations,
			fn( $integration ) => $integration->get_category() === $category
		);
	}

	/**
	 * Initialize all enabled integrations.
	 */
	public function init_enabled_integrations(): void {
		foreach ( $this->get_enabled() as $integration ) {
			$integration->init();
		}
	}

	/**
	 * Enable an integration.
	 *
	 * @param string $id Integration ID.
	 * @return bool True if enabled successfully.
	 */
	public function enable_integration( string $id ): bool {
		$integration = $this->get( $id );
		if ( ! $integration ) {
			return false;
		}
		return $integration->enable();
	}

	/**
	 * Disable an integration.
	 *
	 * @param string $id Integration ID.
	 * @return bool True if disabled successfully.
	 */
	public function disable_integration( string $id ): bool {
		$integration = $this->get( $id );
		if ( ! $integration ) {
			return false;
		}
		return $integration->disable();
	}

	/**
	 * Get available categories.
	 *
	 * @return array Array of category info.
	 */
	public function get_categories(): array {
		return array(
			'automation'  => array(
				'label' => __( 'Automation', 'ai-author-for-websites' ),
				'icon'  => 'dashicons-update-alt',
			),
			'publishing'  => array(
				'label' => __( 'Publishing', 'ai-author-for-websites' ),
				'icon'  => 'dashicons-share',
			),
			'analytics'   => array(
				'label' => __( 'Analytics', 'ai-author-for-websites' ),
				'icon'  => 'dashicons-chart-area',
			),
			'seo'         => array(
				'label' => __( 'SEO', 'ai-author-for-websites' ),
				'icon'  => 'dashicons-search',
			),
			'social'      => array(
				'label' => __( 'Social Media', 'ai-author-for-websites' ),
				'icon'  => 'dashicons-share-alt',
			),
			'other'       => array(
				'label' => __( 'Other', 'ai-author-for-websites' ),
				'icon'  => 'dashicons-admin-plugins',
			),
		);
	}

	/**
	 * Get all integrations as array.
	 *
	 * @return array Array of integration data.
	 */
	public function get_all_as_array(): array {
		$result = array();
		foreach ( $this->integrations as $integration ) {
			$result[] = $integration->to_array();
		}
		return $result;
	}

	/**
	 * Render the integrations admin page.
	 */
	public function render_admin_page(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_integration = isset( $_GET['integration'] ) ? sanitize_key( $_GET['integration'] ) : '';

		if ( $current_integration && $this->get( $current_integration ) ) {
			$this->render_integration_settings_page( $current_integration );
		} else {
			$this->render_integrations_list_page();
		}
	}

	/**
	 * Render the integrations list page.
	 */
	private function render_integrations_list_page(): void {
		include AIAUTHOR_PLUGIN_DIR . 'includes/Views/integrations.php';
	}

	/**
	 * Render a specific integration's settings page.
	 *
	 * @param string $id Integration ID.
	 */
	private function render_integration_settings_page( string $id ): void {
		$integration = $this->get( $id );
		if ( $integration && $integration->has_settings_page() ) {
			$integration->render_settings_page();
		}
	}
}
