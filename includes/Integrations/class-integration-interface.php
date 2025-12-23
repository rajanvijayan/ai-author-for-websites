<?php
/**
 * Integration Interface
 *
 * Defines the contract for all integrations in the AI Author plugin.
 * This interface allows for external plugins to create their own integrations.
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface IntegrationInterface
 *
 * All integrations must implement this interface.
 */
interface IntegrationInterface {

	/**
	 * Get the unique identifier for this integration.
	 *
	 * @return string Unique integration ID (e.g., 'auto-scheduler').
	 */
	public function get_id(): string;

	/**
	 * Get the display name for this integration.
	 *
	 * @return string Integration name.
	 */
	public function get_name(): string;

	/**
	 * Get the description of this integration.
	 *
	 * @return string Integration description.
	 */
	public function get_description(): string;

	/**
	 * Get the version of this integration.
	 *
	 * @return string Integration version.
	 */
	public function get_version(): string;

	/**
	 * Get the author of this integration.
	 *
	 * @return string Integration author.
	 */
	public function get_author(): string;

	/**
	 * Get the icon for this integration.
	 *
	 * @return string Dashicon class or URL to icon.
	 */
	public function get_icon(): string;

	/**
	 * Check if this integration is enabled.
	 *
	 * @return bool True if enabled.
	 */
	public function is_enabled(): bool;

	/**
	 * Enable this integration.
	 *
	 * @return bool True if successfully enabled.
	 */
	public function enable(): bool;

	/**
	 * Disable this integration.
	 *
	 * @return bool True if successfully disabled.
	 */
	public function disable(): bool;

	/**
	 * Initialize the integration.
	 *
	 * Called when the integration is loaded and enabled.
	 *
	 * @return void
	 */
	public function init(): void;

	/**
	 * Get the settings for this integration.
	 *
	 * @return array Integration settings.
	 */
	public function get_settings(): array;

	/**
	 * Update the settings for this integration.
	 *
	 * @param array $settings New settings to save.
	 * @return bool True if updated successfully.
	 */
	public function update_settings( array $settings ): bool;

	/**
	 * Check if this integration has a settings page.
	 *
	 * @return bool True if has settings page.
	 */
	public function has_settings_page(): bool;

	/**
	 * Render the settings page for this integration.
	 *
	 * @return void
	 */
	public function render_settings_page(): void;

	/**
	 * Called when the integration is activated.
	 *
	 * Use this to set up cron jobs, database tables, etc.
	 *
	 * @return void
	 */
	public function on_activate(): void;

	/**
	 * Called when the integration is deactivated.
	 *
	 * Use this to clean up cron jobs, etc.
	 *
	 * @return void
	 */
	public function on_deactivate(): void;

	/**
	 * Get the category of this integration.
	 *
	 * @return string Integration category (e.g., 'automation', 'publishing', 'analytics').
	 */
	public function get_category(): string;

	/**
	 * Check if this integration is a built-in integration.
	 *
	 * @return bool True if built-in.
	 */
	public function is_builtin(): bool;
}
