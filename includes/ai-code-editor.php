<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Handles the cancellation of a modification request, cleaning up the transient.
 */
function ai_wp_genius_handle_cancel_modification() {
    if ( isset( $_GET['page'] ) && $_GET['page'] === 'ai-wordpress-genius' && isset( $_GET['ai_action'] ) && $_GET['ai_action'] === 'cancel_modification' ) {
        delete_transient( 'ai_wp_genius_modification_request' );
        wp_safe_redirect( admin_url( 'admin.php?page=ai-wordpress-genius' ) );
        exit;
    }
}
add_action( 'admin_init', 'ai_wp_genius_handle_cancel_modification' );

/**
 * Generates the prompt for the AI code editor.
 *
 * @param string $instruction The user's instruction.
 * @param string $original_content The original content of the file.
 * @return string The generated prompt.
 */
function ai_wp_genius_generate_code_modification_prompt( $instruction, $original_content ) {
	return sprintf(
		"You are an expert WordPress developer. Your task is to modify a PHP file from a WordPress plugin or theme based on a user's instruction.

The user wants to make the following change: \"%s\"

Here is the entire original content of the PHP file:
```php
%s
```

You MUST respond with ONLY a valid JSON object and nothing else. Do not include any explanatory text before or after the JSON. The JSON object must have the following structure:
{
  \"explanation\": \"A clear explanation of the changes you made, using Markdown for formatting.\",
  \"code\": \"The complete, modified content of the PHP file.\"
}",
		$instruction,
		$original_content
	);
}

/**
 * Handles the initial request to modify code.
 */
