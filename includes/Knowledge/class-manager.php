<?php
/**
 * Knowledge Base Manager Class
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Knowledge;

use AIEngine\Knowledge\KnowledgeBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Manager
 *
 * Handles knowledge base management for the AI Author plugin.
 */
class Manager {

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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['index'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
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

		include AIAUTHOR_PLUGIN_DIR . 'includes/Views/knowledge-base.php';
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
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
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
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$text = isset( $_POST['kb_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kb_text'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
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

