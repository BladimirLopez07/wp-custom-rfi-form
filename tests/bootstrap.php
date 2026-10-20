<?php
/**
 * Test bootstrap: WordPress stubs, the plugin itself, a tiny test registry and
 * helpers for the common scenarios (configure, render the form, submit it).
 */

require __DIR__ . '/wp-stubs.php';

// Load the plugin through its main file, so the include paths are tested too.
require dirname( __DIR__ ) . '/custom-rfi-form.php';

/*
 * ---------------------------------------------------------------------------
 * Registry and assertions
 * ---------------------------------------------------------------------------
 */

$GLOBALS['tests'] = array();

/**
 * Register a test. Each test runs on a clean WordPress state.
 *
 * @param string   $name Description of the behavior under test.
 * @param callable $test Test body; throws on failure.
 */
function test( $name, $test ) {
    $GLOBALS['tests'][] = array( 'name' => $name, 'test' => $test );
}

class Test_Failure extends Exception {}

function assert_true( $condition, $message = 'Expected condition to be true.' ) {
    if ( ! $condition ) {
        throw new Test_Failure( $message );
    }
}

function assert_same( $expected, $actual, $message = '' ) {
    if ( $expected !== $actual ) {
        throw new Test_Failure( trim( $message . ' Expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . '.' ) );
    }
}

function assert_contains( $needle, $haystack, $message = '' ) {
    if ( false === strpos( $haystack, $needle ) ) {
        throw new Test_Failure( trim( $message . ' Expected output to contain: ' . $needle ) );
    }
}

function assert_not_contains( $needle, $haystack, $message = '' ) {
    if ( false !== strpos( $haystack, $needle ) ) {
        throw new Test_Failure( trim( $message . ' Expected output not to contain: ' . $needle ) );
    }
}

/**
 * Assert that the HTML elements with the given IDs appear in this order.
 */
function assert_order( array $ids, $html ) {
    $last = -1;
    foreach ( $ids as $id ) {
        $position = strpos( $html, 'id="' . $id . '"' );
        assert_true( false !== $position, "Element #$id is missing." );
        assert_true( $position > $last, 'Wrong order: ' . implode( ' < ', $ids ) );
        $last = $position;
    }
}

/*
 * ---------------------------------------------------------------------------
 * Fixtures
 * ---------------------------------------------------------------------------
 */

function fixture_campuses() {
    return array( 'Body' => array( 'ItemList' => array(
        array( 'CampusId' => 'C1', 'CampusName' => 'Online', 'Programs' => array(
            array( 'ProgramId' => 'P1', 'ProgramDisplayValue' => 'MBA', 'ProgramLevelName' => 'Masters', 'OptionGroupDisplayValue' => 'Business' ),
            array( 'ProgramId' => 'P2', 'ProgramDisplayValue' => 'BSN', 'ProgramLevelName' => 'Bachelors', 'OptionGroupDisplayValue' => 'Nursing' ),
        ) ),
        array( 'CampusId' => 'C2', 'CampusName' => 'Main', 'Programs' => array(
            array( 'ProgramId' => 'P3', 'ProgramDisplayValue' => 'BA History', 'ProgramLevelName' => 'Bachelors', 'OptionGroupDisplayValue' => 'Arts' ),
        ) ),
    ) ) );
}

function fixture_programs() {
    return array( 'Body' => array( 'ItemList' => array(
        array( 'ProgramId' => 'P1', 'ProgramDisplayValue' => 'MBA', 'OptionGroupDisplayValue' => 'Business', 'CampusList' => array(
            array( 'CampusId' => 'C1', 'CampusName' => 'Online' ),
            array( 'CampusId' => 'C2', 'CampusName' => 'Main' ),
        ) ),
    ) ) );
}

function json_response( $data, $code = 200 ) {
    return array( 'code' => $code, 'body' => is_string( $data ) ? $data : json_encode( $data ) );
}

function zip_lookup_response() {
    return json_response( 'callback([{"Key":"City","Value":"Beverly Hills"},{"Key":"StateCode","Value":"CA"},{"Key":"CountryCode","Value":"US"}])' );
}

function lead_saved_response( $lead_id = 42 ) {
    return json_response( array( 'IsSuccessful' => true, 'Body' => array( 'LeadId' => $lead_id ) ) );
}

/*
 * ---------------------------------------------------------------------------
 * Scenario helpers
 * ---------------------------------------------------------------------------
 */

/**
 * Save plugin settings (merged over sensible test defaults).
 */
function set_settings( array $settings = array() ) {
    update_option( 'custom_rfi_form_settings', array_merge( array(
        'form_track_id' => 'TRACK-1',
        'server_switch' => 'qa',
        'api_url_qa'    => 'https://qa.test/api',
        'api_url_prod'      => 'https://api.test/api',
        'postal_lookup_url' => 'https://zip.test/lookup',
        'form_fields'       => array( 'campus', 'aos', 'fname', 'lname', 'phone', 'zip' ),
    ), $settings ) );
}

function queue_http( ...$responses ) {
    foreach ( $responses as $response ) {
        $GLOBALS['wp_test']['http_responses'][] = $response;
    }
}

function http_requests() {
    return $GLOBALS['wp_test']['http_requests'];
}

/**
 * Render the shortcode as a fresh page load would.
 */
function render_form( array $atts = array() ) {
    $flag = new ReflectionProperty( 'Custom_RFI_Form_Shortcode', 'assets_enqueued' );
    $flag->setAccessible( true );
    $flag->setValue( null, false );
    $GLOBALS['wp_test']['scripts'] = array();

    return Custom_RFI_Form_Shortcode::render_shortcode( $atts );
}

function enqueued( $pattern ) {
    return (bool) preg_grep( $pattern, $GLOBALS['wp_test']['scripts'] );
}

/**
 * A valid submission for the default test settings (campus-first, campus C1, program P1).
 */
function valid_post( array $overrides = array() ) {
    return array_merge( array(
        'action'                => 'custom_rfi_form_submit',
        'custom_rfi_form_nonce' => 'valid-nonce',
        'CampusId'              => 'C1',
        'AreaOfStudyID'         => 'Business',
        'ProgramId'             => 'P1',
        'firstname'             => 'José',
        'lastname'              => "O\\'Connor", // Slashed, as WordPress delivers $_POST
        'dayphone'              => '(555) 123-4567',
        'email'                 => 'jose@example.com',
        'postalcode'            => '90210',
        'APIKey'                => 'TRACK-1',
        'UserAgreement'         => 'Terms & conditions',
        'returntourl'           => 'https://example.test/thanks/',
        'returntourl_sig'       => custom_rfi_form_sign( 'returntourl', 'https://example.test/thanks/' ),
        '_rfi_feature_id'       => '',
        '_rfi_feature_sig'      => custom_rfi_form_sign( '_rfi_feature_id', '' ),
        'Cookies'               => 'true',
        'DeviceType'            => 'Desktop',
    ), $overrides );
}

/**
 * Submit the form and return how the request ended.
 *
 * The campus-first directory is cached up front (as it would be after the form
 * was rendered), so only the zip lookup and the lead submission hit the network.
 *
 * @return WP_Test_Halt
 */
function submit_form( array $post, array $responses = null ) {
    $_SERVER['REQUEST_METHOD']  = 'POST';
    $_SERVER['REMOTE_ADDR']     = '203.0.113.7';
    $_SERVER['HTTP_USER_AGENT'] = 'Test Browser';
    $_POST                      = $post;

    $GLOBALS['wp_test']['http_requests']  = array();
    $GLOBALS['wp_test']['http_responses'] = null === $responses ? array( zip_lookup_response(), lead_saved_response() ) : $responses;

    try {
        custom_rfi_form_handle_submission();
    } catch ( WP_Test_Halt $halt ) {
        return $halt;
    }

    throw new Test_Failure( 'The submission handler did not end the request.' );
}

/**
 * Prime the directory cache the way rendering the form does.
 */
function prime_directory( $data = null, $atts = array() ) {
    queue_http( json_response( null === $data ? fixture_campuses() : $data ) );
    render_form( $atts );
}

/**
 * The JSON payload sent to the lead API in the last submission.
 */
function last_lead_payload() {
    foreach ( array_reverse( http_requests() ) as $request ) {
        if ( false !== strpos( $request['url'], '/institutions/lead-save' ) ) {
            return json_decode( $request['args']['body'], true );
        }
    }
    throw new Test_Failure( 'No lead was sent to the API.' );
}

function additional_questions( array $payload ) {
    return array_column( $payload['AdditionalQuestions'], 'QuestionValue', 'QuestionKey' );
}

function lead_was_sent() {
    foreach ( http_requests() as $request ) {
        if ( false !== strpos( $request['url'], '/institutions/lead-save' ) ) {
            return true;
        }
    }
    return false;
}
