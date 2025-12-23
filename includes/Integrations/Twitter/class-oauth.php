<?php
/**
 * Twitter OAuth Handler
 *
 * Handles OAuth 2.0 authorization flow with PKCE for Twitter/X.
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Integrations\Twitter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OAuth
 *
 * Handles Twitter OAuth 2.0 authorization with PKCE.
 */
class OAuth {

	/**
	 * Twitter OAuth authorization URL.
	 *
	 * @var string
	 */
	const AUTHORIZE_URL = 'https://twitter.com/i/oauth2/authorize';

	/**
	 * Twitter OAuth token URL.
	 *
	 * @var string
	 */
	const TOKEN_URL = 'https://api.twitter.com/2/oauth2/token';

	/**
	 * Twitter API URL.
	 *
	 * @var string
	 */
	const API_URL = 'https://api.twitter.com/2/';

	/**
	 * Required OAuth scopes.
	 *
	 * @var array
	 */
	const SCOPES = array(
		'tweet.read',
		'tweet.write',
		'users.read',
		'offline.access',
	);

	/**
	 * Option key for storing OAuth state.
	 *
	 * @var string
	 */
	const STATE_OPTION = 'aiauthor_twitter_oauth_state';

	/**
	 * Option key for storing PKCE code verifier.
	 *
	 * @var string
	 */
	const VERIFIER_OPTION = 'aiauthor_twitter_oauth_verifier';

	/**
	 * Integration instance.
	 *
	 * @var Twitter
	 */
	private $integration;

	/**
	 * Constructor.
	 *
	 * @param Twitter $integration The Twitter integration instance.
	 */
	public function __construct( Twitter $integration ) {
		$this->integration = $integration;
	}

	/**
	 * Initialize OAuth hooks.
	 */
	public function init(): void {
		add_action( 'admin_init', array( $this, 'handle_oauth_callback' ) );
		add_action( 'wp_ajax_aiauthor_twitter_connect', array( $this, 'ajax_start_oauth' ) );
		add_action( 'wp_ajax_aiauthor_twitter_disconnect', array( $this, 'ajax_disconnect' ) );
		add_action( 'wp_ajax_aiauthor_twitter_refresh_token', array( $this, 'ajax_refresh_token' ) );
	}

	/**
	 * Get the OAuth callback URL.
	 *
	 * @return string Callback URL.
	 */
	public function get_callback_url(): string {
		return admin_url( 'admin.php?page=ai-author-integrations&integration=twitter&oauth=callback' );
	}

	/**
	 * Generate PKCE code verifier.
	 *
	 * @return string Code verifier.
	 */
	private function generate_code_verifier(): string {
		$random = wp_generate_password( 64, false );
		return rtrim( strtr( $random, '+/', '-_' ), '=' );
	}

	/**
	 * Generate PKCE code challenge from verifier.
	 *
	 * @param string $verifier Code verifier.
	 * @return string Code challenge.
	 */
	private function generate_code_challenge( string $verifier ): string {
		$hash = hash( 'sha256', $verifier, true );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Required for PKCE.
		return rtrim( strtr( base64_encode( $hash ), '+/', '-_' ), '=' );
	}

	/**
	 * Generate authorization URL.
	 *
	 * @return string|null Authorization URL or null if not configured.
	 */
	public function get_authorization_url(): ?string {
		$settings = $this->integration->get_settings();

		if ( empty( $settings['client_id'] ) ) {
			return null;
		}

		// Generate state for CSRF protection.
		$state = wp_generate_password( 32, false );
		update_option( self::STATE_OPTION, $state );

		// Generate PKCE verifier and challenge.
		$verifier  = $this->generate_code_verifier();
		$challenge = $this->generate_code_challenge( $verifier );
		update_option( self::VERIFIER_OPTION, $verifier );

		$params = array(
			'response_type'         => 'code',
			'client_id'             => $settings['client_id'],
			'redirect_uri'          => $this->get_callback_url(),
			'scope'                 => implode( ' ', self::SCOPES ),
			'state'                 => $state,
			'code_challenge'        => $challenge,
			'code_challenge_method' => 'S256',
		);

		return self::AUTHORIZE_URL . '?' . http_build_query( $params );
	}

	/**
	 * AJAX handler to start OAuth flow.
	 */
	public function ajax_start_oauth(): void {
		check_ajax_referer( 'aiauthor_twitter_oauth', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-author-for-websites' ) ) );
		}

		$auth_url = $this->get_authorization_url();

		if ( ! $auth_url ) {
			wp_send_json_error( array( 'message' => __( 'Please enter your Twitter Client ID first.', 'ai-author-for-websites' ) ) );
		}

		wp_send_json_success( array( 'auth_url' => $auth_url ) );
	}

