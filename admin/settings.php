<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register the settings for the AI Genius plugin.
 */
function ai_wp_genius_register_settings() {
    register_setting(
        'ai_wp_genius_settings_group', // Option group
        'ai_wp_genius_api_key',        // Option name
        [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ]
    );

    add_settings_section(
        'ai_wp_genius_api_settings_section',      // ID
        __( 'API Settings', 'ai-wordpress-genius' ), // Title
        'ai_wp_genius_api_settings_section_callback', // Callback
        'ai-wordpress-genius-settings'                // Page
    );

    add_settings_field(
        'ai_wp_genius_api_key_field',             // ID
        __( 'Gemini API Key', 'ai-wordpress-genius' ), // Title
        'ai_wp_genius_api_key_field_callback',    // Callback
        'ai-wordpress-genius-settings',           // Page
        'ai_wp_genius_api_settings_section'       // Section
    );
}
add_action( 'admin_init', 'ai_wp_genius_register_settings' );

/**
 * Callback for the API settings section.
 */
function ai_wp_genius_api_settings_section_callback() {
    echo '<p>' . __( 'Enter your API key for the AI service below. Currently, only Google Gemini is supported.', 'ai-wordpress-genius' ) . '</p>';
}

/**
 * Callback for the API key field.
 */
function ai_wp_genius_api_key_field_callback() {
    $api_key = get_option( 'ai_wp_genius_api_key' );
    echo '<input type="password" name="ai_wp_genius_api_key" value="' . esc_attr( $api_key ) . '" class="regular-text" />';
}

/**
 * Render the settings page.
 */
function ai_wp_genius_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'ai_wp_genius_settings_group' );
            do_settings_sections( 'ai-wordpress-genius-settings' );
            submit_button( __( 'Save Settings', 'ai-wordpress-genius' ) );
            ?>
        </form>
    </div>
    <?php
}
