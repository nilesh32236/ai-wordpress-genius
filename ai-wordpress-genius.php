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

/**
 * The code that runs during plugin activation.
 * This action is scheduled to run only once, when the plugin is activated.
 */
function ai_wp_genius_activate_plugin() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'ai_genius_session_history';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		session_id varchar(255) NOT NULL,
		role varchar(10) NOT NULL,
		content longtext NOT NULL,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY session_id (session_id(191)),
		KEY created_at (created_at)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}
register_activation_hook( __FILE__, 'ai_wp_genius_activate_plugin' );

/**
 * The code that runs when the plugin is uninstalled.
 * This will delete the session history table.
 */
function ai_wp_genius_uninstall_plugin() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'ai_genius_session_history';
	$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
}
register_uninstall_hook( __FILE__, 'ai_wp_genius_uninstall_plugin' );

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

// Include the settings page logic
require_once AI_WP_GENIUS_PLUGIN_DIR . 'admin/settings.php';

// Include the AI service layer
require_once AI_WP_GENIUS_PLUGIN_DIR . 'includes/ai-service.php';

// Include the AI code editor logic
require_once AI_WP_GENIUS_PLUGIN_DIR . 'includes/ai-code-editor.php';

// Include the formatting utility
require_once AI_WP_GENIUS_PLUGIN_DIR . 'includes/formatting.php';

// Include the session manager
require_once AI_WP_GENIUS_PLUGIN_DIR . 'includes/session-manager.php';
