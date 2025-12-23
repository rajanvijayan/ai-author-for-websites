<?php
/**
 * Yoast SEO Settings View
 *
 * @package AI_Author_For_Websites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$integrations_manager = \AIAuthor\Integrations\Manager::get_instance();
$yoast                = $integrations_manager->get( 'yoast-seo' );
$settings             = $yoast->get_settings();
$is_yoast_active      = $yoast->is_yoast_active();

// Handle form submission.
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- All inputs are sanitized appropriately.
if ( isset( $_POST['aiauthor_yoast_save'] ) && check_admin_referer( 'aiauthor_yoast_nonce' ) ) {
	$aiauthor_new_settings = array(
		'enabled'                  => ! empty( $_POST['enabled'] ),
		'auto_generate_meta'       => ! empty( $_POST['auto_generate_meta'] ),
		'generate_focus_keyphrase' => ! empty( $_POST['generate_focus_keyphrase'] ),
		'generate_meta_desc'       => ! empty( $_POST['generate_meta_desc'] ),
		'meta_desc_length'         => absint( isset( $_POST['meta_desc_length'] ) ? $_POST['meta_desc_length'] : 155 ),
		'generate_og_title'        => ! empty( $_POST['generate_og_title'] ),
		'generate_og_desc'         => ! empty( $_POST['generate_og_desc'] ),
	);

	$yoast->update_settings( $aiauthor_new_settings );

	// Refresh settings.
	$settings = $yoast->get_settings();
	$saved    = true;
}
// phpcs:enable

$logs = $yoast->get_logs( 15 );
?>
<div class="wrap aiauthor-admin aiauthor-yoast-seo">
	<h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-author-integrations' ) ); ?>" class="aiauthor-back-link">
			<span class="dashicons dashicons-arrow-left-alt"></span>
		</a>
		<span class="dashicons dashicons-search" style="font-size: 30px; margin-right: 8px; color: #a4286a;"></span>
		<?php esc_html_e( 'Yoast SEO Integration', 'ai-author-for-websites' ); ?>
	</h1>

	<?php if ( ! empty( $saved ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved successfully!', 'ai-author-for-websites' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! $is_yoast_active ) : ?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Yoast SEO Not Detected', 'ai-author-for-websites' ); ?></strong> - 
				<?php esc_html_e( 'This integration requires Yoast SEO plugin to be installed and activated.', 'ai-author-for-websites' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<div class="aiauthor-info-card" style="border-color: #a4286a; background: #fdf5f9;">
		<h3>
			<span class="dashicons dashicons-info"></span>
			<?php esc_html_e( 'About This Integration', 'ai-author-for-websites' ); ?>
		</h3>
		<p>
			<?php esc_html_e( 'This integration automatically generates SEO metadata for your AI-generated posts using Yoast SEO. It creates focus keyphrases, meta descriptions, and SEO titles optimized for search engines.', 'ai-author-for-websites' ); ?>
		</p>
		<ul style="margin: 10px 0 0 20px;">
			<li><?php esc_html_e( 'Focus Keyphrase: The main keyword/phrase your content targets', 'ai-author-for-websites' ); ?></li>
			<li><?php esc_html_e( 'Meta Description: Compelling snippet shown in search results (up to 155 characters)', 'ai-author-for-websites' ); ?></li>
			<li><?php esc_html_e( 'SEO Title: Optimized title tag for search engines (up to 60 characters)', 'ai-author-for-websites' ); ?></li>
		</ul>
	</div>

	<form method="post" action="">
		<?php wp_nonce_field( 'aiauthor_yoast_nonce' ); ?>

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
								<label for="enabled"><?php esc_html_e( 'Enable Yoast SEO Integration', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch aiauthor-switch-large">
									<input type="checkbox" 
											id="enabled" 
											name="enabled" 
											value="1" 
											<?php checked( ! empty( $settings['enabled'] ) ); ?>
											<?php disabled( ! $is_yoast_active ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Enable automatic SEO optimization for AI-generated posts using Yoast SEO.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<!-- Auto-Generation Settings -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'Auto-Generation Settings', 'ai-author-for-websites' ); ?>
					</h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="auto_generate_meta"><?php esc_html_e( 'Auto-Generate on Post Creation', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch">
									<input type="checkbox" 
											id="auto_generate_meta" 
											name="auto_generate_meta" 
											value="1" 
											<?php checked( ! empty( $settings['auto_generate_meta'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Automatically generate SEO data when posts are created via Generate Post or Auto Scheduler.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="generate_focus_keyphrase"><?php esc_html_e( 'Generate Focus Keyphrase', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch">
									<input type="checkbox" 
											id="generate_focus_keyphrase" 
											name="generate_focus_keyphrase" 
											value="1" 
											<?php checked( ! empty( $settings['generate_focus_keyphrase'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Generate an AI-optimized focus keyphrase for SEO analysis.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="generate_meta_desc"><?php esc_html_e( 'Generate Meta Description', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch">
									<input type="checkbox" 
											id="generate_meta_desc" 
											name="generate_meta_desc" 
											value="1" 
											<?php checked( ! empty( $settings['generate_meta_desc'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Generate a compelling meta description for search results.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="meta_desc_length"><?php esc_html_e( 'Meta Description Length', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<input type="number" 
										id="meta_desc_length" 
										name="meta_desc_length" 
										value="<?php echo esc_attr( $settings['meta_desc_length'] ?? 160 ); ?>"
										min="120"
										max="320"
										class="small-text">
								<?php esc_html_e( 'characters', 'ai-author-for-websites' ); ?>
								<p class="description">
									<?php esc_html_e( 'Recommended: 145-160 characters. Google displays up to 160 characters in search results.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<!-- Social Meta (Optional) -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-share-alt"></span>
						<?php esc_html_e( 'Social Meta (Optional)', 'ai-author-for-websites' ); ?>
					</h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="generate_og_title"><?php esc_html_e( 'Generate OG Title', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch">
									<input type="checkbox" 
											id="generate_og_title" 
											name="generate_og_title" 
											value="1" 
											<?php checked( ! empty( $settings['generate_og_title'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Generate Open Graph title for social media sharing.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="generate_og_desc"><?php esc_html_e( 'Generate OG Description', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch">
									<input type="checkbox" 
											id="generate_og_desc" 
											name="generate_og_desc" 
											value="1" 
											<?php checked( ! empty( $settings['generate_og_desc'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Generate Open Graph description for social media sharing.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<p class="submit">
					<input type="submit" 
							name="aiauthor_yoast_save" 
							class="button button-primary button-large" 
							value="<?php esc_attr_e( 'Save Settings', 'ai-author-for-websites' ); ?>">
				</p>
			</div>

			<!-- Sidebar -->
			<div class="aiauthor-settings-sidebar">
				<!-- Test Generation -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'Test SEO Generation', 'ai-author-for-websites' ); ?>
					</h2>
					<p><?php esc_html_e( 'Test AI-powered SEO metadata generation.', 'ai-author-for-websites' ); ?></p>
					
					<div class="aiauthor-test-form">
						<input type="text" 
								id="test-title" 
								placeholder="<?php esc_attr_e( 'Enter a sample post title...', 'ai-author-for-websites' ); ?>"
								class="large-text"
								style="margin-bottom: 8px;">
						<textarea id="test-content" 
								placeholder="<?php esc_attr_e( 'Enter sample content (optional)...', 'ai-author-for-websites' ); ?>"
								class="large-text"
								rows="3"
								style="margin-bottom: 12px;"></textarea>
						<button type="button" id="test-generate-btn" class="button button-secondary">
							<span class="dashicons dashicons-admin-generic"></span>
							<?php esc_html_e( 'Generate SEO Data', 'ai-author-for-websites' ); ?>
						</button>
					</div>
					<div id="test-result" class="aiauthor-test-result" style="display: none;"></div>
				</div>

				<!-- Tips -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-lightbulb"></span>
						<?php esc_html_e( 'SEO Tips', 'ai-author-for-websites' ); ?>
					</h2>
					<ul class="aiauthor-tips-list">
						<li><?php esc_html_e( 'Focus keyphrases work best when 2-4 words long.', 'ai-author-for-websites' ); ?></li>
						<li><?php esc_html_e( 'Meta descriptions should be compelling and include a call-to-action.', 'ai-author-for-websites' ); ?></li>
						<li><?php esc_html_e( 'SEO titles should include your focus keyphrase near the beginning.', 'ai-author-for-websites' ); ?></li>
						<li><?php esc_html_e( 'Always review and refine AI-generated SEO data for best results.', 'ai-author-for-websites' ); ?></li>
					</ul>
				</div>

				<!-- Activity Log -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-list-view"></span>
						<?php esc_html_e( 'Activity Log', 'ai-author-for-websites' ); ?>
					</h2>
					<p class="description"><?php esc_html_e( 'Recent Yoast SEO integration activity.', 'ai-author-for-websites' ); ?></p>
					<?php if ( empty( $logs ) ) : ?>
						<p class="aiauthor-no-logs"><?php esc_html_e( 'No activity yet.', 'ai-author-for-websites' ); ?></p>
					<?php else : ?>
						<ul class="aiauthor-seo-log">
							<?php foreach ( $logs as $log ) : ?>
								<?php
								$icon_class = 'dashicons-info';
								$log_class  = 'info';
								switch ( $log['type'] ) {
									case 'success':
										$icon_class = 'dashicons-yes-alt';
										$log_class  = 'success';
										break;
									case 'error':
										$icon_class = 'dashicons-warning';
										$log_class  = 'error';
										break;
									case 'skipped':
										$icon_class = 'dashicons-dismiss';
										$log_class  = 'skipped';
										break;
								}
								?>
								<li class="aiauthor-log-<?php echo esc_attr( $log_class ); ?>">
									<span class="dashicons <?php echo esc_attr( $icon_class ); ?>"></span>
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
	$('#test-generate-btn').on('click', function() {
		var $btn = $(this);
		var $result = $('#test-result');
		var title = $('#test-title').val();
		var content = $('#test-content').val();

		if (!title) {
			if (typeof window.aiauthorShowToast === 'function') {
				window.aiauthorShowToast('warning', '<?php echo esc_js( __( 'Title Required', 'ai-author-for-websites' ) ); ?>', '<?php echo esc_js( __( 'Please enter a sample title.', 'ai-author-for-websites' ) ); ?>');
			}
			return;
		}

		$btn.prop('disabled', true).find('.dashicons').removeClass('dashicons-admin-generic').addClass('dashicons-update spin');
		$result.hide();

		$.ajax({
			url: '<?php echo esc_url( rest_url( 'ai-author/v1/yoast-seo/generate' ) ); ?>',
			type: 'POST',
			headers: {
				'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
			},
			data: {
				title: title,
				content: content
			},
			success: function(response) {
				$btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-admin-generic');
				
				if (response.success && response.data) {
					var html = '<div class="notice notice-success"><p><?php echo esc_js( __( 'SEO data generated successfully!', 'ai-author-for-websites' ) ); ?></p></div>';
					html += '<div class="aiauthor-seo-preview">';
					html += '<div class="aiauthor-seo-item"><strong><?php echo esc_js( __( 'Focus Keyphrase:', 'ai-author-for-websites' ) ); ?></strong><br>' + (response.data.focus_keyphrase || '-') + '</div>';
					html += '<div class="aiauthor-seo-item"><strong><?php echo esc_js( __( 'SEO Title:', 'ai-author-for-websites' ) ); ?></strong><br>' + (response.data.seo_title || '-') + '</div>';
					html += '<div class="aiauthor-seo-item"><strong><?php echo esc_js( __( 'Meta Description:', 'ai-author-for-websites' ) ); ?></strong><br>' + (response.data.meta_description || '-') + '</div>';
					html += '</div>';
					$result.html(html).show();
				} else {
					$result.html('<div class="notice notice-error"><p>' + (response.message || '<?php echo esc_js( __( 'Failed to generate SEO data.', 'ai-author-for-websites' ) ); ?>') + '</p></div>').show();
				}
			},
			error: function(xhr) {
				$btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-admin-generic');
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

.aiauthor-info-card p {
	margin: 0 0 10px;
}

.aiauthor-test-form {
	display: flex;
	flex-direction: column;
}

.aiauthor-test-result {
	margin-top: 16px;
}

.aiauthor-seo-preview {
	background: #f9f9f9;
	padding: 12px;
	border-radius: 6px;
	margin-top: 12px;
}

.aiauthor-seo-item {
	margin-bottom: 12px;
	padding-bottom: 12px;
	border-bottom: 1px solid #eee;
}

.aiauthor-seo-item:last-child {
	margin-bottom: 0;
	padding-bottom: 0;
	border-bottom: none;
}

.aiauthor-tips-list {
	margin: 0;
	padding-left: 20px;
}

.aiauthor-tips-list li {
	margin-bottom: 8px;
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

.aiauthor-no-logs {
	color: #666;
	font-style: italic;
	margin: 0;
}

.aiauthor-seo-log {
	list-style: none;
	margin: 12px 0 0;
	padding: 0;
	max-height: 300px;
	overflow-y: auto;
}

.aiauthor-seo-log li {
	padding: 8px 0;
	border-bottom: 1px solid #eee;
	display: flex;
	align-items: flex-start;
	gap: 6px;
	font-size: 12px;
	line-height: 1.4;
}

.aiauthor-seo-log li:last-child {
	border-bottom: none;
}

.aiauthor-seo-log .dashicons {
	font-size: 14px;
	width: 14px;
	height: 14px;
	flex-shrink: 0;
	margin-top: 2px;
}

.aiauthor-log-success .dashicons {
	color: #46b450;
}

.aiauthor-log-error .dashicons {
	color: #dc3232;
}

.aiauthor-log-skipped .dashicons {
	color: #999;
}

.aiauthor-log-info .dashicons {
	color: #0073aa;
}

.aiauthor-log-message {
	flex: 1;
	word-break: break-word;
}

.aiauthor-log-date {
	font-size: 10px;
	color: #999;
	flex-shrink: 0;
}

@media (max-width: 1200px) {
	.aiauthor-settings-grid {
		grid-template-columns: 1fr;
	}
}
</style>

