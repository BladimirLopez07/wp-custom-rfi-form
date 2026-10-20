<?php
/**
 * Settings sanitizer and endpoint configuration.
 */

test( 'the settings sanitizer only keeps allowed values', function () {
    $saved = Custom_RFI_Form_Sanitize_Settings::sanitize_settings( array(
        'form_track_id'          => ' T ',
        'form_title'             => 'Title',
        'server_switch'          => 'evil',
        'form_fields'            => array( 'fname', 'evil' ),
        'color_scheme'           => array( 'formBackground' => '#fff', 'errorColor' => 'red;}', 'bogus' => '#000' ),
        'external_thank_you_url' => 'javascript:alert(1)',
        'internal_thank_you_url' => 'https://elsewhere.test/',
        'return_lead_id'         => array( 'lead_id', 'x' ),
    ) );

    assert_same( 'T', $saved['form_track_id'] );
    assert_same( 'qa', $saved['server_switch'] );
    assert_same( array( 'fname' ), $saved['form_fields'] );
    assert_same( array( 'formBackground' => '#fff' ), $saved['color_scheme'] );
    assert_same( '', $saved['external_thank_you_url'] );
    assert_same( '', $saved['internal_thank_you_url'] );
    assert_same( array( 'lead_id' ), $saved['return_lead_id'] );
} );

test( 'endpoint URLs must be HTTPS without credentials, query or fragment', function () {
    $saved = Custom_RFI_Form_Sanitize_Settings::sanitize_settings( array(
        'form_track_id'     => 'T',
        'form_title'        => 'Title',
        'api_url_prod'      => 'http://plain.test/api',
        'api_url_qa'        => 'https://qa.test/api?x=1',
        'api_url_stage'     => 'http://stage.local/api/',
        'postal_lookup_url' => 'https://user:pass@zip.test/',
        'trackid_check_url' => 'javascript:alert(1)',
    ) );

    assert_same( '', $saved['api_url_prod'] );
    assert_same( '', $saved['api_url_qa'] );
    assert_same( 'http://stage.local/api', $saved['api_url_stage'], 'Staging may use HTTP; the trailing slash is dropped.' );
    assert_same( '', $saved['postal_lookup_url'] );
    assert_same( '', $saved['trackid_check_url'] );
    assert_same( 4, count( $GLOBALS['wp_test']['notices'] ), 'Each rejected URL is reported.' );
} );
