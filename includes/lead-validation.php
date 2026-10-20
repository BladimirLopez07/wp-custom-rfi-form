<?php
/**
 * Server-side validation of the built-in lead fields.
 *
 * custom-form-validation.js applies the same rules in the browser, but a request
 * can be posted without it, so every lead is checked again here before it is sent
 * to the API. Custom fields are validated in includes/form/fields.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Program ID => IDs of the campuses offering it, from the directory API data.
 *
 * @param array $api_data      Directory data from Custom_RFI_Form_Api::get_data().
 * @param bool  $program_first Whether the data is program-first (programs with a CampusList).
 * @return array
 */
function custom_rfi_form_get_program_campuses( $api_data, $program_first ) {
    $items = isset( $api_data['Body']['ItemList'] ) && is_array( $api_data['Body']['ItemList'] ) ? $api_data['Body']['ItemList'] : array();
    $map   = array();

    foreach ( $items as $item ) {
        if ( $program_first ) {
            if ( ! isset( $item['ProgramId'] ) ) {
                continue;
            }
            $program_id         = (string) $item['ProgramId'];
            $map[ $program_id ] = isset( $map[ $program_id ] ) ? $map[ $program_id ] : array();
            foreach ( isset( $item['CampusList'] ) ? (array) $item['CampusList'] : array() as $campus ) {
                if ( isset( $campus['CampusId'] ) ) {
                    $map[ $program_id ][] = (string) $campus['CampusId'];
                }
            }
        } else {
            $campus_id = isset( $item['CampusId'] ) ? (string) $item['CampusId'] : '';
            foreach ( isset( $item['Programs'] ) ? (array) $item['Programs'] : array() as $program ) {
                if ( isset( $program['ProgramId'] ) ) {
                    $map[ (string) $program['ProgramId'] ][] = $campus_id;
                }
            }
        }
    }

    return $map;
}

/**
 * The directory the submitted form was rendered from, as a program => campuses map.
 *
 * The feature list ID comes from a signed hidden field, because it may have been set
 * by a shortcode attribute that the submission request does not know about.
 *
 * @param array $settings Plugin settings.
 * @return array|null Map from custom_rfi_form_get_program_campuses(), or null when the directory is unavailable.
 */
function custom_rfi_form_get_submitted_directory( $settings ) {
    $program_first = 'true' === $settings['program_before_campus'];
    $feature_id    = (string) custom_rfi_form_get_signed_post_value( '_rfi_feature_id', '_rfi_feature_sig' );

    $api = new Custom_RFI_Form_Api(
        $program_first ? Custom_RFI_Form_Api::MODE_PROGRAMS : Custom_RFI_Form_Api::MODE_CAMPUSES,
        $feature_id
    );
    $map = custom_rfi_form_get_program_campuses( $api->get_data(), $program_first );

    return $map ? $map : null;
}

/**
 * Values allowed by a "Label : value" options setting (Start Term, Highest Level of Education).
 *
 * @param string $setting Setting value, one option per line.
 * @return array
 */
function custom_rfi_form_get_option_values( $setting ) {
    $values = array();

    foreach ( explode( "\n", (string) $setting ) as $line ) {
        $parts = explode( ':', $line );
        if ( count( $parts ) > 1 && '' !== trim( $parts[1] ) ) {
            $values[] = trim( $parts[1] );
        }
    }

    return $values;
}

/**
 * Whether a birth date (any format with the digits MMDDYYYY) is a real date at least 16 years ago.
 *
 * @param string $birthdate Birth date as submitted.
 * @return bool
 */
function custom_rfi_form_is_valid_birthdate( $birthdate ) {
    $digits = preg_replace( '/\D/', '', (string) $birthdate );
    if ( strlen( $digits ) !== 8 ) {
        return false;
    }

    $month = (int) substr( $digits, 0, 2 );
    $day   = (int) substr( $digits, 2, 2 );
    $year  = (int) substr( $digits, 4, 4 );

    if ( $year < 1900 || ! checkdate( $month, $day, $year ) ) {
        return false;
    }

    $sixteenth_birthday = sprintf( '%04d-%02d-%02d', $year + 16, $month, $day );

    return $sixteenth_birthday <= gmdate( 'Y-m-d' );
}

