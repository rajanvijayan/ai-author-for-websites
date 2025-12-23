<?php
/**
 * Yoast SEO Integration
 *
 * Automatically generates and sets SEO metadata using AI for posts.
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Integrations\YoastSEO;

use AIAuthor\Integrations\IntegrationBase;
use AIAuthor\Core\Plugin;
use AIEngine\AIEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class YoastSEO
 *
 * Handles integration with Yoast SEO plugin.
 */
class YoastSEO extends IntegrationBase {

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	protected $default_settings = array(
		'enabled'                  => false,
		'auto_generate_meta'       => true,
		'generate_focus_keyphrase' => true,
		'generate_meta_desc'       => true,
		'meta_desc_length'         => 155,
		'generate_og_title'        => false,
		'generate_og_desc'         => false,
	);

	/**
	 * {@inheritdoc}
	 */
	public function get_id(): string {
		return 'yoast-seo';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return __( 'Yoast SEO', 'ai-author-for-websites' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Automatically generate SEO meta titles, descriptions, and focus keyphrases for AI-generated posts using Yoast SEO.', 'ai-author-for-websites' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_version(): string {
		return '1.0.0';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_author(): string {
		return 'AI Author Team';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_icon(): string {
		return 'dashicons-search';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_category(): string {
		return 'seo';
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_builtin(): bool {
		return true;
	}

	/**
	 * Check if Yoast SEO plugin is active.
	 *
	 * @return bool True if Yoast SEO is active.
	 */
	public function is_yoast_active(): bool {
		return defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Meta' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function init(): void {
		// Register REST API endpoints.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Hook into post creation to auto-generate SEO data.
		add_action( 'aiauthor_post_created', array( $this, 'maybe_generate_seo_data' ), 15, 3 );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'ai-author/v1',
			'/yoast-seo/generate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_generate_seo_data' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'ai-author/v1',
			'/yoast-seo/apply',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_apply_seo_data' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * REST endpoint to generate SEO data using AI.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response The response.
	 */
	public function rest_generate_seo_data( $request ): \WP_REST_Response {
		$title   = sanitize_text_field( $request->get_param( 'title' ) );
		$content = wp_kses_post( $request->get_param( 'content' ) );

		if ( empty( $title ) && empty( $content ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Title or content is required.', 'ai-author-for-websites' ),
				),
				400
			);
		}

		$result = $this->generate_seo_data( $title, $content );

		if ( isset( $result['error'] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $result['error'],
				),
				400
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $result,
			),
			200
		);
	}

	/**
	 * REST endpoint to apply SEO data to a post.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response The response.
	 */
	public function rest_apply_seo_data( $request ): \WP_REST_Response {
		$post_id         = absint( $request->get_param( 'post_id' ) );
		$focus_keyphrase = sanitize_text_field( $request->get_param( 'focus_keyphrase' ) );
		$meta_desc       = sanitize_textarea_field( $request->get_param( 'meta_description' ) );
		$seo_title       = sanitize_text_field( $request->get_param( 'seo_title' ) );

		if ( empty( $post_id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Post ID is required.', 'ai-author-for-websites' ),
				),
				400
			);
		}

		$result = $this->apply_seo_data_to_post(
			$post_id,
			array(
				'focus_keyphrase'  => $focus_keyphrase,
				'meta_description' => $meta_desc,
				'seo_title'        => $seo_title,
			)
		);

		if ( ! $result ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to apply SEO data. Make sure Yoast SEO is active.', 'ai-author-for-websites' ),
				),
				400
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'SEO data applied successfully!', 'ai-author-for-websites' ),
			),
			200
		);
	}

	/**
	 * Generate SEO data using AI.
	 *
	 * @param string $title   Post title.
	 * @param string $content Post content.
	 * @return array SEO data or error.
	 */
	public function generate_seo_data( string $title, string $content ): array {
		$plugin_settings = Plugin::get_settings();

		if ( empty( $plugin_settings['api_key'] ) ) {
			return array( 'error' => __( 'API key is not configured.', 'ai-author-for-websites' ) );
		}

		$settings = $this->get_settings();

		try {
			$ai = new AIEngine(
				$plugin_settings['api_key'],
				array(
					'provider' => $plugin_settings['provider'] ?? 'groq',
					'model'    => $plugin_settings['model'] ?? 'llama-3.3-70b-versatile',
					'timeout'  => 60,
				)
			);

			$meta_desc_length = $settings['meta_desc_length'] ?? 155;
			$content_excerpt  = wp_trim_words( wp_strip_all_tags( $content ), 300 );

			$prompt  = "Generate SEO metadata for the following blog post.\n\n";
			$prompt .= "Title: {$title}\n\n";
			$prompt .= "Content (excerpt): {$content_excerpt}\n\n";
			$prompt .= "Generate the following:\n";
			$prompt .= "1. Focus Keyphrase: A 2-4 word phrase that best represents the main topic\n";
			$prompt .= "2. Meta Description: A compelling description under {$meta_desc_length} characters that includes the focus keyphrase\n";
			$prompt .= "3. SEO Title: An optimized title under 60 characters that includes the focus keyphrase\n\n";
			$prompt .= "Return the response in this exact JSON format:\n";
			$prompt .= '{"focus_keyphrase": "your keyphrase", "meta_description": "your description", "seo_title": "your title"}';
			$prompt .= "\n\nOnly return the JSON, nothing else.";

			$response = $ai->generateContent( $prompt );

			if ( is_array( $response ) && isset( $response['error'] ) ) {
				return array( 'error' => $response['error'] );
			}

			// Parse JSON response.
			$json_match = preg_match( '/\{.*\}/s', $response, $matches );
			$seo_data   = $json_match ? json_decode( $matches[0], true ) : null;

			if ( ! $seo_data ) {
				return array( 'error' => __( 'Could not parse AI response.', 'ai-author-for-websites' ) );
			}

			return array(
				'focus_keyphrase'  => $seo_data['focus_keyphrase'] ?? '',
				'meta_description' => $seo_data['meta_description'] ?? '',
				'seo_title'        => $seo_data['seo_title'] ?? '',
			);
		} catch ( \Exception $e ) {
			return array( 'error' => $e->getMessage() );
		}
	}