	/**
	 * Handle OAuth callback.
	 */
	public function handle_oauth_callback(): void {
		// Check if this is a Twitter OAuth callback.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback doesn't use nonce.
		if ( ! isset( $_GET['page'], $_GET['integration'], $_GET['oauth'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'ai-author-integrations' !== $_GET['page'] || 'twitter' !== $_GET['integration'] || 'callback' !== $_GET['oauth'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$error = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';

		// Check for errors.
		if ( $error ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$error_desc = isset( $_GET['error_description'] ) ? sanitize_text_field( wp_unslash( $_GET['error_description'] ) ) : $error;
			$this->redirect_with_message( 'error', $error_desc );
			return;
		}

		// Verify state for CSRF protection.
		$stored_state = get_option( self::STATE_OPTION );
		if ( ! $stored_state || $state !== $stored_state ) {
			$this->redirect_with_message( 'error', __( 'Invalid state. Please try again.', 'ai-author-for-websites' ) );
			return;
		}

		// Get the stored verifier.
		$verifier = get_option( self::VERIFIER_OPTION );

		// Clear stored state and verifier.
		delete_option( self::STATE_OPTION );
		delete_option( self::VERIFIER_OPTION );

		if ( empty( $code ) ) {
			$this->redirect_with_message( 'error', __( 'No authorization code received.', 'ai-author-for-websites' ) );
			return;
		}

		if ( empty( $verifier ) ) {
			$this->redirect_with_message( 'error', __( 'Missing code verifier. Please try again.', 'ai-author-for-websites' ) );
			return;
		}

		// Exchange code for access token.
		$result = $this->exchange_code_for_token( $code, $verifier );

		if ( isset( $result['error'] ) ) {
			$this->redirect_with_message( 'error', $result['error'] );
			return;
		}

		// Get user info.
		$user_info = $this->get_user_info( $result['access_token'] );

		// Store the tokens.
		$settings                         = $this->integration->get_settings();
		$settings['oauth2_access_token']  = $result['access_token'];
		$settings['oauth2_refresh_token'] = $result['refresh_token'] ?? '';
		$settings['oauth2_token_expires'] = time() + ( $result['expires_in'] ?? 7200 );
		$settings['oauth_connected']      = true;

		if ( ! isset( $user_info['error'] ) && isset( $user_info['data'] ) ) {
			$settings['twitter_username'] = $user_info['data']['username'] ?? '';
			$settings['twitter_name']     = $user_info['data']['name'] ?? '';
			$settings['twitter_id']       = $user_info['data']['id'] ?? '';
		}

		$this->integration->update_settings( $settings );

		$username = $settings['twitter_username'] ?? '';
		$message  = $username
			/* translators: %s: Twitter username */
			? sprintf( __( 'Connected to Twitter as @%s', 'ai-author-for-websites' ), $username )
			: __( 'Connected to Twitter successfully!', 'ai-author-for-websites' );

		$this->redirect_with_message( 'success', $message );
	}

	/**
	 * Exchange authorization code for access token.
	 *
	 * @param string $code     Authorization code.
	 * @param string $verifier PKCE code verifier.
	 * @return array Token data or error.
	 */
	private function exchange_code_for_token( string $code, string $verifier ): array {
		$settings = $this->integration->get_settings();

		$body = array(
			'code'          => $code,
			'grant_type'    => 'authorization_code',
			'client_id'     => $settings['client_id'],
			'redirect_uri'  => $this->get_callback_url(),
			'code_verifier' => $verifier,
		);

		$headers = array(
			'Content-Type' => 'application/x-www-form-urlencoded',
		);

		// Add Basic auth if client secret is provided.
		if ( ! empty( $settings['client_secret'] ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Required for HTTP Basic auth.
			$headers['Authorization'] = 'Basic ' . base64_encode( $settings['client_id'] . ':' . $settings['client_secret'] );
		}

		$response = wp_remote_post(
			self::TOKEN_URL,
			array(
				'timeout' => 30,
				'headers' => $headers,
				'body'    => http_build_query( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['error'] ) ) {
			$error_msg = $data['error_description'] ?? $data['error'];
			return array( 'error' => $error_msg );
		}

		if ( ! isset( $data['access_token'] ) ) {
			return array( 'error' => __( 'No access token in response.', 'ai-author-for-websites' ) );
		}

		return $data;
	}

	/**
	 * Refresh access token.
	 *
	 * @return array New token data or error.
	 */
	public function refresh_token(): array {
		$settings = $this->integration->get_settings();

		if ( empty( $settings['oauth2_refresh_token'] ) ) {
			return array( 'error' => __( 'No refresh token available.', 'ai-author-for-websites' ) );
		}

		$body = array(
			'refresh_token' => $settings['oauth2_refresh_token'],
			'grant_type'    => 'refresh_token',
			'client_id'     => $settings['client_id'],
		);

		$headers = array(
			'Content-Type' => 'application/x-www-form-urlencoded',
		);

		// Add Basic auth if client secret is provided.
		if ( ! empty( $settings['client_secret'] ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Required for HTTP Basic auth.
			$headers['Authorization'] = 'Basic ' . base64_encode( $settings['client_id'] . ':' . $settings['client_secret'] );
		}

		$response = wp_remote_post(
			self::TOKEN_URL,
			array(
				'timeout' => 30,
				'headers' => $headers,
				'body'    => http_build_query( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['error'] ) ) {
			$error_msg = $data['error_description'] ?? $data['error'];
			return array( 'error' => $error_msg );
		}

		if ( isset( $data['access_token'] ) ) {
			// Update stored tokens.
			$settings['oauth2_access_token']  = $data['access_token'];
			$settings['oauth2_token_expires'] = time() + ( $data['expires_in'] ?? 7200 );

			if ( isset( $data['refresh_token'] ) ) {
				$settings['oauth2_refresh_token'] = $data['refresh_token'];
			}

			$this->integration->update_settings( $settings );
		}

		return $data;
	}

	/**
	 * Get user info from Twitter.
	 *
	 * @param string $access_token Access token.
	 * @return array User info or error.
	 */
	private function get_user_info( string $access_token ): array {
		$response = wp_remote_get(
			self::API_URL . 'users/me',
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		return json_decode( $body, true );
	}

	/**
	 * Get valid access token, refreshing if necessary.
	 *
	 * @return string|null Access token or null.
	 */
	public function get_valid_access_token(): ?string {
		$settings = $this->integration->get_settings();

		if ( empty( $settings['oauth2_access_token'] ) ) {
			return null;
		}

		// Check if token is expired or about to expire (within 5 minutes).
		$expires = $settings['oauth2_token_expires'] ?? 0;
		if ( $expires > 0 && $expires < ( time() + 300 ) ) {
			// Try to refresh.
			$result = $this->refresh_token();
			if ( isset( $result['access_token'] ) ) {
				return $result['access_token'];
			}
			// Refresh failed.
			return null;
		}

		return $settings['oauth2_access_token'];
	}

	/**
	 * AJAX handler to refresh token.
	 */
	public function ajax_refresh_token(): void {
		check_ajax_referer( 'aiauthor_twitter_oauth', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-author-for-websites' ) ) );
		}

		$result = $this->refresh_token();

		if ( isset( $result['error'] ) ) {
			wp_send_json_error( array( 'message' => $result['error'] ) );
		}

		wp_send_json_success( array( 'message' => __( 'Token refreshed successfully.', 'ai-author-for-websites' ) ) );
	}

	/**
	 * AJAX handler to disconnect.
	 */
	public function ajax_disconnect(): void {
		check_ajax_referer( 'aiauthor_twitter_oauth', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-author-for-websites' ) ) );
		}

		$settings                         = $this->integration->get_settings();
		$settings['oauth2_access_token']  = '';
		$settings['oauth2_refresh_token'] = '';
		$settings['oauth2_token_expires'] = 0;
		$settings['oauth_connected']      = false;
		$settings['twitter_username']     = '';
		$settings['twitter_name']         = '';
		$settings['twitter_id']           = '';
		$this->integration->update_settings( $settings );

		wp_send_json_success( array( 'message' => __( 'Disconnected from Twitter.', 'ai-author-for-websites' ) ) );
	}

	/**
	 * Check if connected via OAuth.
	 *
	 * @return bool True if connected.
	 */
	public function is_connected(): bool {
		$settings = $this->integration->get_settings();
		return ! empty( $settings['oauth_connected'] ) && ! empty( $settings['oauth2_access_token'] );
	}

	/**
	 * Get connected username.
	 *
	 * @return string Username or empty string.
	 */
	public function get_connected_username(): string {
		$settings = $this->integration->get_settings();
		return $settings['twitter_username'] ?? '';
	}

	/**
	 * Redirect with a message.
	 *
	 * @param string $type    Message type (success/error).
	 * @param string $message The message.
	 */
	private function redirect_with_message( string $type, string $message ): void {
		$url = admin_url( 'admin.php?page=ai-author-integrations&integration=twitter' );
		$url = add_query_arg(
			array(
				'oauth_' . $type => rawurlencode( $message ),
			),
			$url
		);
		wp_safe_redirect( $url );
		exit;
	}
}
