<?php
/**
 * Facebook OAuth Handler
 *
 * Handles OAuth 2.0 authorization flow for Facebook.
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Integrations\Facebook;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OAuth
 *
 * Handles Facebook OAuth 2.0 authorization.
 */
class OAuth {

	/**
	 * Facebook OAuth authorization URL.
	 *
	 * @var string
	 */
	const AUTHORIZE_URL = 'https://www.facebook.com/v18.0/dialog/oauth';

	/**
	 * Facebook OAuth token URL.
	 *
	 * @var string
	 */
	const TOKEN_URL = 'https://graph.facebook.com/v18.0/oauth/access_token';

	/**
	 * Facebook Graph API URL.
	 *
	 * @var string
	 */
	const GRAPH_URL = 'https://graph.facebook.com/v18.0/';

	/**
	 * Required OAuth scopes.
	 *
	 * @var array
	 */
	const SCOPES = array(
		'pages_show_list',
		'pages_read_engagement',
		'pages_manage_posts',
	);

	/**
	 * Option key for storing OAuth state.
	 *
	 * @var string
	 */
	const STATE_OPTION = 'aiauthor_facebook_oauth_state';

	/**
	 * Integration instance.
	 *
	 * @var Facebook
	 */
	private $integration;

	/**
	 * Constructor.
	 *
	 * @param Facebook $integration The Facebook integration instance.
	 */
	public function __construct( Facebook $integration ) {
		$this->integration = $integration;
	}

	/**
	 * Initialize OAuth hooks.
	 */
	public function init(): void {
		add_action( 'admin_init', array( $this, 'handle_oauth_callback' ) );
		add_action( 'wp_ajax_aiauthor_facebook_connect', array( $this, 'ajax_start_oauth' ) );
		add_action( 'wp_ajax_aiauthor_facebook_disconnect', array( $this, 'ajax_disconnect' ) );
		add_action( 'wp_ajax_aiauthor_facebook_get_pages', array( $this, 'ajax_get_pages' ) );
		add_action( 'wp_ajax_aiauthor_facebook_select_page', array( $this, 'ajax_select_page' ) );
	}

	/**
	 * Get the OAuth callback URL.
	 *
	 * @return string Callback URL.
	 */
	public function get_callback_url(): string {
		return admin_url( 'admin.php?page=ai-author-integrations&integration=facebook&oauth=callback' );
	}

	/**
	 * Generate authorization URL.
	 *
	 * @return string|null Authorization URL or null if not configured.
	 */
	public function get_authorization_url(): ?string {
		$settings = $this->integration->get_settings();

		if ( empty( $settings['app_id'] ) ) {
			return null;
		}

		// Generate and store state for CSRF protection.
		$state = wp_generate_password( 32, false );
		update_option( self::STATE_OPTION, $state );

		$params = array(
			'client_id'     => $settings['app_id'],
			'redirect_uri'  => $this->get_callback_url(),
			'state'         => $state,
			'scope'         => implode( ',', self::SCOPES ),
			'response_type' => 'code',
		);

		return self::AUTHORIZE_URL . '?' . http_build_query( $params );
	}

	/**
	 * AJAX handler to start OAuth flow.
	 */
	public function ajax_start_oauth(): void {
		check_ajax_referer( 'aiauthor_facebook_oauth', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-author-for-websites' ) ) );
		}

		$auth_url = $this->get_authorization_url();

		if ( ! $auth_url ) {
			wp_send_json_error( array( 'message' => __( 'Please enter your Facebook App ID first.', 'ai-author-for-websites' ) ) );
		}

		wp_send_json_success( array( 'auth_url' => $auth_url ) );
	}

	/**
	 * Handle OAuth callback.
	 */
	public function handle_oauth_callback(): void {
		// Check if this is a Facebook OAuth callback.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback doesn't use nonce.
		if ( ! isset( $_GET['page'], $_GET['integration'], $_GET['oauth'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'ai-author-integrations' !== $_GET['page'] || 'facebook' !== $_GET['integration'] || 'callback' !== $_GET['oauth'] ) {
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

		// Clear the state.
		delete_option( self::STATE_OPTION );

		if ( empty( $code ) ) {
			$this->redirect_with_message( 'error', __( 'No authorization code received.', 'ai-author-for-websites' ) );
			return;
		}

		// Exchange code for access token.
		$result = $this->exchange_code_for_token( $code );

		if ( isset( $result['error'] ) ) {
			$this->redirect_with_message( 'error', $result['error'] );
			return;
		}

		// Get long-lived token.
		$long_lived = $this->get_long_lived_token( $result['access_token'] );

		if ( isset( $long_lived['error'] ) ) {
			$this->redirect_with_message( 'error', $long_lived['error'] );
			return;
		}

		// Store the user access token temporarily (will be replaced with page token).
		$settings                      = $this->integration->get_settings();
		$settings['user_access_token'] = $long_lived['access_token'];
		$settings['token_expires']     = time() + ( $long_lived['expires_in'] ?? 5184000 ); // Default 60 days.
		$settings['oauth_connected']   = true;
		$this->integration->update_settings( $settings );

		$this->redirect_with_message( 'success', __( 'Connected to Facebook! Now select a Page to post to.', 'ai-author-for-websites' ) );
	}

	/**
	 * Exchange authorization code for access token.
	 *
	 * @param string $code Authorization code.
	 * @return array Token data or error.
	 */
	private function exchange_code_for_token( string $code ): array {
		$settings = $this->integration->get_settings();

		$response = wp_remote_get(
			self::TOKEN_URL . '?' . http_build_query(
				array(
					'client_id'     => $settings['app_id'],
					'client_secret' => $settings['app_secret'],
					'redirect_uri'  => $this->get_callback_url(),
					'code'          => $code,
				)
			),
			array( 'timeout' => 30 )
		);

		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['error'] ) ) {
			return array( 'error' => $data['error']['message'] ?? __( 'Failed to get access token.', 'ai-author-for-websites' ) );
		}

		if ( ! isset( $data['access_token'] ) ) {
			return array( 'error' => __( 'No access token in response.', 'ai-author-for-websites' ) );
		}

		return $data;
	}

	/**
	 * Get long-lived access token.
	 *
	 * @param string $short_token Short-lived access token.
	 * @return array Long-lived token data or error.
	 */
	private function get_long_lived_token( string $short_token ): array {
		$settings = $this->integration->get_settings();

		$response = wp_remote_get(
			self::TOKEN_URL . '?' . http_build_query(
				array(
					'grant_type'        => 'fb_exchange_token',
					'client_id'         => $settings['app_id'],
					'client_secret'     => $settings['app_secret'],
					'fb_exchange_token' => $short_token,
				)
			),
			array( 'timeout' => 30 )
		);

		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['error'] ) ) {
			return array( 'error' => $data['error']['message'] ?? __( 'Failed to get long-lived token.', 'ai-author-for-websites' ) );
		}

