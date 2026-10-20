<?php
/**
 * Minimal stand-ins for the WordPress functions the plugin calls.
 *
 * They keep state in $GLOBALS['wp_test'] (options, transients, filters, HTTP
 * requests and queued responses), so tests can set up a scenario and inspect what
 * the plugin did. reset_wp_state() starts every test from a clean slate.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );

/**
 * Thrown by wp_die() and the redirect functions, which end the request in WordPress.
 */
class WP_Test_Halt extends Exception {
    public $type;
    public $data;

    public function __construct( $type, $data ) {
        parent::__construct( $type );
        $this->type = $type;
        $this->data = $data;
    }
}

class WP_Error {
    private $message;

    public function __construct( $code = '', $message = '' ) {
        $this->message = '' !== $message ? $message : $code;
    }

    public function get_error_message() {
        return $this->message;
    }
}

class WP_Post {}

function reset_wp_state() {
    $GLOBALS['wp_test'] = array(
        'options'        => array(),
        'transients'     => array(),
        'filters'        => array(),
        'capabilities'   => array(),
        'http_responses' => array(),
        'http_requests'  => array(),
        'scripts'        => array(),
        'notices'        => array(),
    );
    $_POST   = array();
    $_GET    = array();
    $_COOKIE = array();
    $_SERVER = array( 'REQUEST_METHOD' => 'GET' );
}
reset_wp_state();

// Hooks
function add_action() {}
function add_shortcode() {}
function register_activation_hook() {}
function add_filter( $tag, $callback ) {
    $GLOBALS['wp_test']['filters'][ $tag ][] = $callback;
}
function apply_filters( $tag, $value ) {
    foreach ( isset( $GLOBALS['wp_test']['filters'][ $tag ] ) ? $GLOBALS['wp_test']['filters'][ $tag ] : array() as $callback ) {
        $value = call_user_func( $callback, $value );
    }
    return $value;
}

// Arguments
function wp_parse_args( $args, $defaults = array() ) {
    return array_merge( $defaults, (array) $args );
}
function shortcode_atts( $pairs, $atts ) {
    $atts = (array) $atts;
    $out  = array();
    foreach ( $pairs as $name => $default ) {
        $out[ $name ] = array_key_exists( $name, $atts ) ? $atts[ $name ] : $default;
    }
    return $out;
}

// Options and transients
function get_option( $name, $default = false ) {
    return array_key_exists( $name, $GLOBALS['wp_test']['options'] ) ? $GLOBALS['wp_test']['options'][ $name ] : $default;
}
function update_option( $name, $value ) {
    $GLOBALS['wp_test']['options'][ $name ] = $value;
    return true;
}
function get_transient( $name ) {
    return array_key_exists( $name, $GLOBALS['wp_test']['transients'] ) ? $GLOBALS['wp_test']['transients'][ $name ] : false;
}
function set_transient( $name, $value ) {
    $GLOBALS['wp_test']['transients'][ $name ] = $value;
    return true;
}

// Users, nonces and salts
function current_user_can( $capability ) {
    return in_array( $capability, $GLOBALS['wp_test']['capabilities'], true );
}
function wp_nonce_field() {
    echo '<input type="hidden" name="custom_rfi_form_nonce" value="valid-nonce">';
}
function wp_verify_nonce( $nonce ) {
    return 'valid-nonce' === $nonce;
}
function wp_salt() {
    return 'test-salt';
}

// Sanitizing and escaping
function wp_unslash( $value ) {
    return is_string( $value ) ? stripslashes( $value ) : $value;
}
function sanitize_text_field( $value ) {
    return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $value ) ) );
}
function sanitize_textarea_field( $value ) {
    return trim( strip_tags( (string) $value ) );
}
function sanitize_key( $value ) {
    return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}
