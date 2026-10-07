<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Form fields forwarded to the lead API. Anything else posted is dropped.
 *
 * @return array
 */
function custom_rfi_form_lead_fields() {
    return array(
        // Program choice
        'CampusId', 'AreaOfStudyID', 'ProgramId', 'HighestLevelofEducationCompleted', 'StartTermDate',
        // Contact details
        'firstname', 'lastname', 'dayphone', 'phone', 'email', 'postalcode', 'BirthDate', 'city', 'state', 'country',
        // Tracking and attribution
        'APIKey', 'TrackingSessionGUID', 'formname', 'UserAgreement', 'returntourl', 'FormLeadUrl', 'LeadInitiatingUrl',
        'LeadSourceUrl', 'LeadSourceType', 'Keyword', 'ReturnLeadId', 'SearchEngine', 'SearchEngineCampaign',
        'VendorAccountID', 'ClientSourceCode', 'DeviceType', 'Cookies', 'CheckAPIKey', 'CheckAPIStatus', 'CheckAPIErrorMessage',
    );
}

/**
 * Read the allowed fields from the request, unslashed and sanitized.
 *
 * @return array
 */
function custom_rfi_form_get_posted_fields() {
    $url_fields = array( 'returntourl', 'FormLeadUrl', 'LeadInitiatingUrl', 'LeadSourceUrl' );
    $form_data  = array();

    foreach ( custom_rfi_form_lead_fields() as $field ) {
        $value = isset( $_POST[ $field ] ) && is_scalar( $_POST[ $field ] ) ? wp_unslash( (string) $_POST[ $field ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by the caller.

        $form_data[ $field ] = in_array( $field, $url_fields, true ) ? esc_url_raw( $value ) : sanitize_text_field( $value );
    }

    return $form_data;
}

/**
 * Look up city, state and country for a US zip code.
 *
 * @param string $postal_code Five-digit zip code.
 * @return array Keys city, state and country when found; empty when the lookup URL is not configured.
 */
function custom_rfi_form_lookup_postal_code( $postal_code ) {
    $lookup_url = custom_rfi_form_get_endpoint( 'postal_lookup_url' );
    if ( '' === $lookup_url ) {
        return array();
    }

    $response = wp_remote_get(
        add_query_arg( 'ZipCode', rawurlencode( $postal_code ), $lookup_url ),
        array( 'timeout' => 5 )
    );

    if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        return array();
    }

    // The endpoint answers with JSONP, e.g. callback([...]); accept plain JSON as well.
    $body = trim( wp_remote_retrieve_body( $response ) );
    if ( preg_match( '/^[\w$.]+\((.*)\)\s*;?$/s', $body, $matches ) ) {
        $body = $matches[1];
    }

    $items = json_decode( $body, true );
    if ( ! is_array( $items ) ) {
        return array();
    }

    $keys     = array( 'City' => 'city', 'StateCode' => 'state', 'CountryCode' => 'country' );
    $location = array();

    foreach ( $items as $item ) {
        if ( isset( $item['Key'], $item['Value'], $keys[ $item['Key'] ] ) && is_scalar( $item['Value'] ) ) {
            $location[ $keys[ $item['Key'] ] ] = sanitize_text_field( (string) $item['Value'] );
        }
    }

    return $location;
}

/**
 * Format a phone number as E.164, assuming +1 (USA/Canada) for 10-digit numbers.
 *
 * @param string $phone_number Phone number in any format.
 * @return string
 */
function custom_rfi_form_format_phone_e164( $phone_number ) {
    $digits = preg_replace( '/\D/', '', $phone_number );

    if ( strlen( $digits ) === 10 ) {
        return '+1' . $digits;
    }

    // 11 digits starting with 1 already carry the country code; anything else is treated as international.
    return '+' . $digits;
}

/**
 * Where to send the visitor after submitting.
 *
 * The form posts back the thank-you URL it was rendered with (which may come from a
 * shortcode attribute). It is only used when its signature matches, so the field
 * cannot be abused as an open redirect.
 *
 * @return string
 */
function custom_rfi_form_get_redirect_url() {
    $url       = isset( $_POST['returntourl'] ) && is_string( $_POST['returntourl'] ) ? esc_url_raw( wp_unslash( $_POST['returntourl'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $signature = isset( $_POST['returntourl_sig'] ) && is_string( $_POST['returntourl_sig'] ) ? sanitize_text_field( wp_unslash( $_POST['returntourl_sig'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

    if ( $url && $signature && hash_equals( custom_rfi_form_sign_url( $url ), $signature ) ) {
        return $url;
    }

    return custom_rfi_form_get_thank_you_url();
}

/**
 * Stop with a generic error. Details go to the error log, not to the visitor.
 *
 * @return void
 */
function custom_rfi_form_submission_failed() {
    wp_die(
        esc_html__( 'Sorry, we could not submit your request right now. Please try again later.', 'custom-rfi-form' ),
        esc_html__( 'Submission error', 'custom-rfi-form' ),
        array( 'response' => 502, 'back_link' => true )
    );
}

/**
 * Build the JSON payload for the lead API from the posted form.
 *
 * @param array $custom_values Custom field values from custom_rfi_form_get_custom_field_values().
 * @return array
 */
function custom_rfi_form_build_lead( $custom_values = array() ) {
    $form_data = custom_rfi_form_get_posted_fields();

    // Names: letters and hyphens only
    $form_data['firstname'] = preg_replace( '/[^A-Za-z-]/', '', $form_data['firstname'] );
    $form_data['lastname']  = preg_replace( '/[^A-Za-z-]/', '', $form_data['lastname'] );

    if ( preg_match( '/\d{10}/', preg_replace( '/\D/', '', $form_data['dayphone'] ), $matches ) ) {
        $form_data['dayphone'] = sprintf( '(%s) %s-%s', substr( $matches[0], 0, 3 ), substr( $matches[0], 3, 3 ), substr( $matches[0], 6, 4 ) );
    }

    $form_data['postalcode'] = substr( preg_replace( '/\D/', '', $form_data['postalcode'] ), 0, 5 );

    // Keep only the last "@" so "a@b@example.com" becomes "ab@example.com"
    $email              = filter_var( $form_data['email'], FILTER_SANITIZE_EMAIL );
    $last_at            = strrpos( $email, '@' );
    $form_data['email'] = false === $last_at
        ? $email
        : str_replace( '@', '', substr( $email, 0, $last_at ) ) . substr( $email, $last_at );

    // Analytics click IDs from first-party cookies
    $form_data['gacid']   = isset( $_COOKIE['_ga'] ) ? preg_replace( '/^.+\.(.+?\..+?)$/', '\\1', sanitize_text_field( wp_unslash( $_COOKIE['_ga'] ) ) ) : '';
    $form_data['msclkid'] = isset( $_COOKIE['_uetmsclkid'] ) ? str_replace( '_uet', '', sanitize_text_field( wp_unslash( $_COOKIE['_uetmsclkid'] ) ) ) : '';

    if ( strlen( $form_data['postalcode'] ) === 5 ) {
        $form_data = array_merge( $form_data, custom_rfi_form_lookup_postal_code( $form_data['postalcode'] ) );
    }

    // QuestionKey names (including the Eddy* keys below) are defined by the lead API; do not rename them.
    $form_data['AdditionalQuestions'] = array(
        array( 'QuestionKey' => 'FormName', 'QuestionValue' => $form_data['formname'] ),
        array( 'QuestionKey' => 'TrackingSessionGUID', 'QuestionValue' => $form_data['TrackingSessionGUID'] ),
        array( 'QuestionKey' => 'LeadUserAgent', 'QuestionValue' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '' ),
        array( 'QuestionKey' => 'EddyIPAddress', 'QuestionValue' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0' ),
        array( 'QuestionKey' => 'FormLeadUrl', 'QuestionValue' => $form_data['FormLeadUrl'] ),
        array( 'QuestionKey' => 'LeadSourceType', 'QuestionValue' => '5' ),
        array( 'QuestionKey' => 'Cookies', 'QuestionValue' => $form_data['Cookies'] ),
        array( 'QuestionKey' => 'DeviceType', 'QuestionValue' => $form_data['DeviceType'] ),
    );

    // Birth date: digits as MMDDYYYY, sent as MM/DD/YYYY plus its parts
    $birthdate = preg_replace( '/\D/', '', $form_data['BirthDate'] );
    if ( strlen( $birthdate ) === 8 ) {
        $month = substr( $birthdate, 0, 2 );
        $day   = substr( $birthdate, 2, 2 );
        $year  = substr( $birthdate, 4, 4 );

        $form_data['BirthDate']             = $month . '/' . $day . '/' . $year;
        $form_data['AdditionalQuestions'][] = array( 'QuestionKey' => 'EddyBirthDate', 'QuestionValue' => $form_data['BirthDate'] );
        $form_data['AdditionalQuestions'][] = array( 'QuestionKey' => 'EddyBirthDay', 'QuestionValue' => $day );
        $form_data['AdditionalQuestions'][] = array( 'QuestionKey' => 'EddyBirthMonth', 'QuestionValue' => $month );
        $form_data['AdditionalQuestions'][] = array( 'QuestionKey' => 'EddyBirthYear', 'QuestionValue' => $year );
    } else {
        unset( $form_data['BirthDate'] );
    }

    // Encode ampersands in User Agreement
    $form_data['UserAgreement'] = str_replace( '&', '%26', $form_data['UserAgreement'] );

    // Landing-page query string saved by rfi-tracking.js (utm_* and similar)
    $additional_fields = array();
    if ( isset( $_COOKIE['RfiAdditionalFields'] ) && is_string( $_COOKIE['RfiAdditionalFields'] ) ) {
        parse_str( wp_unslash( $_COOKIE['RfiAdditionalFields'] ), $additional_fields );
    }
    foreach ( $additional_fields as $key => $value ) {
        if ( is_scalar( $value ) ) {
            $form_data['AdditionalQuestions'][] = array(
                'QuestionKey'   => sanitize_text_field( (string) $key ),
                'QuestionValue' => rawurlencode( (string) $value ),
            );
        }
    }

    // Fields added with the custom_rfi_form_fields filter
    foreach ( $custom_values as $name => $custom ) {
        if ( $custom['additional_question'] ) {
            $form_data['AdditionalQuestions'][] = array( 'QuestionKey' => $name, 'QuestionValue' => $custom['value'] );
        } else {
            $form_data[ $name ] = $custom['value'];
        }
    }

    return $form_data;
}

/**
 * Handles form submissions.
 *
 * @return void
 */
function custom_rfi_form_handle_submission() {
    if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        wp_safe_redirect( home_url() );
        exit;
    }

    if ( ! isset( $_POST['custom_rfi_form_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['custom_rfi_form_nonce'] ) ), 'custom_rfi_form_submit' ) ) {
        wp_die( esc_html__( 'Your session has expired. Please reload the page and try again.', 'custom-rfi-form' ), 'Error', array( 'response' => 403, 'back_link' => true ) );
    }

    $form_settings = custom_rfi_form_get_settings();
    $redirect_url  = custom_rfi_form_get_redirect_url();

    // Honeypot: people never see this field, so a value means a bot. Pretend it worked.
    if ( 'true' === $form_settings['honey_pot_field'] && ! empty( $_POST['validate_xx'] ) ) {
        wp_redirect( $redirect_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- admin-configured or signed URL, may be external.
        exit;
    }

    // Required custom fields are checked here too, as the browser check can be bypassed.
    $custom_values = custom_rfi_form_get_custom_field_values();
    if ( is_wp_error( $custom_values ) ) {
        wp_die( esc_html( $custom_values->get_error_message() ), esc_html__( 'Missing information', 'custom-rfi-form' ), array( 'response' => 400, 'back_link' => true ) );
    }

    $form_data = custom_rfi_form_build_lead( $custom_values );
    $json_data = wp_json_encode( $form_data, JSON_UNESCAPED_SLASHES );

    // Debug mode shows the payload instead of sending it, to administrators only.
    if ( 'true' === $form_settings['debug_mode'] && current_user_can( 'manage_options' ) ) {
        wp_die(
            '<pre>' . esc_html( wp_json_encode( $form_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ) . '</pre>',
            esc_html__( 'Custom RFI Form debug payload', 'custom-rfi-form' ),
            array( 'response' => 200, 'back_link' => true )
        );
    }

    $submit_url = custom_rfi_form_get_submit_url();
    if ( '' === $submit_url ) {
        custom_rfi_form_log( 'Lead submission skipped: the API URL for the selected server is not configured.' );
        custom_rfi_form_submission_failed();
    }

    $response = wp_remote_post( $submit_url, array(
        'timeout'     => 15,
        'headers'     => array( 'Content-Type' => 'application/json' ),
        'body'        => $json_data,
        'data_format' => 'body',
    ) );

    if ( is_wp_error( $response ) ) {
        custom_rfi_form_log( sprintf( 'Lead submission to %s failed: %s', $submit_url, $response->get_error_message() ) );
        custom_rfi_form_submission_failed();
    }

    $http_code = (int) wp_remote_retrieve_response_code( $response );
    if ( $http_code < 200 || $http_code >= 400 ) {
        custom_rfi_form_log( sprintf( 'Lead submission to %s returned HTTP %d.', $submit_url, $http_code ) );
        custom_rfi_form_submission_failed();
    }

    $data       = json_decode( wp_remote_retrieve_body( $response ) );
    $is_success = isset( $data->IsSuccessful ) && true == $data->IsSuccessful; // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
    $lead_id    = isset( $data->Body->LeadId ) ? $data->Body->LeadId : 0;

    // SHA-256 hashes of email and E.164 phone, for ad platforms' enhanced conversions.
    $redirect_url = add_query_arg( array(
        'EILMLeadID'               => rawurlencode( (string) $lead_id ),
        'EILMLeadProcessingState'  => $is_success ? 'Validated' : 'Invalid',
        'EILMLeadProcessingStatus' => $is_success ? 'Ok' : 'Error',
        'email'                    => hash( 'sha256', $form_data['email'] ),
        'phone'                    => hash( 'sha256', custom_rfi_form_format_phone_e164( $form_data['dayphone'] ) ),
    ), $redirect_url );

    wp_redirect( $redirect_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- admin-configured or signed URL, may be external.
    exit;
}
// Handle both logged-in and non-logged-in users
add_action( 'admin_post_nopriv_custom_rfi_form_submit', 'custom_rfi_form_handle_submission' );
add_action( 'admin_post_custom_rfi_form_submit', 'custom_rfi_form_handle_submission' );
