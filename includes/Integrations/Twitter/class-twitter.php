<?php
/**
 * Twitter/X Integration
 *
 * Share/distribute posts on Twitter/X.
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Integrations\Twitter;

use AIAuthor\Integrations\IntegrationBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Twitter
 *
 * Handles sharing posts to Twitter/X.
 */
class Twitter extends IntegrationBase {

	/**
	 * Twitter API URL.
	 *
	 * @var string
	 */
	const API_URL = 'https://api.twitter.com/2/';

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
		'enabled'              => false,
		'client_id'            => '',
		'client_secret'        => '',
		'api_key'              => '',
		'api_secret'           => '',
		'access_token'         => '',
		'access_secret'        => '',
		'bearer_token'         => '',
		'oauth2_access_token'  => '',
		'oauth2_refresh_token' => '',
		'oauth2_token_expires' => 0,
		'oauth_connected'      => false,
		'twitter_username'     => '',
		'twitter_name'         => '',
		'twitter_id'           => '',
		'auto_share'           => true,
		'include_link'         => true,
		'include_hashtags'     => true,
		'max_hashtags'         => 3,
		'share_on_publish'     => true,
		'share_on_schedule'    => true,
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
		return 'twitter';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return __( 'Twitter/X', 'ai-author-for-websites' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Automatically share your AI-generated posts to Twitter/X.', 'ai-author-for-websites' );
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
		return 'dashicons-twitter';
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
			'/twitter/share',
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
			'/twitter/test',
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
	 * REST endpoint to share a post to Twitter.
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
				'success'    => true,
				'twitter_id' => $result['id'],
				'message'    => __( 'Post shared to Twitter successfully!', 'ai-author-for-websites' ),
			),
			200
		);
	}

	/**
	 * REST endpoint to test Twitter connection.
	 *
	 * @param \WP_REST_Request $request The request object (unused but required by REST API).
	 * @return \WP_REST_Response The response.
	 */
	public function rest_test_connection( $request ): \WP_REST_Response { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$settings = $this->get_settings();

		if ( empty( $settings['bearer_token'] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Bearer Token is required.', 'ai-author-for-websites' ),
				),
				400
			);
		}

		// Test by fetching authenticated user info.
		$url      = self::API_URL . 'users/me';
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $settings['bearer_token'],
				),
			)
		);

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

		if ( isset( $data['errors'] ) ) {
			$error_msg = isset( $data['errors'][0]['message'] ) ? $data['errors'][0]['message'] : __( 'Unknown error', 'ai-author-for-websites' );
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $error_msg,
				),
				400
			);
		}

		if ( isset( $data['data'] ) ) {
			return new \WP_REST_Response(
				array(
					'success'  => true,
					'username' => $data['data']['username'] ?? '',
					'name'     => $data['data']['name'] ?? '',
					'message'  => sprintf(
						/* translators: %s: Twitter username */
						__( 'Connected to Twitter as @%s', 'ai-author-for-websites' ),
						$data['data']['username'] ?? ''
					),
				),
				200
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Could not verify Twitter connection.', 'ai-author-for-websites' ),
			),
			400
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
		if ( get_post_meta( $post_id, '_aiauthor_twitter_shared', true ) ) {
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
		if ( get_post_meta( $post->ID, '_aiauthor_twitter_shared', true ) ) {
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
	 * Share a post to Twitter.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $message Optional custom message.
	 * @return array Result with 'id' or 'error'.
	 */
	public function share_post( int $post_id, string $message = '' ): array {
		$settings = $this->get_settings();

		$post = get_post( $post_id );

		if ( ! $post ) {
			return array( 'error' => __( 'Post not found.', 'ai-author-for-websites' ) );
		}

		// Build the tweet text.
		if ( empty( $message ) ) {
			$message = $this->build_tweet_text( $post, $settings );
		}

		// Try OAuth 2.0 first if connected.
		$oauth2_token = $this->get_oauth()->get_valid_access_token();
		if ( $oauth2_token ) {
			$result = $this->post_tweet_oauth2( $message, $oauth2_token );
		} elseif ( ! empty( $settings['api_key'] ) && ! empty( $settings['api_secret'] ) &&
					! empty( $settings['access_token'] ) && ! empty( $settings['access_secret'] ) ) {
			// Fall back to OAuth 1.0a.
			$result = $this->post_tweet( $message, $settings );
		} else {
			return array( 'error' => __( 'Twitter API credentials are required for posting. Please connect via OAuth or enter API credentials.', 'ai-author-for-websites' ) );
		}

		if ( isset( $result['error'] ) ) {
			$this->log_share( $post_id, false, $result['error'] );
			return $result;
		}

		if ( isset( $result['id'] ) ) {
			// Mark as shared.
			update_post_meta( $post_id, '_aiauthor_twitter_shared', true );
			update_post_meta( $post_id, '_aiauthor_twitter_post_id', $result['id'] );
			update_post_meta( $post_id, '_aiauthor_twitter_shared_at', current_time( 'mysql' ) );

			$this->log_share( $post_id, true );

			return $result;
		}

		return array( 'error' => __( 'Failed to share post to Twitter.', 'ai-author-for-websites' ) );
	}

	/**
	 * Post a tweet using OAuth 2.0.
	 *
	 * @param string $text         Tweet text.
	 * @param string $access_token OAuth 2.0 access token.
	 * @return array Result with 'id' or 'error'.
	 */
	private function post_tweet_oauth2( string $text, string $access_token ): array {
		$url = self::API_URL . 'tweets';

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( array( 'text' => $text ) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['errors'] ) ) {
			$error_msg = isset( $data['errors'][0]['message'] ) ? $data['errors'][0]['message'] : __( 'Unknown error', 'ai-author-for-websites' );
			return array( 'error' => $error_msg );
		}

		if ( isset( $data['data']['id'] ) ) {
			return array( 'id' => $data['data']['id'] );
		}

		return array( 'error' => __( 'Failed to post tweet.', 'ai-author-for-websites' ) );
	}

	/**
	 * Build tweet text from post.
	 *
	 * @param \WP_Post $post     Post object.
	 * @param array    $settings Integration settings.
	 * @return string Tweet text.
	 */
	private function build_tweet_text( $post, array $settings ): string {
		$text = $post->post_title;

		// Add link.
		if ( ! empty( $settings['include_link'] ) ) {
			$link  = get_permalink( $post->ID );
			$text .= "\n\n" . $link;
		}

		// Add hashtags.
		if ( ! empty( $settings['include_hashtags'] ) ) {
			$hashtags     = $this->generate_hashtags( $post, $settings['max_hashtags'] ?? 3 );
			$hashtag_text = implode( ' ', $hashtags );

			// Check character limit (280 chars).
			if ( strlen( $text . "\n\n" . $hashtag_text ) <= 280 ) {
				$text .= "\n\n" . $hashtag_text;
			}
		}

		// Trim to 280 characters if needed.
		if ( strlen( $text ) > 280 ) {
			$text = substr( $text, 0, 277 ) . '...';
		}

		return $text;
	}

	/**
	 * Generate hashtags from post tags.
	 *
	 * @param \WP_Post $post  Post object.
	 * @param int      $limit Maximum number of hashtags.
	 * @return array Array of hashtags.
	 */
	private function generate_hashtags( $post, int $limit = 3 ): array {
		$tags = get_the_tags( $post->ID );

		if ( empty( $tags ) ) {
			return array();
		}

		$hashtags = array();
		foreach ( array_slice( $tags, 0, $limit ) as $tag ) {
			// Convert tag to hashtag format (remove spaces, add #).
			$hashtag    = '#' . preg_replace( '/\s+/', '', ucwords( $tag->name ) );
			$hashtags[] = $hashtag;
		}

		return $hashtags;
	}

	/**
	 * Post a tweet using OAuth 1.0a.
	 *
	 * @param string $text     Tweet text.
	 * @param array  $settings API settings.
	 * @return array Result with 'id' or 'error'.
	 */
	private function post_tweet( string $text, array $settings ): array {
		$url = 'https://api.twitter.com/2/tweets';

		// Build OAuth signature.
		$oauth_params = array(
			'oauth_consumer_key'     => $settings['api_key'],
			'oauth_nonce'            => wp_generate_password( 32, false ),
			'oauth_signature_method' => 'HMAC-SHA256',
			'oauth_timestamp'        => time(),
			'oauth_token'            => $settings['access_token'],
			'oauth_version'          => '1.0',
		);

		// Generate signature.
		$base_string = $this->build_base_string( 'POST', $url, $oauth_params );
		$signing_key = rawurlencode( $settings['api_secret'] ) . '&' . rawurlencode( $settings['access_secret'] );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Required for OAuth signature.
		$signature = base64_encode( hash_hmac( 'sha256', $base_string, $signing_key, true ) );

		$oauth_params['oauth_signature'] = $signature;

		// Build Authorization header.
		$oauth_header = 'OAuth ';
		$oauth_parts  = array();
		foreach ( $oauth_params as $key => $value ) {
			$oauth_parts[] = rawurlencode( $key ) . '="' . rawurlencode( $value ) . '"';
		}
		$oauth_header .= implode( ', ', $oauth_parts );

		// Make request.
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => $oauth_header,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( array( 'text' => $text ) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['errors'] ) ) {
			$error_msg = isset( $data['errors'][0]['message'] ) ? $data['errors'][0]['message'] : __( 'Unknown error', 'ai-author-for-websites' );
			return array( 'error' => $error_msg );
		}

		if ( isset( $data['data']['id'] ) ) {
			return array( 'id' => $data['data']['id'] );
		}

		return array( 'error' => __( 'Failed to post tweet.', 'ai-author-for-websites' ) );
	}

	/**
	 * Build OAuth base string.
	 *
	 * @param string $method HTTP method.
	 * @param string $url    Request URL.
	 * @param array  $params OAuth parameters.
	 * @return string Base string.
	 */
	private function build_base_string( string $method, string $url, array $params ): string {
		ksort( $params );

		$param_string = '';
		foreach ( $params as $key => $value ) {
			$param_string .= rawurlencode( $key ) . '=' . rawurlencode( $value ) . '&';
		}
		$param_string = rtrim( $param_string, '&' );

		return strtoupper( $method ) . '&' . rawurlencode( $url ) . '&' . rawurlencode( $param_string );
	}

	/**
	 * Log share attempt.
	 *
	 * @param int    $post_id Post ID.
	 * @param bool   $success Whether share was successful.
	 * @param string $error   Error message if failed.
	 */
	private function log_share( int $post_id, bool $success, string $error = '' ): void {
		$logs   = get_option( 'aiauthor_twitter_logs', array() );
		$logs[] = array(
			'post_id' => $post_id,
			'title'   => get_the_title( $post_id ),
			'success' => $success,
			'error'   => $error,
			'date'    => current_time( 'mysql' ),
		);

		// Keep only the last 50 entries.
		$logs = array_slice( $logs, -50 );

		update_option( 'aiauthor_twitter_logs', $logs );
	}

	/**
	 * Get share logs.
	 *
	 * @param int $limit Number of logs to return.
	 * @return array Array of log entries.
	 */
	public function get_logs( int $limit = 20 ): array {
		$logs = get_option( 'aiauthor_twitter_logs', array() );
		return array_slice( array_reverse( $logs ), 0, $limit );
	}

	/**
	 * {@inheritdoc}
	 */
	public function render_settings_page(): void {
		include AIAUTHOR_PLUGIN_DIR . 'includes/Views/twitter.php';
	}
}
