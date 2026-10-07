<?php
/**
 * Campus, Area of Study and Program selectors, in the order set by "Programs Before Campus".
 *
 * Expects $fields, $program_first, $form and $api_data from Custom_RFI_Form_Shortcode::render_shortcode().
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( $program_first ) {
    if ( in_array( 'aos', $fields, true ) ) {
        include __DIR__ . '/program-first/area-of-study-id.php';
    }

    include __DIR__ . '/program-first/program-id.php';

    if ( in_array( 'campus', $fields, true ) ) {
        include __DIR__ . '/program-first/campus-id.php';
    }
} else {
    if ( in_array( 'campus', $fields, true ) ) {
        include __DIR__ . '/campus-first/campus-id.php';
    }

    if ( in_array( 'aos', $fields, true ) ) {
        include __DIR__ . '/campus-first/area-of-study-id.php';
    }

    include __DIR__ . '/campus-first/program-id.php';
}
