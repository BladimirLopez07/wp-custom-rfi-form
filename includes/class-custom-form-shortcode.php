<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Custom Form Shortcode Class
 *
 * Registers the [custom_rfi_form] shortcode, renders the form and enqueues its assets.
 * The form uses fixed element IDs, so one form per page is supported.
 */
class Custom_RFI_Form_Shortcode {

    /**
     * Flag to check if assets have been enqueued.
     *
     * @var bool
     */
    private static $assets_enqueued = false;

    /**
     * Initialize the class by adding the shortcode [custom_rfi_form].
     */
    public static function init() {
        add_shortcode( 'custom_rfi_form', array( __CLASS__, 'render_shortcode' ) );
    }

    /**
     * Render the shortcode.
     *
     * @param array|string $atts Shortcode attributes.
     * @return string The form HTML.
     */
    public static function render_shortcode( $atts ) {
        $atts = self::sanitize_atts( shortcode_atts( array(
            'steps'              => '', // Falls back to the "Form Steps" setting
            'feature_list_id'    => '',
            'prepop_campus'      => '',
            'prepop_aos'         => '',
            'prepop_program'     => '',
            'thank_you_page'     => '',
            'form_title'         => '',
            'submit_button_text' => '',
        ), $atts, 'custom_rfi_form' ) );

        $form_opt      = custom_rfi_form_get_settings();
        $steps         = '' !== $atts['steps'] ? $atts['steps'] : (string) $form_opt['custom_form_type'];
        $form_steps    = in_array( $steps, array( '2', 'two' ), true ) ? '2' : '1';
        $program_first = 'true' === $form_opt['program_before_campus'];

        self::enqueue_assets( $form_opt, $atts, $form_steps, $program_first );

        $api = new Custom_RFI_Form_Api(
            $program_first ? Custom_RFI_Form_Api::MODE_PROGRAMS : Custom_RFI_Form_Api::MODE_CAMPUSES,
            $atts['feature_list_id']
        );

        // Variables used by the form templates and components
        $api_data           = $api->get_data();
        $form               = isset( $api_data['Body']['ItemList'] ) && is_array( $api_data['Body']['ItemList'] ) ? $api_data['Body']['ItemList'] : array();
        $fields             = (array) $form_opt['form_fields'];
        $form_title         = custom_rfi_form_get_form_title( $atts );
        $submit_button_text = custom_rfi_form_get_submit_button_text( $atts );
        $thank_you_url      = custom_rfi_form_get_thank_you_url( $atts );

        ob_start();
        include CUSTOM_RFI_FORM_PATH . 'includes/form/' . ( '2' === $form_steps ? 'custom-form-two-steps.php' : 'custom-form-one-step.php' );

        return ob_get_clean();
    }

    /**
     * Sanitize shortcode attributes. Post authors (including Contributors) control these.
     *
     * @param array $atts Raw attributes.
     * @return array
     */
    private static function sanitize_atts( $atts ) {
        $atts                   = array_map( 'sanitize_text_field', array_map( 'strval', $atts ) );
        $atts['steps']          = strtolower( $atts['steps'] );
        $atts['thank_you_page'] = esc_url_raw( $atts['thank_you_page'] );

        return $atts;
    }

    /**
     * Enqueue the form styles and scripts.
     *
     * @param array  $form_opt      Plugin settings.
     * @param array  $atts          Sanitized shortcode attributes.
     * @param string $form_steps    '1' or '2'.
     * @param bool   $program_first Whether programs are chosen before campuses.
     * @return void
     */
    private static function enqueue_assets( $form_opt, $atts, $form_steps, $program_first ) {
        if ( self::$assets_enqueued ) {
            return;
        }
        self::$assets_enqueued = true;

        $assets_url = CUSTOM_RFI_FORM_URL . 'includes/';
        $version    = CUSTOM_RFI_FORM_VERSION;

        wp_enqueue_style( 'custom-rfi-form-styles', $assets_url . 'css/qcf.css', array(), $version );
        wp_add_inline_style( 'custom-rfi-form-styles', self::get_css_variables( $form_opt ) );

        // Tracking runs first and carries the data object every other script reads.
        wp_enqueue_script( 'custom-rfi-form-tracking', $assets_url . 'js/rfi-tracking.js', array(), $version, true );
        wp_add_inline_script(
            'custom-rfi-form-tracking',
            'window.CustomRfiFormData = ' . wp_json_encode( self::get_script_data( $form_opt, $atts, $form_steps ) ) . ';',
            'before'
        );

        $filtering = $program_first ? 'js/program-first/custom-form-filtering.js' : 'js/campus-first/custom-form-filtering.js';
        wp_enqueue_script( 'custom-rfi-form-filtering', $assets_url . $filtering, array( 'custom-rfi-form-tracking' ), $version, true );
        wp_enqueue_script( 'custom-rfi-form-validation', $assets_url . 'js/custom-form-validation.js', array( 'custom-rfi-form-tracking' ), $version, true );
        wp_enqueue_script( 'custom-rfi-form-additional', $assets_url . 'js/custom-form-additional.js', array( 'custom-rfi-form-tracking' ), $version, true );
    }

    /**
     * Data exposed to the form scripts as window.CustomRfiFormData.
     *
     * @param array  $form_opt   Plugin settings.
     * @param array  $atts       Sanitized shortcode attributes.
     * @param string $form_steps '1' or '2'.
     * @return array
     */
    private static function get_script_data( $form_opt, $atts, $form_steps ) {
        return array(
            'gp_campusid'       => $form_opt['campus_id'],
            'gp_trackid'        => $form_opt['form_track_id'],
            'opt_group'         => $form_opt['program_option_group'],
            'opt_group_order'   => array_column( (array) $form_opt['opt_group_order'], 'option' ),
            'form_steps'        => $form_steps,
            'prepop_campus'     => $atts['prepop_campus'],
            'prepop_aos'        => $atts['prepop_aos'],
            'prepop_program'    => $atts['prepop_program'],
            'program_first'     => $form_opt['program_before_campus'],
            'trackid_check_url' => custom_rfi_form_get_endpoint( 'trackid_check_url' ),
        );
    }

    /**
     * CSS custom properties for the configured color scheme.
     *
     * @param array $form_opt Plugin settings.
     * @return string
     */
    private static function get_css_variables( $form_opt ) {
        $css_var_map = array(
            'formBackground'   => '--form-background',
            'progressBarColor' => '--progress-bar-color',
            'formTextColor'    => '--form-text-color',
            'formHeadingColor' => '--form-heading-color',
            'errorColor'       => '--form-error-color',
            'buttonBackground' => '--button-background',
            'buttonBorder'     => '--button-border',
            'buttonTextColor'  => '--button-text-color',
        );

        $colors = array_merge( custom_rfi_form_default_colors(), array_filter( (array) $form_opt['color_scheme'] ) );
        $css    = ':root {';

        foreach ( $css_var_map as $key => $css_var_name ) {
            $color = isset( $colors[ $key ] ) ? sanitize_hex_color( $colors[ $key ] ) : '';
            if ( $color ) {
                $css .= $css_var_name . ': ' . $color . '; ';
            }
        }

        return $css . '}';
    }
}
