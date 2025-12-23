<?php
/**
 * Rankmath SEO Integration
 *
 * Automatically generates and sets SEO metadata using AI for posts.
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Integrations\Rankmath;

use AIAuthor\Integrations\IntegrationBase;
use AIAuthor\Core\Plugin;
use AIEngine\AIEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rankmath
 *
 * Handles integration with Rankmath SEO plugin.
 */
class Rankmath extends IntegrationBase {

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	protected $default_settings = array(
		'enabled'                     => false,
		'auto_generate_meta'          => true,
		'generate_focus_keyword'      => true,
		'generate_meta_desc'          => true,
		'meta_desc_length'            => 160,
		'generate_secondary_keywords' => false,
		'generate_og_meta'            => false,
	);

	/**
	 * {@inheritdoc}
	 */
	public function get_id(): string {
		return 'rankmath';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return __( 'Rank Math SEO', 'ai-author-for-websites' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Automatically generate SEO meta titles, descriptions, and focus keywords for AI-generated posts using Rank Math SEO.', 'ai-author-for-websites' );
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
		return 'dashicons-chart-line';
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
	 * Check if Rankmath plugin is active.
	 *
	 * @return bool True if Rankmath is active.
	 */
	public function is_rankmath_active(): bool {
		return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' );
	}

	/**
	 * {@inheritdoc}
	 *
	 * Override to check if Rank Math is active.
	 * Integration cannot be enabled if Rank Math is not installed.
	 */
	public function is_enabled(): bool {
		// If Rank Math is not active, integration cannot be enabled.
		if ( ! $this->is_rankmath_active() ) {
			return false;
		}
		return parent::is_enabled();
	}

	/**
	 * {@inheritdoc}
	 *
	 * Override to prevent enabling if Rank Math is not active.
	 */
	public function enable(): bool {
		if ( ! $this->is_rankmath_active() ) {
			return false;
		}
		return parent::enable();
	}

	/**
	 * Get integration data as array.
	 *
	 * Override to include plugin availability status.
	 *
	 * @return array Integration data.
	 */
	public function to_array(): array {
		$data                      = parent::to_array();
		$data['plugin_active']     = $this->is_rankmath_active();
		$data['plugin_required']   = 'Rank Math SEO';
		$data['availability_note'] = $this->is_rankmath_active() ? '' : __( 'Rank Math SEO plugin is required for this integration.', 'ai-author-for-websites' );
		return $data;
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
			'/rankmath/generate',
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
			'/rankmath/apply',
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
		$post_id            = absint( $request->get_param( 'post_id' ) );
		$focus_keyword      = sanitize_text_field( $request->get_param( 'focus_keyword' ) );
		$secondary_keywords = $request->get_param( 'secondary_keywords' );
		$meta_desc          = sanitize_textarea_field( $request->get_param( 'meta_description' ) );
		$seo_title          = sanitize_text_field( $request->get_param( 'seo_title' ) );

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
				'focus_keyword'      => $focus_keyword,
				'secondary_keywords' => $secondary_keywords,
				'meta_description'   => $meta_desc,
				'seo_title'          => $seo_title,
			)
		);

		if ( ! $result ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to apply SEO data. Make sure Rank Math is active.', 'ai-author-for-websites' ),
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

			$meta_desc_length   = $settings['meta_desc_length'] ?? 155;
			$generate_secondary = ! empty( $settings['generate_secondary_keywords'] );
			$content_excerpt    = wp_trim_words( wp_strip_all_tags( $content ), 300 );

			$prompt  = "Generate SEO metadata for the following blog post.\n\n";
			$prompt .= "Title: {$title}\n\n";
			$prompt .= "Content (excerpt): {$content_excerpt}\n\n";
			$prompt .= "Generate the following with EXACT length requirements:\n\n";
			$prompt .= "1. Focus Keyword: A 2-4 word phrase that best represents the main topic (e.g., 'productivity tips', 'healthy recipes', 'digital marketing strategies')\n\n";
			$prompt .= "2. Meta Description: MUST be between 145-{$meta_desc_length} characters. This is the snippet shown in Google search results. ";
			$prompt .= 'Make it compelling with a call-to-action. Include the focus keyword naturally. ';
			$prompt .= "Example length: 'Discover 10 proven productivity tips that will transform your workday. Learn how to manage time effectively and achieve more in less time.'\n\n";
			$prompt .= '3. SEO Title: MUST be between 50-60 characters. Include the focus keyword near the beginning. ';
			$prompt .= "Make it click-worthy. Example: '10 Productivity Tips to Transform Your Workday | Guide'\n\n";

			if ( $generate_secondary ) {
				$prompt .= "4. Secondary Keywords: 3-5 related keywords/phrases that support the main topic\n\n";
			}

			$prompt .= "IMPORTANT: Meta description must be at least 145 characters and SEO title must be at least 50 characters.\n\n";
			$prompt .= "Return the response in this exact JSON format:\n";
			$prompt .= '{"focus_keyword": "your keyword", "meta_description": "your 145-155 character description here", "seo_title": "your 50-60 character title here"';

			if ( $generate_secondary ) {
				$prompt .= ', "secondary_keywords": ["keyword1", "keyword2", "keyword3"]';
			}

			$prompt .= '}';
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

			$result = array(
				'focus_keyword'    => $seo_data['focus_keyword'] ?? '',
				'meta_description' => $seo_data['meta_description'] ?? '',
				'seo_title'        => $seo_data['seo_title'] ?? '',
			);

			if ( $generate_secondary && isset( $seo_data['secondary_keywords'] ) ) {
				$result['secondary_keywords'] = $seo_data['secondary_keywords'];
			}

			return $result;
		} catch ( \Exception $e ) {
			return array( 'error' => $e->getMessage() );
		}
	}