/**
 * Validate the built-in fields of a lead. Only fields enabled in the settings are checked.
 *
 * @param array      $form_data        Normalized fields from custom_rfi_form_get_normalized_fields().
 * @param array      $settings         Plugin settings.
 * @param array|null $program_campuses Program ID => campus IDs, or null when the directory is unavailable.
 *                                     In that case the program is only checked for presence and the
 *                                     lead API makes the final decision.
 * @return array Error messages for the visitor; empty when the lead is valid.
 */
function custom_rfi_form_validate_lead( $form_data, $settings, $program_campuses ) {
    $enabled   = (array) $settings['form_fields'];
    $is_on     = function ( $key ) use ( $enabled ) {
        return in_array( $key, $enabled, true );
    };
    $is_chosen = function ( $value ) {
        return '' !== $value && 'default' !== $value;
    };
    $errors    = array();

    // Program choice
    $campus_id  = $form_data['CampusId'];
    $program_id = $form_data['ProgramId'];

    if ( $is_on( 'campus' ) && ! $is_chosen( $campus_id ) ) {
        $errors[] = __( 'Please select a campus.', 'custom-rfi-form' );
    }

    if ( $is_on( 'aos' ) && ! $is_chosen( $form_data['AreaOfStudyID'] ) ) {
        $errors[] = __( 'Please select an area of study.', 'custom-rfi-form' );
    }

    if ( ! $is_chosen( $program_id ) ) {
        $errors[] = __( 'Please select a program.', 'custom-rfi-form' );
    } elseif ( null !== $program_campuses ) {
        if ( ! isset( $program_campuses[ $program_id ] ) ) {
            $errors[] = __( 'Please select a program from the list.', 'custom-rfi-form' );
        } elseif ( $is_on( 'campus' ) && $is_chosen( $campus_id ) && ! in_array( $campus_id, $program_campuses[ $program_id ], true ) ) {
            $errors[] = __( 'The selected program is not offered at the selected campus.', 'custom-rfi-form' );
        }
    }

    if ( $is_on( 'highestEducation' ) && ! in_array( $form_data['HighestLevelofEducationCompleted'], custom_rfi_form_get_option_values( $settings['highest_education'] ), true ) ) {
        $errors[] = __( 'Please select your highest level of education.', 'custom-rfi-form' );
    }

    if ( $is_on( 'startTerm' ) && ! in_array( $form_data['StartTermDate'], custom_rfi_form_get_option_values( $settings['start_term'] ), true ) ) {
        $errors[] = __( 'Please select a start term.', 'custom-rfi-form' );
    }

    // Contact details
    if ( $is_on( 'fname' ) && preg_match_all( '/\p{L}/u', $form_data['firstname'] ) < 2 ) {
        $errors[] = __( 'Please enter your first name.', 'custom-rfi-form' );
    }

    if ( $is_on( 'lname' ) && preg_match_all( '/\p{L}/u', $form_data['lastname'] ) < 2 ) {
        $errors[] = __( 'Please enter your last name.', 'custom-rfi-form' );
    }

    if ( $is_on( 'phone' ) && strlen( preg_replace( '/\D/', '', $form_data['dayphone'] ) ) !== 10 ) {
        $errors[] = __( 'Please enter a 10-digit phone number.', 'custom-rfi-form' );
    }

    // Email is always part of the form; the lead API requires it.
    if ( ! is_email( $form_data['email'] ) ) {
        $errors[] = __( 'Please enter a valid email address.', 'custom-rfi-form' );
    }

    if ( $is_on( 'zip' ) && ! preg_match( '/^\d{5}$/', $form_data['postalcode'] ) ) {
        $errors[] = __( 'Please enter a 5-digit zip code.', 'custom-rfi-form' );
    }

    if ( $is_on( 'birthday' ) && ! custom_rfi_form_is_valid_birthdate( $form_data['BirthDate'] ) ) {
        $errors[] = __( 'Please enter a valid birth date. You must be at least 16 years old.', 'custom-rfi-form' );
    }

    return $errors;
}
