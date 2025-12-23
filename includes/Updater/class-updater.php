<?php
/**
 * Plugin Updater Class
 *
 * Handles self-hosted plugin updates from GitHub releases.
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Updater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Updater
 *
 * Checks for plugin updates from GitHub releases and integrates
 * with WordPress plugin update system.
 */
class Updater {

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private $slug = 'ai-author-for-websites';

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	private $basename;

	/**
	 * Current plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * GitHub repository owner.
	 *
	 * @var string
	 */
	private $github_owner = 'rajanvijayan';

	/**
	 * GitHub repository name.
	 *
	 * @var string
	 */
	private $github_repo = 'ai-author-for-websites';

	/**
	 * Cache key for update data.
	 *
	 * @var string
	 */
	private $cache_key = 'aiauthor_update_data';

	/**
	 * Cache expiration in seconds (12 hours).
	 *
	 * @var int
	 */
	private $cache_expiration = 43200;

	/**
	 * Single instance.
	 *
	 * @var Updater|null
	 */
	private static $instance = null;

	/**
	 * Get single instance.
	 *
	 * @return Updater
	 */
	public static function get_instance(): Updater {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->basename = AIAUTHOR_PLUGIN_BASENAME;
		$this->version  = AIAUTHOR_VERSION;

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks(): void {
		// Check for updates.
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );

		// Plugin information popup.
		add_filter( 'plugins_api', [ $this, 'plugin_info' ], 20, 3 );

		// After update cleanup.
		add_action( 'upgrader_process_complete', [ $this, 'after_update' ], 10, 2 );

		// Add update notification in plugin row.
		add_action( 'in_plugin_update_message-' . $this->basename, [ $this, 'update_message' ], 10, 2 );
	}

