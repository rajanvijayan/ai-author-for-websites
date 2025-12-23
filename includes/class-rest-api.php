<?php
/**
 * REST API Class
 *
 * @package AI_Author_For_Websites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIEngine\AIEngine;

/**
 * Class AIAUTHOR_REST_API
 *
 * Handles REST API endpoints for the AI Author plugin.
 */
class AIAUTHOR_REST_API {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	private $namespace = 'ai-author/v1';

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		// Test API connection.
		register_rest_route(
			$this->namespace,
			'/test-connection',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'test_connection' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Generate blog post.
		register_rest_route(
			$this->namespace,
			'/generate-post',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'generate_post' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
				'args'                => [
					'topic'      => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'word_count' => [
						'type'              => 'integer',
						'default'           => 1000,
						'sanitize_callback' => 'absint',
					],
					'tone'       => [
						'type'              => 'string',
						'default'           => 'professional',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		// Save generated post as draft.
		register_rest_route(
			$this->namespace,
			'/save-draft',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'save_draft' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
				'args'                => [
					'title'   => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'content' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'wp_kses_post',
					],
				],
			]
		);

		// Get knowledge base summary.
		register_rest_route(
			$this->namespace,
			'/knowledge-summary',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_knowledge_summary' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);
	}

	/**
	 * Check if user has admin permissions.
	 *
	 * @return bool True if user can manage options.
	 */
	public function admin_permission_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Test API connection.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function test_connection( $request ) {
		$settings = AI_Author_For_Websites::get_settings();

		if ( empty( $settings['api_key'] ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'API key is not configured.', 'ai-author-for-websites' ),
				],
				400
			);
		}

		try {
			$ai = new AIEngine(
				$settings['api_key'],
				[
					'provider' => $settings['provider'] ?? 'groq',
					'model'    => $settings['model'] ?? 'llama-3.3-70b-versatile',
					'timeout'  => 30,
				]
			);

			$response = $ai->generateContent( 'Say "Hello! Connection successful." in exactly those words.' );

			if ( is_array( $response ) && isset( $response['error'] ) ) {
				return new WP_REST_Response(
					[
						'success' => false,
						'message' => $response['error'],
					],
					400
				);
			}

			return new WP_REST_Response(
				[
					'success'  => true,
					'message'  => __( 'Connection successful!', 'ai-author-for-websites' ),
					'provider' => $ai->getProviderName(),
				],
				200
			);
		} catch ( \Exception $e ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Generate a blog post.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function generate_post( $request ) {
		$settings = AI_Author_For_Websites::get_settings();

		if ( empty( $settings['api_key'] ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'API key is not configured. Please configure it in Settings.', 'ai-author-for-websites' ),
				],
				400
			);
		}

		$topic      = $request->get_param( 'topic' );
		$word_count = $request->get_param( 'word_count' ) ?: ( $settings['default_word_count'] ?? 1000 );
		$tone       = $request->get_param( 'tone' ) ?: 'professional';

		try {
			$ai = new AIEngine(
				$settings['api_key'],
				[
					'provider' => $settings['provider'] ?? 'groq',
					'model'    => $settings['model'] ?? 'llama-3.3-70b-versatile',
					'timeout'  => 120,
				]
			);

			// Get knowledge base context.
			$knowledge_manager = new AIAUTHOR_Knowledge_Manager();
			$knowledge_context = $knowledge_manager->get_knowledge_context();

			// Set system instruction.
			$system_instruction = $settings['system_instruction'] ?? 'You are an expert blog writer.';
			$ai->setSystemInstruction( $system_instruction );

			// Build the prompt.
			$prompt = $this->build_generation_prompt( $topic, $word_count, $tone, $knowledge_context );

			// Generate content.
			$response = $ai->chat( $prompt );

			if ( is_array( $response ) && isset( $response['error'] ) ) {
				return new WP_REST_Response(
					[
						'success' => false,
						'message' => $response['error'],
					],
					400
				);
			}

			// Parse the response to extract title and content.
			$parsed = $this->parse_generated_content( $response );

			return new WP_REST_Response(
				[
					'success' => true,
					'title'   => $parsed['title'],
					'content' => $parsed['content'],
				],
				200
			);
		} catch ( \Exception $e ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Build the generation prompt.
	 *
	 * @param string $topic            The topic to write about.
	 * @param int    $word_count       Target word count.
	 * @param string $tone             Writing tone.
	 * @param string $knowledge_context Knowledge base content.
	 * @return string The complete prompt.
	 */
	private function build_generation_prompt( $topic, $word_count, $tone, $knowledge_context ) {
		$prompt = "Write a comprehensive blog post about: {$topic}\n\n";
		$prompt .= "Requirements:\n";
		$prompt .= "- Target word count: approximately {$word_count} words\n";
		$prompt .= "- Tone: {$tone}\n";
		$prompt .= "- Include a compelling title (start with 'TITLE: ')\n";
		$prompt .= "- Use proper HTML formatting with headings (h2, h3), paragraphs, and lists where appropriate\n";
		$prompt .= "- Make it engaging, informative, and SEO-friendly\n";
		$prompt .= "- Include a strong introduction and conclusion\n\n";

		if ( ! empty( $knowledge_context ) ) {
			$prompt .= "Use the following knowledge base to inform your writing and ensure accuracy:\n\n";
			$prompt .= $knowledge_context . "\n\n";
		}

		$prompt .= "Now write the blog post:";

		return $prompt;
	}

	/**
	 * Parse generated content to extract title and body.
	 *
	 * @param string $content The generated content.
	 * @return array Array with 'title' and 'content' keys.
	 */
	private function parse_generated_content( $content ) {
		$title        = '';
		$body_content = $content;

		// Try to extract title from "TITLE: " prefix.
		if ( preg_match( '/^TITLE:\s*(.+?)(?:\n|$)/i', $content, $matches ) ) {
			$title        = trim( $matches[1] );
			$body_content = trim( preg_replace( '/^TITLE:\s*.+?(?:\n|$)/i', '', $content ) );
		} elseif ( preg_match( '/<h1[^>]*>(.+?)<\/h1>/i', $content, $matches ) ) {
			// Try to extract from H1 tag.
			$title        = strip_tags( $matches[1] );
			$body_content = preg_replace( '/<h1[^>]*>.+?<\/h1>/i', '', $content, 1 );
		} elseif ( preg_match( '/^#\s+(.+?)(?:\n|$)/m', $content, $matches ) ) {
			// Try markdown H1.
			$title        = trim( $matches[1] );
			$body_content = trim( preg_replace( '/^#\s+.+?(?:\n|$)/m', '', $content, 1 ) );
		}

		// Convert markdown to HTML if needed.
		$body_content = $this->markdown_to_html( $body_content );

		return [
			'title'   => $title,
			'content' => trim( $body_content ),
		];
	}

	/**
	 * Convert basic markdown to HTML.
	 *
	 * @param string $text The markdown text.
	 * @return string HTML content.
	 */
	private function markdown_to_html( $text ) {
		// Convert markdown headings.
		$text = preg_replace( '/^### (.+)$/m', '<h3>$1</h3>', $text );
		$text = preg_replace( '/^## (.+)$/m', '<h2>$1</h2>', $text );
		$text = preg_replace( '/^# (.+)$/m', '<h1>$1</h1>', $text );

		// Convert bold.
		$text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );

		// Convert italic.
		$text = preg_replace( '/\*(.+?)\*/', '<em>$1</em>', $text );

		// Convert unordered lists.
		$text = preg_replace( '/^[\-\*]\s+(.+)$/m', '<li>$1</li>', $text );
		$text = preg_replace( '/(<li>.+<\/li>\n?)+/', '<ul>$0</ul>', $text );

		// Convert paragraphs (double newlines).
		$paragraphs = preg_split( '/\n\s*\n/', $text );
		$result     = '';
		foreach ( $paragraphs as $para ) {
			$para = trim( $para );
			if ( empty( $para ) ) {
				continue;
			}
			// Don't wrap if already HTML block element.
			if ( preg_match( '/^<(h[1-6]|ul|ol|li|p|div|blockquote)/', $para ) ) {
				$result .= $para . "\n\n";
			} else {
				$result .= "<p>{$para}</p>\n\n";
			}
		}

		return trim( $result );
	}

	/**
	 * Save generated post as draft.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function save_draft( $request ) {
		$title   = $request->get_param( 'title' );
		$content = $request->get_param( 'content' );

		$post_id = wp_insert_post(
			[
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'draft',
				'post_type'    => 'post',
				'post_author'  => get_current_user_id(),
			]
		);

		if ( is_wp_error( $post_id ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => $post_id->get_error_message(),
				],
				500
			);
		}

		return new WP_REST_Response(
			[
				'success'  => true,
				'post_id'  => $post_id,
				'edit_url' => get_edit_post_link( $post_id, 'raw' ),
				'message'  => __( 'Post saved as draft!', 'ai-author-for-websites' ),
			],
			200
		);
	}

	/**
	 * Get knowledge base summary.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function get_knowledge_summary( $request ) {
		$knowledge_manager = new AIAUTHOR_Knowledge_Manager();
		$kb                = $knowledge_manager->get_knowledge_base();
		$summary           = $kb->getSummary();

		return new WP_REST_Response(
			[
				'success' => true,
				'summary' => $summary,
			],
			200
		);
	}
}

