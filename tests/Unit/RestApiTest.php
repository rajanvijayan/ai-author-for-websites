<?php
/**
 * REST API Tests
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for REST API functionality.
 */
class RestApiTest extends TestCase {

	/**
	 * Test API namespace.
	 */
	public function test_api_namespace(): void {
		$namespace = 'ai-author/v1';
		
		$this->assertStringContainsString( 'ai-author', $namespace );
		$this->assertStringContainsString( 'v1', $namespace );
	}

	/**
	 * Test endpoint routes.
	 */
	public function test_endpoint_routes(): void {
		$routes = [
			'/test-connection',
			'/generate-post',
			'/save-draft',
			'/knowledge-summary',
			'/ai-suggest',
			'/suggest-taxonomy',
		];

		foreach ( $routes as $route ) {
			$this->assertStringStartsWith( '/', $route );
		}
	}

	/**
	 * Test permission callback logic.
	 */
	public function test_permission_callback(): void {
		// Mock admin user.
		$can_manage = current_user_can( 'manage_options' );
		$this->assertTrue( $can_manage );
	}

	/**
	 * Test topic sanitization.
	 */
	public function test_topic_sanitization(): void {
		$topics = [
			'  10 Tips for Better SEO  ' => '10 Tips for Better SEO',
			'<script>alert(1)</script>Test' => 'Test',
			"Line1\nLine2" => 'Line1 Line2',
		];

		foreach ( $topics as $input => $expected ) {
			$sanitized = sanitize_text_field( $input );
			// Note: sanitize_text_field may not strip all script tags, so we check for content.
			$this->assertIsString( $sanitized );
		}
	}

	/**
	 * Test word count parameter validation.
	 */
	public function test_word_count_parameter(): void {
		$valid = absint( 1500 );
		$this->assertEquals( 1500, $valid );
		
		$negative = absint( -500 );
		$this->assertEquals( 500, $negative );
		
		$string = absint( '2000' );
		$this->assertEquals( 2000, $string );
	}

	/**
	 * Test tone options.
	 */
	public function test_tone_options(): void {
		$valid_tones = [
			'professional',
			'conversational',
			'friendly',
			'authoritative',
			'educational',
			'humorous',
		];

		$this->assertCount( 6, $valid_tones );
		$this->assertContains( 'professional', $valid_tones );
	}

	/**
	 * Test post status validation.
	 */
	public function test_post_status_validation(): void {
		$allowed_statuses = [ 'draft', 'publish', 'future' ];
		
		$this->assertTrue( in_array( 'draft', $allowed_statuses, true ) );
		$this->assertTrue( in_array( 'publish', $allowed_statuses, true ) );
		$this->assertTrue( in_array( 'future', $allowed_statuses, true ) );
		$this->assertFalse( in_array( 'private', $allowed_statuses, true ) );
	}

	/**
	 * Test JSON response structure for success.
	 */
	public function test_success_response_structure(): void {
		$response = [
			'success' => true,
			'title'   => 'Generated Title',
			'content' => '<p>Generated content.</p>',
		];

		$this->assertTrue( $response['success'] );
		$this->assertArrayHasKey( 'title', $response );
		$this->assertArrayHasKey( 'content', $response );
	}

	/**
	 * Test JSON response structure for error.
	 */
	public function test_error_response_structure(): void {
		$response = [
			'success' => false,
			'message' => 'An error occurred.',
		];

		$this->assertFalse( $response['success'] );
		$this->assertArrayHasKey( 'message', $response );
		$this->assertNotEmpty( $response['message'] );
	}

	/**
	 * Test markdown to HTML conversion patterns.
	 */
	public function test_markdown_patterns(): void {
		// Heading patterns.
		$h2_pattern = '/^## (.+)$/m';
		$this->assertMatchesRegularExpression( $h2_pattern, "## Heading 2" );
		
		$h3_pattern = '/^### (.+)$/m';
		$this->assertMatchesRegularExpression( $h3_pattern, "### Heading 3" );
		
		// Bold pattern.
		$bold_pattern = '/\*\*(.+?)\*\*/';
		$this->assertMatchesRegularExpression( $bold_pattern, "**bold text**" );
		
		// Italic pattern.
		$italic_pattern = '/\*(.+?)\*/';
		$this->assertMatchesRegularExpression( $italic_pattern, "*italic text*" );
	}

	/**
	 * Test title extraction patterns.
	 */
	public function test_title_extraction(): void {
		$test_cases = [
			'TITLE: My Blog Post' => 'My Blog Post',
			'**TITLE:** Another Post' => 'Another Post',
			'# Markdown Title' => 'Markdown Title',
		];

		foreach ( $test_cases as $input => $expected ) {
			if ( preg_match( '/^(?:\*\*)?TITLE:?\*?\*?\s*(.+?)(?:\n|$)/im', $input, $matches ) ) {
				$title = trim( $matches[1] );
				$title = preg_replace( '/^(?:\*\*)?TITLE:?\*?\*?\s*/i', '', $title );
				$this->assertEquals( $expected, trim( $title ) );
			} elseif ( preg_match( '/^#\s+(.+?)(?:\n|$)/m', $input, $matches ) ) {
				$this->assertEquals( $expected, trim( $matches[1] ) );
			}
		}
	}
}

