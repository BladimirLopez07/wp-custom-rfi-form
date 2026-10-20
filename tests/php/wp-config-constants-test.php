<?php
/**
 * Endpoint constants defined in wp-config.php.
 *
 * Constants cannot be undefined, so this file sorts last and its tests run after all others.
 */

test( 'a wp-config.php constant overrides the endpoint setting', function () {
    define( 'CUSTOM_RFI_FORM_API_URL_QA', 'https://locked.test/api/' );
    set_settings( array( 'server_switch' => 'qa', 'api_url_qa' => 'https://from-settings.test/api' ) );

    assert_same( 'https://locked.test/api', custom_rfi_form_get_api_base_url() );
} );

test( 'an insecure constant is ignored like an insecure setting', function () {
    define( 'CUSTOM_RFI_FORM_API_URL_PROD', 'http://insecure.test/api' );
    set_settings( array( 'server_switch' => 'prod', 'api_url_prod' => 'https://from-settings.test/api' ) );

    assert_same( '', custom_rfi_form_get_api_base_url() );
} );
