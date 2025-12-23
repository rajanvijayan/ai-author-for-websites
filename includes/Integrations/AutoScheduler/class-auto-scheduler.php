<?php
/**
 * Auto Scheduler Integration
 *
 * Automatically generates and publishes blog posts on a schedule.
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Integrations\AutoScheduler;

use AIAuthor\Integrations\IntegrationBase;
use AIAuthor\Core\Plugin;
use AIAuthor\Knowledge\Manager as KnowledgeManager;
use AIEngine\AIEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AutoScheduler
 *
 * Handles automatic post generation and scheduling.
 */
class AutoScheduler extends IntegrationBase {

	/**
	 * Cron hook name.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'aiauthor_auto_scheduler_generate';

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	protected $default_settings = array(
		'enabled'              => false,
		'frequency'            => 'weekly',
		'scheduled_day'        => 'monday',
		'scheduled_time'       => '09:00',
		'post_status'          => 'publish',
		'topics'               => array(),
		'auto_generate_topics' => true,
		'word_count'           => 1000,
		'tone'                 => 'professional',
		'default_author'       => 0,
		'default_category'     => 0,
		'ai_generate_category' => false,
		'last_run'             => '',
		'next_run'             => '',
		'posts_generated'      => 0,
	);

	/**
	 * {@inheritdoc}
	 */
	public function get_id(): string {
		return 'auto-scheduler';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return __( 'Auto Scheduler', 'ai-author-for-websites' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Automatically generate and publish blog posts on a schedule. Set frequency, topics, and let AI create content for you.', 'ai-author-for-websites' );
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
		return 'dashicons-calendar-alt';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_category(): string {
		return 'automation';
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
		// Register cron hook.
		add_action( self::CRON_HOOK, array( $this, 'run_scheduled_generation' ) );

		// Register custom cron schedules.
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );

		// Register AJAX handlers.
		add_action( 'wp_ajax_aiauthor_scheduler_test', array( $this, 'ajax_test_generation' ) );
		add_action( 'wp_ajax_aiauthor_scheduler_generate_topics', array( $this, 'ajax_generate_topics' ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function on_activate(): void {
		$this->schedule_next_run();
	}

	/**
	 * {@inheritdoc}
	 */
	public function on_deactivate(): void {
		$this->clear_scheduled_events();
	}

	/**
	 * Add custom cron schedules.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array Modified schedules.
	 */
	public function add_cron_schedules( array $schedules ): array {
		$schedules['aiauthor_twice_daily'] = array(
			'interval' => 12 * HOUR_IN_SECONDS,
			'display'  => __( 'Twice Daily', 'ai-author-for-websites' ),
		);

		$schedules['aiauthor_every_three_days'] = array(
			'interval' => 3 * DAY_IN_SECONDS,
			'display'  => __( 'Every Three Days', 'ai-author-for-websites' ),
		);

		$schedules['aiauthor_biweekly'] = array(
			'interval' => 14 * DAY_IN_SECONDS,
			'display'  => __( 'Bi-Weekly', 'ai-author-for-websites' ),
		);

		$schedules['aiauthor_monthly'] = array(
			'interval' => 30 * DAY_IN_SECONDS,
			'display'  => __( 'Monthly', 'ai-author-for-websites' ),
		);

		return $schedules;
	}

	/**
	 * Get the WordPress cron schedule key for a frequency.
	 *
	 * @param string $frequency The frequency setting.
	 * @return string WordPress cron schedule key.
	 */
	private function get_cron_schedule( string $frequency ): string {
		$map = array(
			'daily'        => 'daily',
			'twice_daily'  => 'aiauthor_twice_daily',
			'every_3_days' => 'aiauthor_every_three_days',
			'weekly'       => 'weekly',
			'biweekly'     => 'aiauthor_biweekly',
			'monthly'      => 'aiauthor_monthly',
		);

		return $map[ $frequency ] ?? 'weekly';
	}

	/**
	 * Schedule the next run.
	 */
	public function schedule_next_run(): void {
		$this->clear_scheduled_events();

		$settings  = $this->get_settings();
		$frequency = $settings['frequency'] ?? 'weekly';
		$day       = $settings['scheduled_day'] ?? 'monday';
		$time      = $settings['scheduled_time'] ?? '09:00';

		// Calculate next run time.
		$next_run = $this->calculate_next_run_time( $frequency, $day, $time );

		// Schedule the event.
		$schedule = $this->get_cron_schedule( $frequency );
		wp_schedule_event( $next_run, $schedule, self::CRON_HOOK );

		// Update settings with next run time.
		$settings['next_run'] = gmdate( 'Y-m-d H:i:s', $next_run );
		$this->update_settings( $settings );
	}

	/**
	 * Calculate the next run time based on settings.
	 *
	 * @param string $frequency The frequency.
	 * @param string $day       The scheduled day.
	 * @param string $time      The scheduled time (HH:MM).
	 * @return int Unix timestamp for next run.
	 */
	private function calculate_next_run_time( string $frequency, string $day, string $time ): int {
		$timezone = wp_timezone();
		$now      = new \DateTime( 'now', $timezone );

		// Parse the time.
		$parts = explode( ':', $time );
		$hour  = isset( $parts[0] ) ? (int) $parts[0] : 9;
		$min   = isset( $parts[1] ) ? (int) $parts[1] : 0;

		// Create target datetime.
		$target = new \DateTime( 'now', $timezone );
		$target->setTime( $hour, $min, 0 );

		switch ( $frequency ) {
			case 'daily':
			case 'twice_daily':
				// If time has passed today, schedule for tomorrow.
				if ( $target <= $now ) {
					$target->modify( '+1 day' );
				}
				break;

			case 'every_3_days':
				// Schedule for 3 days from now at the specified time.
				if ( $target <= $now ) {
					$target->modify( '+3 days' );
				}
				break;

			case 'weekly':
			case 'biweekly':
			case 'monthly':
				// Find the next occurrence of the specified day.
				$target_day = ucfirst( strtolower( $day ) );
				$target->modify( "next {$target_day}" );
				$target->setTime( $hour, $min, 0 );

				// If today is the target day but time has passed, get next week.
				$today = strtolower( $now->format( 'l' ) );
				if ( strtolower( $day ) === $today && $now->format( 'H:i' ) >= $time ) {
					$target->modify( '+1 week' );
				}

				// Adjust for biweekly/monthly.
				if ( 'biweekly' === $frequency ) {
					$target->modify( '+1 week' );
				} elseif ( 'monthly' === $frequency ) {
					$target->modify( '+3 weeks' );
				}
				break;
		}

		return $target->getTimestamp();
	}

	/**
	 * Clear all scheduled events.
	 */
	private function clear_scheduled_events(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Run the scheduled generation.
	 */
	public function run_scheduled_generation(): void {
		$settings = $this->get_settings();

		if ( ! $settings['enabled'] ) {
			return;
		}

		// Check API key.
		$plugin_settings = Plugin::get_settings();
		if ( empty( $plugin_settings['api_key'] ) ) {
			$this->log_error( 'API key not configured. Skipping scheduled generation.' );
			return;
		}

		// Get topic.
		$topic = $this->get_next_topic( $settings );
		if ( empty( $topic ) ) {
			$this->log_error( 'No topic available for generation.' );
			return;
		}

		// Generate post.
		$result = $this->generate_post( $topic, $settings );

		if ( $result['success'] ) {
			// Update statistics.
			$settings['last_run']        = current_time( 'mysql' );
			$settings['posts_generated'] = ( $settings['posts_generated'] ?? 0 ) + 1;
			$this->update_settings( $settings );

			$this->log_success( sprintf( 'Successfully generated post: %s', $result['title'] ) );
		} else {
			$this->log_error( sprintf( 'Failed to generate post: %s', $result['message'] ) );
		}

		// Reschedule next run.
		$this->schedule_next_run();
	}

	/**
	 * Get the next topic for generation.
	 *
	 * @param array $settings Integration settings.
	 * @return string The topic to use.
	 */
	private function get_next_topic( array $settings ): string {
		$topics = $settings['topics'] ?? array();

		// If we have predefined topics, use the next one.
		if ( ! empty( $topics ) ) {
			$topic              = array_shift( $topics );
			$settings['topics'] = $topics;
			$this->update_settings( $settings );
			return $topic;
		}

		// Auto-generate a topic if enabled.
		if ( ! empty( $settings['auto_generate_topics'] ) ) {
			return $this->generate_topic_from_knowledge_base();
		}

		return '';
	}

	/**
	 * Generate a topic from the knowledge base.
	 *
	 * @return string Generated topic.
	 */
	private function generate_topic_from_knowledge_base(): string {
		$plugin_settings = Plugin::get_settings();

		try {
			$ai = new AIEngine(
				$plugin_settings['api_key'],
				array(
					'provider' => $plugin_settings['provider'] ?? 'groq',
					'model'    => $plugin_settings['model'] ?? 'llama-3.3-70b-versatile',
					'timeout'  => 60,
				)
			);

			$knowledge_manager = new KnowledgeManager();
			$context           = $knowledge_manager->get_knowledge_context();

			$site_name = get_bloginfo( 'name' );
			$site_desc = get_bloginfo( 'description' );

			$prompt  = "Based on the following website context, suggest ONE unique blog post topic.\n\n";
			$prompt .= "Website: {$site_name}\n";
			$prompt .= "Description: {$site_desc}\n\n";

			if ( ! empty( $context ) ) {
				$prompt .= "Knowledge Base (excerpt):\n" . wp_trim_words( $context, 500 ) . "\n\n";
			}

			// Get recent posts to avoid duplicates.
			$recent_posts  = get_posts(
				array(
					'numberposts' => 10,
					'post_status' => array( 'publish', 'draft', 'future' ),
				)
			);
			$recent_titles = array_map( fn( $p ) => $p->post_title, $recent_posts );

			if ( ! empty( $recent_titles ) ) {
				$prompt .= "Recent posts (avoid similar topics):\n- " . implode( "\n- ", $recent_titles ) . "\n\n";
			}

			$prompt .= "Requirements:\n";
			$prompt .= "- Suggest a specific, engaging blog post topic\n";
			$prompt .= "- Make it relevant to the website's niche\n";
			$prompt .= "- Avoid topics too similar to recent posts\n";
			$prompt .= "- Return ONLY the topic title, nothing else\n";

			$response = $ai->generateContent( $prompt );

			if ( is_array( $response ) && isset( $response['error'] ) ) {
				return '';
			}

			return trim( $response );
		} catch ( \Exception $e ) {
			$this->log_error( 'Failed to generate topic: ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Generate a blog post.
	 *
	 * @param string $topic    The topic to write about.
	 * @param array  $settings Integration settings.
	 * @return array Result with 'success', 'message', 'post_id', 'title'.
	 */
	private function generate_post( string $topic, array $settings ): array {
		$plugin_settings = Plugin::get_settings();

		try {
			$ai = new AIEngine(
				$plugin_settings['api_key'],
				array(
					'provider' => $plugin_settings['provider'] ?? 'groq',
					'model'    => $plugin_settings['model'] ?? 'llama-3.3-70b-versatile',
					'timeout'  => 120,
				)
			);

			// Get knowledge base context.
			$knowledge_manager = new KnowledgeManager();
			$knowledge_context = $knowledge_manager->get_knowledge_context();

			// Set system instruction.
			$system_instruction = $plugin_settings['system_instruction'] ?? 'You are an expert blog writer.';
			$ai->setSystemInstruction( $system_instruction );

			// Build prompt.
			$word_count = $settings['word_count'] ?? 1000;
			$tone       = $settings['tone'] ?? 'professional';

			$prompt  = "Write a comprehensive blog post about: {$topic}\n\n";
			$prompt .= "Requirements:\n";
			$prompt .= "- Target word count: approximately {$word_count} words\n";
			$prompt .= "- Tone: {$tone}\n";
			$prompt .= "- Include a compelling title (start with 'TITLE: ')\n";
			$prompt .= "- Use proper HTML formatting with headings (h2, h3), paragraphs, and lists where appropriate\n";
			$prompt .= "- Make it engaging, informative, and SEO-friendly\n";
			$prompt .= "- Include a strong introduction and conclusion\n\n";

			if ( ! empty( $knowledge_context ) ) {
				$prompt .= "Use the following knowledge base to inform your writing:\n\n";
				$prompt .= $knowledge_context . "\n\n";
			}

			$prompt .= 'Now write the blog post:';

			// Generate content.
			$response = $ai->chat( $prompt );

			if ( is_array( $response ) && isset( $response['error'] ) ) {
				return array(
					'success' => false,
					'message' => $response['error'],
				);
			}

			// Parse the response.
			$parsed = $this->parse_generated_content( $response );

			// Create the post.
			$author_id = $settings['default_author'] ?? 0;
			if ( ! $author_id ) {
				$admins    = get_users(
					array(
						'role'   => 'administrator',
						'number' => 1,
					)
				);
				$author_id = ! empty( $admins ) ? $admins[0]->ID : 1;
			}

			$post_data = array(
				'post_title'   => ! empty( $parsed['title'] ) ? $parsed['title'] : $topic,
				'post_content' => $parsed['content'],
				'post_status'  => $settings['post_status'] ?? 'publish',
				'post_type'    => 'post',
				'post_author'  => $author_id,
			);

			// Add category if set.
			$category = $settings['default_category'] ?? 0;
			if ( $category ) {
				$post_data['post_category'] = array( $category );
			}

			$post_id = wp_insert_post( $post_data );

			if ( is_wp_error( $post_id ) ) {
				return array(
					'success' => false,
					'message' => $post_id->get_error_message(),
				);
			}

			// Generate AI category if enabled and no default category is set.
			if ( ! empty( $settings['ai_generate_category'] ) && empty( $category ) ) {
				$ai_category = $this->generate_category_for_post( $post_data['post_title'], $post_data['post_content'], $ai );
				if ( $ai_category ) {
					wp_set_post_categories( $post_id, array( $ai_category ) );
				}
			}

			// Add meta to track auto-generated posts.
			update_post_meta( $post_id, '_aiauthor_auto_generated', true );
			update_post_meta( $post_id, '_aiauthor_generation_date', current_time( 'mysql' ) );

			return array(
				'success' => true,
				'message' => 'Post generated successfully.',
				'post_id' => $post_id,
				'title'   => $post_data['post_title'],
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Generate a category for a post using AI.
	 *
	 * @param string   $title   Post title.
	 * @param string   $content Post content.
	 * @param AIEngine $ai      AI engine instance.
	 * @return int|null Category ID or null.
	 */
	private function generate_category_for_post( string $title, string $content, AIEngine $ai ): ?int {
		try {
			// Get existing categories.
			$existing_categories = get_categories( array( 'hide_empty' => false ) );
			$cat_names           = array_map( fn( $c ) => $c->name, $existing_categories );

			$prompt  = "Based on the following blog post, suggest the most appropriate category.\n\n";
			$prompt .= "Title: {$title}\n\n";
			$prompt .= 'Content (excerpt): ' . wp_trim_words( wp_strip_all_tags( $content ), 150 ) . "\n\n";

			if ( ! empty( $cat_names ) ) {
				$prompt .= 'Existing categories in the site: ' . implode( ', ', $cat_names ) . "\n\n";
				$prompt .= 'IMPORTANT: If an existing category fits well, use that exact name. ';
				$prompt .= "Only suggest a new category if none of the existing ones are appropriate.\n\n";
			}

			$prompt .= 'Return ONLY the category name, nothing else. No quotes, no explanation.';

			$response = $ai->generateContent( $prompt );

			if ( is_array( $response ) && isset( $response['error'] ) ) {
				return null;
			}

			$category_name = trim( $response );
			$category_name = trim( $category_name, '"\'.' );

			if ( empty( $category_name ) ) {
				return null;
			}

			// Check if category exists.
			$existing = get_term_by( 'name', $category_name, 'category' );
			if ( $existing ) {
				return $existing->term_id;
			}

			// Create new category.
			$term = wp_insert_term( $category_name, 'category' );
			if ( ! is_wp_error( $term ) ) {
				return $term['term_id'];
			}

			return null;
		} catch ( \Exception $e ) {
			$this->log_error( 'Failed to generate category: ' . $e->getMessage() );
			return null;
		}
	}

	/**
	 * Parse generated content to extract title and body.
	 *
	 * @param string $content The generated content.
	 * @return array Array with 'title' and 'content' keys.
	 */
	private function parse_generated_content( string $content ): array {
		$title        = '';
		$body_content = $content;

		if ( preg_match( '/^(?:\*\*)?TITLE:?\*?\*?\s*(.+?)(?:\n|$)/im', $content, $matches ) ) {
			$title        = trim( $matches[1] );
			$body_content = trim( preg_replace( '/^(?:\*\*)?TITLE:?\*?\*?\s*.+?(?:\n|$)/im', '', $content ) );
		} elseif ( preg_match( '/<h1[^>]*>(.+?)<\/h1>/i', $content, $matches ) ) {
			$title        = wp_strip_all_tags( $matches[1] );
			$body_content = preg_replace( '/<h1[^>]*>.+?<\/h1>/i', '', $content, 1 );
		} elseif ( preg_match( '/^#\s+(.+?)(?:\n|$)/m', $content, $matches ) ) {
			$title        = trim( $matches[1] );
			$body_content = trim( preg_replace( '/^#\s+.+?(?:\n|$)/m', '', $content, 1 ) );
		}

		// Clean up any TITLE: prefix variations from the title.
		$title = preg_replace( '/^(?:\*\*)?TITLE:?\*?\*?\s*/i', '', $title );
		$title = preg_replace( '/^Title:\s*/i', '', $title );
		$title = trim( $title, '"\'*# ' );

		// Also clean up any remaining TITLE: lines from body content.
		$body_content = preg_replace( '/^(?:\*\*)?TITLE:?\*?\*?\s*.+?(?:\n)/im', '', $body_content );
		$body_content = $this->markdown_to_html( $body_content );

		return array(
			'title'   => trim( $title ),
			'content' => trim( $body_content ),
		);
	}

	/**
	 * Convert basic markdown to HTML.
	 *
	 * @param string $text The markdown text.
	 * @return string HTML content.
	 */
	private function markdown_to_html( string $text ): string {
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
	 * Log an error.
	 *
	 * @param string $message Error message.
	 */
	private function log_error( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'AI Author Auto Scheduler Error: ' . $message );
		}

		$this->add_log_entry( 'error', $message );
	}

	/**
	 * Log a success.
	 *
	 * @param string $message Success message.
	 */
	private function log_success( string $message ): void {
		$this->add_log_entry( 'success', $message );
	}

	/**
	 * Add a log entry.
	 *
	 * @param string $type    Log type.
	 * @param string $message Log message.
	 */
	private function add_log_entry( string $type, string $message ): void {
		$logs   = get_option( 'aiauthor_scheduler_logs', array() );
		$logs[] = array(
			'type'    => $type,
			'message' => $message,
			'date'    => current_time( 'mysql' ),
		);

		// Keep only the last 50 entries.
		$logs = array_slice( $logs, -50 );

		update_option( 'aiauthor_scheduler_logs', $logs );
	}

	/**
	 * Get scheduler logs.
	 *
	 * @param int $limit Number of logs to return.
	 * @return array Array of log entries.
	 */
	public function get_logs( int $limit = 20 ): array {
		$logs = get_option( 'aiauthor_scheduler_logs', array() );
		return array_slice( array_reverse( $logs ), 0, $limit );
	}

	/**
	 * AJAX handler for test generation.
	 */
	public function ajax_test_generation(): void {
		check_ajax_referer( 'aiauthor_scheduler_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-author-for-websites' ) ) );
		}

		$settings = $this->get_settings();
		$topic    = sanitize_text_field( wp_unslash( $_POST['topic'] ?? '' ) );

		if ( empty( $topic ) ) {
			$topic = $this->generate_topic_from_knowledge_base();
			if ( empty( $topic ) ) {
				wp_send_json_error( array( 'message' => __( 'Could not generate a topic.', 'ai-author-for-websites' ) ) );
			}
		}

		// Override status for test to use draft.
		$settings['post_status'] = 'draft';

		$result = $this->generate_post( $topic, $settings );

		if ( $result['success'] ) {
			wp_send_json_success(
				array(
					'message'  => $result['message'],
					'post_id'  => $result['post_id'],
					'title'    => $result['title'],
					'edit_url' => get_edit_post_link( $result['post_id'], 'raw' ),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * AJAX handler for generating topic suggestions.
	 */
	public function ajax_generate_topics(): void {
		check_ajax_referer( 'aiauthor_scheduler_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-author-for-websites' ) ) );
		}

		$count           = min( absint( $_POST['count'] ?? 5 ), 20 );
		$plugin_settings = Plugin::get_settings();

		try {
			$ai = new AIEngine(
				$plugin_settings['api_key'],
				array(
					'provider' => $plugin_settings['provider'] ?? 'groq',
					'model'    => $plugin_settings['model'] ?? 'llama-3.3-70b-versatile',
					'timeout'  => 60,
				)
			);

			$knowledge_manager = new KnowledgeManager();
			$context           = $knowledge_manager->get_knowledge_context();

			$site_name = get_bloginfo( 'name' );
			$site_desc = get_bloginfo( 'description' );

			$prompt  = "Based on the following website context, suggest {$count} unique blog post topics.\n\n";
			$prompt .= "Website: {$site_name}\n";
			$prompt .= "Description: {$site_desc}\n\n";

			if ( ! empty( $context ) ) {
				$prompt .= "Knowledge Base (excerpt):\n" . wp_trim_words( $context, 300 ) . "\n\n";
			}

			$prompt .= "Return ONLY a JSON array of topic strings, no explanations. Example:\n";
			$prompt .= '["Topic 1", "Topic 2", "Topic 3"]';

			$response = $ai->generateContent( $prompt );

			if ( is_array( $response ) && isset( $response['error'] ) ) {
				wp_send_json_error( array( 'message' => $response['error'] ) );
			}

			// Parse JSON.
			$json_match = preg_match( '/\[.*\]/s', $response, $matches );
			$topics     = $json_match ? json_decode( $matches[0], true ) : array();

			if ( empty( $topics ) || ! is_array( $topics ) ) {
				wp_send_json_error( array( 'message' => __( 'Could not parse topics.', 'ai-author-for-websites' ) ) );
			}

			wp_send_json_success( array( 'topics' => $topics ) );
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Get frequency options.
	 *
	 * @return array Frequency options.
	 */
	public function get_frequency_options(): array {
		return array(
			'daily'        => __( 'Daily', 'ai-author-for-websites' ),
			'twice_daily'  => __( 'Twice Daily', 'ai-author-for-websites' ),
			'every_3_days' => __( 'Every 3 Days', 'ai-author-for-websites' ),
			'weekly'       => __( 'Weekly', 'ai-author-for-websites' ),
			'biweekly'     => __( 'Bi-Weekly', 'ai-author-for-websites' ),
			'monthly'      => __( 'Monthly', 'ai-author-for-websites' ),
		);
	}

	/**
	 * Get day options.
	 *
	 * @return array Day options.
	 */
	public function get_day_options(): array {
		return array(
			'monday'    => __( 'Monday', 'ai-author-for-websites' ),
			'tuesday'   => __( 'Tuesday', 'ai-author-for-websites' ),
			'wednesday' => __( 'Wednesday', 'ai-author-for-websites' ),
			'thursday'  => __( 'Thursday', 'ai-author-for-websites' ),
			'friday'    => __( 'Friday', 'ai-author-for-websites' ),
			'saturday'  => __( 'Saturday', 'ai-author-for-websites' ),
			'sunday'    => __( 'Sunday', 'ai-author-for-websites' ),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function render_settings_page(): void {
		include AIAUTHOR_PLUGIN_DIR . 'includes/Views/auto-scheduler.php';
	}
}