	/**
	 * Check for plugin updates.
	 *
	 * @param object $transient Update transient.
	 * @return object Modified transient.
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote = $this->get_remote_info();

		if (
			$remote &&
			isset( $remote->version ) &&
			version_compare( $this->version, $remote->version, '<' )
		) {
			$transient->response[ $this->basename ] = (object) [
				'slug'        => $this->slug,
				'plugin'      => $this->basename,
				'new_version' => $remote->version,
				'url'         => $remote->homepage ?? "https://github.com/{$this->github_owner}/{$this->github_repo}",
				'package'     => $remote->download_url ?? '',
				'icons'       => [
					'default' => AIAUTHOR_PLUGIN_URL . 'assets/images/icon-128x128.png',
				],
				'banners'     => [
					'low'  => AIAUTHOR_PLUGIN_URL . 'assets/images/banner-772x250.png',
					'high' => AIAUTHOR_PLUGIN_URL . 'assets/images/banner-1544x500.png',
				],
				'tested'      => $remote->tested ?? '',
				'requires'    => $remote->requires ?? '5.8',
				'requires_php'=> $remote->requires_php ?? '8.0',
			];
		}

		return $transient;
	}

	/**
	 * Get plugin information for the popup.
	 *
	 * @param false|object|array $result The result object or array.
	 * @param string             $action The API action being performed.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || $args->slug !== $this->slug ) {
			return $result;
		}

		$remote = $this->get_remote_info();

		if ( ! $remote ) {
			return $result;
		}

		return (object) [
			'name'              => $remote->name ?? 'AI Author for Websites',
			'slug'              => $this->slug,
			'version'           => $remote->version ?? $this->version,
			'author'            => '<a href="https://rajanvijayan.com">Rajan Vijayan</a>',
			'author_profile'    => 'https://rajanvijayan.com',
			'requires'          => $remote->requires ?? '5.8',
			'tested'            => $remote->tested ?? '',
			'requires_php'      => $remote->requires_php ?? '8.0',
			'downloaded'        => $remote->downloaded ?? 0,
			'last_updated'      => $remote->last_updated ?? '',
			'homepage'          => "https://github.com/{$this->github_owner}/{$this->github_repo}",
			'short_description' => $remote->description ?? 'AI-powered blog post generator for WordPress.',
			'sections'          => [
				'description'  => $remote->sections->description ?? $this->get_default_description(),
				'installation' => $this->get_installation_instructions(),
				'changelog'    => $remote->sections->changelog ?? '',
			],
			'download_link'     => $remote->download_url ?? '',
			'banners'           => [
				'low'  => AIAUTHOR_PLUGIN_URL . 'assets/images/banner-772x250.png',
				'high' => AIAUTHOR_PLUGIN_URL . 'assets/images/banner-1544x500.png',
			],
		];
	}

	/**
	 * Get remote update information.
	 *
	 * @return object|false Remote info or false on failure.
	 */
	private function get_remote_info() {
		// Check cache first.
		$cached = get_transient( $this->cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		// Fetch from GitHub API.
		$response = $this->fetch_github_release();

		if ( $response ) {
			set_transient( $this->cache_key, $response, $this->cache_expiration );
			return $response;
		}

		return false;
	}

	/**
	 * Fetch latest release from GitHub API.
	 *
	 * @return object|false Release info or false on failure.
	 */
	private function fetch_github_release() {
		$api_url = "https://api.github.com/repos/{$this->github_owner}/{$this->github_repo}/releases/latest";

		$response = wp_remote_get(
			$api_url,
			[
				'timeout' => 15,
				'headers' => [
					'Accept'     => 'application/vnd.github.v3+json',
					'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( empty( $data ) || ! isset( $data->tag_name ) ) {
			return false;
		}

		// Parse version from tag (remove 'v' prefix if present).
		$version = ltrim( $data->tag_name, 'v' );

		// Find the zip asset.
		$download_url = '';
		if ( ! empty( $data->assets ) ) {
			foreach ( $data->assets as $asset ) {
				if ( strpos( $asset->name, '.zip' ) !== false ) {
					$download_url = $asset->browser_download_url;
					break;
				}
			}
		}

		// Fallback to zipball if no asset found.
		if ( empty( $download_url ) ) {
			$download_url = $data->zipball_url ?? '';
		}

		return (object) [
			'name'         => 'AI Author for Websites',
			'version'      => $version,
			'download_url' => $download_url,
			'last_updated' => $data->published_at ?? '',
			'requires'     => '5.8',
			'tested'       => '6.4',
			'requires_php' => '8.0',
			'description'  => 'AI-powered blog post generator. Train the AI with your knowledge base and create high-quality content.',
			'sections'     => (object) [
				'description' => $this->get_default_description(),
				'changelog'   => $this->parse_changelog( $data->body ?? '' ),
			],
		];
	}

	/**
	 * Parse changelog from release body.
	 *
	 * @param string $body Release body content.
	 * @return string HTML changelog.
	 */
	private function parse_changelog( string $body ): string {
		if ( empty( $body ) ) {
			return '<p>See the <a href="https://github.com/' . esc_attr( $this->github_owner ) . '/' . esc_attr( $this->github_repo ) . '/releases" target="_blank">GitHub releases</a> for changelog.</p>';
		}

		// Convert markdown to basic HTML.
		$html = $body;

		// Convert headers.
		$html = preg_replace( '/^### (.+)$/m', '<h4>$1</h4>', $html );
		$html = preg_replace( '/^## (.+)$/m', '<h3>$1</h3>', $html );

		// Convert lists.
		$html = preg_replace( '/^- (.+)$/m', '<li>$1</li>', $html );
		$html = preg_replace( '/^  - (.+)$/m', '<li>$1</li>', $html );
		$html = preg_replace( '/(<li>.+<\/li>\n?)+/', '<ul>$0</ul>', $html );

		// Convert line breaks.
		$html = nl2br( $html );

		return $html;
	}

	/**
	 * Get default description.
	 *
	 * @return string Default description HTML.
	 */
	private function get_default_description(): string {
		return '<p>AI Author for Websites is an AI-powered blog post generator for WordPress. Train the AI with your knowledge base and create high-quality, contextually relevant content.</p>
		<h3>Features</h3>
		<ul>
			<li>AI-powered content generation using multiple providers (Groq, Gemini)</li>
			<li>Knowledge base training for contextual content</li>
			<li>Automatic scheduling with Auto Scheduler integration</li>
			<li>Multiple tone and style options</li>
			<li>SEO-friendly content generation</li>
			<li>Category and tag suggestions</li>
		</ul>
		<h3>Requirements</h3>
		<ul>
			<li>WordPress 5.8 or higher</li>
			<li>PHP 8.0 or higher</li>
			<li>API key from supported AI provider</li>
		</ul>';
	}

	/**
	 * Get installation instructions.
	 *
	 * @return string Installation HTML.
	 */
	private function get_installation_instructions(): string {
		return '<ol>
			<li>Upload the plugin files to the <code>/wp-content/plugins/ai-author-for-websites</code> directory, or install the plugin through the WordPress plugins screen directly.</li>
			<li>Activate the plugin through the \'Plugins\' screen in WordPress.</li>
			<li>Go to AI Author → Settings to configure your API key.</li>
			<li>Add content to your Knowledge Base.</li>
			<li>Start generating AI-powered blog posts!</li>
		</ol>';
	}

	/**
	 * Show custom update message.
	 *
	 * @param array  $plugin_data Plugin data.
	 * @param object $response    Update response.
	 */
	public function update_message( $plugin_data, $response ): void {
		if ( empty( $response->package ) ) {
			echo '<br><span style="color: #d63638;">';
			esc_html_e( 'Update package not available. Please download manually from GitHub.', 'ai-author-for-websites' );
			echo '</span>';
		}
	}

	/**
	 * Clean up after update.
	 *
	 * @param \WP_Upgrader $upgrader Upgrader instance.
	 * @param array        $options  Update options.
	 */
	public function after_update( $upgrader, $options ): void {
		if (
			'update' === $options['action'] &&
			'plugin' === $options['type'] &&
			isset( $options['plugins'] ) &&
			in_array( $this->basename, $options['plugins'], true )
		) {
			// Clear cached update data.
			delete_transient( $this->cache_key );
		}
	}

	/**
	 * Force check for updates (useful for debugging).
	 *
	 * @return object|false Remote info or false.
	 */
	public function force_check() {
		delete_transient( $this->cache_key );
		return $this->get_remote_info();
	}

	/**
	 * Get current version.
	 *
	 * @return string Current version.
	 */
	public function get_current_version(): string {
		return $this->version;
	}

	/**
	 * Get latest available version.
	 *
	 * @return string|null Latest version or null.
	 */
	public function get_latest_version(): ?string {
		$remote = $this->get_remote_info();
		return $remote->version ?? null;
	}

	/**
	 * Check if update is available.
	 *
	 * @return bool True if update available.
	 */
	public function is_update_available(): bool {
		$latest = $this->get_latest_version();
		if ( ! $latest ) {
			return false;
		}
		return version_compare( $this->version, $latest, '<' );
	}
}

