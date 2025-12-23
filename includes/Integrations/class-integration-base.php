<?php
/**
 * Integration Base Class
 *
 * Abstract base class that provides common functionality for all integrations.
 *
 * @package AI_Author_For_Websites
 */

namespace AIAuthor\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class IntegrationBase
 *
 * Abstract base class for integrations.
 */
abstract class IntegrationBase implements IntegrationInterface {

	/**
	 * Integration settings option key.
	 *
	 * @var string
	 */
	protected $option_key;

	/**
	 * Default settings for this integration.
	 *
	 * @var array
	 */
	protected $default_settings = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->option_key = 'aiauthor_integration_' . $this->get_id();
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_enabled(): bool {
		$settings = $this->get_settings();
		return ! empty( $settings['enabled'] );
	}

	/**
	 * {@inheritdoc}
	 */
	public function enable(): bool {
		$settings            = $this->get_settings();
		$settings['enabled'] = true;
		$result              = $this->update_settings( $settings );

		if ( $result ) {
			$this->on_activate();
		}

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function disable(): bool {
		$settings            = $this->get_settings();
		$settings['enabled'] = false;
		$result              = $this->update_settings( $settings );

		if ( $result ) {
			$this->on_deactivate();
		}

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_settings(): array {
		$settings = get_option( $this->option_key, array() );
		return array_merge( $this->default_settings, $settings );
	}

	/**
	 * Update integration settings.
	 *
	 * @param array $settings The settings to update.
	 * @return bool True on success, false on failure.
	 */
	public function update_settings( array $settings ): bool {
		$merged = array_merge( $this->get_settings(), $settings );
		return update_option( $this->option_key, $merged );
	}

	/**
	 * {@inheritdoc}
	 */
	public function has_settings_page(): bool {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function on_activate(): void {
		// Override in child classes.
	}

	/**
	 * {@inheritdoc}
	 */
	public function on_deactivate(): void {
		// Override in child classes.
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_builtin(): bool {
		return false;
	}

	/**
	 * Get integration data as array.
	 *
	 * @return array Integration data.
	 */
	public function to_array(): array {
		return array(
			'id'           => $this->get_id(),
			'name'         => $this->get_name(),
			'description'  => $this->get_description(),
			'version'      => $this->get_version(),
			'author'       => $this->get_author(),
			'icon'         => $this->get_icon(),
			'category'     => $this->get_category(),
			'enabled'      => $this->is_enabled(),
			'builtin'      => $this->is_builtin(),
			'has_settings' => $this->has_settings_page(),
			'settings_url' => $this->has_settings_page() ? admin_url( 'admin.php?page=ai-author-integrations&integration=' . $this->get_id() ) : '',
		);
	}
}
