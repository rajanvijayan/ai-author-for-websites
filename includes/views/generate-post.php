<?php
/**
 * Generate Post View
 *
 * @package AI_Author_For_Websites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings          = AI_Author_For_Websites::get_settings();
$knowledge_manager = new AIAUTHOR_Knowledge_Manager();
$kb                = $knowledge_manager->get_knowledge_base();
$summary           = $kb->getSummary();
$has_api_key       = ! empty( $settings['api_key'] );
$has_knowledge     = $summary['count'] > 0;
?>
<div class="wrap aiauthor-admin">
	<h1><?php esc_html_e( 'Generate Blog Post', 'ai-author-for-websites' ); ?></h1>

	<?php if ( ! $has_api_key ) : ?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'API Key Required', 'ai-author-for-websites' ); ?></strong> - 
				<?php esc_html_e( 'Please configure your API key in', 'ai-author-for-websites' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-author-settings&tab=ai-provider' ) ); ?>">
					<?php esc_html_e( 'Settings', 'ai-author-for-websites' ); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( ! $has_knowledge ) : ?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e( 'Knowledge Base Empty', 'ai-author-for-websites' ); ?></strong> - 
				<?php esc_html_e( 'Add content to your knowledge base for better AI-generated posts.', 'ai-author-for-websites' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-author-knowledge' ) ); ?>">
					<?php esc_html_e( 'Add Knowledge', 'ai-author-for-websites' ); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>

	<div class="aiauthor-generate-grid">
		<!-- Input Form -->
		<div class="aiauthor-card aiauthor-generate-form">
			<h2>
				<span class="dashicons dashicons-edit-page" style="color: #0073aa;"></span>
				<?php esc_html_e( 'Post Settings', 'ai-author-for-websites' ); ?>
			</h2>

			<div class="aiauthor-form-row">
				<label for="post-topic"><?php esc_html_e( 'Topic / Title', 'ai-author-for-websites' ); ?></label>
				<input type="text" 
						id="post-topic" 
						class="large-text" 
						placeholder="<?php esc_attr_e( 'e.g., 10 Tips for Better Productivity', 'ai-author-for-websites' ); ?>">
				<p class="description">
					<?php esc_html_e( 'Enter a topic or working title for your blog post.', 'ai-author-for-websites' ); ?>
				</p>
			</div>

			<div class="aiauthor-form-row">
				<label for="post-word-count"><?php esc_html_e( 'Word Count', 'ai-author-for-websites' ); ?></label>
				<input type="number" 
						id="post-word-count" 
						value="<?php echo esc_attr( $settings['default_word_count'] ?? 1000 ); ?>" 
						min="100" 
						max="5000" 
						step="100"
						class="small-text">
				<p class="description">
					<?php esc_html_e( 'Target word count for the generated post.', 'ai-author-for-websites' ); ?>
				</p>
			</div>

			<div class="aiauthor-form-row">
				<label for="post-tone"><?php esc_html_e( 'Writing Tone', 'ai-author-for-websites' ); ?></label>
				<select id="post-tone" class="regular-text">
					<option value="professional"><?php esc_html_e( 'Professional', 'ai-author-for-websites' ); ?></option>
					<option value="conversational"><?php esc_html_e( 'Conversational', 'ai-author-for-websites' ); ?></option>
					<option value="friendly"><?php esc_html_e( 'Friendly & Casual', 'ai-author-for-websites' ); ?></option>
					<option value="authoritative"><?php esc_html_e( 'Authoritative & Expert', 'ai-author-for-websites' ); ?></option>
					<option value="educational"><?php esc_html_e( 'Educational', 'ai-author-for-websites' ); ?></option>
					<option value="humorous"><?php esc_html_e( 'Humorous', 'ai-author-for-websites' ); ?></option>
				</select>
			</div>

			<div class="aiauthor-form-actions">
				<button type="button" 
						id="generate-post-btn" 
						class="button button-primary button-hero"
						<?php disabled( ! $has_api_key ); ?>>
					<span class="dashicons dashicons-admin-generic" style="margin-top: 5px;"></span>
					<?php esc_html_e( 'Generate Post', 'ai-author-for-websites' ); ?>
				</button>
			</div>

			<!-- Knowledge Base Status -->
			<div class="aiauthor-kb-status-mini">
				<span class="dashicons dashicons-book-alt"></span>
				<span>
					<?php
					printf(
						/* translators: %d: Number of documents in knowledge base */
						esc_html__( 'Knowledge Base: %d documents', 'ai-author-for-websites' ),
						intval( $summary['count'] )
					);
					?>
				</span>
			</div>
		</div>

		<!-- Preview/Output -->
		<div class="aiauthor-card aiauthor-generate-output">
			<h2>
				<span class="dashicons dashicons-visibility" style="color: #23a455;"></span>
				<?php esc_html_e( 'Generated Content', 'ai-author-for-websites' ); ?>
			</h2>

			<!-- Loading State -->
			<div id="generate-loading" class="aiauthor-loading" style="display: none;">
				<span class="spinner is-active"></span>
				<p><?php esc_html_e( 'Generating your blog post... This may take a minute.', 'ai-author-for-websites' ); ?></p>
			</div>

			<!-- Empty State -->
			<div id="generate-empty" class="aiauthor-empty-state">
				<span class="dashicons dashicons-welcome-write-blog" style="font-size: 64px; color: #ddd;"></span>
				<p><?php esc_html_e( 'Enter a topic and click "Generate Post" to create AI-powered content.', 'ai-author-for-websites' ); ?></p>
			</div>

			<!-- Result -->
			<div id="generate-result" style="display: none;">
				<div class="aiauthor-result-header">
					<input type="text" id="result-title" class="aiauthor-result-title" placeholder="<?php esc_attr_e( 'Post Title', 'ai-author-for-websites' ); ?>">
				</div>
				<div id="result-content" class="aiauthor-result-content"></div>
				<div class="aiauthor-result-actions">
					<button type="button" id="save-draft-btn" class="button button-primary">
						<span class="dashicons dashicons-media-document" style="margin-top: 3px;"></span>
						<?php esc_html_e( 'Save as Draft', 'ai-author-for-websites' ); ?>
					</button>
					<button type="button" id="copy-content-btn" class="button">
						<span class="dashicons dashicons-clipboard" style="margin-top: 3px;"></span>
						<?php esc_html_e( 'Copy Content', 'ai-author-for-websites' ); ?>
					</button>
					<button type="button" id="regenerate-btn" class="button">
						<span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
						<?php esc_html_e( 'Regenerate', 'ai-author-for-websites' ); ?>
					</button>
				</div>
			</div>

			<!-- Error State -->
			<div id="generate-error" class="aiauthor-error-state" style="display: none;">
				<span class="dashicons dashicons-warning" style="font-size: 48px; color: #dc3232;"></span>
				<p id="error-message"></p>
				<button type="button" class="button" onclick="document.getElementById('generate-error').style.display='none'; document.getElementById('generate-empty').style.display='block';">
					<?php esc_html_e( 'Try Again', 'ai-author-for-websites' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Tips Card -->
	<div class="aiauthor-card">
		<h2>
			<span class="dashicons dashicons-lightbulb" style="color: #f1c40f;"></span>
			<?php esc_html_e( 'Tips for Better Results', 'ai-author-for-websites' ); ?>
		</h2>
		<div class="aiauthor-tips-grid">
			<div class="aiauthor-tip">
				<strong><?php esc_html_e( 'Be Specific', 'ai-author-for-websites' ); ?></strong>
				<p><?php esc_html_e( 'The more specific your topic, the better the output. Instead of "Marketing Tips", try "5 Email Marketing Strategies for E-commerce Stores".', 'ai-author-for-websites' ); ?></p>
			</div>
			<div class="aiauthor-tip">
				<strong><?php esc_html_e( 'Build Your Knowledge Base', 'ai-author-for-websites' ); ?></strong>
				<p><?php esc_html_e( 'Add your website pages, product info, and brand guidelines to help the AI write in your voice.', 'ai-author-for-websites' ); ?></p>
			</div>
			<div class="aiauthor-tip">
				<strong><?php esc_html_e( 'Review & Edit', 'ai-author-for-websites' ); ?></strong>
				<p><?php esc_html_e( 'Always review generated content before publishing. AI is a starting point, not the final product.', 'ai-author-for-websites' ); ?></p>
			</div>
		</div>
	</div>
</div>

