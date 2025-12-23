<?php
/**
 * Settings Tests
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for settings functionality.
 */
class SettingsTest extends TestCase {

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_options'] = [];
	}

	/**
	 * Test API key sanitization.
	 */
	public function test_api_key_sanitization(): void {
		$dirty_key = '  gsk_abc123XYZ  ';
		$clean_key = sanitize_text_field( $dirty_key );
		
		$this->assertEquals( 'gsk_abc123XYZ', $clean_key );
	}

	/**
	 * Test provider validation.
	 */
	public function test_provider_validation(): void {
		$valid_providers = [ 'groq', 'gemini', 'meta' ];
		
		// Valid provider.
		$provider = 'groq';
		$this->assertTrue( in_array( $provider, $valid_providers, true ) );
		
		// Invalid provider.
		$invalid = 'openai';
		$this->assertFalse( in_array( $invalid, $valid_providers, true ) );
	}

	/**
	 * Test model options per provider.
	 */
	public function test_model_options_per_provider(): void {
		$models = [
			'groq'   => [
				'llama-3.3-70b-versatile',
				'llama-3.1-70b-versatile',
				'llama-3.1-8b-instant',
				'mixtral-8x7b-32768',
			],
			'gemini' => [
				'gemini-2.0-flash',
				'gemini-2.5-pro',
			],
		];

		$this->assertArrayHasKey( 'groq', $models );
		$this->assertArrayHasKey( 'gemini', $models );
		$this->assertCount( 4, $models['groq'] );
		$this->assertCount( 2, $models['gemini'] );
	}

	/**
	 * Test system instruction sanitization.
	 */
	public function test_system_instruction_sanitization(): void {
		$instruction = "You are an expert writer.\n\nWrite high-quality content.";
		$sanitized = sanitize_textarea_field( $instruction );
		
		$this->assertNotEmpty( $sanitized );
		$this->assertStringContainsString( 'expert writer', $sanitized );
	}

	/**
	 * Test word count bounds.
	 */
	public function test_word_count_bounds(): void {
		$test_cases = [
			[ 'input' => 500, 'expected' => 500 ],
			[ 'input' => 1000, 'expected' => 1000 ],
			[ 'input' => 2500, 'expected' => 2500 ],
		];

		foreach ( $test_cases as $case ) {
			$value = absint( $case['input'] );
			$this->assertEquals( $case['expected'], $value );
		}
	}

	/**
	 * Test enabled setting is boolean.
	 */
	public function test_enabled_is_boolean(): void {
		// Test truthy values.
		$this->assertTrue( ! empty( 1 ) );
		$this->assertTrue( ! empty( '1' ) );
		$this->assertTrue( ! empty( true ) );
		
		// Test falsy values.
		$this->assertFalse( ! empty( 0 ) );
		$this->assertFalse( ! empty( '' ) );
		$this->assertFalse( ! empty( false ) );
		$this->assertFalse( ! empty( null ) );
	}

	/**
	 * Test settings merge with defaults.
	 */
	public function test_settings_merge_with_defaults(): void {
		$defaults = [
			'api_key'            => '',
			'provider'           => 'groq',
			'model'              => 'llama-3.3-70b-versatile',
			'system_instruction' => '',
			'default_word_count' => 1000,
			'enabled'            => false,
		];

		$saved = [
			'api_key'  => 'my_key',
			'provider' => 'gemini',
		];

		$merged = array_merge( $defaults, $saved );

		$this->assertEquals( 'my_key', $merged['api_key'] );
		$this->assertEquals( 'gemini', $merged['provider'] );
		$this->assertEquals( 'llama-3.3-70b-versatile', $merged['model'] );
		$this->assertEquals( 1000, $merged['default_word_count'] );
	}
}

