<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class responsible for adding settings fields.
 *
 * This class defines all the settings fields used in the plugin's settings page.
 */

class Custom_RFI_Form_Fields_Settings {

    public static function add_fields() {

        // Debug Mode
        add_settings_field(
            'debug_mode',
            'Debug Mode',
            array( 'Custom_RFI_Form_Render_Settings', 'render_radio_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'   => 'debug_mode',
                'options'     => array(
                    'true'  => 'ON',
                    'false' => 'OFF',
                ),
                'description' => 'When ON, administrators who submit the form see the lead payload instead of sending it.',
            )
        );

        // Form Steps
        add_settings_field(
            'custom_form_type',
            'Form Steps',
            array( 'Custom_RFI_Form_Render_Settings', 'render_radio_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'   => 'custom_form_type',
                'options'     => array(
                    '1' => 'One-step',
                    '2' => 'Two-step',
                ),
                'description' => '',
            )
        );

        // Form Track ID
        add_settings_field(
            'form_track_id',
            'Form Track ID',
            array( 'Custom_RFI_Form_Render_Settings', 'render_text_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'   => 'form_track_id',
                'description' => '',
                'required'    => true,
            )
        );

        // Global Feature ID
        add_settings_field(
            'global_feature_id',
            'Global Feature ID',
            array( 'Custom_RFI_Form_Render_Settings', 'render_text_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'   => 'global_feature_id',
                'description' => '',
                'required'    => false,
            )
        );

        // Form Name
        add_settings_field(
            'form_name',
            'Form Name',
            array( 'Custom_RFI_Form_Render_Settings', 'render_text_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'   => 'form_name',
                'description' => '',
                'required'    => false,
            )
        );
        
        // Server Switch
        add_settings_field(
            'server_switch',
            'Server Switch',
            array( 'Custom_RFI_Form_Render_Settings', 'render_select_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'   => 'server_switch',
                'options'     => array(
                    'stage' => 'Staging Server',
                    'prod'  => 'Production Server',
                    'qa'    => 'QA Server',
                ),
                'description' => '',
            )
        );

        // API Endpoints
        $endpoint_fields = array(
            'api_url_prod'      => array( 'Production API URL', 'https://partners.example.com/api', 'Base URL of the production lead API.' ),
            'api_url_qa'        => array( 'QA API URL', 'https://partners.qa.example.com/api', 'Base URL of the QA lead API.' ),
            'api_url_stage'     => array( 'Staging API URL', 'http://partners.staging.example.local/api', 'Base URL of the staging lead API. Plain HTTP is allowed here for internal hosts.' ),
            'postal_lookup_url' => array( 'Zip Code Lookup URL', 'https://forms.example.com/FormValidation/GetCityStateCountry', 'Optional. Fills in city, state and country from the zip code (?ZipCode= is appended).' ),
            'trackid_check_url' => array( 'Track ID Check URL', 'https://partners.example.com/api/util/checkapikey', 'Optional. Validates campaign track IDs in the browser (?apikey= is appended).' ),
        );

        foreach ( $endpoint_fields as $key => $field ) {
            add_settings_field(
                $key,
                $field[0],
                array( 'Custom_RFI_Form_Render_Settings', 'render_endpoint_field' ),
                'custom-rfi-form',
                'api_settings_section',
                array(
                    'label_for'   => $key,
                    'placeholder' => $field[1],
                    'description' => $field[2],
                )
            );
        }

        // Form Title
        add_settings_field(
            'form_title',
            'Form Title',
            array( 'Custom_RFI_Form_Render_Settings', 'render_text_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'   => 'form_title',
                'description' => '',
                'required'    => true,
            )
        );

        // Programs Before Campus radio button
        add_settings_field(
            'program_before_campus',
            'Programs Before Campus',
            array( 'Custom_RFI_Form_Render_Settings', 'render_radio_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'   => 'program_before_campus',
                'options'     => array(
                    'true'  => 'TRUE',
                    'false' => 'FALSE',
                ),
                'description' => '',
            )
        );


        // Campus Type
        add_settings_field(
            'campus_type',
            'Campus Type',
            array( 'Custom_RFI_Form_Render_Settings', 'render_select_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'   => 'campus_type',
                'options'     => array(
                    'single' => 'Single',
                    'multiple'  => 'Multiple',
                ),
                'description' => '',
            )
        );
        // Campus ID (conditionally displayed when Campus Type is 'single')
        add_settings_field(
            'campus_id',
            'Campus ID',
            array( 'Custom_RFI_Form_Render_Settings', 'render_text_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'        => 'campus_id',
                'description'      => '',
                'conditional_show' => array(
                    'field'   => 'campus_type',
                    'value'   => 'single',
                    'operator'=> '==',
                ),
            )
        );
                
        // Form Fields
        add_settings_field(
            'form_fields',
            'Form Fields',
            array( 'Custom_RFI_Form_Render_Settings', 'render_checkbox_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'   => 'form_fields',
                'options'     => array(
                    'campus'           => 'Campus',
                    'aos'              => 'Area of Study',
                    'highestEducation' => 'Highest Education',
                    'startTerm'        => 'Start Term',
                    'fname'            => 'First Name',
                    'lname'            => 'Last Name',
                    'birthday'         => 'Birthday',
                    'phone'            => 'Phone Number',
                    'zip'              => 'Zip',
                ),
                'description' => '',
            )
        );

        // Highest Level of Education (conditionally displayed when Form Field 'highestEducation' is checked)
        add_settings_field(
            'highest_education',
            'Highest Level of Education',
            array( 'Custom_RFI_Form_Render_Settings', 'render_text_area_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'        => 'highest_education',
                'description'      => 'Add Highest Education level fields separated by a new line',
                'conditional_show' => array(
                    'field'   => 'form_fields',
                    'value'   => 'highestEducation',
                    'operator'=> 'in',
                ),
            )
        );
        

        // Start Term (conditionally displayed when Form Field 'startTerm' is checked)
        add_settings_field(
            'start_term',
            'Start Term',
            array( 'Custom_RFI_Form_Render_Settings', 'render_text_area_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'        => 'start_term',
                'description'      => 'Add Start Term Dates separated by a new line',
                'conditional_show' => array(
                    'field'   => 'form_fields',
                    'value'   => 'startTerm',
                    'operator'=> 'in',
                ),
            )
        );
        
        // Program Option Group
        add_settings_field(
            'program_option_group',
            'Program Option Group',
            array( 'Custom_RFI_Form_Render_Settings', 'render_radio_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'   => 'program_option_group',
                'options'     => array(
                    'true'  => 'ON',
                    'false' => 'OFF',
                ),
                'description' => '',
            )
        );

        // Option Group Order
        add_settings_field(
            'opt_group_order',
            'Option Group Order',
            array( 'Custom_RFI_Form_Render_Settings', 'render_repeater_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'   => 'opt_group_order',
                'description' => 'Leave blank for alphabetical sorting.',
            )
        );   

        // Disclaimer
        add_settings_field(
            'disclaimer',
            'Disclaimer',
            array( 'Custom_RFI_Form_Render_Settings', 'render_text_area_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'        => 'disclaimer',
                'description'      => '',
                'required'         => false,
            )
        );
        

        // Submit Button Text
        add_settings_field(
            'submit_button_text',
            'Submit Button Text',
            array( 'Custom_RFI_Form_Render_Settings', 'render_text_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'        => 'submit_button_text',
                'description'      => '',
                'required'         => false,
            )
        );
        
        // Thank You URL Type
        add_settings_field(
            'thank_you_url_type',
            'Thank You URL Type',
            array( 'Custom_RFI_Form_Render_Settings', 'render_radio_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'   => 'thank_you_url_type',
                'default'     => 'internal',
                'options'     => array(
                    'internal'  => 'Internal',
                    'external' => 'External',
                ),
                'description' => '',
            )
        );
        // External Thank You URL
        add_settings_field(
            'external_thank_you_url',
            'External Thank You URL',
            array( 'Custom_RFI_Form_Render_Settings', 'render_text_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'        => 'external_thank_you_url',
                'description'      => '',
                'required'         => false,
            )
        );

        // Internal Thank You URL
        add_settings_field(
            'internal_thank_you_url',           
            'Internal Thank You URL',
            array( 'Custom_RFI_Form_Render_Settings', 'render_select_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'   => 'internal_thank_you_url',
                'options_callback' => 'custom_rfi_form_get_thank_you_page_options', // Built only when the page renders
                'description' => '',
            )
        );

        // Return LeadId
        add_settings_field(
            'return_lead_id',
            'Return LeadId',
            array( 'Custom_RFI_Form_Render_Settings', 'render_checkbox_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'   => 'return_lead_id',
                'options'     => array(
                    'lead_id'              => 'Return Lead ID for unsuccessful leads?',
                ),
                'description' => '',
            )
        );
        // Honey Pot Field
        add_settings_field(
            'honey_pot_field',
            'Honey Pot Field',
            array( 'Custom_RFI_Form_Render_Settings', 'render_radio_field' ),
            'custom-rfi-form',
            'general_settings_section',
            array(
                'label_for'   => 'honey_pot_field',
                'default'     => 'false',
                'options'     => array(
                    'true'  => 'ON',
                    'false' => 'OFF',
                ),
                'description' => '',
            )
        );

        // Color Scheme
        add_settings_field(
            'color_scheme',
            'Color Scheme',
            array( 'Custom_RFI_Form_Render_Settings', 'render_color_field' ),
            'custom-rfi-form',
            'style_settings_section',
            array(
                'label_for'   => 'color_scheme',
                'options'     => array(
                    'formBackground'    => 'Form Background',
                    'progressBarColor'  => 'Progress Bar Color',
                    'formTextColor'     => 'Form Text Color',
                    'formHeadingColor'  => 'Form Heading Color',
                    'errorColor'        => 'Error Color',
                    'buttonBackground'  => 'Button Background',
                    'buttonBorder'      => 'Button Border',
                    'buttonTextColor'   => 'Button Text Color',
                ),
                'description' => '',
            )
        );
        

    }
}
