<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Generates the prompt for the AI theme generator.
 *
 * @param string $theme_description The user's description of the theme.
 * @return string The generated prompt.
 */
function ai_wp_genius_generate_theme_prompt( $theme_description ) {
	return sprintf(
		"You are an expert WordPress theme developer specializing in modern block themes. Your task is to generate the complete structure of a WordPress block theme based on a user's description.

You MUST respond with ONLY a valid JSON object and nothing else. Do not include ```json markdown delimiters or any explanatory text before or after the JSON. The JSON object must have the following structure:
{
  \"settings\": { ... },
  \"styles\": { ... },
  \"templates\": {
    \"index\": \"HTML content for index.html\"
  },
  \"parts\": {
    \"header\": \"HTML content for parts/header.html\",
    \"footer\": \"HTML content for parts/footer.html\"
  }
}

The \"settings\" and \"styles\" objects must conform to the structure of a WordPress `theme.json` file (version 2).
The \"templates\" and \"parts\" values must be strings containing the full HTML block markup for the respective template files.

User's theme description: \"%s\"

Based on this description, generate the complete JSON object. The HTML templates should be simple but functional, including elements like `wp:site-title`, `wp:navigation`, `wp:query-loop`, `wp:post-title`, `wp:post-content`, and `wp:template-part`.",
		$theme_description
	);
}


/**
 * Handles the AI theme creation logic.
 */
function ai_wp_genius_handle_theme_creation() {
	if ( ! isset( $_POST['submit_theme'] ) || ! isset( $_POST['theme_name'] ) ) {
		return;
	}

	if ( ! isset( $_POST['ai_wp_genius_theme_nonce'] ) || ! wp_verify_nonce( $_POST['ai_wp_genius_theme_nonce'], 'ai_wp_genius_create_theme' ) ) {
		wp_die( __( 'Security check failed.', 'ai-wordpress-genius' ) );
	}

	if ( ! current_user_can( 'install_themes' ) ) {
		wp_die( __( 'You do not have permission to install themes.', 'ai-wordpress-genius' ) );
	}

	$theme_name = sanitize_text_field( $_POST['theme_name'] );
	$theme_slug = sanitize_title( $theme_name );
	$theme_description = sanitize_textarea_field( $_POST['theme_description'] );
	$theme_path = get_theme_root() . '/' . $theme_slug;

	if ( file_exists( $theme_path ) ) {
		add_action( 'admin_notices', function () use ( $theme_name ) {
			echo '<div class="notice notice-warning is-dismissible"><p>' . sprintf( __( 'A theme named "%s" already exists.', 'ai-wordpress-genius' ), esc_html( $theme_name ) ) . '</p></div>';
		} );
		return;
	}

	// Generate the prompt and get the AI response.
	$prompt = ai_wp_genius_generate_theme_prompt( $theme_description );
	$ai_response_json = ai_wp_genius_get_ai_response( $prompt );

	// Handle errors from the AI service.
	if ( is_wp_error( $ai_response_json ) ) {
		add_action( 'admin_notices', function () use ( $ai_response_json ) {
			echo '<div class="notice notice-error is-dismissible"><p><strong>' . __( 'AI Service Error:', 'ai-wordpress-genius' ) . '</strong> ' . esc_html( $ai_response_json->get_error_message() ) . '</p></div>';
		} );
		return;
	}

	$ai_response = json_decode( $ai_response_json, true );

	if ( ! $ai_response || ! isset($ai_response['settings']) || ! isset($ai_response['templates']['index']) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error is-dismissible"><p>' . __( 'The AI returned an invalid or incomplete structure for the theme. Please try again with a different prompt.', 'ai-wordpress-genius' ) . '</p></div>';
		} );
		return;
	}

	// Initialize WP_Filesystem
	global $wp_filesystem;
	if ( empty( $wp_filesystem ) ) {
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();
	}

	if ( ! $wp_filesystem->mkdir( $theme_path ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Could not create theme directory.', 'ai-wordpress-genius' ) . '</p></div>';
		} );
		return;
	}

	// --- Create files ---
	$files_to_create = [];
	$files_to_create['style.css'] = "/*\n Theme Name: {$theme_name}\n Author: AI WordPress Genius\n Version: 1.0\n*/";

	$theme_json_content = [
		'version' => 2,
		'$schema' => 'https://schemas.wp.org/wp/6.2/theme.json',
		'settings' => $ai_response['settings'],
		'styles' => $ai_response['styles'],
	];
	$files_to_create['theme.json'] = json_encode( $theme_json_content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

	$wp_filesystem->mkdir( $theme_path . '/templates' );
	$files_to_create['templates/index.html'] = $ai_response['templates']['index'];

	$wp_filesystem->mkdir( $theme_path . '/parts' );
	if ( isset( $ai_response['parts'] ) && is_array( $ai_response['parts'] ) ) {
		foreach ( $ai_response['parts'] as $part_name => $part_content ) {
			$files_to_create["parts/{$part_name}.html"] = $part_content;
		}
	}

	$all_files_written = true;
	foreach ( $files_to_create as $filename => $content ) {
		if ( ! $wp_filesystem->put_contents( "{$theme_path}/{$filename}", $content, FS_CHMOD_FILE ) ) {
			$all_files_written = false;
			break;
		}
	}

	if ( ! $all_files_written ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Error writing theme files. The process has been aborted.', 'ai-wordpress-genius' ) . '</p></div>';
		} );
		$wp_filesystem->rmdir( $theme_path, true );
		return;
	}

	add_action( 'admin_notices', function () use ( $theme_name ) {
		$themes_page_url = admin_url( 'themes.php' );
		echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Successfully created the "%s" theme. You can now <a href="%s">preview and activate it</a>.', 'ai-wordpress-genius' ), esc_html( $theme_name ), esc_url( $themes_page_url ) ) . '</p></div>';
	} );
}
add_action( 'admin_init', 'ai_wp_genius_handle_theme_creation' );
