<?php
/**
 * Runs the PHP test suite: php tests/run.php
 *
 * Exits with status 1 if any test fails, so CI marks the build as failed.
 */

error_reporting( E_ALL );
ini_set( 'display_errors', '1' );
// The plugin logs API failures with error_log(); keep them out of the test output.
ini_set( 'error_log', sys_get_temp_dir() . '/custom-rfi-form-tests.log' );

// Turn warnings and notices into failures instead of letting them scroll by.
set_error_handler( function ( $severity, $message, $file, $line ) {
    throw new ErrorException( $message, 0, $severity, $file, $line );
} );

require __DIR__ . '/bootstrap.php';

foreach ( glob( __DIR__ . '/php/*-test.php' ) as $file ) {
    require $file;
}

$failures = 0;
foreach ( $GLOBALS['tests'] as $case ) {
    reset_wp_state();
    try {
        call_user_func( $case['test'] );
        echo "  \u{2713} {$case['name']}\n";
    } catch ( Throwable $error ) {
        $failures++;
        $where = $error instanceof Test_Failure ? '' : ' (' . basename( $error->getFile() ) . ':' . $error->getLine() . ')';
        echo "  \u{2717} {$case['name']}\n      " . get_class( $error ) . ': ' . $error->getMessage() . $where . "\n";
    }
}

$total = count( $GLOBALS['tests'] );
echo "\n" . ( $total - $failures ) . " of $total tests passed.\n";
exit( $failures ? 1 : 0 );
