<?php
/**
 * REST API Class
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\API;

use AIEngine\AIEngine;
use AIAuthor\Core\Plugin;
use AIAuthor\Knowledge\Manager as KnowledgeManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class REST
 *
 * Handles REST API endpoints for the AI Author plugin.
 */
class REST {

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
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'test_connection' ),
				'permission_callback' => array( $this, 'admin_permission_check' ),
			)
		);

		// Generate blog post.
		register_rest_route(
			$this->namespace,
			'/generate-post',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generate_post' ),
				'permission_callback' => array( $this, 'admin_permission_check' ),
			)
		);

		// Save generated post as draft.
		register_rest_route(
			$this->namespace,
			'/save-draft',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_draft' ),
				'permission_callback' => array( $this, 'admin_permission_check' ),
			)
		);

		// Get knowledge base summary.
		register_rest_route(
			$this->namespace,
			'/knowledge-summary',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_knowledge_summary' ),
				'permission_callback' => array( $this, 'admin_permission_check' ),
			)
		);

		// AI Suggestion endpoint.
		register_rest_route(
			$this->namespace,
			'/ai-suggest',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'ai_suggest' ),
				'permission_callback' => array( $this, 'admin_permission_check' ),
			)
		);

		// Suggest categories and tags.
		register_rest_route(
			$this->namespace,
			'/suggest-taxonomy',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'suggest_taxonomy' ),
				'permission_callback' => array( $this, 'admin_permission_check' ),
			)
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
	 * @param \WP_REST_Request $request The request object (unused but required by REST API).
	 * @return \WP_REST_Response The response.
	 */
	public function test_connection( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$settings = Plugin::get_settings();

		if ( empty( $settings['api_key'] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'API key is not configured.', 'ai-author-for-websites' ),
				),
				400
			);
		}

		try {
			$ai = new AIEngine(
				$settings['api_key'],
				array(
					'provider' => $settings['provider'] ?? 'groq',
					'model'    => $settings['model'] ?? 'llama-3.3-70b-versatile',
					'timeout'  => 30,
				)
			);

			$response = $ai->generateContent( 'Say "Hello! Connection successful." in exactly those words.' );

			if ( is_array( $response ) && isset( $response['error'] ) ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => $response['error'],
					),
					400
				);
			}

			return new \WP_REST_Response(
				array(
					'success'  => true,
					'message'  => __( 'Connection successful!', 'ai-author-for-websites' ),
					'provider' => $ai->getProviderName(),
				),
				200
			);
		} catch ( \Exception $e ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Generate a blog post.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response The response.
	 */
	public function generate_post( $request ) {
		$settings = Plugin::get_settings();

		if ( empty( $settings['api_key'] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'API key is not configured. Please configure it in Settings.', 'ai-author-for-websites' ),
				),
				400
			);
		}

		$topic      = sanitize_text_field( $request->get_param( 'topic' ) );
		$word_count = absint( $request->get_param( 'word_count' ) );
		$word_count = $word_count > 0 ? $word_count : ( isset( $settings['default_word_count'] ) ? $settings['default_word_count'] : 1000 );
		$tone       = sanitize_text_field( $request->get_param( 'tone' ) );
		$tone       = ! empty( $tone ) ? $tone : 'professional';

		if ( empty( $topic ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Please provide a topic.', 'ai-author-for-websites' ),
				),
				400
			);
		}

		try {
			$ai = new AIEngine(
				$settings['api_key'],
				array(
					'provider' => $settings['provider'] ?? 'groq',
					'model'    => $settings['model'] ?? 'llama-3.3-70b-versatile',
					'timeout'  => 120,
				)
			);

			// Get knowledge base context.
			$knowledge_manager = new KnowledgeManager();
			$knowledge_context = $knowledge_manager->get_knowledge_context();

			// Set system instruction.
			$system_instruction = $settings['system_instruction'] ?? 'You are an expert blog writer.';
			$ai->setSystemInstruction( $system_instruction );

			// Build the prompt.
			$prompt = $this->build_generation_prompt( $topic, $word_count, $tone, $knowledge_context );

			// Generate content.
			$response = $ai->chat( $prompt );

			if ( is_array( $response ) && isset( $response['error'] ) ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => $response['error'],
					),
					400
				);
			}

			// Parse the response.
			$parsed = $this->parse_generated_content( $response );

			return new \WP_REST_Response(
				array(
					'success' => true,
					'title'   => $parsed['title'],
					'content' => $parsed['content'],
				),
				200
			);
		} catch ( \Exception $e ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * AI Suggestion endpoint.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response The response.
	 */
	public function ai_suggest( $request ) {
		$settings = Plugin::get_settings();

		if ( empty( $settings['api_key'] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'API key is not configured.', 'ai-author-for-websites' ),
				),
				400
			);
		}

		$type          = sanitize_text_field( $request->get_param( 'type' ) );
		$custom_prompt = sanitize_textarea_field( $request->get_param( 'custom_prompt' ) );

		try {
			$ai = new AIEngine(
				$settings['api_key'],
				array(
					'provider' => $settings['provider'] ?? 'groq',
					'model'    => $settings['model'] ?? 'llama-3.3-70b-versatile',
					'timeout'  => 60,
				)
			);

			$prompt   = $this->build_suggestion_prompt( $type, $custom_prompt );
			$response = $ai->generateContent( $prompt );

			if ( is_array( $response ) && isset( $response['error'] ) ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => $response['error'],
					),
					400
				);
			}

			return new \WP_REST_Response(
				array(
					'success'    => true,
					'suggestion' => trim( $response ),
				),
				200
			);
		} catch ( \Exception $e ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Suggest categories and tags based on content.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response The response.
	 */
	public function suggest_taxonomy( $request ) {
		$settings = Plugin::get_settings();

		if ( empty( $settings['api_key'] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'API key is not configured.', 'ai-author-for-websites' ),
				),
				400
			);
		}

		$title   = sanitize_text_field( $request->get_param( 'title' ) );
		$content = wp_kses_post( $request->get_param( 'content' ) );

		if ( empty( $title ) && empty( $content ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Please provide title or content.', 'ai-author-for-websites' ),
				),
				400
			);
		}

		try {
			$ai = new AIEngine(
				$settings['api_key'],
				array(
					'provider' => $settings['provider'] ?? 'groq',
					'model'    => $settings['model'] ?? 'llama-3.3-70b-versatile',
					'timeout'  => 60,
				)
			);

			// Get existing categories and tags.
			$existing_categories = get_categories( array( 'hide_empty' => false ) );
			$existing_tags       = get_tags( array( 'hide_empty' => false ) );

			$cat_names = array_map( fn( $c ) => $c->name, $existing_categories );
			$tag_names = array_map( fn( $t ) => $t->name, $existing_tags );

			$prompt  = "Based on the following blog post, suggest appropriate categories and tags.\n\n";
			$prompt .= "Title: {$title}\n\n";
			$prompt .= 'Content (excerpt): ' . wp_trim_words( wp_strip_all_tags( $content ), 200 ) . "\n\n";

			if ( ! empty( $cat_names ) ) {
				$prompt .= 'Existing categories in the site: ' . implode( ', ', $cat_names ) . "\n";
			}
			if ( ! empty( $tag_names ) ) {
				$prompt .= 'Existing tags in the site: ' . implode( ', ', array_slice( $tag_names, 0, 50 ) ) . "\n";
			}

			$prompt .= "\nRespond in this exact JSON format:\n";
			$prompt .= '{"categories": ["Category1", "Category2"], "tags": ["tag1", "tag2", "tag3", "tag4", "tag5"]}';
			$prompt .= "\n\nPrefer existing categories/tags when appropriate. Suggest 1-3 categories and 3-7 tags. Only return the JSON, nothing else.";

			$response = $ai->generateContent( $prompt );

			if ( is_array( $response ) && isset( $response['error'] ) ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => $response['error'],
					),
					400
				);
			}

			// Parse JSON response.
			$json_match  = preg_match( '/\{.*\}/s', $response, $matches );
			$suggestions = $json_match ? json_decode( $matches[0], true ) : null;

			if ( ! $suggestions ) {
				$suggestions = array(
					'categories' => array(),
					'tags'       => array(),
				);
			}

			return new \WP_REST_Response(
				array(
					'success'     => true,
					'suggestions' => $suggestions,
					'existing'    => array(
						'categories' => array_map(
							fn( $c ) => array(
								'id'   => $c->term_id,
								'name' => $c->name,
							),
							$existing_categories
						),
						'tags'       => array_map(
							fn( $t ) => array(
								'id'   => $t->term_id,
								'name' => $t->name,
							),
							$existing_tags
						),
					),
				),
				200
			);
		} catch ( \Exception $e ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Build suggestion prompt.
	 *
	 * @param string $type          The type of suggestion.
	 * @param string $custom_prompt Custom prompt from user.
	 * @return string The prompt.
	 */
	private function build_suggestion_prompt( $type, $custom_prompt = '' ) {
		$site_name = get_bloginfo( 'name' );
		$site_desc = get_bloginfo( 'description' );

		switch ( $type ) {
			case 'system_instruction':
				$prompt  = "Generate a system instruction for an AI blog writer assistant.\n\n";
				$prompt .= "Website: {$site_name}\n";
				$prompt .= "Description: {$site_desc}\n\n";

				if ( ! empty( $custom_prompt ) ) {
					$prompt .= "Additional requirements: {$custom_prompt}\n\n";
				}

				$prompt .= "Create a detailed system instruction that:\n";
				$prompt .= "1. Defines the AI's role as a blog writer\n";
				$prompt .= "2. Specifies the writing style and tone\n";
				$prompt .= "3. Mentions SEO best practices\n";
				$prompt .= "4. Includes formatting guidelines\n";
				$prompt .= "5. Is specific to this website's purpose\n\n";
				$prompt .= 'Return only the system instruction text, no explanations.';
				break;

			default:
				$prompt = "Generate a helpful suggestion for: {$type}\n";
				if ( ! empty( $custom_prompt ) ) {
					$prompt .= "Additional context: {$custom_prompt}";
				}
				break;
		}

		return $prompt;
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
		$prompt  = "Write a comprehensive blog post about: {$topic}\n\n";
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

		$prompt .= 'Now write the blog post:';

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

		// Try various title formats.
		// Format: TITLE: My Title or **TITLE:** My Title or Title: My Title.
		if ( preg_match( '/^(?:\*\*)?TITLE:?\*?\*?\s*(.+?)(?:\n|$)/im', $content, $matches ) ) {
			$title        = trim( $matches[1] );
			$body_content = trim( preg_replace( '/^(?:\*\*)?TITLE:?\*?\*?\s*.+?(?:\n|$)/im', '', $content ) );
		} elseif ( preg_match( '/<h1[^>]*>(.+?)<\/h1>/i', $content, $matches ) ) {
			// Format: <h1>My Title</h1>.
			$title        = wp_strip_all_tags( $matches[1] );
			$body_content = preg_replace( '/<h1[^>]*>.+?<\/h1>/i', '', $content, 1 );
		} elseif ( preg_match( '/^#\s+(.+?)(?:\n|$)/m', $content, $matches ) ) {
			// Format: # My Title.
			$title        = trim( $matches[1] );
			$body_content = trim( preg_replace( '/^#\s+.+?(?:\n|$)/m', '', $content, 1 ) );
		}

		// Clean up the title - remove any remaining TITLE: prefix variations.
		$title = preg_replace( '/^(?:\*\*)?TITLE:?\*?\*?\s*/i', '', $title );
		$title = trim( $title );

		// Also clean up any TITLE: that might be in the body content.
		$body_content = preg_replace( '/^(?:\*\*)?TITLE:?\*?\*?\s*.+?(?:\n)/im', '', $body_content );

		$body_content = $this->markdown_to_html( $body_content );

		return array(
			'title'   => $title,
			'content' => trim( $body_content ),
		);
	}

	/**
	 * Convert basic markdown to HTML.
	 *
	 * @param string $text The markdown text.
	 * @return string HTML content.
	 */
	private function markdown_to_html( $text ) {
		$text = preg_replace( '/^### (.+)$/m', '<h3>$1</h3>', $text );
		$text = preg_replace( '/^## (.+)$/m', '<h2>$1</h2>', $text );
		$text = preg_replace( '/^# (.+)$/m', '<h1>$1</h1>', $text );
		$text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
		$text = preg_replace( '/\*(.+?)\*/', '<em>$1</em>', $text );
		$text = preg_replace( '/^[\-\*]\s+(.+)$/m', '<li>$1</li>', $text );
		$text = preg_replace( '/(<li>.+<\/li>\n?)+/', '<ul>$0</ul>', $text );

		$paragraphs = preg_split( '/\n\s*\n/', $text );
		$result     = '';
		foreach ( $paragraphs as $para ) {
			$para = trim( $para );
			if ( empty( $para ) ) {
				continue;
			}
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
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response The response.
	 */
	public function save_draft( $request ) {
		$title         = sanitize_text_field( $request->get_param( 'title' ) );
		$content       = wp_kses_post( $request->get_param( 'content' ) );
		$author_id     = absint( $request->get_param( 'author_id' ) );
		$author_id     = $author_id > 0 ? $author_id : get_current_user_id();
		$categories    = $request->get_param( 'categories' );
		$tags          = $request->get_param( 'tags' );
		$status        = sanitize_text_field( $request->get_param( 'status' ) );
		$status        = ! empty( $status ) ? $status : 'draft';
		$schedule_date = sanitize_text_field( $request->get_param( 'schedule_date' ) );

		if ( empty( $title ) || empty( $content ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Title and content are required.', 'ai-author-for-websites' ),
				),
				400
			);
		}

		// Validate status.
		$allowed_statuses = array( 'draft', 'publish', 'future' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = 'draft';
		}

		$post_data = array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => $status,
			'post_type'    => 'post',
			'post_author'  => $author_id,
		);

		// Handle scheduled posts.
		if ( 'future' === $status && ! empty( $schedule_date ) ) {
			$post_data['post_date']     = $schedule_date;
			$post_data['post_date_gmt'] = get_gmt_from_date( $schedule_date );
		}

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $post_id->get_error_message(),
				),
				500
			);
		}

		// Handle categories.
		if ( ! empty( $categories ) ) {
			$category_ids = array();
			foreach ( $categories as $cat ) {
				if ( is_numeric( $cat ) ) {
					$category_ids[] = absint( $cat );
				} else {
					// Create new category.
					$term = wp_insert_term( sanitize_text_field( $cat ), 'category' );
					if ( ! is_wp_error( $term ) ) {
						$category_ids[] = $term['term_id'];
					}
				}
			}
			if ( ! empty( $category_ids ) ) {
				wp_set_post_categories( $post_id, $category_ids );
			}
		}

		// Handle tags.
		if ( ! empty( $tags ) ) {
			$tag_names = array_map( 'sanitize_text_field', $tags );
			wp_set_post_tags( $post_id, $tag_names );
		}

		// Trigger post created action for integrations (e.g., Pixabay featured image).
		do_action( 'aiauthor_post_created', $post_id, $title, $content );

		// Prepare response message.
		switch ( $status ) {
			case 'publish':
				$message = __( 'Post published successfully!', 'ai-author-for-websites' );
				break;
			case 'future':
				$message = __( 'Post scheduled successfully!', 'ai-author-for-websites' );
				break;
			default:
				$message = __( 'Post saved as draft!', 'ai-author-for-websites' );
		}

		return new \WP_REST_Response(
			array(
				'success'  => true,
				'post_id'  => $post_id,
				'status'   => $status,
				'edit_url' => get_edit_post_link( $post_id, 'raw' ),
				'view_url' => get_permalink( $post_id ),
				'message'  => $message,
			),
			200
		);
	}

	/**
	 * Get knowledge base summary.
	 *
	 * @param \WP_REST_Request $request The request object (unused but required by REST API).
	 * @return \WP_REST_Response The response.
	 */
	public function get_knowledge_summary( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$knowledge_manager = new KnowledgeManager();
		$kb                = $knowledge_manager->get_knowledge_base();
		$summary           = $kb->getSummary();

		return new \WP_REST_Response(
			array(
				'success' => true,
				'summary' => $summary,
			),
			200
		);
	}
}
