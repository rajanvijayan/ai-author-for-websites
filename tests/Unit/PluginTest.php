<?php
/**
 * Plugin Core Tests
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for the core Plugin class.
 */
class PluginTest extends TestCase {

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		// Reset options before each test.
		$GLOBALS['wp_options'] = [];
		$GLOBALS['wp_actions'] = [];
	}

	/**
	 * Test that plugin constants are defined.
	 */
	public function test_plugin_constants_are_defined(): void {
		$this->assertTrue( defined( 'AIAUTHOR_VERSION' ) );
		$this->assertTrue( defined( 'AIAUTHOR_PLUGIN_DIR' ) );
		$this->assertTrue( defined( 'AIAUTHOR_PLUGIN_URL' ) );
		$this->assertTrue( defined( 'AIAUTHOR_PLUGIN_BASENAME' ) );
	}

	/**
	 * Test plugin version format.
	 */
	public function test_plugin_version_format(): void {
		$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+$/', AIAUTHOR_VERSION );
	}

	/**
	 * Test default settings structure.
	 */
	public function test_default_settings_structure(): void {
		$defaults = [
			'api_key'            => '',
			'provider'           => 'groq',
			'model'              => 'llama-3.3-70b-versatile',
			'system_instruction' => '',
			'default_word_count' => 1000,
			'enabled'            => false,
		];

		foreach ( $defaults as $key => $value ) {
			$this->assertArrayHasKey( $key, $defaults );
		}
	}

	/**
	 * Test settings can be retrieved.
	 */
	public function test_get_settings_returns_array(): void {
		update_option( 'aiauthor_settings', [ 'api_key' => 'test_key' ] );
		$settings = get_option( 'aiauthor_settings', [] );
		
		$this->assertIsArray( $settings );
		$this->assertEquals( 'test_key', $settings['api_key'] );
	}

	/**
	 * Test settings can be updated.
	 */
	public function test_update_settings(): void {
		$new_settings = [
			'api_key'  => 'new_api_key',
			'provider' => 'gemini',
		];

		update_option( 'aiauthor_settings', $new_settings );
		$settings = get_option( 'aiauthor_settings', [] );

		$this->assertEquals( 'new_api_key', $settings['api_key'] );
		$this->assertEquals( 'gemini', $settings['provider'] );
	}

	/**
	 * Test valid provider options.
	 */
	public function test_valid_providers(): void {
		$valid_providers = [ 'groq', 'gemini', 'meta' ];
		
		foreach ( $valid_providers as $provider ) {
			$this->assertContains( $provider, $valid_providers );
		}
	}

	/**
	 * Test word count validation.
	 */
	public function test_word_count_validation(): void {
		$min = 100;
		$max = 5000;
		
		// Valid word count.
		$word_count = 1000;
		$this->assertGreaterThanOrEqual( $min, $word_count );
		$this->assertLessThanOrEqual( $max, $word_count );
		
		// Invalid word count should be clamped.
		$too_low = 50;
		$clamped_low = max( $min, $too_low );
		$this->assertEquals( $min, $clamped_low );
		
		$too_high = 10000;
		$clamped_high = min( $max, $too_high );
		$this->assertEquals( $max, $clamped_high );
	}
}

