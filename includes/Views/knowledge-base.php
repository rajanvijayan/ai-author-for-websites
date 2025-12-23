<?php
/**
 * Knowledge Base View
 *
 * @package AI_Author_For_Websites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
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
				array(
					'number'      => 10,
					'sort_column' => 'post_modified',
					'sort_order'  => 'DESC',
				)
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
				array(
					'numberposts' => 10,
					'orderby'     => 'date',
					'order'       => 'DESC',
				)
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
								<a href="
								<?php
								echo esc_url(
									wp_nonce_url(
										add_query_arg(
											array(
												'action' => 'delete',
												'index' => $index,
											)
										),
										'aiauthor_delete_' . $index
									)
								);
								?>
											" 
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
