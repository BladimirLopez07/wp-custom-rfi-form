<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * ---------------------------------------------------------------------------
 * Settings
 * ---------------------------------------------------------------------------
 */

/**
 * Return default settings for the plugin.
 *
 * @return array Default settings.
 */
function custom_rfi_form_default_settings() {
    return array(
        // General Settings
        'debug_mode'             => 'false',
        'custom_form_type'       => '1', // '1' or '2'
        'form_track_id'          => '',
        'global_feature_id'      => '',
        'form_name'              => 'Custom RFI Form',
        'server_switch'          => 'qa',
        'form_title'             => 'Request Information',
        'program_before_campus'  => 'false', // 'true' or 'false'
        'campus_type'            => 'multiple', // 'single' or 'multiple'
        'campus_id'              => '',

        // Form Fields (default checked fields)
        'form_fields'            => array( 'fname', 'lname', 'email', 'phone', 'zip', 'campus' ),
        'start_term'             => "Fall 2024 : fall-2024\nSpring 2025 : spring-2025\nFall 2025 : fall-2025",
        'highest_education'      => "High School Diploma : high-school-diploma\nSome College : some-college\nAssociate's Degree : associate-degree\nBachelor's Degree : bachelor-degree\nMaster's Degree : master-degree\nDoctorate : doctorate\nOther : other",
        'program_option_group'   => 'false', // 'true' or 'false'
        'opt_group_order'        => array(),
        'disclaimer'             => 'I acknowledge that, by clicking the SUBMIT button above, I consent to representatives of SCHOOL NAME contacting me about educational opportunities via email, text, or phone, at the phone number above, including my mobile phone, using an automated dialer, or pre-recorded message. Message and data rates may apply. I understand that my consent is not a requirement for enrollment, and I may withdraw my consent at any time.',
        'submit_button_text'     => 'Submit',
        'thank_you_url_type'     => 'internal',
        'internal_thank_you_url' => '', // Empty falls back to the home page
        'external_thank_you_url' => '',
        'return_lead_id'         => array( 'lead_id' ),
        'honey_pot_field'        => 'false', // 'true' or 'false'

        // API Endpoints (can be overridden by constants, see custom_rfi_form_endpoint_constants())
        'api_url_prod'           => '',
        'api_url_qa'             => '',
        'api_url_stage'          => '',
        'postal_lookup_url'      => '',
        'trackid_check_url'      => '',

        // Style Settings
        'color_scheme'           => custom_rfi_form_default_colors(),
    );
}

/**
 * Get the plugin saved settings merged over the defaults.
 *
 * @return array The plugin settings.
 */
function custom_rfi_form_get_settings() {
    $saved_settings = get_option( 'custom_rfi_form_settings', array() );

    return wp_parse_args( is_array( $saved_settings ) ? $saved_settings : array(), custom_rfi_form_default_settings() );
}

/**
 * Activation: persist defaults for any setting that has not been saved yet.
 *
 * @return void
 */
function custom_rfi_form_activate() {
    update_option( 'custom_rfi_form_settings', custom_rfi_form_get_settings() );
}

/**
 * Return default colors.
 *
 * @return array
 */
function custom_rfi_form_default_colors() {
    return array(
        'formBackground'   => '#F6F7F2',
        'progressBarColor' => '#3d3f45',
        'formTextColor'    => '#3d3f45',
        'formHeadingColor' => '#333333',
        'errorColor'       => '#d40001',
        'buttonBackground' => '#F0F0F0',
        'buttonBorder'     => '#3d3f45',
        'buttonTextColor'  => '#3d3f45',
    );
}

/**
 * Capability required to manage the plugin settings and clear the API cache.
 *
 * @return string
 */
function custom_rfi_form_settings_capability() {
    return apply_filters( 'custom_rfi_form_settings_capability', 'manage_options' );
}

/*
 * ---------------------------------------------------------------------------
 * Partner lead API endpoints
 * ---------------------------------------------------------------------------
 */

/**
 * Endpoint settings and the wp-config.php constant that overrides each one.
 *
 * Lead data is sent to these URLs, so they can be locked in wp-config.php,
 * out of reach of anyone with wp-admin access.
 *
 * @return array Setting key => constant name.
 */
function custom_rfi_form_endpoint_constants() {
    return array(
        'api_url_prod'      => 'CUSTOM_RFI_FORM_API_URL_PROD',
        'api_url_qa'        => 'CUSTOM_RFI_FORM_API_URL_QA',
        'api_url_stage'     => 'CUSTOM_RFI_FORM_API_URL_STAGE',
        'postal_lookup_url' => 'CUSTOM_RFI_FORM_POSTAL_LOOKUP_URL',
        'trackid_check_url' => 'CUSTOM_RFI_FORM_TRACKID_CHECK_URL',
    );
}