function sanitize_email( $email ) {
    return is_email( $email ) ? $email : '';
}
function sanitize_hex_color( $color ) {
    return preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', (string) $color ) ? $color : null;
}
function is_email( $email ) {
    return false !== filter_var( $email, FILTER_VALIDATE_EMAIL );
}
function esc_html( $value ) {
    return htmlspecialchars( (string) $value, ENT_QUOTES );
}
function esc_attr( $value ) {
    return htmlspecialchars( (string) $value, ENT_QUOTES );
}
function esc_url( $url ) {
    return esc_attr( $url );
}
function esc_url_raw( $url, $protocols = null ) {
    $protocols = $protocols ? $protocols : array( 'http', 'https' );
    return preg_match( '#^([a-z]+)://#i', (string) $url, $match ) && in_array( strtolower( $match[1] ), $protocols, true ) ? $url : '';
}
function esc_html__( $text ) {
    return esc_html( $text );
}
function __( $text ) {
    return $text;
}
function wp_json_encode( $data, $flags = 0 ) {
    return json_encode( $data, $flags );
}
function _doing_it_wrong( $function, $message ) {
    $GLOBALS['wp_test']['notices'][] = $message;
}

// URLs
function home_url( $path = '' ) {
    return 'https://example.test' . $path;
}
function admin_url( $path = '' ) {
    return 'https://example.test/wp-admin/' . $path;
}
function plugin_dir_path( $file ) {
    return dirname( $file ) . '/';
}
function plugin_dir_url( $file ) {
    return 'https://example.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
}
function is_ssl() {
    return true;
}
function wp_parse_url( $url ) {
    return parse_url( $url );
}
function untrailingslashit( $value ) {
    return rtrim( $value, '/\\' );
}
function add_query_arg( $key, $value = null, $url = null ) {
    if ( is_array( $key ) ) {
        $url   = $value;
        $query = $key;
    } else {
        $query = array( $key => $value );
    }
    return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . http_build_query( $query );
}
function get_pages() {
    return array();
}

// Assets
function wp_enqueue_style( $handle ) {
    $GLOBALS['wp_test']['scripts'][] = "style:$handle";
}
function wp_add_inline_style( $handle, $css ) {
    $GLOBALS['wp_test']['scripts'][] = "inline-style:$css";
}
function wp_enqueue_script( $handle, $src, $deps = array() ) {
    $GLOBALS['wp_test']['scripts'][] = "script:$handle deps=" . implode( ',', $deps );
}
function wp_add_inline_script( $handle, $code ) {
    $GLOBALS['wp_test']['scripts'][] = "inline-script:$code";
}

// HTTP: requests are recorded, responses are taken from the queue in order.
function wp_remote_post( $url, $args = array() ) {
    $GLOBALS['wp_test']['http_requests'][] = array( 'method' => 'POST', 'url' => $url, 'args' => $args );
    return array_shift( $GLOBALS['wp_test']['http_responses'] );
}
function wp_remote_get( $url, $args = array() ) {
    $GLOBALS['wp_test']['http_requests'][] = array( 'method' => 'GET', 'url' => $url, 'args' => $args );
    return array_shift( $GLOBALS['wp_test']['http_responses'] );
}
function is_wp_error( $value ) {
    return $value instanceof WP_Error;
}
function wp_remote_retrieve_response_code( $response ) {
    return $response['code'];
}
function wp_remote_retrieve_body( $response ) {
    return $response['body'];
}

// Ending the request
function wp_die( $message, $title = '', $args = array() ) {
    throw new WP_Test_Halt( 'die', array( 'message' => $message, 'status' => isset( $args['response'] ) ? $args['response'] : 500 ) );
}
function wp_redirect( $url ) {
    throw new WP_Test_Halt( 'redirect', $url );
}
function wp_safe_redirect( $url ) {
    throw new WP_Test_Halt( 'redirect', $url );
}

// Settings API (used by the settings sanitizer)
function add_settings_error( $setting, $code, $message ) {
    $GLOBALS['wp_test']['notices'][] = $message;
}
