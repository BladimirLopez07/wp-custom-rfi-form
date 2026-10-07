<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'settings-classes/class-sanitize-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'settings-classes/class-fields-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'settings-classes/class-render-settings.php';

/**
 * Main plugin class for Custom RFI Form Plugin.
 *
 * Adds the settings page (Settings → Form Settings), registers the settings
 * with the WordPress Settings API and enqueues the admin assets.
 */
class Custom_RFI_Form_Plugin {

    const OPTION_NAME  = 'custom_rfi_form_settings';
    const OPTION_GROUP = 'custom_rfi_form_settings_group';
    const PAGE_SLUG    = 'custom-rfi-form';

    /**
     * Initialize the plugin.
     *
     * @return void
     */
    public static function init() {
        $self = new self();
        $self->hooks();
    }

    /**
     * Register all hooks with WordPress.
     *
     * @return void
     */
    private function hooks() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Let options.php save the settings for users with the plugin's capability.
        add_filter( 'option_page_capability_' . self::OPTION_GROUP, 'custom_rfi_form_settings_capability' );
    }

    /**
     * Add the settings page to the WordPress admin menu.
     *
     * @return void
     */
    public function add_settings_page() {
        add_options_page(
            'Form Settings',
            'Form Settings',
            custom_rfi_form_settings_capability(),
            self::PAGE_SLUG,
            array( $this, 'settings_page_content' )
        );
    }

    /**
     * Display the content of the settings page.
     *
     * Saving goes through options.php, which checks the nonce and capability,
     * runs the sanitize callback and shows the "Settings saved." notice.
     *
     * @return void
     */
    public function settings_page_content() {
        if ( ! current_user_can( custom_rfi_form_settings_capability() ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>Custom RFI Form Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( self::OPTION_GROUP );
                do_settings_sections( self::PAGE_SLUG );
                submit_button();
                ?>
            </form>
            <p>
                You can add the form to a page or post by using the shortcode <code>[custom_rfi_form]</code>.
            </p>
            <p>
                Available attributes for the shortcode are: <code>steps</code>, <code>feature_list_id</code>, <code>prepop_campus</code>, <code>prepop_aos</code>, <code>prepop_program</code>, <code>form_title</code>, <code>submit_button_text</code> and <code>thank_you_page</code>.
            </p>
        </div>
        <?php
    }

    /**
     * Register plugin settings and add settings sections and fields.
     *
     * @return void
     */
    public function register_settings() {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( 'Custom_RFI_Form_Sanitize_Settings', 'sanitize_settings' ),
            )
        );

        add_settings_section(
            'general_settings_section',
            'General Settings',
            null,
            self::PAGE_SLUG
        );
        add_settings_section(
            'api_settings_section',
            'API Endpoints',
            array( $this, 'render_api_section_intro' ),
            self::PAGE_SLUG
        );
        add_settings_section(
            'style_settings_section',
            'Style Settings',
            null,
            self::PAGE_SLUG
        );

        Custom_RFI_Form_Fields_Settings::add_fields();
    }

    /**
     * Intro text for the API Endpoints section.
     *
     * @return void
     */
    public function render_api_section_intro() {
        echo '<p>' . esc_html__( 'Lead data is sent to these URLs. Production and QA must use HTTPS. For extra protection, define them in wp-config.php so they cannot be changed from wp-admin.', 'custom-rfi-form' ) . '</p>';
    }

    /**
     * Enqueue admin scripts and styles for the settings page.
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
            return;
        }

        wp_enqueue_script( 'custom-rfi-form-admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', array(), CUSTOM_RFI_FORM_VERSION, true );
        wp_enqueue_style( 'custom-rfi-form-admin', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), CUSTOM_RFI_FORM_VERSION );
    }
}
