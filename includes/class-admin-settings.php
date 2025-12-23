<?php
/**
 * Admin Settings Class
 *
 * @package AI_Author_For_Websites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AIAUTHOR_Admin_Settings
 *
 * Handles the admin settings page for the AI Author plugin.
 */
class AIAUTHOR_Admin_Settings {

	/**
	 * Current active tab.
	 *
	 * @var string
	 */
	private $active_tab = 'general';

	/**
	 * Render the settings page.
	 */
	public function render() {
		// Handle form submission.
		if ( isset( $_POST['aiauthor_save_settings'] ) && check_admin_referer( 'aiauthor_settings_nonce' ) ) {
			$this->save_settings();
		}

		// Get current tab from URL parameter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just reading tab parameter for display.
		$this->active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

		$settings = AI_Author_For_Websites::get_settings();
		?>
		<div class="wrap aiauthor-admin">
			<h1><?php esc_html_e( 'AI Author Settings', 'ai-author-for-websites' ); ?></h1>

			<?php $this->render_tabs(); ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'aiauthor_settings_nonce' ); ?>
				<input type="hidden" name="aiauthor_active_tab" value="<?php echo esc_attr( $this->active_tab ); ?>">

				<div class="aiauthor-tab-content">
					<?php
					switch ( $this->active_tab ) {
						case 'ai-provider':
							$this->render_ai_provider_tab( $settings );
							break;
						case 'content':
							$this->render_content_tab( $settings );
							break;
						default:
							$this->render_general_tab( $settings );
							break;
					}
					?>
				</div>

