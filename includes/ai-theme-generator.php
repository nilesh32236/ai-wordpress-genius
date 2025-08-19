<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Simulates a call to an AI model to get theme structure.
 *
 * In a real implementation, this would make an API call to an AI service.
 * The prompt would include the user's description.
 *
 * @param string $theme_description The user's description of the theme.
 * @return string A JSON string representing the theme structure.
 */
function ai_wp_genius_get_simulated_ai_response( $theme_description ) {
	// For now, we ignore the description and return a hardcoded "dark theme" response.
	$response = [
		'settings' => [
			'color' => [
				'palette' => [
					[ 'slug' => 'background', 'color' => '#1a1a1a', 'name' => 'Background' ],
					[ 'slug' => 'foreground', 'color' => '#f0f0f0', 'name' => 'Foreground' ],
					[ 'slug' => 'primary', 'color' => '#00bfff', 'name' => 'Primary' ],
				],
			],
			'layout' => [
				'contentSize' => '700px',
				'wideSize' => '1100px',
			],
		],
		'styles' => [
			'color' => [
				'background' => 'var(--wp--preset--color--background)',
				'text' => 'var(--wp--preset--color--foreground)',
			],
			'elements' => [
				'link' => [
					'color' => [
						'text' => 'var(--wp--preset--color--primary)',
					],
				],
			],
		],
		'templates' => [
			'index' => '<!-- wp:template-part {"slug":"header","tagName":"header"} /--><!-- wp:group {"tagName":"main","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} --><!-- wp:query-loop --><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --><!-- /wp:query-loop --><!-- /wp:group --><!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->',
			'parts' => [
				'header' => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"flex","justifyContent":"space-between"}} --><!-- wp:site-title /--><!-- wp:navigation /--><!-- /wp:group -->',
				'footer' => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"flex","justifyContent":"center"}} --><!-- wp:paragraph {"align":"center"} -->Proudly powered by AI WordPress Genius.<!-- /wp:paragraph --><!-- /wp:group -->',
			],
		],
	];

	return json_encode( $response );
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

	// Initialize WP_Filesystem
	global $wp_filesystem;
	if ( empty( $wp_filesystem ) ) {
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();
	}

	// Create the theme directory
	if ( ! $wp_filesystem->mkdir( $theme_path ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Could not create theme directory.', 'ai-wordpress-genius' ) . '</p></div>';
		} );
		return;
	}

	// Simulate AI call and get theme structure
	$ai_response_json = ai_wp_genius_get_simulated_ai_response( $theme_description );
	$ai_response = json_decode( $ai_response_json, true );

	if ( ! $ai_response ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error is-dismissible"><p>' . __( 'AI failed to generate a valid theme structure.', 'ai-wordpress-genius' ) . '</p></div>';
		} );
		$wp_filesystem->rmdir( $theme_path, true ); // Clean up
		return;
	}

	// --- Create files ---
	$files_to_create = [];

	// style.css
	$files_to_create['style.css'] = "/*\n Theme Name: {$theme_name}\n Author: AI WordPress Genius\n Version: 1.0\n*/";

	// theme.json
	$theme_json_content = [
		'version' => 2,
		'$schema' => 'https://schemas.wp.org/wp/6.2/theme.json',
		'settings' => $ai_response['settings'],
		'styles' => $ai_response['styles'],
	];
	$files_to_create['theme.json'] = json_encode( $theme_json_content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

	// templates/index.html
	$wp_filesystem->mkdir( $theme_path . '/templates' );
	$files_to_create['templates/index.html'] = $ai_response['templates']['index'];

	// parts/header.html and parts/footer.html
	$wp_filesystem->mkdir( $theme_path . '/parts' );
	foreach ( $ai_response['templates']['parts'] as $part_name => $part_content ) {
		$files_to_create["parts/{$part_name}.html"] = $part_content;
	}

	// Write all files
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
		$wp_filesystem->rmdir( $theme_path, true ); // Clean up
		return;
	}

	// Success!
	add_action( 'admin_notices', function () use ( $theme_name ) {
		$themes_page_url = admin_url( 'themes.php' );
		echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Successfully created the "%s" theme. You can now <a href="%s">preview and activate it</a>.', 'ai-wordpress-genius' ), esc_html( $theme_name ), esc_url( $themes_page_url ) ) . '</p></div>';
	} );
}
add_action( 'admin_init', 'ai_wp_genius_handle_theme_creation' );
