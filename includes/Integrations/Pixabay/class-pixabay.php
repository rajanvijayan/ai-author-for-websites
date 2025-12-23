<?php
/**
 * Pixabay Integration
 *
 * Fetches images from Pixabay API to use as featured images for posts.
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Integrations\Pixabay;

use AIAuthor\Integrations\IntegrationBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Pixabay
 *
 * Handles fetching images from Pixabay API.
 */
class Pixabay extends IntegrationBase {

	/**
	 * Pixabay API URL.
	 *
	 * @var string
	 */
	const API_URL = 'https://pixabay.com/api/';

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	protected $default_settings = array(
		'enabled'             => false,
		'api_key'             => '',
		'image_type'          => 'photo',
		'orientation'         => 'horizontal',
		'min_width'           => 1200,
		'min_height'          => 630,
		'safesearch'          => true,
		'auto_set_featured'   => true,
		'attribution_in_post' => false,
	);

	/**
	 * {@inheritdoc}
	 */
	public function get_id(): string {
		return 'pixabay';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return __( 'Pixabay', 'ai-author-for-websites' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Automatically fetch and set featured images from Pixabay for your AI-generated posts.', 'ai-author-for-websites' );
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
		return 'dashicons-format-image';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_category(): string {
		return 'media';
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
		// Register REST API endpoints.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Hook into post save to auto-set featured image.
		add_action( 'aiauthor_post_created', array( $this, 'maybe_set_featured_image' ), 10, 3 );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'ai-author/v1',
			'/pixabay/search',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_search_images' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'ai-author/v1',
			'/pixabay/set-featured',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_set_featured_image' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * REST endpoint to search Pixabay images.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response The response.
	 */
	public function rest_search_images( $request ): \WP_REST_Response {
		$query = sanitize_text_field( $request->get_param( 'query' ) );
		$page  = absint( $request->get_param( 'page' ) );
		$page  = $page > 0 ? $page : 1;

		if ( empty( $query ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Search query is required.', 'ai-author-for-websites' ),
				),
				400
			);
		}

		$result = $this->search_images( $query, $page );

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
	 * REST endpoint to set featured image from Pixabay.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response The response.
	 */
	public function rest_set_featured_image( $request ): \WP_REST_Response {
		$post_id   = absint( $request->get_param( 'post_id' ) );
		$image_url = esc_url_raw( $request->get_param( 'image_url' ) );
		$image_alt = sanitize_text_field( $request->get_param( 'image_alt' ) );

		if ( empty( $post_id ) || empty( $image_url ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Post ID and image URL are required.', 'ai-author-for-websites' ),
				),
				400
			);
		}

		$result = $this->set_featured_image( $post_id, $image_url, $image_alt );

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
				'success'       => true,
				'attachment_id' => $result['attachment_id'],
				'message'       => __( 'Featured image set successfully!', 'ai-author-for-websites' ),
			),
			200
		);
	}

	/**
	 * Search images on Pixabay.
	 *
	 * @param string $query   Search query.
	 * @param int    $page    Page number.
	 * @param int    $per_page Results per page.
	 * @return array Search results or error.
	 */
	public function search_images( string $query, int $page = 1, int $per_page = 20 ): array {
		$settings = $this->get_settings();

		if ( empty( $settings['api_key'] ) ) {
			return array( 'error' => __( 'Pixabay API key is not configured.', 'ai-author-for-websites' ) );
		}

		$params = array(
			'key'         => $settings['api_key'],
			'q'           => rawurlencode( $query ),
			'image_type'  => $settings['image_type'] ?? 'photo',
			'orientation' => $settings['orientation'] ?? 'horizontal',
			'min_width'   => $settings['min_width'] ?? 1200,
			'min_height'  => $settings['min_height'] ?? 630,
			'safesearch'  => ! empty( $settings['safesearch'] ) ? 'true' : 'false',
			'page'        => $page,
			'per_page'    => $per_page,
		);

		$url = add_query_arg( $params, self::API_URL );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || isset( $data['error'] ) ) {
			$error_message = isset( $data['error'] ) ? $data['error'] : __( 'Failed to fetch images from Pixabay.', 'ai-author-for-websites' );
			return array( 'error' => $error_message );
		}