function ai_wp_genius_handle_code_modification() {
	if ( ! isset( $_POST['submit_modify_plugin'] ) && ! isset( $_POST['submit_modify_theme'] ) ) {
		return;
	}

	$modify_type = sanitize_text_field( $_POST['modify_type'] );
	$target_slug = sanitize_text_field( $_POST['modify_target'] );
	$instruction = sanitize_textarea_field( $_POST['instruction'] );
	$nonce_action = 'ai_wp_genius_modify_' . $modify_type;
	$nonce_name = 'ai_wp_genius_modify_' . $modify_type . '_nonce';

	if ( ! isset( $_POST[$nonce_name] ) || ! wp_verify_nonce( $_POST[$nonce_name], $nonce_action ) ) {
		wp_die( __( 'Security check failed.', 'ai-wordpress-genius' ) );
	}

	if ( ! current_user_can( 'edit_plugins' ) && ! current_user_can( 'edit_themes' ) ) {
		wp_die( __( 'You do not have permission to edit files.', 'ai-wordpress-genius' ) );
	}

	$target_file = '';
	if ( $modify_type === 'plugin' ) {
		$target_file = WP_PLUGIN_DIR . '/' . $target_slug;
	} elseif ( $modify_type === 'theme' ) {
		$target_file = get_theme_root() . '/' . $target_slug . '/functions.php';
	}

	if ( ! $target_file || ! file_exists( $target_file ) ) {
		add_action( 'admin_notices', function() { echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Target file not found.', 'ai-wordpress-genius' ) . '</p></div>'; });
		return;
	}

	global $wp_filesystem;
	if ( empty( $wp_filesystem ) ) {
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();
	}

	$original_content = $wp_filesystem->get_contents( $target_file );

	// Start a new conversation session
	$session_id = ai_wp_genius_create_new_session_id();
	$prompt = ai_wp_genius_generate_code_modification_prompt( $instruction, $original_content );
	$ai_response_json = ai_wp_genius_get_ai_response( $prompt, $session_id );

	if ( is_wp_error( $ai_response_json ) ) {
		add_action( 'admin_notices', function() use ( $ai_response_json ) { echo '<div class="notice notice-error is-dismissible"><p><strong>' . __( 'AI Service Error:', 'ai-wordpress-genius' ) . '</strong> ' . esc_html( $ai_response_json->get_error_message() ) . '</p></div>'; });
		return;
	}

	$decoded_response = ai_wp_genius_clean_and_decode_json( $ai_response_json );
	if ( ! $decoded_response || ! isset( $decoded_response['code'] ) || ! isset( $decoded_response['explanation'] ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error is-dismissible"><p>' . __( 'The AI returned an invalid or unexpected JSON format. Please try again.', 'ai-wordpress-genius' ) . '</p></div>';
		} );
		return;
	}

	// Create a transient to hold the modification request for approval
	$key = md5( uniqid( rand(), true ) );
	$modification_request = [
		'key'              => $key,
		'session_id'       => $session_id,
		'full_path'        => $target_file,
		'relative_path'    => str_replace( WP_CONTENT_DIR, '', $target_file ),
		'original_content' => $original_content,
		'new_content'      => $decoded_response['code'],
		'explanation'      => $decoded_response['explanation'],
	];
	set_transient( 'ai_wp_genius_modification_request', $modification_request, HOUR_IN_SECONDS );

	wp_safe_redirect( admin_url( 'admin.php?page=ai-wordpress-genius' ) );
	exit;
}
add_action( 'admin_init', 'ai_wp_genius_handle_code_modification' );

/**
 * Handles the approval of AI-generated code changes.
 */
function ai_wp_genius_handle_approve_changes() {
	if ( ! isset( $_POST['submit_approve_changes'] ) ) {
		return;
	}

	if ( ! isset( $_POST['ai_wp_genius_approve_changes_nonce'] ) || ! wp_verify_nonce( $_POST['ai_wp_genius_approve_changes_nonce'], 'ai_wp_genius_approve_changes' ) ) {
		wp_die( __( 'Security check failed.', 'ai-wordpress-genius' ) );
	}

	if ( ! current_user_can( 'edit_plugins' ) && ! current_user_can( 'edit_themes' ) ) {
		wp_die( __( 'You do not have permission to edit files.', 'ai-wordpress-genius' ) );
	}

	$modification_request = get_transient( 'ai_wp_genius_modification_request' );

	if ( ! $modification_request || $modification_request['key'] !== $_POST['modification_key'] ) {
		add_action( 'admin_notices', function() { echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Modification request not found or expired. Please try again.', 'ai-wordpress-genius' ) . '</p></div>'; });
		return;
	}

	global $wp_filesystem;
	if ( empty( $wp_filesystem ) ) {
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();
	}

	if ( $wp_filesystem->put_contents( $modification_request['full_path'], $modification_request['new_content'], FS_CHMOD_FILE ) ) {
		add_action( 'admin_notices', function() { echo '<div class="notice notice-success is-dismissible"><p>' . __( 'File successfully updated!', 'ai-wordpress-genius' ) . '</p></div>'; });
	} else {
		add_action( 'admin_notices', function() { echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Could not write to file. Please check file permissions.', 'ai-wordpress-genius' ) . '</p></div>'; });
	}

	delete_transient( 'ai_wp_genius_modification_request' );
}
add_action( 'admin_init', 'ai_wp_genius_handle_approve_changes' );

/**
 * Handles follow-up instructions for an ongoing code modification session.
 */
function ai_wp_genius_handle_follow_up() {
	if ( ! isset( $_POST['submit_follow_up'] ) ) {
		return;
	}

	if ( ! isset( $_POST['ai_wp_genius_follow_up_nonce'] ) || ! wp_verify_nonce( $_POST['ai_wp_genius_follow_up_nonce'], 'ai_wp_genius_follow_up' ) ) {
		wp_die( __( 'Security check failed.', 'ai-wordpress-genius' ) );
	}

	if ( ! current_user_can( 'edit_plugins' ) && ! current_user_can( 'edit_themes' ) ) {
		wp_die( __( 'You do not have permission to edit files.', 'ai-wordpress-genius' ) );
	}

	$session_id = sanitize_text_field( $_POST['session_id'] );
	$instruction = sanitize_textarea_field( $_POST['follow_up_instruction'] );
	$modification_request = get_transient( 'ai_wp_genius_modification_request' );

	if ( ! $modification_request || $modification_request['session_id'] !== $session_id ) {
		add_action( 'admin_notices', function() { echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Modification session not found or expired. Please start again.', 'ai-wordpress-genius' ) . '</p></div>'; });
		return;
	}

	// The "original" content for this turn is the AI's last output.
	$current_code = $modification_request['new_content'];
	$prompt = ai_wp_genius_generate_code_modification_prompt( $instruction, $current_code );
	$ai_response_json = ai_wp_genius_get_ai_response( $prompt, $session_id );

	if ( is_wp_error( $ai_response_json ) ) {
		add_action( 'admin_notices', function() use ( $ai_response_json ) { echo '<div class="notice notice-error is-dismissible"><p><strong>' . __( 'AI Service Error:', 'ai-wordpress-genius' ) . '</strong> ' . esc_html( $ai_response_json->get_error_message() ) . '</p></div>'; });
		// We need to re-save the transient so the user can try again.
		set_transient( 'ai_wp_genius_modification_request', $modification_request, HOUR_IN_SECONDS );
		wp_safe_redirect( admin_url( 'admin.php?page=ai-wordpress-genius' ) );
		exit;
	}

	$decoded_response = ai_wp_genius_clean_and_decode_json( $ai_response_json );
	if ( ! $decoded_response || ! isset( $decoded_response['code'] ) || ! isset( $decoded_response['explanation'] ) ) {
		add_action( 'admin_notices', function () { echo '<div class="notice notice-error is-dismissible"><p>' . __( 'The AI returned an invalid or unexpected JSON format. Please try again.', 'ai-wordpress-genius' ) . '</p></div>';	} );
		set_transient( 'ai_wp_genius_modification_request', $modification_request, HOUR_IN_SECONDS );
		wp_safe_redirect( admin_url( 'admin.php?page=ai-wordpress-genius' ) );
		exit;
	}

	// Update the transient with the new response, but keep the original content.
	$modification_request['new_content'] = $decoded_response['code'];
	$modification_request['explanation'] = $decoded_response['explanation'];
	set_transient( 'ai_wp_genius_modification_request', $modification_request, HOUR_IN_SECONDS );

	wp_safe_redirect( admin_url( 'admin.php?page=ai-wordpress-genius' ) );
	exit;
}
add_action( 'admin_init', 'ai_wp_genius_handle_follow_up' );
