<?php
/**
 * The custom_rfi_form_fields filter (includes/form/fields.php).
 */

function add_test_fields() {
    add_filter( 'custom_rfi_form_fields', function ( $fields ) {
        $fields['company']  = array( 'label' => 'Company', 'priority' => 35, 'required' => true, 'max_length' => 10 );
        $fields['notes']    = array( 'label' => 'Notes', 'type' => 'textarea', 'priority' => 5 );
        $fields['website']  = array( 'label' => 'Website', 'type' => 'url', 'priority' => 36, 'additional_question' => false );
        $fields['archived'] = array( 'label' => 'Archived', 'enabled' => false );
        return $fields;
    } );
}

test( 'custom fields are placed by priority among the built-in ones', function () {
    set_settings();
    add_test_fields();
    queue_http( json_response( fixture_campuses() ) );

    $html = render_form();

    assert_order( array( 'notes', 'firstname', 'lastname', 'dayphone', 'company', 'website', 'email', 'postalcode' ), $html );
    assert_true( (bool) preg_match( '/<textarea[^>]*id="notes"[^>]*data-rfi-custom="1"[^>]*><\/textarea>/', $html ), 'Textarea not rendered.' );
    assert_true( (bool) preg_match( '/<input[^>]*id="company"[^>]*required="required"[^>]*maxlength="10"/', $html ), 'Required field attributes missing.' );
    assert_true( ! preg_match( '/<input[^>]*id="website"[^>]*required/', $html ), 'Optional field must not be required.' );
    assert_not_contains( 'id="archived"', $html );
} );

test( 'invalid custom field definitions are skipped with a notice', function () {
    set_settings();
    add_filter( 'custom_rfi_form_fields', function ( $fields ) {
        $fields['company'] = array( 'label' => 'Company' );
        $fields['bad']     = array( 'name' => '1st-choice' );
        $fields['clash']   = array( 'name' => 'email' );
        $fields['dupe']    = array( 'name' => 'company' );
        $fields['select']  = array( 'type' => 'select' );
        return $fields;
    } );

    $fields = custom_rfi_form_get_fields();

    assert_true( isset( $fields['company'] ) );
    foreach ( array( 'bad', 'clash', 'dupe', 'select' ) as $key ) {
        assert_true( ! isset( $fields[ $key ] ), "Field $key should have been skipped." );
    }
    assert_same( 4, count( $GLOBALS['wp_test']['notices'] ) );
} );

test( 'built-in fields can be relabeled, moved and turned off, but keep their name', function () {
    set_settings();
    add_filter( 'custom_rfi_form_fields', function ( $fields ) {
        $fields['fname']['label']   = 'Given name';
        $fields['fname']['name']    = 'hijacked';
        $fields['zip']['priority']  = 15;
        $fields['phone']['enabled'] = false;
        return $fields;
    } );

    $fields = custom_rfi_form_get_fields();

    assert_same( 'Given name', $fields['fname']['label'] );
    assert_same( 'firstname', $fields['fname']['name'] );
    assert_same( array( 'fname', 'zip', 'lname' ), array_slice( array_keys( $fields ), 0, 3 ) );
    assert_same( false, $fields['phone']['enabled'] );
} );

test( 'the email field cannot be removed or turned off', function () {
    set_settings();
    add_filter( 'custom_rfi_form_fields', function ( $fields ) {
        $fields['email']['enabled'] = false;
        $fields['email']['label']   = 'Work email';
        return $fields;
    } );
    add_filter( 'custom_rfi_form_fields', function ( $fields ) {
        unset( $fields['email'] );
        return $fields;
    } );

    $fields = custom_rfi_form_get_fields();

    assert_true( isset( $fields['email'] ) && true === $fields['email']['enabled'] );
} );

test( 'custom field values are sanitized, length-limited and sent with the lead', function () {
    set_settings();
    add_test_fields();
    prime_directory();

    submit_form( valid_post( array(
        'company'  => 'Acme Corporation Inc',
        'website'  => 'https://acme.test',
        'notes'    => "line one\nline two",
        'archived' => 'x',
    ) ) );

    $lead      = last_lead_payload();
    $questions = additional_questions( $lead );
    assert_same( 'Acme Corpo', $questions['company'] );
    assert_same( "line one\nline two", $questions['notes'] );
    assert_same( 'https://acme.test', $lead['website'], 'additional_question => false sends a top-level field.' );
    assert_true( ! isset( $questions['website'] ) && ! isset( $questions['archived'] ) && ! isset( $lead['archived'] ) );
} );

test( 'an empty required custom field is rejected on the server', function () {
    set_settings();
    add_test_fields();
    prime_directory();

    $halt = submit_form( valid_post( array( 'company' => '   ' ) ) );

    assert_same( 400, $halt->data['status'] );
    assert_contains( 'Company', $halt->data['message'] );
    assert_true( ! lead_was_sent() );
} );