	/**
	 * Apply SEO data to a post using Rankmath meta fields.
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $seo_data SEO data to apply.
	 * @return bool True if successful.
	 */
	public function apply_seo_data_to_post( int $post_id, array $seo_data ): bool {
		if ( ! $this->is_rankmath_active() ) {
			$this->log_activity( 'error', 'Rank Math is not active' );
			return false;
		}

		$applied = false;

		// Focus Keyword.
		if ( ! empty( $seo_data['focus_keyword'] ) ) {
			update_post_meta( $post_id, 'rank_math_focus_keyword', sanitize_text_field( $seo_data['focus_keyword'] ) );
			$applied = true;
		}

		// Secondary Keywords (Rank Math stores these comma-separated in focus_keyword).
		if ( ! empty( $seo_data['secondary_keywords'] ) && is_array( $seo_data['secondary_keywords'] ) ) {
			$primary   = get_post_meta( $post_id, 'rank_math_focus_keyword', true );
			$secondary = implode( ',', array_map( 'sanitize_text_field', $seo_data['secondary_keywords'] ) );

			if ( $primary ) {
				// Rank Math stores all keywords comma-separated.
				update_post_meta( $post_id, 'rank_math_focus_keyword', $primary . ',' . $secondary );
			}
			$applied = true;
		}

		// Meta Description.
		if ( ! empty( $seo_data['meta_description'] ) ) {
			update_post_meta( $post_id, 'rank_math_description', sanitize_textarea_field( $seo_data['meta_description'] ) );
			$applied = true;
		}

		// SEO Title.
		if ( ! empty( $seo_data['seo_title'] ) ) {
			update_post_meta( $post_id, 'rank_math_title', sanitize_text_field( $seo_data['seo_title'] ) );
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

		// Check if Rankmath is active.
		if ( ! $this->is_rankmath_active() ) {
			$this->log_activity( 'skipped', 'Rank Math is not active' );
			return;
		}

		// Check if auto-generate is enabled.
		if ( empty( $settings['auto_generate_meta'] ) ) {
			$this->log_activity( 'skipped', 'Auto-generate SEO is disabled' );
			return;
		}

		// Check if SEO was already applied via the form submission.
		$seo_already_applied = get_post_meta( $post_id, '_aiauthor_seo_applied', true );
		if ( $seo_already_applied ) {
			$this->log_activity( 'skipped', 'SEO data was provided by user, not auto-generating' );
			// Clean up the flag.
			delete_post_meta( $post_id, '_aiauthor_seo_applied' );
			return;
		}

		// Check if SEO data already exists - don't overwrite existing data.
		$existing_focus_kw  = get_post_meta( $post_id, 'rank_math_focus_keyword', true );
		$existing_meta_desc = get_post_meta( $post_id, 'rank_math_description', true );
		$existing_seo_title = get_post_meta( $post_id, 'rank_math_title', true );

		if ( ! empty( $existing_focus_kw ) || ! empty( $existing_meta_desc ) || ! empty( $existing_seo_title ) ) {
			$this->log_activity( 'skipped', 'SEO data already exists for this post, not overwriting' );
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
		$logs   = get_option( 'aiauthor_rankmath_logs', array() );
		$logs[] = array(
			'type'    => $type,
			'message' => $message,
			'date'    => current_time( 'mysql' ),
		);

		// Keep only the last 50 entries.
		$logs = array_slice( $logs, -50 );

		update_option( 'aiauthor_rankmath_logs', $logs );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( 'AI Author Rank Math [%s]: %s', strtoupper( $type ), $message ) );
		}
	}

	/**
	 * Get activity logs.
	 *
	 * @param int $limit Number of logs to return.
	 * @return array Array of log entries.
	 */
	public function get_logs( int $limit = 20 ): array {
		$logs = get_option( 'aiauthor_rankmath_logs', array() );
		return array_slice( array_reverse( $logs ), 0, $limit );
	}

	/**
	 * Clear activity logs.
	 */
	public function clear_logs(): void {
		delete_option( 'aiauthor_rankmath_logs' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function render_settings_page(): void {
		include AIAUTHOR_PLUGIN_DIR . 'includes/Views/rankmath.php';
	}
}
