<?php
/**
 * Plugin Name:       Custom RFI Form
 * Description:       Request-for-information (RFI) lead form that loads campuses and programs from a partner lead API and submits leads to it.
 * Version:           1.0.0
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Cristian Bladimir Lopez Hurtarte
 * Text Domain:       custom-rfi-form
 */

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CUSTOM_RFI_FORM_VERSION', '1.0.0' );
define( 'CUSTOM_RFI_FORM_FILE', __FILE__ );
define( 'CUSTOM_RFI_FORM_PATH', plugin_dir_path( __FILE__ ) );
define( 'CUSTOM_RFI_FORM_URL', plugin_dir_url( __FILE__ ) );

require_once CUSTOM_RFI_FORM_PATH . 'includes/form/helper-functions.php';
require_once CUSTOM_RFI_FORM_PATH . 'includes/form/fields.php';
require_once CUSTOM_RFI_FORM_PATH . 'includes/form/class-custom-rfi-form-api.php';
require_once CUSTOM_RFI_FORM_PATH . 'includes/custom-form-submission.php';
require_once CUSTOM_RFI_FORM_PATH . 'includes/class-custom-form-shortcode.php';
require_once CUSTOM_RFI_FORM_PATH . 'includes/class-custom-rfi-form-plugin.php';

// Optional self-hosted updates (https://github.com/YahnisElsts/plugin-update-checker).
// Enable by defining CUSTOM_RFI_FORM_UPDATE_URL (HTTPS URL of plugin-info.json) in wp-config.php.
if ( defined( 'CUSTOM_RFI_FORM_UPDATE_URL' ) && custom_rfi_form_sanitize_endpoint_url( CUSTOM_RFI_FORM_UPDATE_URL ) ) {
    require_once CUSTOM_RFI_FORM_PATH . 'plugin-update-checker/plugin-update-checker.php';

    YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        custom_rfi_form_sanitize_endpoint_url( CUSTOM_RFI_FORM_UPDATE_URL ),
        __FILE__,
        'custom-rfi-form' // Must match the plugin directory name
    );
}

register_activation_hook( __FILE__, 'custom_rfi_form_activate' );

Custom_RFI_Form_Shortcode::init();
add_action( 'plugins_loaded', array( 'Custom_RFI_Form_Plugin', 'init' ) );