		return array(
			'total'       => $data['totalHits'] ?? 0,
			'total_pages' => ceil( ( $data['totalHits'] ?? 0 ) / $per_page ),
			'page'        => $page,
			'images'      => array_map(
				function ( $hit ) {
					return array(
						'id'          => $hit['id'],
						'preview_url' => $hit['webformatURL'],
						'full_url'    => $hit['largeImageURL'],
						'width'       => $hit['imageWidth'],
						'height'      => $hit['imageHeight'],
						'tags'        => $hit['tags'],
						'user'        => $hit['user'],
						'user_url'    => 'https://pixabay.com/users/' . $hit['user'] . '-' . $hit['user_id'],
						'pixabay_url' => $hit['pageURL'],
					);
				},
				$data['hits'] ?? array()
			),
		);
	}

	/**
	 * Download and set featured image for a post.
	 *
	 * @param int    $post_id   The post ID.
	 * @param string $image_url The image URL from Pixabay.
	 * @param string $alt_text  Alt text for the image.
	 * @return array Result with attachment_id or error.
	 */
	public function set_featured_image( int $post_id, string $image_url, string $alt_text = '' ): array {
		// Require the necessary WordPress files.
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Download image to temp location.
		$tmp = download_url( $image_url );

		if ( is_wp_error( $tmp ) ) {
			return array( 'error' => $tmp->get_error_message() );
		}

		// Generate filename from URL.
		$url_parts = wp_parse_url( $image_url );
		$filename  = basename( $url_parts['path'] );

		// Ensure we have a proper extension.
		if ( ! preg_match( '/\.(jpe?g|png|gif|webp)$/i', $filename ) ) {
			$filename = sanitize_file_name( $alt_text ? $alt_text : 'pixabay-image' ) . '.jpg';
		}

		$file_array = array(
			'name'     => $filename,
			'tmp_name' => $tmp,
		);

		// Upload the image.
		$attachment_id = media_handle_sideload( $file_array, $post_id, $alt_text );

		// Clean up temp file.
		if ( file_exists( $tmp ) ) {
			wp_delete_file( $tmp );
		}

		if ( is_wp_error( $attachment_id ) ) {
			return array( 'error' => $attachment_id->get_error_message() );
		}

		// Set as featured image.
		set_post_thumbnail( $post_id, $attachment_id );

		// Set alt text.
		if ( ! empty( $alt_text ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
		}

		// Store Pixabay attribution.
		update_post_meta( $attachment_id, '_pixabay_source', $image_url );

		return array(
			'attachment_id' => $attachment_id,
		);
	}

	/**
	 * Maybe set featured image automatically when post is created.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $title   Post title.
	 * @param string $content Post content.
	 */
	public function maybe_set_featured_image( int $post_id, string $title, string $content ): void {
		$settings = $this->get_settings();

		// Check if auto-set is enabled.
		if ( empty( $settings['auto_set_featured'] ) ) {
			return;
		}

		// Check if post already has a featured image.
		if ( has_post_thumbnail( $post_id ) ) {
			return;
		}

		// Search for an image based on the title.
		$result = $this->search_images( $title, 1, 5 );

		if ( isset( $result['error'] ) || empty( $result['images'] ) ) {
			return;
		}

		// Get the first image.
		$image = $result['images'][0];

		// Set it as the featured image.
		$this->set_featured_image( $post_id, $image['full_url'], $title );
	}

	/**
	 * Get image type options.
	 *
	 * @return array Image type options.
	 */
	public function get_image_type_options(): array {
		return array(
			'all'          => __( 'All', 'ai-author-for-websites' ),
			'photo'        => __( 'Photo', 'ai-author-for-websites' ),
			'illustration' => __( 'Illustration', 'ai-author-for-websites' ),
			'vector'       => __( 'Vector', 'ai-author-for-websites' ),
		);
	}

	/**
	 * Get orientation options.
	 *
	 * @return array Orientation options.
	 */
	public function get_orientation_options(): array {
		return array(
			'all'        => __( 'All', 'ai-author-for-websites' ),
			'horizontal' => __( 'Horizontal', 'ai-author-for-websites' ),
			'vertical'   => __( 'Vertical', 'ai-author-for-websites' ),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function render_settings_page(): void {
		include AIAUTHOR_PLUGIN_DIR . 'includes/Views/pixabay.php';
	}
}
