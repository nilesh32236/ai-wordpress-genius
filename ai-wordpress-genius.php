<?php
/**
 * Plugin Name:       AI WordPress Genius
 * Plugin URI:        https://example.com/
 * Description:       A WordPress plugin using AI to generate sites, plugins, and themes, with Gutenberg block theme support and automatic child theme creation.
 * Version:           1.0.0
 * Author:            Jules
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-wordpress-genius
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants
define( 'AI_WP_GENIUS_VERSION', '1.0.0' );
define( 'AI_WP_GENIUS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AI_WP_GENIUS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include the admin menu
require_once AI_WP_GENIUS_PLUGIN_DIR . 'admin/menu.php';

// Include the child theme generator logic
require_once AI_WP_GENIUS_PLUGIN_DIR . 'includes/child-theme-generator.php';

// Include the AI theme generator logic
require_once AI_WP_GENIUS_PLUGIN_DIR . 'includes/ai-theme-generator.php';

// Include the AI plugin generator logic
require_once AI_WP_GENIUS_PLUGIN_DIR . 'includes/ai-plugin-generator.php';

// Include the AI bug finder logic
require_once AI_WP_GENIUS_PLUGIN_DIR . 'includes/ai-bug-finder.php';
