<?php
/**
 * Facebook Integration
 *
 * Share/distribute posts on Facebook.
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Integrations\Facebook;

use AIAuthor\Integrations\IntegrationBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Facebook
 *
 * Handles sharing posts to Facebook.
 */
class Facebook extends IntegrationBase {

	/**
	 * Facebook Graph API URL.
	 *
	 * @var string
	 */
	const API_URL = 'https://graph.facebook.com/v18.0/';

	/**
	 * OAuth handler instance.
	 *
	 * @var OAuth|null
	 */
	private $oauth = null;

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	protected $default_settings = array(
		'enabled'           => false,
		'app_id'            => '',
		'app_secret'        => '',
		'access_token'      => '',
		'page_id'           => '',
		'page_access_token' => '',
		'page_name'         => '',
		'user_access_token' => '',
		'oauth_connected'   => false,
		'token_expires'     => 0,
		'auto_share'        => true,
		'share_as_link'     => true,
		'include_excerpt'   => true,
		'share_on_publish'  => true,
		'share_on_schedule' => true,
	);

	/**
	 * Get the OAuth handler.
	 *
	 * @return OAuth
	 */
	public function get_oauth(): OAuth {
		if ( null === $this->oauth ) {
			require_once __DIR__ . '/class-oauth.php';
			$this->oauth = new OAuth( $this );
		}
		return $this->oauth;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_id(): string {
		return 'facebook';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return __( 'Facebook', 'ai-author-for-websites' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Automatically share your AI-generated posts to Facebook Pages.', 'ai-author-for-websites' );
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
		return 'dashicons-facebook';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_category(): string {
		return 'social';
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_builtin(): bool {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function init(): void {
		// Initialize OAuth handler.
		$this->get_oauth()->init();

		// Hook into post publish to auto-share.
		add_action( 'publish_post', array( $this, 'maybe_share_on_publish' ), 10, 2 );

		// Hook into scheduled post publish.
		add_action( 'future_to_publish', array( $this, 'maybe_share_on_schedule' ), 10, 1 );

		// Register REST API endpoints.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Hook into auto-scheduler post creation.
		add_action( 'aiauthor_post_created', array( $this, 'maybe_share_scheduled_post' ), 20, 3 );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'ai-author/v1',
			'/facebook/share',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_share_post' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'ai-author/v1',
			'/facebook/test',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_test_connection' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * REST endpoint to share a post to Facebook.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response The response.
	 */
	public function rest_share_post( $request ): \WP_REST_Response {
		$post_id = absint( $request->get_param( 'post_id' ) );
		$message = sanitize_textarea_field( $request->get_param( 'message' ) );

		if ( empty( $post_id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Post ID is required.', 'ai-author-for-websites' ),
				),
				400
			);
		}

		$result = $this->share_post( $post_id, $message );

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
				'success'     => true,
				'facebook_id' => $result['id'],
				'message'     => __( 'Post shared to Facebook successfully!', 'ai-author-for-websites' ),
			),
			200
		);
	}

	/**
	 * REST endpoint to test Facebook connection.
	 *
	 * @param \WP_REST_Request $request The request object (unused but required by REST API).
	 * @return \WP_REST_Response The response.
	 */
	public function rest_test_connection( $request ): \WP_REST_Response { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$settings = $this->get_settings();

		if ( empty( $settings['page_access_token'] ) || empty( $settings['page_id'] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Page Access Token and Page ID are required.', 'ai-author-for-websites' ),
				),
				400
			);
		}

		// Test by fetching page info.
		$url      = self::API_URL . $settings['page_id'] . '?access_token=' . $settings['page_access_token'] . '&fields=name,id';
		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $response->get_error_message(),
				),
				400
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['error'] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $data['error']['message'] ?? __( 'Unknown error', 'ai-author-for-websites' ),
				),
				400
			);
		}

		return new \WP_REST_Response(
			array(
				'success'   => true,
				'page_name' => $data['name'] ?? '',
				'page_id'   => $data['id'] ?? '',
				'message'   => sprintf(
					/* translators: %s: Facebook page name */
					__( 'Connected to Facebook Page: %s', 'ai-author-for-websites' ),
					$data['name'] ?? ''
				),
			),
			200
		);
	}

	/**
	 * Maybe share post when published.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function maybe_share_on_publish( int $post_id, $post ): void {
		// Check if integration is enabled.
		if ( ! $this->is_enabled() ) {
			return;
		}

		$settings = $this->get_settings();

		// Check if auto-share is enabled.
		if ( empty( $settings['auto_share'] ) || empty( $settings['share_on_publish'] ) ) {
			return;
		}

		// Check if already shared.
		if ( get_post_meta( $post_id, '_aiauthor_facebook_shared', true ) ) {
			return;
		}

		// Only share posts.
		if ( 'post' !== $post->post_type ) {
			return;
		}

		$this->share_post( $post_id );
	}

	/**
	 * Maybe share post when scheduled post becomes published.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function maybe_share_on_schedule( $post ): void {
		// Check if integration is enabled.
		if ( ! $this->is_enabled() ) {
			return;
		}

		$settings = $this->get_settings();

		// Check if auto-share is enabled.
		if ( empty( $settings['auto_share'] ) || empty( $settings['share_on_schedule'] ) ) {
			return;
		}

		// Check if already shared.
		if ( get_post_meta( $post->ID, '_aiauthor_facebook_shared', true ) ) {
			return;
		}

		// Only share posts.
		if ( 'post' !== $post->post_type ) {
			return;
		}

		$this->share_post( $post->ID );
	}

	/**
	 * Maybe share post created by auto-scheduler.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $title   Post title.
	 * @param string $content Post content.
	 */
	public function maybe_share_scheduled_post( int $post_id, string $title, string $content ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Check if integration is enabled.
		if ( ! $this->is_enabled() ) {
			return;
		}

		$settings = $this->get_settings();

		// Check if auto-share is enabled.
		if ( empty( $settings['auto_share'] ) ) {
			return;
		}

		$post = get_post( $post_id );

		// Only share if published.
		if ( ! $post || 'publish' !== $post->post_status ) {
			return;
		}

		$this->share_post( $post_id );
	}

	/**
	 * Share a post to Facebook.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $message Optional custom message.
	 * @return array Result with 'id' or 'error'.
	 */
	public function share_post( int $post_id, string $message = '' ): array {
		$settings = $this->get_settings();

		if ( empty( $settings['page_access_token'] ) || empty( $settings['page_id'] ) ) {
			return array( 'error' => __( 'Facebook Page Access Token and Page ID are required.', 'ai-author-for-websites' ) );
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			return array( 'error' => __( 'Post not found.', 'ai-author-for-websites' ) );
		}

		// Build the message.
		if ( empty( $message ) ) {
			$message = $post->post_title;

			if ( ! empty( $settings['include_excerpt'] ) ) {
				$excerpt = wp_strip_all_tags( get_the_excerpt( $post ) );
				if ( ! empty( $excerpt ) ) {
					$message .= "\n\n" . $excerpt;
				}
			}
		}

		// Prepare post data.
		$post_data = array(
			'message'      => $message,
			'access_token' => $settings['page_access_token'],
		);

		// Add link if sharing as link post.
		if ( ! empty( $settings['share_as_link'] ) ) {
			$post_data['link'] = get_permalink( $post_id );
		}

		// Make API request.
		$url      = self::API_URL . $settings['page_id'] . '/feed';
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 30,
				'body'    => $post_data,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->log_share( $post_id, false, $response->get_error_message() );
			return array( 'error' => $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['error'] ) ) {
			$error_msg = $data['error']['message'] ?? __( 'Unknown error', 'ai-author-for-websites' );
			$this->log_share( $post_id, false, $error_msg );
			return array( 'error' => $error_msg );
		}

		if ( isset( $data['id'] ) ) {
			// Mark as shared.
			update_post_meta( $post_id, '_aiauthor_facebook_shared', true );
			update_post_meta( $post_id, '_aiauthor_facebook_post_id', $data['id'] );
			update_post_meta( $post_id, '_aiauthor_facebook_shared_at', current_time( 'mysql' ) );

			$this->log_share( $post_id, true );

			return array( 'id' => $data['id'] );
		}

		return array( 'error' => __( 'Failed to share post to Facebook.', 'ai-author-for-websites' ) );
	}

	/**
	 * Log share attempt.
	 *
	 * @param int    $post_id Post ID.
	 * @param bool   $success Whether share was successful.
	 * @param string $error   Error message if failed.
	 */
	private function log_share( int $post_id, bool $success, string $error = '' ): void {
		$logs   = get_option( 'aiauthor_facebook_logs', array() );
		$logs[] = array(
			'post_id' => $post_id,
			'title'   => get_the_title( $post_id ),
			'success' => $success,
			'error'   => $error,
			'date'    => current_time( 'mysql' ),
		);

		// Keep only the last 50 entries.
		$logs = array_slice( $logs, -50 );

		update_option( 'aiauthor_facebook_logs', $logs );
	}

	/**
	 * Get share logs.
	 *
	 * @param int $limit Number of logs to return.
	 * @return array Array of log entries.
	 */
	public function get_logs( int $limit = 20 ): array {
		$logs = get_option( 'aiauthor_facebook_logs', array() );
		return array_slice( array_reverse( $logs ), 0, $limit );
	}

	/**
	 * {@inheritdoc}
	 */
	public function render_settings_page(): void {
		include AIAUTHOR_PLUGIN_DIR . 'includes/Views/facebook.php';
	}
}
