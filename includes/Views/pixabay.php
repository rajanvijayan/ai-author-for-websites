<?php
/**
 * Pixabay Settings View
 *
 * @package AI_Author_For_Websites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$integrations_manager = \AIAuthor\Integrations\Manager::get_instance();
$pixabay              = $integrations_manager->get( 'pixabay' );
$settings             = $pixabay->get_settings();

// Handle form submission.
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- All inputs are sanitized appropriately.
if ( isset( $_POST['aiauthor_pixabay_save'] ) && check_admin_referer( 'aiauthor_pixabay_nonce' ) ) {
	$aiauthor_new_settings = array(
		'enabled'             => ! empty( $_POST['enabled'] ),
		'api_key'             => sanitize_text_field( isset( $_POST['api_key'] ) ? wp_unslash( $_POST['api_key'] ) : '' ),
		'image_type'          => sanitize_key( isset( $_POST['image_type'] ) ? $_POST['image_type'] : 'photo' ),
		'orientation'         => sanitize_key( isset( $_POST['orientation'] ) ? $_POST['orientation'] : 'horizontal' ),
		'min_width'           => absint( isset( $_POST['min_width'] ) ? $_POST['min_width'] : 1200 ),
		'min_height'          => absint( isset( $_POST['min_height'] ) ? $_POST['min_height'] : 630 ),
		'safesearch'          => ! empty( $_POST['safesearch'] ),
		'auto_set_featured'   => ! empty( $_POST['auto_set_featured'] ),
		'attribution_in_post' => ! empty( $_POST['attribution_in_post'] ),
	);

	$pixabay->update_settings( $aiauthor_new_settings );

	// Refresh settings.
	$settings = $pixabay->get_settings();
	$saved    = true;
}
// phpcs:enable

// Get options.
$image_types  = $pixabay->get_image_type_options();
$orientations = $pixabay->get_orientation_options();
$logs         = $pixabay->get_logs( 15 );
?>
<div class="wrap aiauthor-admin aiauthor-pixabay">
	<h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-author-integrations' ) ); ?>" class="aiauthor-back-link">
			<span class="dashicons dashicons-arrow-left-alt"></span>
		</a>
		<span class="dashicons dashicons-format-image" style="font-size: 30px; margin-right: 8px;"></span>
		<?php esc_html_e( 'Pixabay Integration', 'ai-author-for-websites' ); ?>
	</h1>

	<?php if ( ! empty( $saved ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved successfully!', 'ai-author-for-websites' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="aiauthor-info-card">
		<h3>
			<span class="dashicons dashicons-info"></span>
			<?php esc_html_e( 'About Pixabay', 'ai-author-for-websites' ); ?>
		</h3>
		<p>
			<?php esc_html_e( 'Pixabay provides over 2.7 million free high-quality stock images, videos, and music. All content is released under the Pixabay License, making it safe to use without attribution (though attribution is appreciated).', 'ai-author-for-websites' ); ?>
		</p>
		<p>
			<a href="https://pixabay.com/api/docs/" target="_blank" rel="noopener">
				<?php esc_html_e( 'Get your free API key from Pixabay', 'ai-author-for-websites' ); ?>
				<span class="dashicons dashicons-external"></span>
			</a>
		</p>
	</div>

	<form method="post" action="">
		<?php wp_nonce_field( 'aiauthor_pixabay_nonce' ); ?>

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
								<label for="enabled"><?php esc_html_e( 'Enable Pixabay', 'ai-author-for-websites' ); ?></label>
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
									<?php esc_html_e( 'Enable automatic image fetching from Pixabay for generated posts.', 'ai-author-for-websites' ); ?>
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
								<label for="api_key"><?php esc_html_e( 'Pixabay API Key', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<input type="password" 
										id="api_key" 
										name="api_key" 
										value="<?php echo esc_attr( $settings['api_key'] ); ?>"
										class="regular-text">
								<p class="description">
									<?php esc_html_e( 'Enter your Pixabay API key. Get one free at pixabay.com/api/docs/', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<!-- Image Settings -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-images-alt2"></span>
						<?php esc_html_e( 'Image Settings', 'ai-author-for-websites' ); ?>
					</h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="image_type"><?php esc_html_e( 'Image Type', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<select id="image_type" name="image_type" class="regular-text">
									<?php foreach ( $image_types as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['image_type'] ?? 'photo', $key ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="orientation"><?php esc_html_e( 'Orientation', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<select id="orientation" name="orientation" class="regular-text">
									<?php foreach ( $orientations as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['orientation'] ?? 'horizontal', $key ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">
									<?php esc_html_e( 'Horizontal images work best for featured images in most themes.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="min_width"><?php esc_html_e( 'Minimum Dimensions', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<input type="number" 
										id="min_width" 
										name="min_width" 
										value="<?php echo esc_attr( $settings['min_width'] ?? 1200 ); ?>"
										min="100"
										max="5000"
										class="small-text"> 
								<?php esc_html_e( 'x', 'ai-author-for-websites' ); ?>
								<input type="number" 
										id="min_height" 
										name="min_height" 
										value="<?php echo esc_attr( $settings['min_height'] ?? 630 ); ?>"
										min="100"
										max="5000"
										class="small-text">
								<?php esc_html_e( 'pixels', 'ai-author-for-websites' ); ?>
								<p class="description">
									<?php esc_html_e( 'Minimum width and height for images. 1200x630 is optimal for social sharing.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="safesearch"><?php esc_html_e( 'Safe Search', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch">
									<input type="checkbox" 
											id="safesearch" 
											name="safesearch" 
											value="1" 
											<?php checked( ! empty( $settings['safesearch'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Filter out images that may not be suitable for all audiences.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<!-- Behavior Settings -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'Behavior', 'ai-author-for-websites' ); ?>
					</h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="auto_set_featured"><?php esc_html_e( 'Auto-Set Featured Image', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch">
									<input type="checkbox" 
											id="auto_set_featured" 
											name="auto_set_featured" 
											value="1" 
											<?php checked( ! empty( $settings['auto_set_featured'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Automatically search and set a featured image when posts are generated by Auto Scheduler.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="attribution_in_post"><?php esc_html_e( 'Add Attribution', 'ai-author-for-websites' ); ?></label>
							</th>
							<td>
								<label class="aiauthor-switch">
									<input type="checkbox" 
											id="attribution_in_post" 
											name="attribution_in_post" 
											value="1" 
											<?php checked( ! empty( $settings['attribution_in_post'] ) ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description">
									<?php esc_html_e( 'Add a Pixabay attribution line at the end of generated posts.', 'ai-author-for-websites' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<p class="submit">
					<input type="submit" 
							name="aiauthor_pixabay_save" 
							class="button button-primary button-large" 
							value="<?php esc_attr_e( 'Save Settings', 'ai-author-for-websites' ); ?>">
				</p>
			</div>

			<!-- Sidebar -->
			<div class="aiauthor-settings-sidebar">
				<!-- Test Search -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-search"></span>
						<?php esc_html_e( 'Test Search', 'ai-author-for-websites' ); ?>
					</h2>
					<p><?php esc_html_e( 'Test your API key by searching for images.', 'ai-author-for-websites' ); ?></p>
					
					<div class="aiauthor-test-form">
						<input type="text" 
								id="test-query" 
								placeholder="<?php esc_attr_e( 'Enter search term...', 'ai-author-for-websites' ); ?>"
								class="large-text">
						<button type="button" id="test-search-btn" class="button button-secondary" <?php disabled( empty( $settings['api_key'] ) ); ?>>
							<span class="dashicons dashicons-search"></span>
							<?php esc_html_e( 'Search Images', 'ai-author-for-websites' ); ?>
						</button>
					</div>
					<div id="test-result" class="aiauthor-test-result" style="display: none;"></div>
				</div>

				<!-- Usage Tips -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-lightbulb"></span>
						<?php esc_html_e( 'Tips', 'ai-author-for-websites' ); ?>
					</h2>
					<ul class="aiauthor-tips-list">
						<li><?php esc_html_e( 'The free API allows 100 requests/minute.', 'ai-author-for-websites' ); ?></li>
						<li><?php esc_html_e( 'Images will be searched using your post title.', 'ai-author-for-websites' ); ?></li>
						<li><?php esc_html_e( 'You can manually choose images in the Generate Post page.', 'ai-author-for-websites' ); ?></li>
					</ul>
				</div>

				<!-- Activity Log -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-list-view"></span>
						<?php esc_html_e( 'Activity Log', 'ai-author-for-websites' ); ?>
					</h2>
					<p class="description"><?php esc_html_e( 'Recent Pixabay integration activity (for debugging).', 'ai-author-for-websites' ); ?></p>
					<?php if ( empty( $logs ) ) : ?>
						<p class="aiauthor-no-logs"><?php esc_html_e( 'No activity yet. Generate a post to see logs.', 'ai-author-for-websites' ); ?></p>
					<?php else : ?>
						<ul class="aiauthor-pixabay-log">
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
									case 'warning':
										$icon_class = 'dashicons-flag';
										$log_class  = 'warning';
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
	// Test search.
	$('#test-search-btn').on('click', function() {
		var $btn = $(this);
		var $result = $('#test-result');
		var query = $('#test-query').val();

		if (!query) {
			if (typeof window.aiauthorShowToast === 'function') {
				window.aiauthorShowToast('warning', '<?php echo esc_js( __( 'Search Required', 'ai-author-for-websites' ) ); ?>', '<?php echo esc_js( __( 'Please enter a search term.', 'ai-author-for-websites' ) ); ?>');
			}
			return;
		}

		$btn.prop('disabled', true).find('.dashicons').removeClass('dashicons-search').addClass('dashicons-update spin');
		$result.hide();

		$.ajax({
			url: '<?php echo esc_url( rest_url( 'ai-author/v1/pixabay/search' ) ); ?>',
			type: 'POST',
			headers: {
				'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
			},
			data: {
				query: query,
				page: 1
			},
			success: function(response) {
				$btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-search');
				
				if (response.success && response.data.images) {
					var html = '<div class="notice notice-success"><p>' + 
						'<?php echo esc_js( __( 'Found', 'ai-author-for-websites' ) ); ?> ' + response.data.total + ' <?php echo esc_js( __( 'images', 'ai-author-for-websites' ) ); ?></p></div>';
					
					html += '<div class="aiauthor-image-grid">';
					response.data.images.slice(0, 6).forEach(function(img) {
						html += '<div class="aiauthor-image-item">';
						html += '<img src="' + img.preview_url + '" alt="' + img.tags + '">';
						html += '</div>';
					});
					html += '</div>';
					
					$result.html(html).show();
				} else {
					$result.html('<div class="notice notice-error"><p>' + (response.message || '<?php echo esc_js( __( 'No images found.', 'ai-author-for-websites' ) ); ?>') + '</p></div>').show();
				}
			},
			error: function(xhr) {
				$btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-search');
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

.aiauthor-info-card p:last-child {
	margin-bottom: 0;
}

.aiauthor-info-card a {
	display: inline-flex;
	align-items: center;
	gap: 4px;
}

.aiauthor-test-form {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.aiauthor-test-result {
	margin-top: 16px;
}

.aiauthor-image-grid {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 8px;
	margin-top: 12px;
}

.aiauthor-image-item img {
	width: 100%;
	height: 80px;
	object-fit: cover;
	border-radius: 4px;
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

.aiauthor-pixabay-log {
	list-style: none;
	margin: 12px 0 0;
	padding: 0;
	max-height: 300px;
	overflow-y: auto;
}

.aiauthor-pixabay-log li {
	padding: 8px 0;
	border-bottom: 1px solid #eee;
	display: flex;
	align-items: flex-start;
	gap: 6px;
	font-size: 12px;
	line-height: 1.4;
}

.aiauthor-pixabay-log li:last-child {
	border-bottom: none;
}

.aiauthor-pixabay-log .dashicons {
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

.aiauthor-log-warning .dashicons {
	color: #f0b849;
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

