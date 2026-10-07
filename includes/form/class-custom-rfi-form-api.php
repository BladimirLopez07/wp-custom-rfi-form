<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Custom_RFI_Form_Api Class
 *
 * Loads the campus / program directory for the form from the partner lead API,
 * cached in a transient.
 *
 * - campuses mode (campusesformicrosites): campuses, each with its programs.
 * - programs mode (programsformicrosites): programs, each with the campuses offering it.
 */
class Custom_RFI_Form_Api {

    const MODE_CAMPUSES = 'campuses';
    const MODE_PROGRAMS = 'programs';

    /**
     * How long a successful response is cached, in seconds.
     */
    const CACHE_TTL = DAY_IN_SECONDS;

    /**
     * How long a failed request is remembered, in seconds, so an API outage
     * does not trigger a blocking request on every page view.
     */
    const FAILURE_TTL = MINUTE_IN_SECONDS;

    /**
     * @var string One of the MODE_* constants.
     */
    private $mode;

    /**
     * @var string Feature list ID; falls back to the global feature ID setting.
     */
    private $feature_id;

    /**
     * @var array Plugin settings.
     */
    private $settings;

    /**
     * Constructor
     *
     * @param string $mode       One of the MODE_* constants.
     * @param string $feature_id Feature list ID from the shortcode, if any.
     */
    public function __construct( $mode, $feature_id = '' ) {
        $this->mode       = self::MODE_PROGRAMS === $mode ? self::MODE_PROGRAMS : self::MODE_CAMPUSES;
        $this->settings   = custom_rfi_form_get_settings();
        $this->feature_id = '' !== (string) $feature_id ? (string) $feature_id : (string) $this->settings['global_feature_id'];
    }

    /**
     * Get the API data, from cache when available.
     *
     * @return array API data, or an empty array when the API is unavailable.
     */
    public function get_data() {
        $cache_key = $this->get_cache_key();
        $cached    = get_transient( $cache_key );

        if ( false !== $cached ) {
            return $cached;
        }

        $data = $this->fetch();

        if ( null === $data ) {
            set_transient( $cache_key, array(), self::FAILURE_TTL );
            return array();
        }

        if ( self::MODE_CAMPUSES === $this->mode ) {
            $data = $this->add_areas_of_study( $data );
        }

        set_transient( $cache_key, $data, self::CACHE_TTL );

        return $data;
    }

    /**
     * Request the directory from the API.
     *
     * @return array|null Decoded response body, or null on failure.
     */
    private function fetch() {
        $directory = self::MODE_PROGRAMS === $this->mode ? 'programsformicrosites' : 'campusesformicrosites';
        $endpoint  = custom_rfi_form_get_directory_url( $directory );

        if ( '' === $endpoint ) {
            custom_rfi_form_log( 'Directory request skipped: the API URL for the selected server is not configured.' );
            return null;
        }

        $response = wp_remote_post( $endpoint, array(
            'timeout' => 10,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( array(
                'apiKey'    => $this->settings['form_track_id'],
                'campusId'  => $this->get_campus_id(),
                'featureId' => $this->feature_id,
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            custom_rfi_form_log( sprintf( 'Directory request to %s failed: %s', $endpoint, $response->get_error_message() ) );
            return null;
        }

        $status = (int) wp_remote_retrieve_response_code( $response );
        if ( 200 !== $status ) {
            custom_rfi_form_log( sprintf( 'Directory request to %s returned HTTP %d.', $endpoint, $status ) );
            return null;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $body ) ) {
            custom_rfi_form_log( sprintf( 'Directory request to %s returned invalid JSON.', $endpoint ) );
            return null;
        }

        return $body;
    }

    /**
     * Campus ID to filter by; only used when the site is set up for a single campus.
     *
     * @return string
     */
    private function get_campus_id() {
        return 'multiple' !== $this->settings['campus_type'] ? (string) $this->settings['campus_id'] : '';
    }

    /**
     * Build the per-campus list of areas of study (Body.AOS) and tag each program with its campus.
     *
     * @param array $body API response body.
     * @return array
     */
    private function add_areas_of_study( $body ) {
        if ( empty( $body['Body']['ItemList'] ) || ! is_array( $body['Body']['ItemList'] ) ) {
            return $body;
        }

        $body['Body']['AOS'] = array();

        foreach ( $body['Body']['ItemList'] as &$campus ) {
            if ( empty( $campus['Programs'] ) || ! is_array( $campus['Programs'] ) ) {
                continue;
            }

            $campus_id = isset( $campus['CampusId'] ) ? $campus['CampusId'] : '';

            foreach ( $campus['Programs'] as &$program ) {
                $area_of_study = isset( $program['OptionGroupDisplayValue'] ) ? $program['OptionGroupDisplayValue'] : '';

                if ( '' !== trim( $area_of_study )
                    && ( ! isset( $body['Body']['AOS'][ $campus_id ] ) || ! in_array( $area_of_study, $body['Body']['AOS'][ $campus_id ], true ) ) ) {
                    $body['Body']['AOS'][ $campus_id ][] = $area_of_study;
                }

                $program['CampusId'] = $campus_id;
            }
            unset( $program );
        }
        unset( $campus );

        return $body;
    }

    /**
     * Cache key covering every input that changes the API response.
     *
     * @return string
     */
    private function get_cache_key() {
        return 'custom_rfi_form_' . md5( implode( '|', array(
            custom_rfi_form_cache_version(),
            $this->mode,
            custom_rfi_form_get_api_base_url(),
            $this->settings['form_track_id'],
            $this->get_campus_id(),
            $this->feature_id,
        ) ) );
    }
}
