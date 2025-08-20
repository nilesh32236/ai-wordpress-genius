<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Include the Markdown parsing library.
require_once AI_WP_GENIUS_PLUGIN_DIR . 'includes/lib/Parsedown.php';

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
