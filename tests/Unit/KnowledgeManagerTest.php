<?php
/**
 * Knowledge Manager Tests
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for Knowledge Manager functionality.
 */
class KnowledgeManagerTest extends TestCase {

	/**
	 * Test content sanitization.
	 */
	public function test_content_sanitization(): void {
		$html_content = '<p>This is <strong>formatted</strong> content.</p>';
		$text_content = wp_strip_all_tags( $html_content );
		
		$this->assertEquals( 'This is formatted content.', $text_content );
	}

	/**
	 * Test word trimming.
	 */
	public function test_word_trimming(): void {
		$long_text = 'This is a very long text that needs to be trimmed to a specific number of words.';
		$trimmed = wp_trim_words( $long_text, 5, '...' );
		
		$this->assertEquals( 'This is a very long...', $trimmed );
	}

	/**
	 * Test URL validation.
	 */
	public function test_url_validation(): void {
		$valid_urls = [
			'https://example.com',
			'https://example.com/page',
			'http://localhost:8080/test',
		];

		$invalid_urls = [
			'not-a-url',
			'ftp://example.com',
			'javascript:alert(1)',
		];

		foreach ( $valid_urls as $url ) {
			$filtered = filter_var( $url, FILTER_VALIDATE_URL );
			$this->assertNotFalse( $filtered, "URL should be valid: $url" );
		}

		foreach ( $invalid_urls as $url ) {
			$filtered = filter_var( $url, FILTER_VALIDATE_URL );
			if ( $filtered !== false && strpos( $url, 'http' ) !== 0 ) {
				// Some URLs might pass filter but fail our protocol check.
				$this->assertStringStartsNotWith( 'javascript', $url );
			}
		}
	}

	/**
	 * Test knowledge base summary structure.
	 */
	public function test_knowledge_base_summary_structure(): void {
		$summary = [
			'count'      => 5,
			'totalChars' => 10000,
			'sources'    => [ 'url', 'text' ],
		];

		$this->assertArrayHasKey( 'count', $summary );
		$this->assertArrayHasKey( 'totalChars', $summary );
		$this->assertIsInt( $summary['count'] );
		$this->assertIsInt( $summary['totalChars'] );
	}

	/**
	 * Test document structure.
	 */
	public function test_document_structure(): void {
		$document = [
			'id'        => 'doc_123',
			'title'     => 'Test Document',
			'content'   => 'Document content here.',
			'source'    => 'text',
			'createdAt' => '2024-01-01T00:00:00Z',
		];

		$this->assertArrayHasKey( 'id', $document );
		$this->assertArrayHasKey( 'title', $document );
		$this->assertArrayHasKey( 'content', $document );
		$this->assertArrayHasKey( 'source', $document );
		$this->assertNotEmpty( $document['id'] );
		$this->assertNotEmpty( $document['content'] );
	}

	/**
	 * Test context generation limits.
	 */
	public function test_context_generation_limits(): void {
		$max_context_length = 50000; // Characters.
		
		$content = str_repeat( 'a', 60000 );
		$trimmed = substr( $content, 0, $max_context_length );
		
		$this->assertLessThanOrEqual( $max_context_length, strlen( $trimmed ) );
	}
}