	/**
	 * Apply SEO data to a post using Yoast SEO meta fields.
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $seo_data SEO data to apply.
	 * @return bool True if successful.
	 */
	public function apply_seo_data_to_post( int $post_id, array $seo_data ): bool {
		if ( ! $this->is_yoast_active() ) {
			$this->log_activity( 'error', 'Yoast SEO is not active' );
			return false;
		}

		$applied = false;

		// Focus Keyphrase.
		if ( ! empty( $seo_data['focus_keyphrase'] ) ) {
			update_post_meta( $post_id, '_yoast_wpseo_focuskw', sanitize_text_field( $seo_data['focus_keyphrase'] ) );
			$applied = true;
		}

		// Meta Description.
		if ( ! empty( $seo_data['meta_description'] ) ) {
			update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_textarea_field( $seo_data['meta_description'] ) );
			$applied = true;
		}

		// SEO Title.
		if ( ! empty( $seo_data['seo_title'] ) ) {
			update_post_meta( $post_id, '_yoast_wpseo_title', sanitize_text_field( $seo_data['seo_title'] ) );
			$applied = true;
		}

		if ( $applied ) {
			$this->log_activity( 'success', sprintf( 'SEO data applied to post ID %d', $post_id ) );
		}

		return $applied;
	}

	/**
	 * Maybe generate SEO data when a post is created.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $title   Post title.
	 * @param string $content Post content.
	 */
	public function maybe_generate_seo_data( int $post_id, string $title, string $content ): void {
		$this->log_activity( 'info', sprintf( 'Hook triggered for post ID %d: "%s"', $post_id, $title ) );

		$settings = $this->get_settings();

		// Check if integration is enabled.
		if ( ! $this->is_enabled() ) {
			$this->log_activity( 'skipped', 'Integration is not enabled' );
			return;
		}

		// Check if Yoast SEO is active.
		if ( ! $this->is_yoast_active() ) {
			$this->log_activity( 'skipped', 'Yoast SEO is not active' );
			return;
		}

		// Check if auto-generate is enabled.
		if ( empty( $settings['auto_generate_meta'] ) ) {
			$this->log_activity( 'skipped', 'Auto-generate SEO is disabled' );
			return;
		}

		// Generate SEO data.
		$seo_data = $this->generate_seo_data( $title, $content );

		if ( isset( $seo_data['error'] ) ) {
			$this->log_activity( 'error', sprintf( 'Failed to generate SEO data: %s', $seo_data['error'] ) );
			return;
		}

		// Apply SEO data to the post.
		$this->apply_seo_data_to_post( $post_id, $seo_data );
	}

	/**
	 * Log activity for debugging.
	 *
	 * @param string $type    Log type.
	 * @param string $message Log message.
	 */
	private function log_activity( string $type, string $message ): void {
		$logs   = get_option( 'aiauthor_yoast_seo_logs', array() );
		$logs[] = array(
			'type'    => $type,
			'message' => $message,
			'date'    => current_time( 'mysql' ),
		);

		// Keep only the last 50 entries.
		$logs = array_slice( $logs, -50 );

		update_option( 'aiauthor_yoast_seo_logs', $logs );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( 'AI Author Yoast SEO [%s]: %s', strtoupper( $type ), $message ) );
		}
	}

	/**
	 * Get activity logs.
	 *
	 * @param int $limit Number of logs to return.
	 * @return array Array of log entries.
	 */
	public function get_logs( int $limit = 20 ): array {
		$logs = get_option( 'aiauthor_yoast_seo_logs', array() );
		return array_slice( array_reverse( $logs ), 0, $limit );
	}

	/**
	 * Clear activity logs.
	 */
	public function clear_logs(): void {
		delete_option( 'aiauthor_yoast_seo_logs' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function render_settings_page(): void {
		include AIAUTHOR_PLUGIN_DIR . 'includes/Views/yoast-seo.php';
	}
}
