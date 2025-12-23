<?php
/**
 * PHPUnit Bootstrap File
 *
 * @package AI_Author_For_Websites
 */

// Define test constants.
define( 'AIAUTHOR_TESTING', true );
define( 'ABSPATH', __DIR__ . '/stubs/' );
define( 'AIAUTHOR_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'AIAUTHOR_PLUGIN_URL', 'https://example.com/wp-content/plugins/ai-author-for-websites/' );
define( 'AIAUTHOR_PLUGIN_BASENAME', 'ai-author-for-websites/ai-author-for-websites.php' );
define( 'AIAUTHOR_PLUGIN_FILE', dirname( __DIR__ ) . '/ai-author-for-websites.php' );
define( 'AIAUTHOR_VERSION', '1.0.0' );

// Load Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load WordPress function stubs.
require_once __DIR__ . '/stubs/wordpress-functions.php';

