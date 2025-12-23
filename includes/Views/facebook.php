<?php
/**
 * Facebook Settings View
 *
 * @package AI_Author_For_Websites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$integrations_manager = \AIAuthor\Integrations\Manager::get_instance();
$facebook             = $integrations_manager->get( 'facebook' );
$settings             = $facebook->get_settings();
$logs                 = $facebook->get_logs( 10 );

// Handle form submission.
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- All inputs are sanitized appropriately.
if ( isset( $_POST['aiauthor_facebook_save'] ) && check_admin_referer( 'aiauthor_facebook_nonce' ) ) {
	$aiauthor_new_settings = array(
		'enabled'           => ! empty( $_POST['enabled'] ),
		'app_id'            => sanitize_text_field( isset( $_POST['app_id'] ) ? wp_unslash( $_POST['app_id'] ) : '' ),
		'app_secret'        => sanitize_text_field( isset( $_POST['app_secret'] ) ? wp_unslash( $_POST['app_secret'] ) : '' ),
		'page_id'           => sanitize_text_field( isset( $_POST['page_id'] ) ? wp_unslash( $_POST['page_id'] ) : '' ),
		'page_access_token' => sanitize_text_field( isset( $_POST['page_access_token'] ) ? wp_unslash( $_POST['page_access_token'] ) : '' ),
		'auto_share'        => ! empty( $_POST['auto_share'] ),
		'share_as_link'     => ! empty( $_POST['share_as_link'] ),
		'include_excerpt'   => ! empty( $_POST['include_excerpt'] ),
		'share_on_publish'  => ! empty( $_POST['share_on_publish'] ),
		'share_on_schedule' => ! empty( $_POST['share_on_schedule'] ),
	);

	$facebook->update_settings( $aiauthor_new_settings );

	// Refresh settings.
	$settings = $facebook->get_settings();
	$saved    = true;
}
// phpcs:enable
?>
<div class="wrap aiauthor-admin aiauthor-facebook">
	<h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-author-integrations' ) ); ?>" class="aiauthor-back-link">
			<span class="dashicons dashicons-arrow-left-alt"></span>
		</a>
		<span class="dashicons dashicons-facebook" style="font-size: 30px; margin-right: 8px; color: #1877f2;"></span>
		<?php esc_html_e( 'Facebook Integration', 'ai-author-for-websites' ); ?>
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
			<li><?php esc_html_e( 'Go to developers.facebook.com and create a new App.', 'ai-author-for-websites' ); ?></li>
			<li><?php esc_html_e( 'Add the "Facebook Login" product and configure it.', 'ai-author-for-websites' ); ?></li>
			<li><?php esc_html_e( 'Generate a Page Access Token with publish_pages and pages_manage_posts permissions.', 'ai-author-for-websites' ); ?></li>
			<li><?php esc_html_e( 'Enter your Page ID and Page Access Token below.', 'ai-author-for-websites' ); ?></li>
		</ol>
		<p>
			<a href="https://developers.facebook.com/docs/pages/getting-started" target="_blank" rel="noopener">
				<?php esc_html_e( 'View Facebook API Documentation', 'ai-author-for-websites' ); ?>
				<span class="dashicons dashicons-external"></span>
			</a>
		</p>
	</div>

	<form method="post" action="">
		<?php wp_nonce_field( 'aiauthor_facebook_nonce' ); ?>

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
								<label for="enabled"><?php esc_html_e( 'Enable Facebook', 'ai-author-for-websites' ); ?></label>
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
									<?php esc_html_e( 'Enable automatic sharing of posts to Facebook.', 'ai-author-for-websites' ); ?>
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
								<label for="app_id"><?php esc_html_e( 'App ID', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<input type="text" 
										id="app_id" 
										name="app_id" 
										value="<?php echo esc_attr( $settings['app_id'] ); ?>"
										class="regular-text">
								<p class="description">
									<?php esc_html_e( 'Your Facebook App ID (optional for basic posting).', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="app_secret"><?php esc_html_e( 'App Secret', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<input type="password" 
										id="app_secret" 
										name="app_secret" 
										value="<?php echo esc_attr( $settings['app_secret'] ); ?>"
										class="regular-text">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="page_id"><?php esc_html_e( 'Page ID', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<input type="text" 
										id="page_id" 
										name="page_id" 
										value="<?php echo esc_attr( $settings['page_id'] ); ?>"
										class="regular-text">
								<p class="description">
									<?php esc_html_e( 'Your Facebook Page ID. Find it in your Page settings.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="page_access_token"><?php esc_html_e( 'Page Access Token', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<textarea id="page_access_token" 
										name="page_access_token" 
										rows="3"
										class="large-text"><?php echo esc_textarea( $settings['page_access_token'] ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'A long-lived Page Access Token with pages_manage_posts permission.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<!-- Sharing Settings -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-share"></span>
						<?php esc_html_e( 'Sharing Settings', 'ai-author-for-websites' ); ?>
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
									<?php esc_html_e( 'Automatically share new posts to Facebook.', 'ai-author-for-websites' ); ?>
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
									<?php esc_html_e( 'Share posts when they are published manually.', 'ai-author-for-websites' ); ?>
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
									<?php esc_html_e( 'Share posts when scheduled posts become published.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="share_as_link"><?php esc_html_e( 'Share as Link', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch">
									<input type="checkbox" 
											id="share_as_link" 
											name="share_as_link" 
											value="1" 
											<?php checked( ! empty( $settings['share_as_link'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Include the post URL in the Facebook post.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="include_excerpt"><?php esc_html_e( 'Include Excerpt', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch">
									<input type="checkbox" 
											id="include_excerpt" 
											name="include_excerpt" 
											value="1" 
											<?php checked( ! empty( $settings['include_excerpt'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Include the post excerpt in the Facebook message.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<p class="submit">
					<input type="submit" 
							name="aiauthor_facebook_save" 
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
					<p><?php esc_html_e( 'Test your Facebook API connection.', 'ai-author-for-websites' ); ?></p>
					
					<button type="button" id="test-connection-btn" class="button button-secondary" <?php disabled( empty( $settings['page_access_token'] ) || empty( $settings['page_id'] ) ); ?>>
						<span class="dashicons dashicons-admin-plugins"></span>
						<?php esc_html_e( 'Test Connection', 'ai-author-for-websites' ); ?>
					</button>
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
			url: '<?php echo esc_url( rest_url( 'ai-author/v1/facebook/test' ) ); ?>',
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
	background: #e7f3ff;
	border: 1px solid #72aee6;
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

