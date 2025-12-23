<?php
/**
 * Auto Scheduler Settings View
 *
 * @package AI_Author_For_Websites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$integrations_manager = \AIAuthor\Integrations\Manager::get_instance();
$scheduler            = $integrations_manager->get( 'auto-scheduler' );
$settings             = $scheduler->get_settings();
$plugin_settings      = \AIAuthor\Core\Plugin::get_settings();
$has_api_key          = ! empty( $plugin_settings['api_key'] );

// Handle form submission.
if ( isset( $_POST['aiauthor_scheduler_save'] ) && check_admin_referer( 'aiauthor_scheduler_nonce' ) ) {
	$new_settings = [
		'enabled'               => ! empty( $_POST['enabled'] ),
		'frequency'             => sanitize_key( $_POST['frequency'] ?? 'weekly' ),
		'scheduled_day'         => sanitize_key( $_POST['scheduled_day'] ?? 'monday' ),
		'scheduled_time'        => sanitize_text_field( $_POST['scheduled_time'] ?? '09:00' ),
		'post_status'           => sanitize_key( $_POST['post_status'] ?? 'publish' ),
		'auto_generate_topics'  => ! empty( $_POST['auto_generate_topics'] ),
		'word_count'            => absint( $_POST['word_count'] ?? 1000 ),
		'tone'                  => sanitize_text_field( $_POST['tone'] ?? 'professional' ),
		'default_author'        => absint( $_POST['default_author'] ?? 0 ),
		'default_category'      => absint( $_POST['default_category'] ?? 0 ),
		'ai_generate_category'  => ! empty( $_POST['ai_generate_category'] ),
	];

	// Handle topics.
	$topics_raw = sanitize_textarea_field( $_POST['topics'] ?? '' );
	$topics     = array_filter( array_map( 'trim', explode( "\n", $topics_raw ) ) );
	$new_settings['topics'] = $topics;

	$scheduler->update_settings( $new_settings );

	// Reschedule if enabled.
	if ( $new_settings['enabled'] ) {
		$scheduler->schedule_next_run();
	} else {
		$scheduler->on_deactivate();
	}

	// Refresh settings.
	$settings = $scheduler->get_settings();
	$saved    = true;
}

// Get options.
$frequencies = $scheduler->get_frequency_options();
$days        = $scheduler->get_day_options();
$logs        = $scheduler->get_logs( 10 );

// Get authors.
$authors = get_users(
	[
		'capability' => [ 'edit_posts' ],
		'orderby'    => 'display_name',
	]
);

// Get categories.
$categories = get_categories( [ 'hide_empty' => false ] );

// Get next scheduled run.
$next_run = wp_next_scheduled( 'aiauthor_auto_scheduler_generate' );
?>
<div class="wrap aiauthor-admin aiauthor-scheduler">
	<h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-author-integrations' ) ); ?>" class="aiauthor-back-link">
			<span class="dashicons dashicons-arrow-left-alt"></span>
		</a>
		<span class="dashicons dashicons-calendar-alt" style="font-size: 30px; margin-right: 8px;"></span>
		<?php esc_html_e( 'Auto Scheduler', 'ai-author-for-websites' ); ?>
	</h1>

	<?php if ( ! empty( $saved ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved successfully!', 'ai-author-for-websites' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! $has_api_key ) : ?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'API Key Required', 'ai-author-for-websites' ); ?></strong> - 
				<?php esc_html_e( 'Please configure your API key in', 'ai-author-for-websites' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-author-settings&tab=ai-provider' ) ); ?>">
					<?php esc_html_e( 'Settings', 'ai-author-for-websites' ); ?>
				</a>
				<?php esc_html_e( 'before using the Auto Scheduler.', 'ai-author-for-websites' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'aiauthor_scheduler_nonce' ); ?>

		<div class="aiauthor-scheduler-grid">
			<!-- Main Settings -->
			<div class="aiauthor-scheduler-main">
				<!-- Enable/Status Card -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-admin-settings"></span>
						<?php esc_html_e( 'Scheduler Status', 'ai-author-for-websites' ); ?>
					</h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="enabled"><?php esc_html_e( 'Enable Auto Scheduler', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch aiauthor-switch-large">
									<input type="checkbox" 
											id="enabled" 
											name="enabled" 
											value="1" 
											<?php checked( ! empty( $settings['enabled'] ) ); ?>
											<?php disabled( ! $has_api_key ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'When enabled, posts will be generated automatically based on your schedule.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<?php if ( $next_run && $settings['enabled'] ) : ?>
						<div class="aiauthor-next-run-info">
							<span class="dashicons dashicons-clock"></span>
							<strong><?php esc_html_e( 'Next scheduled run:', 'ai-author-for-websites' ); ?></strong>
							<?php
							echo esc_html(
								wp_date(
									get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
									$next_run
								)
							);
							?>
						</div>
					<?php endif; ?>
				</div>

				<!-- Schedule Settings -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-clock"></span>
						<?php esc_html_e( 'Schedule', 'ai-author-for-websites' ); ?>
					</h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="frequency"><?php esc_html_e( 'Frequency', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<select id="frequency" name="frequency" class="regular-text">
									<?php foreach ( $frequencies as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['frequency'] ?? 'weekly', $key ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr class="aiauthor-day-row">
							<th scope="row">
								<label for="scheduled_day"><?php esc_html_e( 'Day of Week', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<select id="scheduled_day" name="scheduled_day" class="regular-text">
									<?php foreach ( $days as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['scheduled_day'] ?? 'monday', $key ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">
									<?php esc_html_e( 'For weekly/bi-weekly schedules, select which day.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="scheduled_time"><?php esc_html_e( 'Time', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<input type="time" 
										id="scheduled_time" 
										name="scheduled_time" 
										value="<?php echo esc_attr( $settings['scheduled_time'] ?? '09:00' ); ?>"
										class="regular-text">
								<p class="description">
									<?php
									printf(
										/* translators: %s: timezone name */
										esc_html__( 'Time in your WordPress timezone (%s).', 'ai-author-for-websites' ),
										esc_html( wp_timezone_string() )
									);
									?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<!-- Content Settings -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-edit"></span>
						<?php esc_html_e( 'Content Settings', 'ai-author-for-websites' ); ?>
					</h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="auto_generate_topics"><?php esc_html_e( 'Auto-Generate Topics', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch">
									<input type="checkbox" 
											id="auto_generate_topics" 
											name="auto_generate_topics" 
											value="1" 
											<?php checked( ! empty( $settings['auto_generate_topics'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Let AI generate topics based on your knowledge base when the topic queue is empty.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="topics"><?php esc_html_e( 'Topic Queue', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<textarea id="topics" 
											name="topics" 
											rows="5" 
											class="large-text"
											placeholder="<?php esc_attr_e( 'One topic per line...', 'ai-author-for-websites' ); ?>"><?php echo esc_textarea( implode( "\n", $settings['topics'] ?? [] ) ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'Enter topics to generate (one per line). Posts will be generated in order.', 'ai-author-for-websites' ); ?>
								</p>
								<button type="button" id="generate-topics-btn" class="button button-secondary" style="margin-top: 8px;">
									<span class="dashicons dashicons-lightbulb"></span>
									<?php esc_html_e( 'AI Suggest Topics', 'ai-author-for-websites' ); ?>
								</button>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="word_count"><?php esc_html_e( 'Word Count', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<input type="number" 
										id="word_count" 
										name="word_count" 
										value="<?php echo esc_attr( $settings['word_count'] ?? 1000 ); ?>"
										min="100"
										max="5000"
										step="100"
										class="small-text">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="tone"><?php esc_html_e( 'Writing Tone', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<select id="tone" name="tone" class="regular-text">
									<option value="professional" <?php selected( $settings['tone'] ?? 'professional', 'professional' ); ?>>
										<?php esc_html_e( 'Professional', 'ai-author-for-websites' ); ?>
									</option>
									<option value="conversational" <?php selected( $settings['tone'] ?? '', 'conversational' ); ?>>
										<?php esc_html_e( 'Conversational', 'ai-author-for-websites' ); ?>
									</option>
									<option value="friendly" <?php selected( $settings['tone'] ?? '', 'friendly' ); ?>>
										<?php esc_html_e( 'Friendly & Casual', 'ai-author-for-websites' ); ?>
									</option>
									<option value="authoritative" <?php selected( $settings['tone'] ?? '', 'authoritative' ); ?>>
										<?php esc_html_e( 'Authoritative & Expert', 'ai-author-for-websites' ); ?>
									</option>
									<option value="educational" <?php selected( $settings['tone'] ?? '', 'educational' ); ?>>
										<?php esc_html_e( 'Educational', 'ai-author-for-websites' ); ?>
									</option>
								</select>
							</td>
						</tr>
					</table>
				</div>

				<!-- Publishing Settings -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-admin-post"></span>
						<?php esc_html_e( 'Publishing Settings', 'ai-author-for-websites' ); ?>
					</h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="post_status"><?php esc_html_e( 'Post Status', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<select id="post_status" name="post_status" class="regular-text">
									<option value="publish" <?php selected( $settings['post_status'] ?? 'publish', 'publish' ); ?>>
										<?php esc_html_e( 'Publish Immediately', 'ai-author-for-websites' ); ?>
									</option>
									<option value="draft" <?php selected( $settings['post_status'] ?? '', 'draft' ); ?>>
										<?php esc_html_e( 'Save as Draft (Review First)', 'ai-author-for-websites' ); ?>
									</option>
								</select>
								<p class="description">
									<?php esc_html_e( 'Choose whether posts should be published immediately or saved as drafts for review.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="default_author"><?php esc_html_e( 'Default Author', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<select id="default_author" name="default_author" class="regular-text">
									<?php foreach ( $authors as $author ) : ?>
										<option value="<?php echo esc_attr( $author->ID ); ?>" <?php selected( $settings['default_author'] ?? 0, $author->ID ); ?>>
											<?php echo esc_html( $author->display_name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="default_category"><?php esc_html_e( 'Default Category', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<select id="default_category" name="default_category" class="regular-text">
									<option value="0"><?php esc_html_e( '— None —', 'ai-author-for-websites' ); ?></option>
									<?php foreach ( $categories as $cat ) : ?>
										<option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $settings['default_category'] ?? 0, $cat->term_id ); ?>>
											<?php echo esc_html( $cat->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="ai_generate_category"><?php esc_html_e( 'AI Category Generation', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch">
									<input type="checkbox" 
											id="ai_generate_category" 
											name="ai_generate_category" 
											value="1" 
											<?php checked( ! empty( $settings['ai_generate_category'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'When enabled, AI will automatically generate or select an appropriate category based on the post content. This only applies when no default category is set.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<p class="submit">
					<input type="submit" 
							name="aiauthor_scheduler_save" 
							class="button button-primary button-large" 
							value="<?php esc_attr_e( 'Save Settings', 'ai-author-for-websites' ); ?>">
				</p>
			</div>

			<!-- Sidebar -->
			<div class="aiauthor-scheduler-sidebar">
				<!-- Statistics -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-chart-bar"></span>
						<?php esc_html_e( 'Statistics', 'ai-author-for-websites' ); ?>
					</h2>
					<div class="aiauthor-stats-grid">
						<div class="aiauthor-stat-item">
							<span class="aiauthor-stat-number"><?php echo esc_html( $settings['posts_generated'] ?? 0 ); ?></span>
							<span class="aiauthor-stat-label"><?php esc_html_e( 'Posts Generated', 'ai-author-for-websites' ); ?></span>
						</div>
						<div class="aiauthor-stat-item">
							<span class="aiauthor-stat-number"><?php echo esc_html( count( $settings['topics'] ?? [] ) ); ?></span>
							<span class="aiauthor-stat-label"><?php esc_html_e( 'Topics in Queue', 'ai-author-for-websites' ); ?></span>
						</div>
					</div>
					<?php if ( ! empty( $settings['last_run'] ) ) : ?>
						<div class="aiauthor-last-run">
							<strong><?php esc_html_e( 'Last Run:', 'ai-author-for-websites' ); ?></strong>
							<?php
							echo esc_html(
								wp_date(
									get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
									strtotime( $settings['last_run'] )
								)
							);
							?>
						</div>
					<?php endif; ?>
				</div>

				<!-- Test Generation -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'Test Generation', 'ai-author-for-websites' ); ?>
					</h2>
					<p><?php esc_html_e( 'Generate a test post (saved as draft) to verify your settings.', 'ai-author-for-websites' ); ?></p>
					
					<div class="aiauthor-test-form">
						<input type="text" 
								id="test-topic" 
								placeholder="<?php esc_attr_e( 'Topic (optional - leave blank for auto)', 'ai-author-for-websites' ); ?>"
								class="large-text">
						<button type="button" id="test-generation-btn" class="button button-secondary" <?php disabled( ! $has_api_key ); ?>>
							<span class="dashicons dashicons-controls-play"></span>
							<?php esc_html_e( 'Generate Test Post', 'ai-author-for-websites' ); ?>
						</button>
					</div>
					<div id="test-result" class="aiauthor-test-result" style="display: none;"></div>
				</div>

				<!-- Activity Log -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-list-view"></span>
						<?php esc_html_e( 'Recent Activity', 'ai-author-for-websites' ); ?>
					</h2>
					<?php if ( empty( $logs ) ) : ?>
						<p class="aiauthor-no-logs"><?php esc_html_e( 'No activity yet.', 'ai-author-for-websites' ); ?></p>
					<?php else : ?>
						<ul class="aiauthor-activity-log">
							<?php foreach ( $logs as $log ) : ?>
								<li class="aiauthor-log-<?php echo esc_attr( $log['type'] ); ?>">
									<span class="dashicons <?php echo 'success' === $log['type'] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
									<span class="aiauthor-log-message"><?php echo esc_html( $log['message'] ); ?></span>
									<span class="aiauthor-log-date"><?php echo esc_html( human_time_diff( strtotime( $log['date'] ) ) ); ?> ago</span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</form>
</div>

<script>
jQuery(document).ready(function($) {
	// Handle frequency change to show/hide day selector.
	$('#frequency').on('change', function() {
		var freq = $(this).val();
		if (freq === 'weekly' || freq === 'biweekly' || freq === 'monthly') {
			$('.aiauthor-day-row').show();
		} else {
			$('.aiauthor-day-row').hide();
		}
	}).trigger('change');

	// Test generation.
	$('#test-generation-btn').on('click', function() {
		var $btn = $(this);
		var $result = $('#test-result');
		var topic = $('#test-topic').val();

		$btn.prop('disabled', true).find('.dashicons').removeClass('dashicons-controls-play').addClass('dashicons-update spin');
		$result.hide();

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'aiauthor_scheduler_test',
				nonce: '<?php echo esc_js( wp_create_nonce( 'aiauthor_scheduler_nonce' ) ); ?>',
				topic: topic
			},
			success: function(response) {
				$btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-controls-play');
				
				if (response.success) {
					$result.html(
						'<div class="notice notice-success">' +
						'<p><strong>' + response.data.message + '</strong></p>' +
						'<p>Title: ' + response.data.title + '</p>' +
						'<p><a href="' + response.data.edit_url + '" class="button" target="_blank">Edit Post</a></p>' +
						'</div>'
					).show();
				} else {
					$result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>').show();
				}
			},
			error: function() {
				$btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-controls-play');
				$result.html('<div class="notice notice-error"><p><?php echo esc_js( __( 'An error occurred.', 'ai-author-for-websites' ) ); ?></p></div>').show();
			}
		});
	});

	// Generate topics.
	$('#generate-topics-btn').on('click', function() {
		var $btn = $(this);
		var $textarea = $('#topics');

		$btn.prop('disabled', true).find('.dashicons').removeClass('dashicons-lightbulb').addClass('dashicons-update spin');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'aiauthor_scheduler_generate_topics',
				nonce: '<?php echo esc_js( wp_create_nonce( 'aiauthor_scheduler_nonce' ) ); ?>',
				count: 5
			},
			success: function(response) {
				$btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-lightbulb');
				
				if (response.success) {
					var existing = $textarea.val().trim();
					var newTopics = response.data.topics.join('\n');
					$textarea.val(existing ? existing + '\n' + newTopics : newTopics);
				} else {
					alert(response.data.message);
				}
			},
			error: function() {
				$btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-lightbulb');
				alert('<?php echo esc_js( __( 'Failed to generate topics.', 'ai-author-for-websites' ) ); ?>');
			}
		});
	});
});
</script>

<style>
.aiauthor-scheduler-grid {
	display: grid;
	grid-template-columns: 1fr 360px;
	gap: 24px;
	margin-top: 20px;
}

.aiauthor-scheduler-main .aiauthor-card {
	margin-bottom: 20px;
}

.aiauthor-scheduler-sidebar .aiauthor-card {
	margin-bottom: 20px;
}

.aiauthor-back-link {
	text-decoration: none;
	color: #50575e;
	margin-right: 8px;
}

.aiauthor-back-link:hover {
	color: #0073aa;
}

.aiauthor-next-run-info {
	background: #e7f7e1;
	border: 1px solid #46b450;
	padding: 12px 16px;
	border-radius: 4px;
	margin-top: 16px;
	display: flex;
	align-items: center;
	gap: 8px;
}

.aiauthor-next-run-info .dashicons {
	color: #46b450;
}

.aiauthor-stats-grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 16px;
	margin-bottom: 16px;
}

.aiauthor-stat-item {
	text-align: center;
	padding: 16px;
	background: #f6f7f7;
	border-radius: 8px;
}

.aiauthor-stat-item .aiauthor-stat-number {
	display: block;
	font-size: 32px;
	font-weight: 600;
	color: #0073aa;
}

.aiauthor-stat-item .aiauthor-stat-label {
	display: block;
	font-size: 12px;
	color: #666;
	margin-top: 4px;
}

.aiauthor-last-run {
	font-size: 13px;
	color: #666;
	padding-top: 12px;
	border-top: 1px solid #ddd;
}

.aiauthor-test-form {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.aiauthor-test-result {
	margin-top: 16px;
}

.aiauthor-no-logs {
	color: #666;
	font-style: italic;
}

.aiauthor-activity-log {
	list-style: none;
	margin: 0;
	padding: 0;
}

.aiauthor-activity-log li {
	padding: 10px 0;
	border-bottom: 1px solid #eee;
	display: flex;
	align-items: flex-start;
	gap: 8px;
	font-size: 13px;
}

.aiauthor-activity-log li:last-child {
	border-bottom: none;
}

.aiauthor-log-success .dashicons {
	color: #46b450;
}

.aiauthor-log-error .dashicons {
	color: #dc3232;
}

.aiauthor-log-message {
	flex: 1;
}

.aiauthor-log-date {
	font-size: 11px;
	color: #999;
}

.aiauthor-switch-large .slider {
	width: 60px;
	height: 30px;
}

.aiauthor-switch-large .slider:before {
	height: 22px;
	width: 22px;
	bottom: 4px;
	left: 4px;
}

.aiauthor-switch-large input:checked + .slider:before {
	transform: translateX(30px);
}

.spin {
	animation: spin 1s linear infinite;
}

@keyframes spin {
	100% { transform: rotate(360deg); }
}

@media (max-width: 1200px) {
	.aiauthor-scheduler-grid {
		grid-template-columns: 1fr;
	}
}
</style>

