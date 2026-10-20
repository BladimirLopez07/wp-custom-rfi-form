<?php
/**
 * Server-side validation of the built-in fields (includes/lead-validation.php).
 *
 * Every case posts directly to the handler, as a script bypassing the browser would.
 */

/**
 * Submit and assert the lead was rejected with a 400 that mentions $message, and nothing was sent.
 */
function assert_rejected( array $post, $message ) {
    $halt = submit_form( $post );

    assert_same( 'die', $halt->type, 'The submission should have been rejected.' );
    assert_same( 400, $halt->data['status'] );
    assert_contains( $message, $halt->data['message'] );
    assert_true( ! lead_was_sent(), 'An invalid lead must not reach the API.' );
}

test( 'empty or too-short names are rejected', function () {
    set_settings();
    prime_directory();

    assert_rejected( valid_post( array( 'firstname' => '' ) ), 'Please enter your first name.' );
    assert_rejected( valid_post( array( 'lastname' => 'J3!' ) ), 'Please enter your last name.' );
} );

test( 'an invalid email is rejected, not rewritten', function () {
    set_settings();
    prime_directory();

    assert_rejected( valid_post( array( 'email' => 'a@b@example.com' ) ), 'Please enter a valid email address.' );
    assert_rejected( valid_post( array( 'email' => '' ) ), 'Please enter a valid email address.' );
} );

test( 'phone, zip and birth date formats are enforced', function () {
    set_settings( array( 'form_fields' => array( 'campus', 'aos', 'phone', 'zip', 'birthday' ) ) );
    prime_directory();

    assert_rejected( valid_post( array( 'dayphone' => '555-1234', 'BirthDate' => '01/02/1990' ) ), 'Please enter a 10-digit phone number.' );
    assert_rejected( valid_post( array( 'postalcode' => '123456', 'BirthDate' => '01/02/1990' ) ), 'Please enter a 5-digit zip code.' );
    assert_rejected( valid_post( array( 'BirthDate' => '02/30/1990' ) ), 'Please enter a valid birth date.' );
    assert_rejected( valid_post( array( 'BirthDate' => '01/01/' . gmdate( 'Y' ) ) ), 'You must be at least 16 years old.' );
} );

test( 'fields that are turned off are not validated', function () {
    set_settings( array( 'form_fields' => array( 'campus', 'aos' ) ) );
    prime_directory();

    $halt = submit_form( valid_post( array( 'firstname' => '', 'lastname' => '', 'dayphone' => '', 'postalcode' => '' ) ) );

    assert_same( 'redirect', $halt->type );
} );

test( 'a program that is not in the directory is rejected', function () {
    set_settings();
    prime_directory();

    assert_rejected( valid_post( array( 'ProgramId' => 'P999' ) ), 'Please select a program from the list.' );
    assert_rejected( valid_post( array( 'ProgramId' => 'default' ) ), 'Please select a program.' );
} );

test( 'a program must be offered at the selected campus', function () {
    set_settings();
    prime_directory();

    // P3 exists, but only at campus C2.
    assert_rejected( valid_post( array( 'ProgramId' => 'P3' ) ), 'The selected program is not offered at the selected campus.' );
    assert_rejected( valid_post( array( 'CampusId' => 'default' ) ), 'Please select a campus.' );
} );

test( 'program-first directories are checked the same way', function () {
    set_settings( array( 'program_before_campus' => 'true' ) );
    prime_directory( fixture_programs() );

    $halt = submit_form( valid_post( array( 'CampusId' => 'C2' ) ) );
    assert_same( 'redirect', $halt->type, 'P1 is offered at C2.' );

    assert_rejected( valid_post( array( 'CampusId' => 'C9' ) ), 'The selected program is not offered at the selected campus.' );
} );

test( 'the directory is looked up with the signed feature list ID from the form', function () {
    set_settings();
    prime_directory( null, array( 'feature_list_id' => '1234' ) );
    $post = valid_post( array( '_rfi_feature_id' => '1234', '_rfi_feature_sig' => custom_rfi_form_sign( '_rfi_feature_id', '1234' ) ) );

    $halt = submit_form( $post );
    assert_same( 'redirect', $halt->type );
    assert_same( 2, count( http_requests() ), 'The cached directory for feature 1234 should be reused.' );

    // A forged feature ID is ignored: the global directory (not cached here) is requested instead.
    $halt = submit_form( array_merge( $post, array( '_rfi_feature_id' => '9999' ) ), array( new WP_Error( 'down' ), zip_lookup_response(), lead_saved_response() ) );
    assert_true( false === strpos( http_requests()[0]['args']['body'], '9999' ), 'A forged feature ID must not be used.' );
} );

test( 'when the directory is unavailable the program is only checked for presence', function () {
    set_settings();
    queue_http( new WP_Error( 'down' ) );
    render_form(); // Caches the failure

    $halt = submit_form( valid_post( array( 'ProgramId' => 'P999' ) ) );

    assert_same( 'redirect', $halt->type, 'The lead API makes the final decision when the directory is down.' );
} );

test( 'education level and start term must be configured options', function () {
    set_settings( array(
        'form_fields'       => array( 'campus', 'aos', 'highestEducation', 'startTerm' ),
        'highest_education' => "High School : hs\nBachelor's Degree : ba",
        'start_term'        => 'Fall 2026 : fall-2026',
    ) );
    prime_directory();

    assert_rejected( valid_post( array( 'HighestLevelofEducationCompleted' => 'phd', 'StartTermDate' => 'fall-2026' ) ), 'Please select your highest level of education.' );
    assert_rejected( valid_post( array( 'HighestLevelofEducationCompleted' => 'ba', 'StartTermDate' => 'default' ) ), 'Please select a start term.' );

    $halt = submit_form( valid_post( array( 'HighestLevelofEducationCompleted' => 'ba', 'StartTermDate' => 'fall-2026' ) ) );
    assert_same( 'redirect', $halt->type );
} );

test( 'all problems are reported at once', function () {
    set_settings();
    prime_directory();

    $halt = submit_form( valid_post( array( 'firstname' => '', 'email' => 'nope', 'postalcode' => '1' ) ) );

    assert_contains( 'first name', $halt->data['message'] );
    assert_contains( 'valid email', $halt->data['message'] );
    assert_contains( 'zip code', $halt->data['message'] );
} );
