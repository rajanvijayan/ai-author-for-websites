<?php
/**
 * WordPress Function Stubs for Testing
 *
 * Minimal stubs to allow unit testing without WordPress.
 *
 * @package AI_Author_For_Websites
 */

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Mock get_option.
	 *
	 * @param string $option  Option name.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	function get_option( $option, $default = false ) {
		global $wp_options;
		return $wp_options[ $option ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * Mock update_option.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Option value.
	 * @return bool
	 */
	function update_option( $option, $value ) {
		global $wp_options;
		$wp_options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'add_option' ) ) {
	/**
	 * Mock add_option.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Option value.
	 * @return bool
	 */
	function add_option( $option, $value = '' ) {
		global $wp_options;
		if ( ! isset( $wp_options[ $option ] ) ) {
			$wp_options[ $option ] = $value;
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	/**
	 * Mock delete_option.
	 *
	 * @param string $option Option name.
	 * @return bool
	 */
	function delete_option( $option ) {
		global $wp_options;
		unset( $wp_options[ $option ] );
		return true;
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Mock translation function.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Mock esc_html__ function.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function esc_html__( $text, $domain = 'default' ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Mock esc_attr function.
	 *
	 * @param string $text Text to escape.
	 * @return string
	 */
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Mock esc_html function.
	 *
	 * @param string $text Text to escape.
	 * @return string
	 */
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * Mock esc_url function.
	 *
	 * @param string $url URL to escape.
	 * @return string
	 */
	function esc_url( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Mock sanitize_text_field function.
	 *
	 * @param string $str String to sanitize.
	 * @return string
	 */
	function sanitize_text_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	/**
	 * Mock sanitize_textarea_field function.
	 *
	 * @param string $str String to sanitize.
	 * @return string
	 */
	function sanitize_textarea_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * Mock sanitize_key function.
	 *
	 * @param string $key Key to sanitize.
	 * @return string
	 */
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Mock absint function.
	 *
	 * @param mixed $value Value to convert.
	 * @return int
	 */
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * Mock wp_kses_post function.
	 *
	 * @param string $data Content to filter.
	 * @return string
	 */
	function wp_kses_post( $data ) {
		return $data;
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Mock wp_strip_all_tags function.
	 *
	 * @param string $string String to strip.
	 * @return string
	 */
	function wp_strip_all_tags( $string ) {
		return strip_tags( $string );
	}
}

if ( ! function_exists( 'wp_trim_words' ) ) {
	/**
	 * Mock wp_trim_words function.
	 *
	 * @param string $text      Text to trim.
	 * @param int    $num_words Number of words.
	 * @param string $more      What to append if trimmed.
	 * @return string
	 */
	function wp_trim_words( $text, $num_words = 55, $more = '...' ) {
		$words = explode( ' ', $text );
		if ( count( $words ) > $num_words ) {
			return implode( ' ', array_slice( $words, 0, $num_words ) ) . $more;
		}
		return $text;
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	/**
	 * Mock get_bloginfo function.
	 *
	 * @param string $show What to retrieve.
	 * @return string
	 */
	function get_bloginfo( $show = '' ) {
		$info = [
			'name'        => 'Test Site',
			'description' => 'Just another WordPress site',
			'url'         => 'https://example.com',
			'admin_email' => 'admin@example.com',
		];
		return $info[ $show ] ?? '';
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	/**
	 * Mock admin_url function.
	 *
	 * @param string $path Path to append.
	 * @return string
	 */
	function admin_url( $path = '' ) {
		return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Mock add_action function.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback function.
	 * @param int      $priority Priority.
	 * @param int      $args     Number of arguments.
	 * @return bool
	 */
	function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
		global $wp_actions;
		$wp_actions[ $hook ][] = [
			'callback' => $callback,
			'priority' => $priority,
			'args'     => $args,
		];
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Mock add_filter function.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback function.
	 * @param int      $priority Priority.
	 * @param int      $args     Number of arguments.
	 * @return bool
	 */
	function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
		return add_action( $hook, $callback, $priority, $args );
	}
}

if ( ! function_exists( 'do_action' ) ) {
	/**
	 * Mock do_action function.
	 *
	 * @param string $hook Hook name.
	 * @param mixed  ...$args Arguments to pass.
	 */
	function do_action( $hook, ...$args ) {
		global $wp_actions;
		if ( isset( $wp_actions[ $hook ] ) ) {
			foreach ( $wp_actions[ $hook ] as $action ) {
				call_user_func_array( $action['callback'], $args );
			}
		}
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Mock apply_filters function.
	 *
	 * @param string $hook  Hook name.
	 * @param mixed  $value Value to filter.
	 * @param mixed  ...$args Additional arguments.
	 * @return mixed
	 */
	function apply_filters( $hook, $value, ...$args ) {
		global $wp_actions;
		if ( isset( $wp_actions[ $hook ] ) ) {
			foreach ( $wp_actions[ $hook ] as $filter ) {
				$value = call_user_func_array( $filter['callback'], array_merge( [ $value ], $args ) );
			}
		}
		return $value;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	/**
	 * Mock current_user_can function.
	 *
	 * @param string $capability Capability to check.
	 * @return bool
	 */
	function current_user_can( $capability ) {
		return true;
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	/**
	 * Mock wp_create_nonce function.
	 *
	 * @param string $action Nonce action.
	 * @return string
	 */
	function wp_create_nonce( $action = '' ) {
		return md5( $action . 'test_nonce' );
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	/**
	 * Mock wp_verify_nonce function.
	 *
	 * @param string $nonce  Nonce to verify.
	 * @param string $action Nonce action.
	 * @return bool|int
	 */
	function wp_verify_nonce( $nonce, $action = '' ) {
		return $nonce === md5( $action . 'test_nonce' ) ? 1 : false;
	}
}

if ( ! function_exists( 'check_admin_referer' ) ) {
	/**
	 * Mock check_admin_referer function.
	 *
	 * @param string $action Nonce action.
	 * @param string $query_arg Query arg name.
	 * @return bool
	 */
	function check_admin_referer( $action = '', $query_arg = '_wpnonce' ) {
		return true;
	}
}

if ( ! function_exists( 'check_ajax_referer' ) ) {
	/**
	 * Mock check_ajax_referer function.
	 *
	 * @param string $action    Nonce action.
	 * @param string $query_arg Query arg name.
	 * @param bool   $die       Whether to die on failure.
	 * @return bool
	 */
	function check_ajax_referer( $action = '', $query_arg = 'nonce', $die = true ) {
		return true;
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Mock wp_unslash function.
	 *
	 * @param string|array $value Value to unslash.
	 * @return string|array
	 */
	function wp_unslash( $value ) {
		return is_array( $value ) ? array_map( 'stripslashes', $value ) : stripslashes( $value );
	}
}

if ( ! function_exists( 'wp_upload_dir' ) ) {
	/**
	 * Mock wp_upload_dir function.
	 *
	 * @return array
	 */
	function wp_upload_dir() {
		return [
			'path'    => '/tmp/uploads',
			'url'     => 'https://example.com/wp-content/uploads',
			'subdir'  => '',
			'basedir' => '/tmp/uploads',
			'baseurl' => 'https://example.com/wp-content/uploads',
			'error'   => false,
		];
	}
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
	/**
	 * Mock wp_mkdir_p function.
	 *
	 * @param string $target Directory to create.
	 * @return bool
	 */
	function wp_mkdir_p( $target ) {
		if ( file_exists( $target ) ) {
			return is_dir( $target );
		}
		return @mkdir( $target, 0755, true );
	}
}

if ( ! function_exists( 'register_activation_hook' ) ) {
	/**
	 * Mock register_activation_hook function.
	 *
	 * @param string   $file     Plugin file.
	 * @param callable $callback Callback function.
	 */
	function register_activation_hook( $file, $callback ) {
		// No-op for testing.
	}
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
	/**
	 * Mock register_deactivation_hook function.
	 *
	 * @param string   $file     Plugin file.
	 * @param callable $callback Callback function.
	 */
	function register_deactivation_hook( $file, $callback ) {
		// No-op for testing.
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	/**
	 * Mock plugin_dir_path function.
	 *
	 * @param string $file Plugin file.
	 * @return string
	 */
	function plugin_dir_path( $file ) {
		return dirname( $file ) . '/';
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	/**
	 * Mock plugin_dir_url function.
	 *
	 * @param string $file Plugin file.
	 * @return string
	 */
	function plugin_dir_url( $file ) {
		return 'https://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
}

if ( ! function_exists( 'plugin_basename' ) ) {
	/**
	 * Mock plugin_basename function.
	 *
	 * @param string $file Plugin file.
	 * @return string
	 */
	function plugin_basename( $file ) {
		return basename( dirname( $file ) ) . '/' . basename( $file );
	}
}

if ( ! function_exists( 'rest_url' ) ) {
	/**
	 * Mock rest_url function.
	 *
	 * @param string $path REST path.
	 * @return string
	 */
	function rest_url( $path = '' ) {
		return 'https://example.com/wp-json/' . ltrim( $path, '/' );
	}
}

// Initialize global mocks.
$GLOBALS['wp_options'] = [];
$GLOBALS['wp_actions'] = [];

