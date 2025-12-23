<?php
/**
 * Integrations List View
 *
 * @package AI_Author_For_Websites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$integrations_manager = \AIAuthor\Integrations\Manager::get_instance();
$integrations         = $integrations_manager->get_all();
$categories           = $integrations_manager->get_categories();

// Handle toggle action.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( isset( $_GET['action'], $_GET['integration'], $_GET['_wpnonce'] ) ) {
	$action      = sanitize_key( $_GET['action'] );
	$integration = sanitize_key( $_GET['integration'] );
	$nonce       = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );

	if ( wp_verify_nonce( $nonce, 'aiauthor_integration_toggle' ) ) {
		if ( 'enable' === $action ) {
			$integrations_manager->enable_integration( $integration );
		} elseif ( 'disable' === $action ) {
			$integrations_manager->disable_integration( $integration );
		}
		// Redirect to remove query params.
		wp_safe_redirect( admin_url( 'admin.php?page=ai-author-integrations&updated=1' ) );
		exit;
	}
}
?>
<div class="wrap aiauthor-admin aiauthor-integrations">
	<h1>
		<span class="dashicons dashicons-admin-plugins" style="font-size: 30px; margin-right: 8px;"></span>
		<?php esc_html_e( 'Integrations', 'ai-author-for-websites' ); ?>
	</h1>

	<p class="aiauthor-page-description">
		<?php esc_html_e( 'Extend AI Author with powerful integrations. Enable built-in features or install additional integration plugins.', 'ai-author-for-websites' ); ?>
	</p>

	<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Integration settings updated.', 'ai-author-for-websites' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Integrations Grid -->
	<div class="aiauthor-integrations-grid">
		<?php foreach ( $integrations as $integration ) : ?>
			<?php
			$is_enabled    = $integration->is_enabled();
			$toggle_action = $is_enabled ? 'disable' : 'enable';
			$toggle_url    = wp_nonce_url(
				add_query_arg(
					array(
						'action'      => $toggle_action,
						'integration' => $integration->get_id(),
					),
					admin_url( 'admin.php?page=ai-author-integrations' )
				),
				'aiauthor_integration_toggle'
			);
			?>
			<div class="aiauthor-integration-card <?php echo $is_enabled ? 'is-enabled' : ''; ?>">
				<div class="aiauthor-integration-header">
					<div class="aiauthor-integration-icon">
						<span class="dashicons <?php echo esc_attr( $integration->get_icon() ); ?>"></span>
					</div>
					<div class="aiauthor-integration-meta">
						<h3><?php echo esc_html( $integration->get_name() ); ?></h3>
						<span class="aiauthor-integration-version">
							v<?php echo esc_html( $integration->get_version() ); ?>
						</span>
						<?php if ( $integration->is_builtin() ) : ?>
							<span class="aiauthor-badge aiauthor-badge-builtin">
								<?php esc_html_e( 'Built-in', 'ai-author-for-websites' ); ?>
							</span>
						<?php endif; ?>
					</div>
					<div class="aiauthor-integration-toggle">
						<label class="aiauthor-switch">
							<input type="checkbox" 
									<?php checked( $is_enabled ); ?>
									onchange="window.location.href='<?php echo esc_url( $toggle_url ); ?>'">
							<span class="slider"></span>
						</label>
					</div>
				</div>

				<div class="aiauthor-integration-body">
					<p><?php echo esc_html( $integration->get_description() ); ?></p>
					
					<div class="aiauthor-integration-details">
						<span class="aiauthor-integration-author">
							<span class="dashicons dashicons-admin-users"></span>
							<?php echo esc_html( $integration->get_author() ); ?>
						</span>
						<span class="aiauthor-integration-category">
							<?php
							$cat_key = $integration->get_category();
							$cat     = $categories[ $cat_key ] ?? $categories['other'];
							?>
							<span class="dashicons <?php echo esc_attr( $cat['icon'] ); ?>"></span>
							<?php echo esc_html( $cat['label'] ); ?>
						</span>
					</div>
				</div>

				<div class="aiauthor-integration-footer">
					<?php if ( $integration->has_settings_page() ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-author-integrations&integration=' . $integration->get_id() ) ); ?>" 
							class="button <?php echo $is_enabled ? 'button-primary' : ''; ?>">
							<span class="dashicons dashicons-admin-generic"></span>
							<?php esc_html_e( 'Configure', 'ai-author-for-websites' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>

		<!-- Coming Soon Card -->
		<div class="aiauthor-integration-card aiauthor-integration-coming-soon">
			<div class="aiauthor-integration-header">
				<div class="aiauthor-integration-icon">
					<span class="dashicons dashicons-plus-alt2"></span>
				</div>
				<div class="aiauthor-integration-meta">
					<h3><?php esc_html_e( 'More Coming Soon', 'ai-author-for-websites' ); ?></h3>
				</div>
			</div>
			<div class="aiauthor-integration-body">
				<p><?php esc_html_e( 'We\'re working on more integrations including social media auto-posting, SEO optimization, and analytics tracking.', 'ai-author-for-websites' ); ?></p>
			</div>
		</div>
	</div>

	<!-- Developer Section -->
	<div class="aiauthor-card aiauthor-developer-section">
		<h2>
			<span class="dashicons dashicons-code-standards"></span>
			<?php esc_html_e( 'Build Your Own Integration', 'ai-author-for-websites' ); ?>
		</h2>
		<p>
			<?php esc_html_e( 'Developers can create custom integrations as standalone plugins. Use the AI Author Integrations API to extend functionality.', 'ai-author-for-websites' ); ?>
		</p>
		<div class="aiauthor-code-example">
			<pre><code>&lt;?php
/**
 * Plugin Name: My AI Author Integration
 */

add_action( 'aiauthor_register_integrations', function( $manager ) {
	require_once __DIR__ . '/class-my-integration.php';
	$manager->register( new MyIntegration() );
});</code></pre>
		</div>
		<p>
			<a href="https://github.com/rajanvijayan/ai-author-for-websites#integrations" target="_blank" class="button">
				<span class="dashicons dashicons-book"></span>
				<?php esc_html_e( 'View Documentation', 'ai-author-for-websites' ); ?>
			</a>
		</p>
	</div>
</div>

