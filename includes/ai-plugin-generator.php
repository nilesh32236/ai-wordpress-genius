<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Generates the prompt for the AI plugin generator.
 *
 * @param string $plugin_description The user's description of the plugin.
 * @return string The generated prompt.
 */
function ai_wp_genius_generate_plugin_prompt( $plugin_description ) {
	return sprintf(
		"You are an expert WordPress plugin developer. Your task is to generate the PHP code for a simple WordPress plugin based on a user's description.

You MUST respond with ONLY the raw PHP code for the plugin's functionality. Do NOT include the opening `<?php` tag, the plugin header comments, or any security checks like `if ( ! defined( 'WPINC' ) )`, as these will be added automatically. Do not include any explanatory text before or after the code.

User's plugin description: \"%s\"

Based on this description, generate only the necessary PHP code body. For example, if the user asks for \"a shortcode [year] that displays the current year\", you should respond with:
function my_current_year_shortcode() {
    return date('Y');
}
add_shortcode('year', 'my_current_year_shortcode');",
		$plugin_description
	);
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

	// Generate the prompt and get the AI response.
	$prompt = ai_wp_genius_generate_plugin_prompt( $plugin_description );
	$php_code_body = ai_wp_genius_get_ai_response( $prompt );

	// Handle errors from the AI service.
	if ( is_wp_error( $php_code_body ) ) {
		add_action( 'admin_notices', function () use ( $php_code_body ) {
			echo '<div class="notice notice-error is-dismissible"><p><strong>' . __( 'AI Service Error:', 'ai-wordpress-genius' ) . '</strong> ' . esc_html( $php_code_body->get_error_message() ) . '</p></div>';
		} );
		return;
	}

	if ( empty( $php_code_body ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error is-dismissible"><p>' . __( 'The AI returned empty code. Please try again with a different prompt.', 'ai-wordpress-genius' ) . '</p></div>';
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

	add_action( 'admin_notices', function () use ( $plugin_name ) {
		$plugins_page_url = admin_url( 'plugins.php' );
		echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Successfully created the "%s" plugin. You can now <a href="%s">activate it from the Plugins page</a>.', 'ai-wordpress-genius' ), esc_html( $plugin_name ), esc_url( $plugins_page_url ) ) . '</p></div>';
	} );
}
add_action( 'admin_init', 'ai_wp_genius_handle_plugin_creation' );