/**
 * Validate an endpoint URL.
 *
 * HTTPS only (staging may use HTTP for internal hosts), with a host and no
 * credentials, query string or fragment.
 *
 * @param string $url        URL to validate.
 * @param bool   $allow_http Whether plain HTTP is allowed.
 * @return string The URL without a trailing slash, or '' when invalid.
 */
function custom_rfi_form_sanitize_endpoint_url( $url, $allow_http = false ) {
    $url = esc_url_raw( trim( (string) $url ), $allow_http ? array( 'https', 'http' ) : array( 'https' ) );
    if ( '' === $url ) {
        return '';
    }

    $parts = wp_parse_url( $url );
    if ( empty( $parts['host'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['query'] ) || isset( $parts['fragment'] ) ) {
        return '';
    }

    return untrailingslashit( $url );
}

/**
 * Get a configured endpoint URL. A wp-config.php constant wins over the setting.
 *
 * @param string $key One of the keys of custom_rfi_form_endpoint_constants().
 * @return string Validated URL, or '' when not configured.
 */
function custom_rfi_form_get_endpoint( $key ) {
    $constants = custom_rfi_form_endpoint_constants();

    if ( isset( $constants[ $key ] ) && defined( $constants[ $key ] ) ) {
        $url = constant( $constants[ $key ] );
    } else {
        $settings = custom_rfi_form_get_settings();
        $url      = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
    }

    return custom_rfi_form_sanitize_endpoint_url( $url, 'api_url_stage' === $key );
}

/**
 * Setting key of the API base URL for the selected server.
 *
 * @return string
 */
function custom_rfi_form_get_api_url_key() {
    $settings = custom_rfi_form_get_settings();

    return in_array( $settings['server_switch'], array( 'prod', 'qa' ), true ) ? 'api_url_' . $settings['server_switch'] : 'api_url_stage';
}

/**
 * Base URL of the lead API for the selected server.
 *
 * @return string Base URL, e.g. https://partners.example.com/api, or '' when not configured.
 */
function custom_rfi_form_get_api_base_url() {
    return custom_rfi_form_get_endpoint( custom_rfi_form_get_api_url_key() );
}

/**
 * URL of a directory endpoint (campusesformicrosites, programsformicrosites).
 *
 * @param string $directory Directory endpoint name.
 * @return string URL, or '' when the API URL is not configured.
 */
function custom_rfi_form_get_directory_url( $directory ) {
    $base_url = custom_rfi_form_get_api_base_url();

    return $base_url ? $base_url . '/directory/' . $directory : '';
}

/**
 * Whether the API should return a lead ID for unsuccessful leads.
 *
 * @return bool
 */
function custom_rfi_form_returns_lead_id() {
    $settings = custom_rfi_form_get_settings();

    return in_array( 'lead_id', (array) $settings['return_lead_id'], true );
}

/**
 * URL the lead is submitted to.
 *
 * @return string URL, or '' when the API URL is not configured.
 */
function custom_rfi_form_get_submit_url() {
    $base_url = custom_rfi_form_get_api_base_url();
    if ( '' === $base_url ) {
        return '';
    }

    $url = $base_url . '/institutions/lead-save';

    if ( custom_rfi_form_returns_lead_id() ) {
        $url = add_query_arg( 'returnLeadId', 'true', $url );
    }

    return $url;
}

/**
 * Write a message to the PHP error log.
 *
 * @param string $message Message to log.
 * @return void
 */
function custom_rfi_form_log( $message ) {
    error_log( '[Custom RFI Form] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
}

/**
 * Warn administrators when the API URL for the selected server is missing.
 *
 * @return void
 */
function custom_rfi_form_missing_endpoint_notice() {
    if ( ! current_user_can( custom_rfi_form_settings_capability() ) || '' !== custom_rfi_form_get_api_base_url() ) {
        return;
    }

    $settings_url = admin_url( 'options-general.php?page=custom-rfi-form' );
    printf(
        '<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
        esc_html__( 'Custom RFI Form: the API URL for the selected server is not set, so the form cannot load programs or submit leads.', 'custom-rfi-form' ),
        esc_url( $settings_url ),
        esc_html__( 'Configure it', 'custom-rfi-form' )
    );
}
add_action( 'admin_notices', 'custom_rfi_form_missing_endpoint_notice' );

/*
 * ---------------------------------------------------------------------------
 * API cache
 * ---------------------------------------------------------------------------
 */

/**
 * Current cache version. It is part of every cache key, so bumping it invalidates
 * all cached API responses without a database scan or a full object-cache flush.
 *
 * @return int
 */
function custom_rfi_form_cache_version() {
    return (int) get_option( 'custom_rfi_form_cache_version', 1 );
}

/**
 * Invalidate all cached API responses.
 *
 * @return void
 */
function custom_rfi_form_clear_cache() {
    update_option( 'custom_rfi_form_cache_version', custom_rfi_form_cache_version() + 1, false );
}

// Settings changes can change what the API returns.
add_action( 'add_option_custom_rfi_form_settings', 'custom_rfi_form_clear_cache' );
add_action( 'update_option_custom_rfi_form_settings', 'custom_rfi_form_clear_cache' );

/**
 * Add a "Clear API Cache" button to the admin toolbar.
 *
 * @param WP_Admin_Bar $wp_admin_bar The admin bar object.
 * @return void
 */
function custom_rfi_form_admin_bar_button( $wp_admin_bar ) {
    if ( ! current_user_can( custom_rfi_form_settings_capability() ) ) {
        return;
    }

    $wp_admin_bar->add_node( array(
        'id'    => 'clear_api_cache',
        'title' => __( 'Clear API Cache', 'custom-rfi-form' ),
        'href'  => wp_nonce_url( add_query_arg( 'custom_rfi_form_clear_api_cache', '1' ), 'custom_rfi_form_clear_api_cache' ),
        'meta'  => array(
            'title' => __( 'Clear API Cache', 'custom-rfi-form' ),
            'class' => 'clear-api-cache-button',
        ),
    ) );
}
add_action( 'admin_bar_menu', 'custom_rfi_form_admin_bar_button', 999 );

/**
 * Handle the toolbar "Clear API Cache" request.
 *
 * @return void
 */
function custom_rfi_form_handle_clear_cache_request() {
    if ( ! isset( $_GET['custom_rfi_form_clear_api_cache'], $_GET['_wpnonce'] ) ) {
        return;
    }

    if ( ! current_user_can( custom_rfi_form_settings_capability() )
        || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'custom_rfi_form_clear_api_cache' ) ) {
        return;
    }

    custom_rfi_form_clear_cache();

    // Reset the tracking cookies so the next page view behaves like a fresh visit.
    foreach ( array( '_CampaignTrackID', '_InitialLeadUrl', '_Session' ) as $cookie_name ) {
        if ( isset( $_COOKIE[ $cookie_name ] ) ) {
            setcookie( $cookie_name, '', time() - HOUR_IN_SECONDS, '/' );
        }
    }

    wp_safe_redirect( remove_query_arg( array( 'custom_rfi_form_clear_api_cache', '_wpnonce' ) ) );
    exit;
}
add_action( 'init', 'custom_rfi_form_handle_clear_cache_request' );

/*
 * ---------------------------------------------------------------------------
 * Per-form display options (settings, overridable by shortcode attributes)
 * ---------------------------------------------------------------------------
 */

/**
 * Thank-you page URL: the shortcode attribute wins, then the configured page.
 *
 * @param array $atts Sanitized shortcode attributes.
 * @return string
 */
function custom_rfi_form_get_thank_you_url( $atts = array() ) {
    if ( ! empty( $atts['thank_you_page'] ) ) {
        return $atts['thank_you_page'];
    }

    $settings = custom_rfi_form_get_settings();
    $url      = 'external' === $settings['thank_you_url_type']
        ? $settings['external_thank_you_url']
        : $settings['internal_thank_you_url'];

    return $url ? $url : home_url( '/' );
}

/**
 * Sign a return URL so the submission handler can trust the value posted back by the form.
 *
 * @param string $url URL to sign.
 * @return string
 */
function custom_rfi_form_sign_url( $url ) {
    return hash_hmac( 'sha256', $url, wp_salt( 'nonce' ) );
}

/**
 * Submit button text: the shortcode attribute wins over the setting.
 *
 * @param array $atts Sanitized shortcode attributes.
 * @return string
 */
function custom_rfi_form_get_submit_button_text( $atts = array() ) {
    if ( ! empty( $atts['submit_button_text'] ) ) {
        return $atts['submit_button_text'];
    }

    $settings = custom_rfi_form_get_settings();

    return $settings['submit_button_text'] ? $settings['submit_button_text'] : 'Submit';
}

/**
 * Form title: the shortcode attribute wins over the setting.
 *
 * @param array $atts Sanitized shortcode attributes.
 * @return string
 */
function custom_rfi_form_get_form_title( $atts = array() ) {
    if ( ! empty( $atts['form_title'] ) ) {
        return $atts['form_title'];
    }

    $settings = custom_rfi_form_get_settings();

    return $settings['form_title'];
}

/**
 * Permalinks of all published pages, keyed by URL.
 *
 * @return array
 */
function custom_rfi_form_get_pages() {
    $page_list = array();

    foreach ( get_pages() as $page ) {
        if ( $page instanceof WP_Post ) {
            $page_list[ get_permalink( $page->ID ) ] = $page->post_title;
        }
    }
    krsort( $page_list );

    return $page_list;
}

/**
 * Options for the "Internal Thank You URL" setting.
 *
 * @return array
 */
function custom_rfi_form_get_thank_you_page_options() {
    return array( '' => '— Home page —' ) + custom_rfi_form_get_pages();
}