				<p class="submit">
					<input type="submit" 
							name="aiauthor_save_settings" 
							class="button button-primary" 
							value="<?php esc_attr_e( 'Save Settings', 'ai-author-for-websites' ); ?>">
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the tab navigation.
	 */
	private function render_tabs() {
		$tabs = [
			'general'     => [
				'label' => __( 'General', 'ai-author-for-websites' ),
				'icon'  => 'dashicons-admin-settings',
			],
			'ai-provider' => [
				'label' => __( 'AI Provider', 'ai-author-for-websites' ),
				'icon'  => 'dashicons-cloud',
			],
			'content'     => [
				'label' => __( 'Content Settings', 'ai-author-for-websites' ),
				'icon'  => 'dashicons-editor-paragraph',
			],
		];

		$base_url = admin_url( 'admin.php?page=ai-author-settings' );
		?>
		<nav class="aiauthor-tabs-nav">
			<?php foreach ( $tabs as $tab_id => $tab ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'tab', $tab_id, $base_url ) ); ?>" 
					class="aiauthor-tab-link <?php echo $this->active_tab === $tab_id ? 'active' : ''; ?>">
					<span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>"></span>
					<?php echo esc_html( $tab['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * Render the General tab content.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_general_tab( $settings ) {
		?>
		<div class="aiauthor-card">
			<h2><?php esc_html_e( 'Plugin Status', 'ai-author-for-websites' ); ?></h2>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="enabled"><?php esc_html_e( 'Enable AI Author', 'ai-author-for-websites' ); ?></label>
					</th>
					<td>
						<label class="aiauthor-switch">
							<input type="checkbox" 
									id="enabled" 
									name="enabled" 
									value="1" 
									<?php checked( ! empty( $settings['enabled'] ) ); ?>>
							<span class="slider"></span>
						</label>
						<p class="description">
							<?php esc_html_e( 'Enable or disable the AI Author functionality', 'ai-author-for-websites' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<div class="aiauthor-card">
			<h2><?php esc_html_e( 'Quick Start Guide', 'ai-author-for-websites' ); ?></h2>
			<ol class="aiauthor-quick-start">
				<li>
					<strong><?php esc_html_e( 'Configure AI Provider', 'ai-author-for-websites' ); ?></strong>
					<p><?php esc_html_e( 'Go to the AI Provider tab and enter your API key.', 'ai-author-for-websites' ); ?></p>
				</li>
				<li>
					<strong><?php esc_html_e( 'Build Knowledge Base', 'ai-author-for-websites' ); ?></strong>
					<p><?php esc_html_e( 'Add content to train the AI by visiting the Knowledge Base page.', 'ai-author-for-websites' ); ?></p>
				</li>
				<li>
					<strong><?php esc_html_e( 'Generate Content', 'ai-author-for-websites' ); ?></strong>
					<p><?php esc_html_e( 'Go to Generate Post and start creating AI-powered blog posts!', 'ai-author-for-websites' ); ?></p>
				</li>
			</ol>
		</div>

		<div class="aiauthor-card">
			<h2><?php esc_html_e( 'Knowledge Base Status', 'ai-author-for-websites' ); ?></h2>
			<?php
			$knowledge_manager = new AIAUTHOR_Knowledge_Manager();
			$kb                = $knowledge_manager->get_knowledge_base();
			$summary           = $kb->getSummary();
			?>
			<div class="aiauthor-kb-status">
				<div class="aiauthor-stat">
					<span class="aiauthor-stat-number"><?php echo esc_html( $summary['count'] ); ?></span>
					<span class="aiauthor-stat-label"><?php esc_html_e( 'Documents', 'ai-author-for-websites' ); ?></span>
				</div>
				<div class="aiauthor-stat">
					<span class="aiauthor-stat-number"><?php echo esc_html( number_format( $summary['totalChars'] ) ); ?></span>
					<span class="aiauthor-stat-label"><?php esc_html_e( 'Characters', 'ai-author-for-websites' ); ?></span>
				</div>
			</div>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-author-knowledge' ) ); ?>" class="button">
					<span class="dashicons dashicons-book-alt" style="margin-top: 3px;"></span>
					<?php esc_html_e( 'Manage Knowledge Base', 'ai-author-for-websites' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the AI Provider tab content.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_ai_provider_tab( $settings ) {
		?>
		<div class="aiauthor-card">
			<h2><?php esc_html_e( 'AI Provider Configuration', 'ai-author-for-websites' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Configure your AI provider to power content generation.', 'ai-author-for-websites' ); ?>
			</p>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="provider"><?php esc_html_e( 'AI Provider', 'ai-author-for-websites' ); ?></label>
					</th>
					<td>
						<select id="provider" name="provider" class="regular-text">
							<option value="groq" <?php selected( $settings['provider'] ?? 'groq', 'groq' ); ?>>
								<?php esc_html_e( 'Groq (Llama Models)', 'ai-author-for-websites' ); ?>
							</option>
							<option value="gemini" <?php selected( $settings['provider'] ?? '', 'gemini' ); ?>>
								<?php esc_html_e( 'Google Gemini', 'ai-author-for-websites' ); ?>
							</option>
							<option value="meta" <?php selected( $settings['provider'] ?? '', 'meta' ); ?>>
								<?php esc_html_e( 'Meta Llama (Direct)', 'ai-author-for-websites' ); ?>
							</option>
						</select>
						<p class="description">
							<?php esc_html_e( 'Select your AI provider. Groq is recommended for fast inference.', 'ai-author-for-websites' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="api_key"><?php esc_html_e( 'API Key', 'ai-author-for-websites' ); ?></label>
					</th>
					<td>
						<div class="aiauthor-api-key-wrapper">
							<input type="password" 
									id="api_key" 
									name="api_key" 
									value="<?php echo esc_attr( $settings['api_key'] ?? '' ); ?>" 
									class="large-text" 
									autocomplete="off">
							<button type="button" class="button aiauthor-toggle-password">
								<span class="dashicons dashicons-visibility"></span>
							</button>
						</div>
						<p class="description" id="api-key-help">
							<?php esc_html_e( 'Get your free API key from', 'ai-author-for-websites' ); ?>
							<a href="https://console.groq.com" target="_blank" rel="noopener">console.groq.com</a>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="model"><?php esc_html_e( 'Model', 'ai-author-for-websites' ); ?></label>
					</th>
					<td>
						<select id="model" name="model" class="regular-text">
							<optgroup label="<?php esc_attr_e( 'Groq Models', 'ai-author-for-websites' ); ?>" class="groq-models">
								<option value="llama-3.3-70b-versatile" <?php selected( $settings['model'] ?? '', 'llama-3.3-70b-versatile' ); ?>>
									Llama 3.3 70B Versatile
								</option>
								<option value="llama-3.1-70b-versatile" <?php selected( $settings['model'] ?? '', 'llama-3.1-70b-versatile' ); ?>>
									Llama 3.1 70B Versatile
								</option>
								<option value="llama-3.1-8b-instant" <?php selected( $settings['model'] ?? '', 'llama-3.1-8b-instant' ); ?>>
									Llama 3.1 8B Instant
								</option>
								<option value="mixtral-8x7b-32768" <?php selected( $settings['model'] ?? '', 'mixtral-8x7b-32768' ); ?>>
									Mixtral 8x7B
								</option>
							</optgroup>
							<optgroup label="<?php esc_attr_e( 'Gemini Models', 'ai-author-for-websites' ); ?>" class="gemini-models">
								<option value="gemini-2.0-flash" <?php selected( $settings['model'] ?? '', 'gemini-2.0-flash' ); ?>>
									Gemini 2.0 Flash
								</option>
								<option value="gemini-2.5-pro" <?php selected( $settings['model'] ?? '', 'gemini-2.5-pro' ); ?>>
									Gemini 2.5 Pro
								</option>
							</optgroup>
						</select>
					</td>
				</tr>
			</table>

			<div class="aiauthor-test-connection">
				<button type="button" id="aiauthor-test-api" class="button">
					<span class="dashicons dashicons-yes-alt" style="margin-top: 3px;"></span>
					<?php esc_html_e( 'Test Connection', 'ai-author-for-websites' ); ?>
				</button>
				<span id="aiauthor-test-result" class="aiauthor-test-result"></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Content Settings tab.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_content_tab( $settings ) {
		?>
		<div class="aiauthor-card">
			<h2><?php esc_html_e( 'Content Generation Settings', 'ai-author-for-websites' ); ?></h2>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="system_instruction"><?php esc_html_e( 'System Instruction', 'ai-author-for-websites' ); ?></label>
					</th>
					<td>
						<textarea id="system_instruction" 
									name="system_instruction" 
									rows="5" 
									class="large-text"><?php echo esc_textarea( $settings['system_instruction'] ?? '' ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Instructions that define how the AI should write blog posts. Be specific about tone, style, and structure.', 'ai-author-for-websites' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="default_word_count"><?php esc_html_e( 'Default Word Count', 'ai-author-for-websites' ); ?></label>
					</th>
					<td>
						<input type="number" 
								id="default_word_count" 
								name="default_word_count" 
								value="<?php echo esc_attr( $settings['default_word_count'] ?? 1000 ); ?>" 
								min="100" 
								max="5000" 
								step="100"
								class="small-text">
						<p class="description">
							<?php esc_html_e( 'Default target word count for generated posts.', 'ai-author-for-websites' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<div class="aiauthor-card">
			<h2><?php esc_html_e( 'Content Templates', 'ai-author-for-websites' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'These templates help structure the generated content. More templates coming soon!', 'ai-author-for-websites' ); ?>
			</p>
			
			<div class="aiauthor-templates-grid">
				<div class="aiauthor-template-card">
					<h3><?php esc_html_e( 'Blog Post', 'ai-author-for-websites' ); ?></h3>
					<p><?php esc_html_e( 'Standard blog article with introduction, body, and conclusion.', 'ai-author-for-websites' ); ?></p>
					<span class="aiauthor-badge aiauthor-badge-active"><?php esc_html_e( 'Default', 'ai-author-for-websites' ); ?></span>
				</div>
				<div class="aiauthor-template-card aiauthor-template-coming-soon">
					<h3><?php esc_html_e( 'How-To Guide', 'ai-author-for-websites' ); ?></h3>
					<p><?php esc_html_e( 'Step-by-step tutorial format.', 'ai-author-for-websites' ); ?></p>
					<span class="aiauthor-badge"><?php esc_html_e( 'Coming Soon', 'ai-author-for-websites' ); ?></span>
				</div>
				<div class="aiauthor-template-card aiauthor-template-coming-soon">
					<h3><?php esc_html_e( 'Listicle', 'ai-author-for-websites' ); ?></h3>
					<p><?php esc_html_e( 'Numbered list format article.', 'ai-author-for-websites' ); ?></p>
					<span class="aiauthor-badge"><?php esc_html_e( 'Coming Soon', 'ai-author-for-websites' ); ?></span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save settings.
	 */
	private function save_settings() {
		// Nonce already verified in render() before calling this method.
		$settings = AI_Author_For_Websites::get_settings();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in render().
		$active_tab = isset( $_POST['aiauthor_active_tab'] ) ? sanitize_key( $_POST['aiauthor_active_tab'] ) : 'general';

		switch ( $active_tab ) {
			case 'general':
				$settings['enabled'] = ! empty( $_POST['enabled'] );
				break;

			case 'ai-provider':
				if ( isset( $_POST['api_key'] ) ) {
					$settings['api_key'] = sanitize_text_field( wp_unslash( $_POST['api_key'] ) );
				}
				if ( isset( $_POST['provider'] ) ) {
					$settings['provider'] = sanitize_text_field( wp_unslash( $_POST['provider'] ) );
				}
				if ( isset( $_POST['model'] ) ) {
					$settings['model'] = sanitize_text_field( wp_unslash( $_POST['model'] ) );
				}
				break;

			case 'content':
				if ( isset( $_POST['system_instruction'] ) ) {
					$settings['system_instruction'] = sanitize_textarea_field( wp_unslash( $_POST['system_instruction'] ) );
				}
				if ( isset( $_POST['default_word_count'] ) ) {
					$settings['default_word_count'] = absint( $_POST['default_word_count'] );
				}
				break;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		AI_Author_For_Websites::update_settings( $settings );

		add_settings_error( 'aiauthor_messages', 'aiauthor_message', __( 'Settings saved.', 'ai-author-for-websites' ), 'updated' );
		settings_errors( 'aiauthor_messages' );
	}
}

