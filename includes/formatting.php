<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Include the Markdown parsing library, but only if the class doesn't already exist.
if ( ! class_exists( 'Parsedown' ) ) {
	require_once AI_WP_GENIUS_PLUGIN_DIR . 'includes/lib/Parsedown.php';
}

/**
 * Cleans the raw JSON string from the AI and decodes it.
 *
 * @param string $raw_json The raw string from the AI, which might be wrapped in markdown fences.
 * @return array|null The decoded JSON as an associative array, or null on failure.
 */
function ai_wp_genius_clean_and_decode_json( $raw_json ) {
    // 1. Clean up common AI artifacts like markdown code block delimiters.
    $cleaned_json = preg_replace( '/^```(json)?\s*/i', '', $raw_json );
    $cleaned_json = preg_replace( '/\s*```$/i', '', $cleaned_json );
    $cleaned_json = trim( $cleaned_json );

    // 2. Decode the cleaned JSON string.
    $decoded = json_decode( $cleaned_json, true );

    // 3. Check for errors and return null if decoding failed.
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return null;
    }

    return $decoded;
}

/**
 * Cleans, parses, and sanitizes the raw text response from the AI.
 *
 * @param string $raw_text The raw text from the AI service.
 * @return string The formatted and sanitized HTML.
 */
function ai_wp_genius_format_ai_response( $raw_text ) {
    // 1. Clean up common AI artifacts like markdown code block delimiters.
    $cleaned_text = preg_replace( '/^```(json|php|html)?\s*/i', '', $raw_text );
    $cleaned_text = preg_replace( '/\s*```$/i', '', $cleaned_text );
    $cleaned_text = trim( $cleaned_text );

    // 2. Parse the text with Parsedown.
    $Parsedown = new Parsedown();
    $Parsedown->setSafeMode(true); // Important for security.
    $html = $Parsedown->text( $cleaned_text );

    // 3. Sanitize the generated HTML with WordPress's standard post sanitizer.
    $safe_html = wp_kses_post( $html );

    return $safe_html;
}
