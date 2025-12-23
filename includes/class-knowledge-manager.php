<?php
/**
 * Knowledge Base Manager Class
 *
 * @package AI_Author_For_Websites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIEngine\Knowledge\KnowledgeBase;

/**
 * Class AIAUTHOR_Knowledge_Manager
 *
 * Handles knowledge base management for the AI Author plugin.
 */
class AIAUTHOR_Knowledge_Manager {

	/**
	 * Path to the knowledge base file.
	 *
	 * @var string
	 */
	private $kb_file;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$upload_dir    = wp_upload_dir();
		$this->kb_file = $upload_dir['basedir'] . '/ai-author-knowledge/knowledge-base.json';
	}

	/**
	 * Render the knowledge base admin page.
	 */
	public function render_admin_page() {
		// Handle actions.
		if ( isset( $_POST['aiauthor_add_url'] ) && check_admin_referer( 'aiauthor_kb_nonce' ) ) {
			$this->add_url();
		}

		if ( isset( $_POST['aiauthor_add_text'] ) && check_admin_referer( 'aiauthor_kb_nonce' ) ) {
			$this->add_text();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below.
		if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['index'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Used for nonce action.
			if ( check_admin_referer( 'aiauthor_delete_' . absint( $_GET['index'] ) ) ) {
				$this->delete_document( absint( $_GET['index'] ) );
			}
		}

		if ( isset( $_POST['aiauthor_clear_kb'] ) && check_admin_referer( 'aiauthor_kb_nonce' ) ) {
			$this->clear_knowledge();
		}

		$kb        = $this->get_knowledge_base();
		$documents = $kb->getDocuments();
		$summary   = $kb->getSummary();
		?>
		<div class="wrap aiauthor-admin">
			<h1><?php esc_html_e( 'Knowledge Base', 'ai-author-for-websites' ); ?></h1>
			
			<p class="description">
				<?php esc_html_e( 'Add content to train your AI author. The AI will use this information to create relevant blog posts about your website.', 'ai-author-for-websites' ); ?>
			</p>

			<div class="aiauthor-kb-grid">
				<!-- Add from URL -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-admin-links" style="color: #0073aa;"></span>
						<?php esc_html_e( 'Add from URL', 'ai-author-for-websites' ); ?>
					</h2>
					<form method="post" action="">
						<?php wp_nonce_field( 'aiauthor_kb_nonce' ); ?>
						<p>
							<input type="url" 
									name="kb_url" 
									placeholder="https://example.com/page" 
									class="large-text" 
									required>
						</p>
						<p class="description">
							<?php esc_html_e( 'Enter a URL to fetch and add its content to the knowledge base.', 'ai-author-for-websites' ); ?>
						</p>
						<p>
							<input type="submit" 
									name="aiauthor_add_url" 
									class="button button-primary" 
									value="<?php esc_attr_e( 'Add URL', 'ai-author-for-websites' ); ?>">
						</p>
					</form>
				</div>

				<!-- Add Custom Text -->
				<div class="aiauthor-card">
					<h2>
						<span class="dashicons dashicons-edit" style="color: #23a455;"></span>
						<?php esc_html_e( 'Add Custom Text', 'ai-author-for-websites' ); ?>
					</h2>
					<form method="post" action="">
						<?php wp_nonce_field( 'aiauthor_kb_nonce' ); ?>
						<div class="aiauthor-form-row">
							<label for="kb_title"><?php esc_html_e( 'Title', 'ai-author-for-websites' ); ?></label>
							<input type="text" 
									id="kb_title"
									name="kb_title" 
									placeholder="<?php esc_attr_e( 'e.g., Company Info, Product Details', 'ai-author-for-websites' ); ?>" 
									class="large-text">
						</div>
						<div class="aiauthor-form-row">
							<label for="kb_category"><?php esc_html_e( 'Category', 'ai-author-for-websites' ); ?></label>
							<select name="kb_category" id="kb_category" class="regular-text">
								<option value=""><?php esc_html_e( 'Select a category...', 'ai-author-for-websites' ); ?></option>
								<option value="company"><?php esc_html_e( 'Company Information', 'ai-author-for-websites' ); ?></option>
								<option value="product"><?php esc_html_e( 'Products/Services', 'ai-author-for-websites' ); ?></option>
								<option value="industry"><?php esc_html_e( 'Industry Knowledge', 'ai-author-for-websites' ); ?></option>
								<option value="blog-style"><?php esc_html_e( 'Blog Style Guide', 'ai-author-for-websites' ); ?></option>
								<option value="audience"><?php esc_html_e( 'Target Audience', 'ai-author-for-websites' ); ?></option>
								<option value="keywords"><?php esc_html_e( 'Keywords & Topics', 'ai-author-for-websites' ); ?></option>
								<option value="other"><?php esc_html_e( 'Other', 'ai-author-for-websites' ); ?></option>
							</select>
						</div>
						<div class="aiauthor-form-row">
							<label for="kb_text"><?php esc_html_e( 'Content', 'ai-author-for-websites' ); ?></label>
							<textarea name="kb_text" 
										id="kb_text"
										rows="5" 
										class="large-text" 
										placeholder="<?php esc_attr_e( 'Enter your content here...', 'ai-author-for-websites' ); ?>" 
										required></textarea>
							<div class="aiauthor-char-count">
								<span id="kb-char-count">0</span> <?php esc_html_e( 'characters', 'ai-author-for-websites' ); ?>
							</div>
						</div>
						<p>
							<input type="submit" 
									name="aiauthor_add_text" 
									class="button button-primary" 
									value="<?php esc_attr_e( 'Add Text', 'ai-author-for-websites' ); ?>">
						</p>
					</form>
				</div>
			</div>

			<!-- Quick Add Website Pages -->
			<div class="aiauthor-card">
				<h2>
					<span class="dashicons dashicons-admin-page" style="color: #3498db;"></span>
					<?php esc_html_e( 'Quick Add Website Pages', 'ai-author-for-websites' ); ?>
				</h2>
				<p class="description">
					<?php esc_html_e( 'Quickly add your website pages to the knowledge base:', 'ai-author-for-websites' ); ?>
				</p>
				<div class="aiauthor-quick-pages">
					<?php
					$pages = get_pages(
						[
							'number'      => 10,
							'sort_column' => 'post_modified',
							'sort_order'  => 'DESC',
						]
					);
					foreach ( $pages as $page ) :
						?>
						<form method="post" action="" style="display: inline-block; margin: 5px;">
							<?php wp_nonce_field( 'aiauthor_kb_nonce' ); ?>
							<input type="hidden" name="kb_url" value="<?php echo esc_url( get_permalink( $page->ID ) ); ?>">
							<button type="submit" name="aiauthor_add_url" class="button button-small">
								+ <?php echo esc_html( $page->post_title ); ?>
							</button>
						</form>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Quick Add Blog Posts -->
			<div class="aiauthor-card">
				<h2>
					<span class="dashicons dashicons-welcome-write-blog" style="color: #e67e22;"></span>
					<?php esc_html_e( 'Recent Blog Posts', 'ai-author-for-websites' ); ?>
				</h2>
				<p class="description">
					<?php esc_html_e( 'Add your existing blog posts to help the AI understand your writing style:', 'ai-author-for-websites' ); ?>
				</p>
				<div class="aiauthor-quick-pages">
					<?php
					$posts = get_posts(
						[
							'numberposts' => 10,
							'orderby'     => 'date',
							'order'       => 'DESC',
						]
					);
					foreach ( $posts as $post ) :
						?>
						<form method="post" action="" style="display: inline-block; margin: 5px;">
							<?php wp_nonce_field( 'aiauthor_kb_nonce' ); ?>
							<input type="hidden" name="kb_url" value="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
							<button type="submit" name="aiauthor_add_url" class="button button-small">
								+ <?php echo esc_html( $post->post_title ); ?>
							</button>
						</form>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Knowledge Base Contents -->
			<div class="aiauthor-card">
				<h2>
					<?php esc_html_e( 'Knowledge Base Contents', 'ai-author-for-websites' ); ?>
					<span class="aiauthor-badge"><?php echo esc_html( $summary['count'] ); ?> <?php esc_html_e( 'documents', 'ai-author-for-websites' ); ?></span>
				</h2>

				<?php if ( $summary['count'] > 0 ) : ?>
					<p class="description">
						<?php
						/* translators: %s: Total number of characters */
						printf( esc_html__( 'Total content: %s characters', 'ai-author-for-websites' ), esc_html( number_format( $summary['totalChars'] ) ) );
						?>
					</p>

					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th style="width: 5%;">#</th>
								<th style="width: 25%;"><?php esc_html_e( 'Title', 'ai-author-for-websites' ); ?></th>
								<th style="width: 35%;"><?php esc_html_e( 'Source', 'ai-author-for-websites' ); ?></th>
								<th style="width: 15%;"><?php esc_html_e( 'Size', 'ai-author-for-websites' ); ?></th>
								<th style="width: 10%;"><?php esc_html_e( 'Added', 'ai-author-for-websites' ); ?></th>
								<th style="width: 10%;"><?php esc_html_e( 'Actions', 'ai-author-for-websites' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $documents as $index => $doc ) : ?>
								<tr>
									<td><?php echo esc_html( $index + 1 ); ?></td>
									<td><?php echo esc_html( $doc['title'] ?? __( 'Untitled', 'ai-author-for-websites' ) ); ?></td>
									<td>
										<?php
										$source      = $doc['source'] ?? '';
										$source_icon = 'dashicons-admin-page';

										if ( filter_var( $source, FILTER_VALIDATE_URL ) ) {
											$source_icon = 'dashicons-admin-links';
										} elseif ( strpos( $source, 'manual-entry-' ) === 0 ) {
											$source_icon = 'dashicons-edit';
										}
										?>
										<span class="dashicons <?php echo esc_attr( $source_icon ); ?>" style="opacity: 0.5;"></span>
										<?php if ( filter_var( $source, FILTER_VALIDATE_URL ) ) : ?>
											<a href="<?php echo esc_url( $source ); ?>" target="_blank">
												<?php echo esc_html( substr( $source, 0, 40 ) ); ?>...
											</a>
										<?php else : ?>
											<?php echo esc_html( substr( $source, 0, 30 ) ); ?>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( number_format( strlen( $doc['content'] ) ) ); ?> <?php esc_html_e( 'chars', 'ai-author-for-websites' ); ?></td>
									<td><?php echo esc_html( gmdate( 'M j', strtotime( $doc['addedAt'] ) ) ); ?></td>
									<td>
										<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'delete', 'index' => $index ] ), 'aiauthor_delete_' . $index ) ); ?>" 
											class="button button-small button-link-delete"
											onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'ai-author-for-websites' ); ?>');">
											<?php esc_html_e( 'Delete', 'ai-author-for-websites' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<form method="post" action="" style="margin-top: 20px;">
						<?php wp_nonce_field( 'aiauthor_kb_nonce' ); ?>
						<input type="submit" 
								name="aiauthor_clear_kb" 
								class="button button-link-delete" 
								value="<?php esc_attr_e( 'Clear All Knowledge', 'ai-author-for-websites' ); ?>"
								onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete all knowledge? This cannot be undone.', 'ai-author-for-websites' ); ?>');">
					</form>
				<?php else : ?>
					<div class="aiauthor-empty-state">
						<span class="dashicons dashicons-book-alt" style="font-size: 48px; color: #ccc;"></span>
						<p><?php esc_html_e( 'No content added yet. Add URLs, text, or website pages above to train your AI author.', 'ai-author-for-websites' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get knowledge base instance.
	 *
	 * @return KnowledgeBase The knowledge base instance.
	 */
	public function get_knowledge_base() {
		$kb = new KnowledgeBase();

		if ( file_exists( $this->kb_file ) ) {
			$kb->load( $this->kb_file );
		}

		return $kb;
	}

	/**
	 * Save knowledge base.
	 *
	 * @param KnowledgeBase $kb The knowledge base instance to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_knowledge_base( KnowledgeBase $kb ) {
		$dir = dirname( $this->kb_file );
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		return $kb->save( $this->kb_file );
	}

	/**
	 * Add URL to knowledge base.
	 */
	private function add_url() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in render_admin_page.
		$url = isset( $_POST['kb_url'] ) ? esc_url_raw( wp_unslash( $_POST['kb_url'] ) ) : '';

		if ( empty( $url ) ) {
			$this->show_notice( __( 'Please enter a valid URL.', 'ai-author-for-websites' ), 'error' );
			return;
		}

		$kb     = $this->get_knowledge_base();
		$result = $kb->addUrl( $url );

		if ( $result['success'] ) {
			$this->save_knowledge_base( $kb );
			$title = $result['title'] ?? __( 'Untitled', 'ai-author-for-websites' );
			/* translators: %s: Title of the added content */
			$this->show_notice( sprintf( __( 'Added: %s', 'ai-author-for-websites' ), $title ), 'success' );
		} else {
			$this->show_notice( $result['error'] ?? __( 'Failed to fetch URL.', 'ai-author-for-websites' ), 'error' );
		}
	}

	/**
	 * Add text to knowledge base.
	 */
	private function add_text() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in render_admin_page.
		$text = isset( $_POST['kb_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kb_text'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in render_admin_page.
		$title = isset( $_POST['kb_title'] ) ? sanitize_text_field( wp_unslash( $_POST['kb_title'] ) ) : '';

		if ( empty( $text ) ) {
			$this->show_notice( __( 'Please enter some text.', 'ai-author-for-websites' ), 'error' );
			return;
		}

		$kb          = $this->get_knowledge_base();
		$source      = 'manual-entry-' . time();
		$title_param = ! empty( $title ) ? $title : null;
		$result      = $kb->addText( $text, $source, $title_param );

		if ( $result ) {
			$this->save_knowledge_base( $kb );
			$this->show_notice( __( 'Text added to knowledge base.', 'ai-author-for-websites' ), 'success' );
		} else {
			$this->show_notice( __( 'Failed to add text.', 'ai-author-for-websites' ), 'error' );
		}
	}

	/**
	 * Delete a document.
	 *
	 * @param int $index The index of the document to delete.
	 */
	private function delete_document( $index ) {
		$kb = $this->get_knowledge_base();

		if ( $kb->remove( $index ) ) {
			$this->save_knowledge_base( $kb );
			$this->show_notice( __( 'Document deleted.', 'ai-author-for-websites' ), 'success' );
		}
	}

	/**
	 * Clear all knowledge.
	 */
	private function clear_knowledge() {
		$kb = $this->get_knowledge_base();
		$kb->clear();
		$this->save_knowledge_base( $kb );
		$this->show_notice( __( 'Knowledge base cleared.', 'ai-author-for-websites' ), 'success' );
	}

	/**
	 * Show admin notice.
	 *
	 * @param string $message The message to display.
	 * @param string $type    The type of notice (success, error, warning, info).
	 */
	private function show_notice( $message, $type = 'success' ) {
		add_settings_error( 'aiauthor_kb_messages', 'aiauthor_kb_message', $message, $type );
		settings_errors( 'aiauthor_kb_messages' );
	}

	/**
	 * Get the knowledge base context for AI prompts.
	 *
	 * @return string The formatted knowledge base content.
	 */
	public function get_knowledge_context() {
		$kb        = $this->get_knowledge_base();
		$documents = $kb->getDocuments();

		if ( empty( $documents ) ) {
			return '';
		}

		$context = "Here is the knowledge base content to use for generating blog posts:\n\n";

		foreach ( $documents as $index => $doc ) {
			$title    = $doc['title'] ?? 'Document ' . ( $index + 1 );
			$content  = $doc['content'] ?? '';
			$context .= "--- {$title} ---\n";
			$context .= $content . "\n\n";
		}

		return $context;
	}
}

