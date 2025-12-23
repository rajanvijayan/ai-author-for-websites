<?php
/**
 * Twitter/X Settings View
 *
 * @package AI_Author_For_Websites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$integrations_manager = \AIAuthor\Integrations\Manager::get_instance();
$twitter              = $integrations_manager->get( 'twitter' );
$settings             = $twitter->get_settings();
$logs                 = $twitter->get_logs( 10 );

// Handle form submission.
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- All inputs are sanitized appropriately.
if ( isset( $_POST['aiauthor_twitter_save'] ) && check_admin_referer( 'aiauthor_twitter_nonce' ) ) {
	$aiauthor_new_settings = array(
		'enabled'           => ! empty( $_POST['enabled'] ),
		'api_key'           => sanitize_text_field( isset( $_POST['api_key'] ) ? wp_unslash( $_POST['api_key'] ) : '' ),
		'api_secret'        => sanitize_text_field( isset( $_POST['api_secret'] ) ? wp_unslash( $_POST['api_secret'] ) : '' ),
		'access_token'      => sanitize_text_field( isset( $_POST['access_token'] ) ? wp_unslash( $_POST['access_token'] ) : '' ),
		'access_secret'     => sanitize_text_field( isset( $_POST['access_secret'] ) ? wp_unslash( $_POST['access_secret'] ) : '' ),
		'bearer_token'      => sanitize_text_field( isset( $_POST['bearer_token'] ) ? wp_unslash( $_POST['bearer_token'] ) : '' ),
		'auto_share'        => ! empty( $_POST['auto_share'] ),
		'include_link'      => ! empty( $_POST['include_link'] ),
		'include_hashtags'  => ! empty( $_POST['include_hashtags'] ),
		'max_hashtags'      => absint( isset( $_POST['max_hashtags'] ) ? $_POST['max_hashtags'] : 3 ),
		'share_on_publish'  => ! empty( $_POST['share_on_publish'] ),
		'share_on_schedule' => ! empty( $_POST['share_on_schedule'] ),
	);

	$twitter->update_settings( $aiauthor_new_settings );

	// Refresh settings.
	$settings = $twitter->get_settings();
	$saved    = true;
}
// phpcs:enable
?>
<div class="wrap aiauthor-admin aiauthor-twitter">
	<h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-author-integrations' ) ); ?>" class="aiauthor-back-link">
			<span class="dashicons dashicons-arrow-left-alt"></span>
		</a>
		<span class="dashicons dashicons-twitter" style="font-size: 30px; margin-right: 8px; color: #1da1f2;"></span>
		<?php esc_html_e( 'Twitter/X Integration', 'ai-author-for-websites' ); ?>
	</h1>

	<?php if ( ! empty( $saved ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved successfully!', 'ai-author-for-websites' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="aiauthor-info-card">
		<h3>
			<span class="dashicons dashicons-info"></span>
			<?php esc_html_e( 'Setup Instructions', 'ai-author-for-websites' ); ?>
		</h3>
		<ol>
			<li><?php esc_html_e( 'Go to developer.twitter.com and create a new App.', 'ai-author-for-websites' ); ?></li>
			<li><?php esc_html_e( 'Enable OAuth 1.0a with Read and Write permissions.', 'ai-author-for-websites' ); ?></li>
			<li><?php esc_html_e( 'Generate your API Key, API Secret, Access Token, and Access Token Secret.', 'ai-author-for-websites' ); ?></li>
			<li><?php esc_html_e( 'Generate a Bearer Token for testing the connection.', 'ai-author-for-websites' ); ?></li>
		</ol>
		<p>
			<a href="https://developer.twitter.com/en/docs/twitter-api/getting-started/getting-access-to-the-twitter-api" target="_blank" rel="noopener">
				<?php esc_html_e( 'View Twitter API Documentation', 'ai-author-for-websites' ); ?>
				<span class="dashicons dashicons-external"></span>
			</a>
		</p>
	</div>

	<form method="post" action="">
		<?php wp_nonce_field( 'aiauthor_twitter_nonce' ); ?>

		<div class="aiauthor-settings-grid">
			<div class="aiauthor-settings-main">
				<!-- Enable Integration -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-admin-settings"></span>
						<?php esc_html_e( 'Integration Status', 'ai-author-for-websites' ); ?>
					</h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="enabled"><?php esc_html_e( 'Enable Twitter/X', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch aiauthor-switch-large">
									<input type="checkbox" 
											id="enabled" 
											name="enabled" 
											value="1" 
											<?php checked( ! empty( $settings['enabled'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Enable automatic sharing of posts to Twitter/X.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<!-- API Configuration -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-admin-network"></span>
						<?php esc_html_e( 'API Configuration', 'ai-author-for-websites' ); ?>
					</h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="api_key"><?php esc_html_e( 'API Key (Consumer Key)', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<input type="password" 
										id="api_key" 
										name="api_key" 
										value="<?php echo esc_attr( $settings['api_key'] ); ?>"
										class="regular-text">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="api_secret"><?php esc_html_e( 'API Secret (Consumer Secret)', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<input type="password" 
										id="api_secret" 
										name="api_secret" 
										value="<?php echo esc_attr( $settings['api_secret'] ); ?>"
										class="regular-text">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="access_token"><?php esc_html_e( 'Access Token', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<input type="password" 
										id="access_token" 
										name="access_token" 
										value="<?php echo esc_attr( $settings['access_token'] ); ?>"
										class="regular-text">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="access_secret"><?php esc_html_e( 'Access Token Secret', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<input type="password" 
										id="access_secret" 
										name="access_secret" 
										value="<?php echo esc_attr( $settings['access_secret'] ); ?>"
										class="regular-text">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="bearer_token"><?php esc_html_e( 'Bearer Token', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<input type="password" 
										id="bearer_token" 
										name="bearer_token" 
										value="<?php echo esc_attr( $settings['bearer_token'] ); ?>"
										class="regular-text">
								<p class="description">
									<?php esc_html_e( 'Used for testing the connection.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<!-- Tweet Settings -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-share"></span>
						<?php esc_html_e( 'Tweet Settings', 'ai-author-for-websites' ); ?>
					</h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="auto_share"><?php esc_html_e( 'Auto Share', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch">
									<input type="checkbox" 
											id="auto_share" 
											name="auto_share" 
											value="1" 
											<?php checked( ! empty( $settings['auto_share'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Automatically tweet new posts.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="share_on_publish"><?php esc_html_e( 'Share on Publish', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch">
									<input type="checkbox" 
											id="share_on_publish" 
											name="share_on_publish" 
											value="1" 
											<?php checked( ! empty( $settings['share_on_publish'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Tweet posts when they are published manually.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="share_on_schedule"><?php esc_html_e( 'Share Scheduled Posts', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch">
									<input type="checkbox" 
											id="share_on_schedule" 
											name="share_on_schedule" 
											value="1" 
											<?php checked( ! empty( $settings['share_on_schedule'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Tweet posts when scheduled posts become published.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="include_link"><?php esc_html_e( 'Include Link', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch">
									<input type="checkbox" 
											id="include_link" 
											name="include_link" 
											value="1" 
											<?php checked( ! empty( $settings['include_link'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Include the post URL in the tweet.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="include_hashtags"><?php esc_html_e( 'Include Hashtags', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch">
									<input type="checkbox" 
											id="include_hashtags" 
											name="include_hashtags" 
											value="1" 
											<?php checked( ! empty( $settings['include_hashtags'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Add hashtags from post tags.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="max_hashtags"><?php esc_html_e( 'Max Hashtags', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<input type="number" 
										id="max_hashtags" 
										name="max_hashtags" 
										value="<?php echo esc_attr( $settings['max_hashtags'] ?? 3 ); ?>"
										min="1"
										max="10"
										class="small-text">
								<p class="description">
									<?php esc_html_e( 'Maximum number of hashtags to include.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<p class="submit">
					<input type="submit" 
							name="aiauthor_twitter_save" 
							class="button button-primary button-large" 
							value="<?php esc_attr_e( 'Save Settings', 'ai-author-for-websites' ); ?>">
				</p>
			</div>

			<!-- Sidebar -->
			<div class="aiauthor-settings-sidebar">
				<!-- Test Connection -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'Test Connection', 'ai-author-for-websites' ); ?>
					</h2>
					<p><?php esc_html_e( 'Test your Twitter API connection.', 'ai-author-for-websites' ); ?></p>
					
					<button type="button" id="test-connection-btn" class="button button-secondary" <?php disabled( empty( $settings['bearer_token'] ) ); ?>>
						<span class="dashicons dashicons-admin-plugins"></span>
						<?php esc_html_e( 'Test Connection', 'ai-author-for-websites' ); ?>
					</button>
					<div id="test-result" class="aiauthor-test-result" style="display: none;"></div>
				</div>

				<!-- Tweet Preview -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-visibility"></span>
						<?php esc_html_e( 'Tweet Format', 'ai-author-for-websites' ); ?>
					</h2>
					<div class="aiauthor-tweet-preview">
						<p><?php esc_html_e( 'Your tweets will be formatted like:', 'ai-author-for-websites' ); ?></p>
						<div class="tweet-example">
							<strong>[Post Title]</strong><br>
							<?php if ( ! empty( $settings['include_link'] ) ) : ?>
								<span style="color: #1da1f2;">https://your-site.com/post</span><br>
							<?php endif; ?>
							<?php if ( ! empty( $settings['include_hashtags'] ) ) : ?>
								<span style="color: #1da1f2;">#Tag1 #Tag2 #Tag3</span>
							<?php endif; ?>
						</div>
					</div>
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
								<li class="aiauthor-log-<?php echo $log['success'] ? 'success' : 'error'; ?>">
									<span class="dashicons <?php echo $log['success'] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
									<span class="aiauthor-log-message">
										<?php echo esc_html( $log['title'] ); ?>
										<?php if ( ! $log['success'] && ! empty( $log['error'] ) ) : ?>
											<br><small><?php echo esc_html( $log['error'] ); ?></small>
										<?php endif; ?>
									</span>
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
	// Test connection.
	$('#test-connection-btn').on('click', function() {
		var $btn = $(this);
		var $result = $('#test-result');

		$btn.prop('disabled', true).find('.dashicons').removeClass('dashicons-admin-plugins').addClass('dashicons-update spin');
		$result.hide();

		$.ajax({
			url: '<?php echo esc_url( rest_url( 'ai-author/v1/twitter/test' ) ); ?>',
			type: 'POST',
			headers: {
				'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
			},
			success: function(response) {
				$btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-admin-plugins');
				
				if (response.success) {
					$result.html('<div class="notice notice-success"><p>' + response.message + '</p></div>').show();
				} else {
					$result.html('<div class="notice notice-error"><p>' + response.message + '</p></div>').show();
				}
			},
			error: function(xhr) {
				$btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-admin-plugins');
				var msg = xhr.responseJSON ? xhr.responseJSON.message : '<?php echo esc_js( __( 'An error occurred.', 'ai-author-for-websites' ) ); ?>';
				$result.html('<div class="notice notice-error"><p>' + msg + '</p></div>').show();
			}
		});
	});
});
</script>

<style>
.aiauthor-settings-grid {
	display: grid;
	grid-template-columns: 1fr 360px;
	gap: 24px;
	margin-top: 20px;
}

.aiauthor-settings-main .aiauthor-card,
.aiauthor-settings-sidebar .aiauthor-card {
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

.aiauthor-info-card {
	background: #e7f6ff;
	border: 1px solid #1da1f2;
	padding: 16px 20px;
	border-radius: 8px;
	margin: 20px 0;
}

.aiauthor-info-card h3 {
	margin: 0 0 10px;
	display: flex;
	align-items: center;
	gap: 8px;
}

.aiauthor-info-card ol {
	margin: 0 0 10px 20px;
}

.aiauthor-info-card p {
	margin: 0;
}

.aiauthor-info-card a {
	display: inline-flex;
	align-items: center;
	gap: 4px;
}

.aiauthor-test-result {
	margin-top: 16px;
}

.aiauthor-tweet-preview {
	background: #f5f8fa;
	border: 1px solid #e1e8ed;
	border-radius: 8px;
	padding: 12px;
}

.tweet-example {
	background: white;
	border: 1px solid #e1e8ed;
	border-radius: 8px;
	padding: 12px;
	font-size: 14px;
	line-height: 1.5;
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
	.aiauthor-settings-grid {
		grid-template-columns: 1fr;
	}
}
</style>

