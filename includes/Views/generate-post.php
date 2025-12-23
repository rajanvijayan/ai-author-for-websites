<?php
/**
 * Generate Post View
 *
 * @package AI_Author_For_Websites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings          = \AIAuthor\Core\Plugin::get_settings();
$knowledge_manager = new \AIAuthor\Knowledge\Manager();
$kb                = $knowledge_manager->get_knowledge_base();
$summary           = $kb->getSummary();
$has_api_key       = ! empty( $settings['api_key'] );
$has_knowledge     = $summary['count'] > 0;

// Get authors.
$authors = get_users(
	array(
		'capability' => array( 'edit_posts' ),
		'orderby'    => 'display_name',
	)
);

// Get categories.
$categories = get_categories( array( 'hide_empty' => false ) );
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
						value="1000" 
						min="100" 
						max="5000" 
						step="100"
						class="regular-text"
						style="width: 120px;">
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

			<div class="aiauthor-form-row">
				<label for="post-author"><?php esc_html_e( 'Author', 'ai-author-for-websites' ); ?></label>
				<select id="post-author" class="regular-text">
					<?php foreach ( $authors as $author ) : ?>
						<option value="<?php echo esc_attr( $author->ID ); ?>" <?php selected( $author->ID, get_current_user_id() ); ?>>
							<?php echo esc_html( $author->display_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="aiauthor-form-actions">
				<button type="button" 
						id="generate-post-btn" 
						class="button button-primary button-hero aiauthor-btn-with-icon"
						<?php disabled( ! $has_api_key ); ?>>
					<span class="dashicons dashicons-admin-generic"></span>
					<span class="btn-text"><?php esc_html_e( 'Generate Post', 'ai-author-for-websites' ); ?></span>
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

				<!-- SEO Section -->
				<?php
				$seo_integrations_manager = \AIAuthor\Integrations\Manager::get_instance();
				$yoast_seo_integration    = $seo_integrations_manager->get( 'yoast-seo' );
				$rankmath_seo_integration = $seo_integrations_manager->get( 'rankmath' );
				$show_seo_section         = ( $yoast_seo_integration && $yoast_seo_integration->is_enabled() ) || ( $rankmath_seo_integration && $rankmath_seo_integration->is_enabled() );
				$active_seo_plugin        = '';
				if ( $yoast_seo_integration && $yoast_seo_integration->is_yoast_active() ) {
					$active_seo_plugin = 'yoast';
				} elseif ( $rankmath_seo_integration && $rankmath_seo_integration->is_rankmath_active() ) {
					$active_seo_plugin = 'rankmath';
				}
				?>
				<?php if ( $show_seo_section || $active_seo_plugin ) : ?>
				<div class="aiauthor-seo-section">
					<div class="aiauthor-seo-header">
						<h3>
							<span class="dashicons dashicons-search" style="color: #0073aa;"></span>
							<?php esc_html_e( 'SEO Settings', 'ai-author-for-websites' ); ?>
							<?php if ( $active_seo_plugin ) : ?>
								<span class="aiauthor-seo-badge">
									<?php echo 'yoast' === $active_seo_plugin ? 'Yoast SEO' : 'Rank Math'; ?>
								</span>
							<?php endif; ?>
						</h3>
						<button type="button" id="generate-seo-btn" class="button button-small">
							<span class="dashicons dashicons-admin-generic"></span>
							<span><?php esc_html_e( 'Generate SEO', 'ai-author-for-websites' ); ?></span>
						</button>
					</div>

					<div id="seo-loading" class="aiauthor-seo-loading" style="display: none;">
						<span class="spinner is-active"></span>
						<span><?php esc_html_e( 'Generating SEO data...', 'ai-author-for-websites' ); ?></span>
					</div>

					<div id="seo-fields" class="aiauthor-seo-fields">
						<div class="aiauthor-form-row">
							<label for="seo-focus-keyword"><?php esc_html_e( 'Focus Keyword', 'ai-author-for-websites' ); ?></label>
							<input type="text" 
									id="seo-focus-keyword" 
									class="large-text" 
									placeholder="<?php esc_attr_e( 'e.g., productivity tips', 'ai-author-for-websites' ); ?>">
							<p class="description"><?php esc_html_e( 'The main keyword you want this post to rank for.', 'ai-author-for-websites' ); ?></p>
						</div>

						<div class="aiauthor-form-row">
							<label for="seo-title"><?php esc_html_e( 'SEO Title', 'ai-author-for-websites' ); ?></label>
							<input type="text" 
									id="seo-title" 
									class="large-text" 
									placeholder="<?php esc_attr_e( 'Optimized title for search engines (max 60 chars)', 'ai-author-for-websites' ); ?>">
							<p class="description">
								<span id="seo-title-count">0</span>/60 <?php esc_html_e( 'characters', 'ai-author-for-websites' ); ?>
							</p>
						</div>

						<div class="aiauthor-form-row">
							<label for="seo-meta-desc"><?php esc_html_e( 'Meta Description', 'ai-author-for-websites' ); ?></label>
							<textarea id="seo-meta-desc" 
									class="large-text" 
									rows="3"
									placeholder="<?php esc_attr_e( 'Compelling description for search results (max 155 chars)', 'ai-author-for-websites' ); ?>"></textarea>
							<p class="description">
								<span id="seo-desc-count">0</span>/155 <?php esc_html_e( 'characters', 'ai-author-for-websites' ); ?>
							</p>
						</div>

						<div class="aiauthor-seo-preview">
							<p class="aiauthor-seo-preview-label"><?php esc_html_e( 'Search Preview:', 'ai-author-for-websites' ); ?></p>
							<div class="aiauthor-serp-preview">
								<div class="aiauthor-serp-title" id="serp-title"><?php esc_html_e( 'Your SEO Title Will Appear Here', 'ai-author-for-websites' ); ?></div>
								<div class="aiauthor-serp-url"><?php echo esc_url( home_url( '/your-post-slug/' ) ); ?></div>
								<div class="aiauthor-serp-desc" id="serp-desc"><?php esc_html_e( 'Your meta description will appear here. Make it compelling to improve click-through rates from search results.', 'ai-author-for-websites' ); ?></div>
							</div>
						</div>
					</div>
				</div>
				<?php endif; ?>

				<!-- Category & Tags Section -->
				<div class="aiauthor-taxonomy-section">
					<div class="aiauthor-taxonomy-header">
						<h3><?php esc_html_e( 'Categories & Tags', 'ai-author-for-websites' ); ?></h3>
						<button type="button" id="suggest-taxonomy-btn" class="button button-small">
							<span class="dashicons dashicons-lightbulb"></span>
							<span><?php esc_html_e( 'AI Suggest', 'ai-author-for-websites' ); ?></span>
						</button>
					</div>

					<div class="aiauthor-form-row">
						<label><?php esc_html_e( 'Categories', 'ai-author-for-websites' ); ?></label>
						<div id="category-selector" class="aiauthor-tag-selector">
							<?php foreach ( $categories as $cat ) : ?>
								<label class="aiauthor-checkbox-label">
									<input type="checkbox" name="post-categories[]" value="<?php echo esc_attr( $cat->term_id ); ?>">
									<span><?php echo esc_html( $cat->name ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
						<div class="aiauthor-add-new-taxonomy">
							<input type="text" id="new-category-input" placeholder="<?php esc_attr_e( 'Add new category', 'ai-author-for-websites' ); ?>" class="regular-text">
							<button type="button" id="add-category-btn" class="button button-small">
								<?php esc_html_e( 'Add', 'ai-author-for-websites' ); ?>
							</button>
						</div>
					</div>

					<div class="aiauthor-form-row">
						<label><?php esc_html_e( 'Tags', 'ai-author-for-websites' ); ?></label>
						<div id="tag-container" class="aiauthor-tags-container"></div>
						<div class="aiauthor-add-new-taxonomy">
							<input type="text" id="new-tag-input" placeholder="<?php esc_attr_e( 'Add tag and press Enter', 'ai-author-for-websites' ); ?>" class="regular-text">
							<button type="button" id="add-tag-btn" class="button button-small">
								<?php esc_html_e( 'Add', 'ai-author-for-websites' ); ?>
							</button>
						</div>
					</div>
				</div>

				<div class="aiauthor-result-actions">
					<!-- Publish Dropdown -->
					<div class="aiauthor-publish-dropdown">
						<button type="button" id="publish-dropdown-btn" class="button button-primary aiauthor-btn-with-icon">
							<span class="dashicons dashicons-upload"></span>
							<span class="btn-text"><?php esc_html_e( 'Publish', 'ai-author-for-websites' ); ?></span>
							<span class="dashicons dashicons-arrow-up-alt2" style="margin-left: 4px;"></span>
						</button>
						<div class="aiauthor-publish-menu">
							<button type="button" id="menu-publish-now" class="aiauthor-publish-menu-item">
								<span class="dashicons dashicons-yes-alt"></span>
								<span><?php esc_html_e( 'Publish Now', 'ai-author-for-websites' ); ?></span>
							</button>
							<button type="button" id="menu-schedule" class="aiauthor-publish-menu-item">
								<span class="dashicons dashicons-calendar-alt"></span>
								<span><?php esc_html_e( 'Schedule for Later', 'ai-author-for-websites' ); ?></span>
							</button>
							<div class="aiauthor-publish-menu-divider"></div>
							<button type="button" id="menu-save-draft" class="aiauthor-publish-menu-item">
								<span class="dashicons dashicons-media-document"></span>
								<span><?php esc_html_e( 'Save as Draft', 'ai-author-for-websites' ); ?></span>
							</button>
						</div>
					</div>
					<button type="button" id="save-draft-btn" class="button aiauthor-btn-with-icon">
						<span class="dashicons dashicons-media-document"></span>
						<span class="btn-text"><?php esc_html_e( 'Save Draft', 'ai-author-for-websites' ); ?></span>
					</button>
					<button type="button" id="copy-content-btn" class="button aiauthor-btn-with-icon">
						<span class="dashicons dashicons-clipboard"></span>
						<span class="btn-text"><?php esc_html_e( 'Copy', 'ai-author-for-websites' ); ?></span>
					</button>
					<button type="button" id="regenerate-btn" class="button aiauthor-btn-with-icon">
						<span class="dashicons dashicons-update"></span>
						<span class="btn-text"><?php esc_html_e( 'Regenerate', 'ai-author-for-websites' ); ?></span>
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
