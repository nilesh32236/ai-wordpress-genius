<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Simulates a call to an AI model to get plugin code.
 *
 * @param string $plugin_description The user's description of the plugin.
 * @return string A JSON string containing the core PHP code for the plugin.
 */
function ai_wp_genius_get_simulated_plugin_ai_response( $plugin_description ) {
	// In a real implementation, the AI would generate this code based on the description.
	// For this simulation, we'll return a fixed shortcode implementation.
	$response = [
		'php_code_body' => "/**\n * The shortcode function.\n */\n" .
		"function ai_wp_genius_generated_shortcode() {\n" .
		"    // This is a simple example. A real AI could generate more complex code.\n" .
		"    return 'Hello from your AI-generated plugin!';\n" .
		"}\n\n" .
		"/**\n * Register the shortcode.\n */\n" .
		"function ai_wp_genius_register_generated_shortcode() {\n" .
		"    add_shortcode('ai_shortcode', 'ai_wp_genius_generated_shortcode');\n" .
		"}\n" .
		"add_action('init', 'ai_wp_genius_register_generated_shortcode');",
	];

	// Let's add a different response for a "year" shortcode to make it more dynamic
	if ( strpos( strtolower( $plugin_description ), 'year' ) !== false ) {
		$response['php_code_body'] = "/**\n * The shortcode function for the current year.\n */\n" .
		"function ai_wp_genius_year_shortcode() {\n" .
		"    return date('Y');\n" .
		"}\n\n" .
		"/**\n * Register the shortcode.\n */\n" .
		"function ai_wp_genius_register_year_shortcode() {\n" .
		"    add_shortcode('year', 'ai_wp_genius_year_shortcode');\n" .
		"}\n" .
		"add_action('init', 'ai_wp_genius_register_year_shortcode');";
	}

	return json_encode( $response );
}

/**
 * Handles the AI plugin creation logic.
 */
function ai_wp_genius_handle_plugin_creation() {
	if ( ! isset( $_POST['submit_plugin'] ) || ! isset( $_POST['plugin_name'] ) ) {
		return;
	}

	if ( ! isset( $_POST['ai_wp_genius_plugin_nonce'] ) || ! wp_verify_nonce( $_POST['ai_wp_genius_plugin_nonce'], 'ai_wp_genius_create_plugin' ) ) {
		wp_die( __( 'Security check failed.', 'ai-wordpress-genius' ) );
	}

	if ( ! current_user_can( 'install_plugins' ) ) {
		wp_die( __( 'You do not have permission to install plugins.', 'ai-wordpress-genius' ) );
	}

	$plugin_name = sanitize_text_field( $_POST['plugin_name'] );
	$plugin_slug = sanitize_title( $plugin_name );
	$plugin_description = sanitize_textarea_field( $_POST['plugin_description'] );
	$plugin_dir_path = WP_PLUGIN_DIR . '/' . $plugin_slug;
	$plugin_file_path = $plugin_dir_path . '/' . $plugin_slug . '.php';

	if ( file_exists( $plugin_dir_path ) ) {
		add_action( 'admin_notices', function () use ( $plugin_name ) {
			echo '<div class="notice notice-warning is-dismissible"><p>' . sprintf( __( 'A plugin folder named "%s" already exists.', 'ai-wordpress-genius' ), esc_html( $plugin_slug ) ) . '</p></div>';
		} );
		return;
	}

	// Initialize WP_Filesystem
	global $wp_filesystem;
	if ( empty( $wp_filesystem ) ) {
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();
	}

	if ( ! $wp_filesystem->mkdir( $plugin_dir_path ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Could not create plugin directory.', 'ai-wordpress-genius' ) . '</p></div>';
		} );
		return;
	}

	// Get the AI-generated code
	$ai_response_json = ai_wp_genius_get_simulated_plugin_ai_response( $plugin_description );
	$ai_response = json_decode( $ai_response_json, true );
	$php_code_body = $ai_response['php_code_body'] ?? '';

	if ( empty( $php_code_body ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error is-dismissible"><p>' . __( 'AI failed to generate valid code.', 'ai-wordpress-genius' ) . '</p></div>';
		} );
		$wp_filesystem->rmdir( $plugin_dir_path, true ); // Clean up
		return;
	}

	// Construct the full plugin file content
	$plugin_header = sprintf(
		"<?php\n" .
		"/**\n" .
		" * Plugin Name:       %s\n" .
		" * Description:       %s\n" .
		" * Version:           1.0.0\n" .
		" * Author:            AI WordPress Genius\n" .
		" */\n\n" .
		"// If this file is called directly, abort.\n" .
		"if ( ! defined( 'WPINC' ) ) {\n" .
		"    die;\n" .
		"}\n\n",
		$plugin_name,
		$plugin_description
	);

	$full_plugin_code = $plugin_header . $php_code_body;

	if ( ! $wp_filesystem->put_contents( $plugin_file_path, $full_plugin_code, FS_CHMOD_FILE ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Error writing plugin file.', 'ai-wordpress-genius' ) . '</p></div>';
		} );
		$wp_filesystem->rmdir( $plugin_dir_path, true ); // Clean up
		return;
	}

	// Success!
	add_action( 'admin_notices', function () use ( $plugin_name ) {
		$plugins_page_url = admin_url( 'plugins.php' );
		echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Successfully created the "%s" plugin. You can now <a href="%s">activate it from the Plugins page</a>.', 'ai-wordpress-genius' ), esc_html( $plugin_name ), esc_url( $plugins_page_url ) ) . '</p></div>';
	} );
}
add_action( 'admin_init', 'ai_wp_genius_handle_plugin_creation' );
