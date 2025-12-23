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
$oauth                = $facebook->get_oauth();
$is_connected         = $oauth->is_connected();
$page_name            = $oauth->get_connected_page_name();

// Check for OAuth messages.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$oauth_success = isset( $_GET['oauth_success'] ) ? sanitize_text_field( wp_unslash( $_GET['oauth_success'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$oauth_error = isset( $_GET['oauth_error'] ) ? sanitize_text_field( wp_unslash( $_GET['oauth_error'] ) ) : '';

// Handle form submission.
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- All inputs are sanitized appropriately.
if ( isset( $_POST['aiauthor_facebook_save'] ) && check_admin_referer( 'aiauthor_facebook_nonce' ) ) {
	$aiauthor_new_settings = array(
		'enabled'           => ! empty( $_POST['enabled'] ),
		'app_id'            => sanitize_text_field( isset( $_POST['app_id'] ) ? wp_unslash( $_POST['app_id'] ) : '' ),
		'app_secret'        => sanitize_text_field( isset( $_POST['app_secret'] ) ? wp_unslash( $_POST['app_secret'] ) : '' ),
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

	<?php if ( $oauth_success ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html( $oauth_success ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $oauth_error ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $oauth_error ); ?></p>
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
			<li>
				<?php esc_html_e( 'Add this OAuth Redirect URI:', 'ai-author-for-websites' ); ?>
				<code><?php echo esc_html( $oauth->get_callback_url() ); ?></code>
			</li>
			<li><?php esc_html_e( 'Enter your App ID and App Secret below, then click "Connect to Facebook".', 'ai-author-for-websites' ); ?></li>
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

				<!-- OAuth Connection -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-admin-network"></span>
						<?php esc_html_e( 'Connect to Facebook', 'ai-author-for-websites' ); ?>
					</h2>

					<?php if ( $is_connected ) : ?>
						<div class="aiauthor-connected-status">
							<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
							<span>
								<?php
								printf(
									/* translators: %s: Facebook page name */
									esc_html__( 'Connected to: %s', 'ai-author-for-websites' ),
									'<strong>' . esc_html( $page_name ) . '</strong>'
								);
								?>
							</span>
							<button type="button" id="disconnect-btn" class="button button-link-delete">
								<?php esc_html_e( 'Disconnect', 'ai-author-for-websites' ); ?>
							</button>
						</div>
					<?php else : ?>
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
						</table>

						<div class="aiauthor-oauth-actions">
							<button type="submit" name="aiauthor_facebook_save" class="button" style="margin-right: 10px;">
								<?php esc_html_e( 'Save App Credentials', 'ai-author-for-websites' ); ?>
							</button>
							<button type="button" id="connect-btn" class="button button-primary" <?php disabled( empty( $settings['app_id'] ) || empty( $settings['app_secret'] ) ); ?>>
								<span class="dashicons dashicons-facebook" style="margin-top: 3px;"></span>
								<?php esc_html_e( 'Connect to Facebook', 'ai-author-for-websites' ); ?>
							</button>
						</div>

						<?php if ( ! empty( $settings['oauth_connected'] ) && empty( $settings['page_access_token'] ) ) : ?>
							<!-- Page Selection -->
							<div class="aiauthor-page-selection" style="margin-top: 20px;">
								<h3><?php esc_html_e( 'Select a Page', 'ai-author-for-websites' ); ?></h3>
								<p><?php esc_html_e( 'Choose which Facebook Page to post to:', 'ai-author-for-websites' ); ?></p>
								<div id="pages-list" class="aiauthor-pages-list">
									<p><span class="spinner is-active"></span> <?php esc_html_e( 'Loading pages...', 'ai-author-for-websites' ); ?></p>
								</div>
							</div>
						<?php endif; ?>
					<?php endif; ?>
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
				<!-- Connection Status -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'Connection Status', 'ai-author-for-websites' ); ?>
					</h2>
					<?php if ( $is_connected ) : ?>
						<div class="aiauthor-status-ok">
							<span class="dashicons dashicons-yes-alt"></span>
							<?php esc_html_e( 'Connected', 'ai-author-for-websites' ); ?>
						</div>
						<p><strong><?php esc_html_e( 'Page:', 'ai-author-for-websites' ); ?></strong> <?php echo esc_html( $page_name ); ?></p>
					<?php else : ?>
						<div class="aiauthor-status-warn">
							<span class="dashicons dashicons-warning"></span>
							<?php esc_html_e( 'Not Connected', 'ai-author-for-websites' ); ?>
						</div>
						<p><?php esc_html_e( 'Enter your App credentials and click Connect.', 'ai-author-for-websites' ); ?></p>
					<?php endif; ?>
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
	var oauthNonce = '<?php echo esc_js( wp_create_nonce( 'aiauthor_facebook_oauth' ) ); ?>';

	// Connect button.
	$('#connect-btn').on('click', function() {
		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Connecting...', 'ai-author-for-websites' ) ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'aiauthor_facebook_connect',
				nonce: oauthNonce
			},
			success: function(response) {
				if (response.success && response.data.auth_url) {
					window.location.href = response.data.auth_url;
				} else {
					alert(response.data.message || '<?php echo esc_js( __( 'Failed to start OAuth.', 'ai-author-for-websites' ) ); ?>');
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-facebook" style="margin-top: 3px;"></span> <?php echo esc_js( __( 'Connect to Facebook', 'ai-author-for-websites' ) ); ?>');
				}
			},
			error: function() {
				alert('<?php echo esc_js( __( 'An error occurred.', 'ai-author-for-websites' ) ); ?>');
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-facebook" style="margin-top: 3px;"></span> <?php echo esc_js( __( 'Connect to Facebook', 'ai-author-for-websites' ) ); ?>');
			}
		});
	});

	// Disconnect button.
	$('#disconnect-btn').on('click', function() {
		if (!confirm('<?php echo esc_js( __( 'Are you sure you want to disconnect from Facebook?', 'ai-author-for-websites' ) ); ?>')) {
			return;
		}

		var $btn = $(this);
		$btn.prop('disabled', true);

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'aiauthor_facebook_disconnect',
				nonce: oauthNonce
			},
			success: function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert(response.data.message);
					$btn.prop('disabled', false);
				}
			}
		});
	});

	// Load pages if needed.
	<?php if ( ! empty( $settings['oauth_connected'] ) && empty( $settings['page_access_token'] ) ) : ?>
	$.ajax({
		url: ajaxurl,
		type: 'POST',
		data: {
			action: 'aiauthor_facebook_get_pages',
			nonce: oauthNonce
		},
		success: function(response) {
			if (response.success && response.data.pages) {
				var html = '';
				response.data.pages.forEach(function(page) {
					html += '<div class="aiauthor-page-item" data-id="' + page.id + '" data-token="' + page.access_token + '" data-name="' + page.name + '">';
					html += '<img src="' + (page.picture?.data?.url || '') + '" alt="">';
					html += '<span>' + page.name + '</span>';
					html += '<button type="button" class="button select-page-btn"><?php echo esc_js( __( 'Select', 'ai-author-for-websites' ) ); ?></button>';
					html += '</div>';
				});
				$('#pages-list').html(html || '<p><?php echo esc_js( __( 'No pages found. Make sure you have admin access to at least one Facebook Page.', 'ai-author-for-websites' ) ); ?></p>');
			} else {
				$('#pages-list').html('<p class="error">' + (response.data.message || '<?php echo esc_js( __( 'Failed to load pages.', 'ai-author-for-websites' ) ); ?>') + '</p>');
			}
		}
	});

	$(document).on('click', '.select-page-btn', function() {
		var $item = $(this).closest('.aiauthor-page-item');
		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Selecting...', 'ai-author-for-websites' ) ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'aiauthor_facebook_select_page',
				nonce: oauthNonce,
				page_id: $item.data('id'),
				page_access_token: $item.data('token'),
				page_name: $item.data('name')
			},
			success: function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert(response.data.message);
					$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Select', 'ai-author-for-websites' ) ); ?>');
				}
			}
		});
	});
	<?php endif; ?>
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

.aiauthor-info-card code {
	background: #fff;
	padding: 2px 6px;
	border-radius: 3px;
}

.aiauthor-connected-status {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 16px;
	background: #e7f7e1;
	border: 1px solid #46b450;
	border-radius: 8px;
}

.aiauthor-oauth-actions {
	margin-top: 20px;
	padding-top: 20px;
	border-top: 1px solid #ddd;
}

.aiauthor-status-ok {
	color: #46b450;
	font-weight: 600;
	display: flex;
	align-items: center;
	gap: 8px;
	margin-bottom: 10px;
}

.aiauthor-status-warn {
	color: #dba617;
	font-weight: 600;
	display: flex;
	align-items: center;
	gap: 8px;
	margin-bottom: 10px;
}

.aiauthor-pages-list {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.aiauthor-page-item {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 12px;
	background: #f6f7f7;
	border-radius: 8px;
}

.aiauthor-page-item img {
	width: 40px;
	height: 40px;
	border-radius: 50%;
}

.aiauthor-page-item span {
	flex: 1;
	font-weight: 500;
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

@media (max-width: 1200px) {
	.aiauthor-settings-grid {
		grid-template-columns: 1fr;
	}
}
</style>
