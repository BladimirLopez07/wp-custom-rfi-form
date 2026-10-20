<?php
/**
 * Rendering the [custom_rfi_form] shortcode.
 */

test( 'campus-first form loads the directory from the selected server and renders its options', function () {
    set_settings( array( 'server_switch' => 'prod' ) );
    queue_http( json_response( fixture_campuses() ) );

    $html = render_form();

    assert_same( 'https://api.test/api/directory/campusesformicrosites', http_requests()[0]['url'] );
    assert_contains( '<option value="C1">Online</option>', $html );
    assert_contains( 'data-campus="C1" data-aos-id="1">Business', $html );
    assert_contains( 'value="P1">MBA', $html );
    assert_order( array( 'CampusId', 'AreaOfStudyID', 'ProgramId', 'firstname', 'lastname', 'dayphone', 'email', 'postalcode' ), $html );
} );

test( 'shortcode attributes are escaped', function () {
    set_settings();
    queue_http( json_response( fixture_campuses() ) );

    $html = render_form( array(
        'form_title'         => '"><script>alert(1)</script>',
        'submit_button_text' => '<b>Go</b>',
    ) );

    assert_not_contains( '<script>', $html );
    assert_contains( '>Go</button>', $html );
} );

test( 'the thank-you URL and feature list ID are posted back with signatures', function () {
    set_settings();
    queue_http( json_response( fixture_campuses() ) );

    $html = render_form( array( 'thank_you_page' => 'https://example.test/thanks/', 'feature_list_id' => '1234' ) );

    assert_contains( 'name="returntourl" id="returntourl" value="https://example.test/thanks/"', $html );
    assert_contains( custom_rfi_form_sign( 'returntourl', 'https://example.test/thanks/' ), $html );
    assert_contains( 'name="_rfi_feature_id" value="1234"', $html );
    assert_contains( custom_rfi_form_sign( '_rfi_feature_id', '1234' ), $html );
} );

test( 'scripts get their configuration and load after the tracking script', function () {
    set_settings();
    queue_http( json_response( fixture_campuses() ) );

    render_form();

    assert_true( enqueued( '/^inline-script:window\.CustomRfiFormData = .*"form_steps":"1"/' ), 'Config object missing.' );
    assert_true( enqueued( '/^script:custom-rfi-form-filtering deps=custom-rfi-form-tracking$/' ), 'Filtering script should depend on tracking.' );
    assert_true( enqueued( '/--form-background: #F6F7F2/' ), 'Color variables missing.' );
} );

test( 'directory responses are cached, and clearing the cache fetches them again', function () {
    set_settings();
    queue_http( json_response( fixture_campuses() ) );
    render_form();
    render_form();
    assert_same( 1, count( http_requests() ), 'Second render should use the cache.' );

    custom_rfi_form_clear_cache();
    queue_http( json_response( fixture_campuses() ) );
    render_form();
    assert_same( 2, count( http_requests() ) );
} );

test( 'an API failure still renders the form and is cached briefly', function () {
    set_settings();
    queue_http( new WP_Error( 'http_request_failed', 'timeout' ) );

    $html = render_form();
    render_form();

    assert_contains( 'id="rfiform"', $html );
    assert_same( 1, count( http_requests() ), 'A failure should not be retried on every page view.' );
} );

test( 'without an API URL for the selected server no request is made', function () {
    set_settings( array( 'server_switch' => 'stage' ) );

    $html = render_form();

    assert_same( 0, count( http_requests() ) );
    assert_contains( 'id="rfiform"', $html );
} );

test( 'program-first two-step form: selectors in step one, contact details in step two', function () {
    set_settings( array(
        'program_before_campus' => 'true',
        'honey_pot_field'       => 'true',
        'form_fields'           => array( 'campus', 'aos', 'fname', 'startTerm' ),
    ) );
    queue_http( json_response( fixture_programs() ) );

    $html = render_form( array( 'steps' => 'two' ) );

    assert_same( 'https://qa.test/api/directory/programsformicrosites', http_requests()[0]['url'] );
    assert_contains( 'is-two-step', $html );
    assert_true( enqueued( '/"form_steps":"2"/' ), 'steps="two" must reach the scripts as "2".' );
    assert_contains( 'data-campus="C1,C2"', $html );
    assert_order( array( 'AreaOfStudyID', 'ProgramId', 'CampusId', 'StartTermDate' ), $html );

    $step_two = strpos( $html, 'class="step-two' );
    assert_true( strpos( $html, 'id="StartTermDate"' ) < $step_two, 'Start term belongs in step one.' );
    assert_true( strpos( $html, 'id="firstname"' ) > $step_two, 'Contact details belong in step two.' );
} );

test( 'the honeypot is hidden from people and never required', function () {
    set_settings( array( 'honey_pot_field' => 'true' ) );
    queue_http( json_response( fixture_campuses() ) );

    $html = render_form();

    assert_true( (bool) preg_match( '/<input[^>]*id="validate_xx"[^>]*tabindex="-1"/', $html ), 'Honeypot should be out of the tab order.' );
    assert_true( ! preg_match( '/<input[^>]*id="validate_xx"[^>]*required/', $html ), 'Honeypot must not be required.' );
} );

test( 'a hidden campus field carries the configured campus', function () {
    set_settings( array( 'program_before_campus' => 'true', 'campus_type' => 'single', 'campus_id' => 'C1', 'form_fields' => array( 'fname' ) ) );
    queue_http( json_response( fixture_programs() ) );

    $html = render_form();

    assert_contains( '<input name="CampusId" id="CampusId" value="C1" type="hidden">', $html );
    assert_not_contains( '<select aria-label="Campus"', $html );
} );
