<?php
/**
 * Submitting a lead: payload, redirects and failure handling.
 */

test( 'a valid lead is normalized, enriched and sent to the selected server', function () {
    set_settings();
    prime_directory();
    $_COOKIE = array( 'RfiAdditionalFields' => 'utm_source=google', '_ga' => 'GA1.1.111.222' );

    $halt = submit_form( valid_post( array( 'BirthDate' => '01/02/1990' ) ) );

    $requests = http_requests();
    assert_same( 'GET', $requests[0]['method'] );
    assert_contains( 'ZipCode=90210', $requests[0]['url'] );
    assert_same( 'https://qa.test/api/institutions/lead-save?returnLeadId=true', $requests[1]['url'] );

    $lead = last_lead_payload();
    assert_same( '(555) 123-4567', $lead['dayphone'] );
    assert_same( 'Beverly Hills', $lead['city'] );
    assert_same( 'CA', $lead['state'] );
    assert_same( '01/02/1990', $lead['BirthDate'] );
    assert_same( 'Terms %26 conditions', $lead['UserAgreement'] );
    assert_same( '111.222', $lead['gacid'] );

    $questions = additional_questions( $lead );
    assert_same( 'google', $questions['utm_source'] );
    assert_same( '203.0.113.7', $questions['EddyIPAddress'] );
    assert_same( 'Test Browser', $questions['LeadUserAgent'] );

    assert_same( 'redirect', $halt->type );
    assert_contains( 'https://example.test/thanks/?EILMLeadID=42&EILMLeadProcessingState=Validated', $halt->data );
    assert_contains( 'email=' . hash( 'sha256', 'jose@example.com' ), $halt->data );
    assert_contains( 'phone=' . hash( 'sha256', '+15551234567' ), $halt->data );
} );

test( 'names keep their spelling: accents, apostrophes and spaces', function () {
    set_settings();
    prime_directory();

    submit_form( valid_post( array( 'firstname' => '  Mary   Ann3 ', 'lastname' => "O\\'Connor-Núñez" ) ) );

    $lead = last_lead_payload();
    assert_same( 'Mary Ann', $lead['firstname'] );
    assert_same( "O'Connor-Núñez", $lead['lastname'] );

    submit_form( valid_post() );
    assert_same( 'José', last_lead_payload()['firstname'] );
} );

test( 'only known fields are forwarded to the API', function () {
    set_settings();
    prime_directory();

    submit_form( valid_post( array( 'injected' => 'x', 'submitURL' => 'https://attacker.test' ) ) );

    $lead = last_lead_payload();
    foreach ( array( 'injected', 'submitURL', 'action', 'custom_rfi_form_nonce', 'returntourl_sig', '_rfi_feature_id' ) as $field ) {
        assert_true( ! array_key_exists( $field, $lead ), "$field must not be forwarded." );
    }
} );

test( 'an array in the landing-page query string is ignored instead of crashing', function () {
    set_settings();
    prime_directory();
    $_COOKIE = array( 'RfiAdditionalFields' => 'utm_source=google&tags[]=a' );

    submit_form( valid_post() );

    $questions = additional_questions( last_lead_payload() );
    assert_same( 'google', $questions['utm_source'] );
    assert_true( ! isset( $questions['tags'] ) );
} );

test( 'a tampered thank-you URL falls back to the configured one', function () {
    set_settings( array( 'thank_you_url_type' => 'external', 'external_thank_you_url' => 'https://school.test/thanks' ) );
    prime_directory();

    $halt = submit_form( valid_post( array( 'returntourl' => 'https://attacker.test/' ) ) );

    assert_same( 0, strpos( $halt->data, 'https://school.test/thanks?' ) );
} );

test( 'honeypot hits are dropped silently', function () {
    set_settings( array( 'honey_pot_field' => 'true' ) );

    $halt = submit_form( valid_post( array( 'validate_xx' => 'bot' ) ), array() );

    assert_same( 'redirect', $halt->type );
    assert_same( 0, count( http_requests() ) );
} );

test( 'an invalid nonce is rejected', function () {
    set_settings();

    $halt = submit_form( valid_post( array( 'custom_rfi_form_nonce' => 'forged' ) ), array() );

    assert_same( 403, $halt->data['status'] );
} );

test( 'debug mode shows administrators the escaped payload and sends nothing', function () {
    set_settings( array( 'debug_mode' => 'true' ) );
    prime_directory();
    $GLOBALS['wp_test']['capabilities'] = array( 'manage_options' );

    $halt = submit_form( valid_post( array( 'AreaOfStudyID' => '<img src=x onerror=alert(1)>' ) ) );

    assert_same( 'die', $halt->type );
    assert_not_contains( '<img', $halt->data['message'] );
    assert_true( ! lead_was_sent() );
} );

test( 'debug mode does not change anything for visitors', function () {
    set_settings( array( 'debug_mode' => 'true' ) );
    prime_directory();

    $halt = submit_form( valid_post() );

    assert_same( 'redirect', $halt->type );
    assert_true( lead_was_sent() );
} );

test( 'an API error shows a generic message', function () {
    set_settings();
    prime_directory();

    $halt = submit_form( valid_post(), array( zip_lookup_response(), json_response( '', 500 ) ) );

    assert_same( 502, $halt->data['status'] );
    assert_not_contains( 'qa.test', $halt->data['message'] );
} );

test( 'without an API URL nothing is sent', function () {
    set_settings( array( 'server_switch' => 'stage' ) );

    $halt = submit_form( valid_post(), array( zip_lookup_response() ) );

    assert_same( 502, $halt->data['status'] );
    assert_true( ! lead_was_sent() );
} );