		return $data;
	}

	/**
	 * Get user's Facebook pages.
	 *
	 * @return array Pages list or error.
	 */
	public function get_pages(): array {
		$settings = $this->integration->get_settings();

		if ( empty( $settings['user_access_token'] ) ) {
			return array( 'error' => __( 'Not connected to Facebook.', 'ai-author-for-websites' ) );
		}

		$response = wp_remote_get(
			self::GRAPH_URL . 'me/accounts?' . http_build_query(
				array(
					'access_token' => $settings['user_access_token'],
					'fields'       => 'id,name,access_token,picture',
				)
			),
			array( 'timeout' => 30 )
		);

		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['error'] ) ) {
			return array( 'error' => $data['error']['message'] ?? __( 'Failed to get pages.', 'ai-author-for-websites' ) );
		}

		return $data['data'] ?? array();
	}

	/**
	 * AJAX handler to get pages.
	 */
	public function ajax_get_pages(): void {
		check_ajax_referer( 'aiauthor_facebook_oauth', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-author-for-websites' ) ) );
		}

		$pages = $this->get_pages();

		if ( isset( $pages['error'] ) ) {
			wp_send_json_error( array( 'message' => $pages['error'] ) );
		}

		wp_send_json_success( array( 'pages' => $pages ) );
	}

	/**
	 * AJAX handler to select a page.
	 */
	public function ajax_select_page(): void {
		check_ajax_referer( 'aiauthor_facebook_oauth', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-author-for-websites' ) ) );
		}

		$page_id           = sanitize_text_field( wp_unslash( $_POST['page_id'] ?? '' ) );
		$page_access_token = sanitize_text_field( wp_unslash( $_POST['page_access_token'] ?? '' ) );
		$page_name         = sanitize_text_field( wp_unslash( $_POST['page_name'] ?? '' ) );

		if ( empty( $page_id ) || empty( $page_access_token ) ) {
			wp_send_json_error( array( 'message' => __( 'Page ID and access token are required.', 'ai-author-for-websites' ) ) );
		}

		$settings                      = $this->integration->get_settings();
		$settings['page_id']           = $page_id;
		$settings['page_access_token'] = $page_access_token;
		$settings['page_name']         = $page_name;
		$this->integration->update_settings( $settings );

		wp_send_json_success(
			array(
				'message'   => sprintf(
					/* translators: %s: Facebook page name */
					__( 'Connected to page: %s', 'ai-author-for-websites' ),
					$page_name
				),
				'page_name' => $page_name,
			)
		);
	}

	/**
	 * AJAX handler to disconnect.
	 */
	public function ajax_disconnect(): void {
		check_ajax_referer( 'aiauthor_facebook_oauth', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-author-for-websites' ) ) );
		}

		$settings                      = $this->integration->get_settings();
		$settings['user_access_token'] = '';
		$settings['page_access_token'] = '';
		$settings['page_id']           = '';
		$settings['page_name']         = '';
		$settings['oauth_connected']   = false;
		$settings['token_expires']     = 0;
		$this->integration->update_settings( $settings );

		wp_send_json_success( array( 'message' => __( 'Disconnected from Facebook.', 'ai-author-for-websites' ) ) );
	}

	/**
	 * Check if connected via OAuth.
	 *
	 * @return bool True if connected.
	 */
	public function is_connected(): bool {
		$settings = $this->integration->get_settings();
		return ! empty( $settings['oauth_connected'] ) && ! empty( $settings['page_access_token'] );
	}

	/**
	 * Get connected page name.
	 *
	 * @return string Page name or empty string.
	 */
	public function get_connected_page_name(): string {
		$settings = $this->integration->get_settings();
		return $settings['page_name'] ?? '';
	}

	/**
	 * Redirect with a message.
	 *
	 * @param string $type    Message type (success/error).
	 * @param string $message The message.
	 */
	private function redirect_with_message( string $type, string $message ): void {
		$url = admin_url( 'admin.php?page=ai-author-integrations&integration=facebook' );
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
