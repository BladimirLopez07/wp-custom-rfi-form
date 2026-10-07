<?php
/**
 * Hidden tracking and attribution fields sent with the lead.
 * Empty values are filled in by rfi-tracking.js / custom-form-additional.js or on the server.
 *
 * Expects $form_opt, $fields and $thank_you_url from Custom_RFI_Form_Shortcode::render_shortcode().
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_url = '';
if ( isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) {
    $current_url = esc_url_raw( ( is_ssl() ? 'https://' : 'http://' ) . wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ) );
}

// Signed so the submission handler can trust the posted value (see custom_rfi_form_get_redirect_url()).
$return_url = esc_url_raw( $thank_you_url );
?>
<?php if ( ! in_array( 'campus', $fields, true ) ) : ?>
    <input name="CampusId" id="CampusId" value="<?php echo esc_attr( $form_opt['campus_id'] ); ?>" type="hidden">
<?php endif; ?>

<input type="hidden" name="city" id="city" value="">
<input type="hidden" name="state" id="state" value="">
<input type="hidden" name="country" id="country" value="">
<input type="hidden" name="phone" id="phone" value="">
<input type="hidden" name="returntourl" id="returntourl" value="<?php echo esc_attr( $return_url ); ?>">
<input type="hidden" name="returntourl_sig" id="returntourl_sig" value="<?php echo esc_attr( custom_rfi_form_sign_url( $return_url ) ); ?>">
<input type="hidden" name="TrackingSessionGUID" id="TrackingSessionGUID" value="">
<input type="hidden" name="APIKey" id="APIKey" value="<?php echo esc_attr( $form_opt['form_track_id'] ); ?>">
<input type="hidden" name="UserAgreement" id="UserAgreement" value="<?php echo esc_attr( $form_opt['disclaimer'] ); ?>"/>
<input type="hidden" name="formname" id="formname" value="<?php echo esc_attr( $form_opt['form_name'] ); ?>">
<input type="hidden" name="FormLeadUrl" id="FormLeadUrl" value="<?php echo esc_attr( $current_url ); ?>">
<input type="hidden" name="LeadInitiatingUrl" id="LeadInitiatingUrl" value="">
<input type="hidden" name="LeadSourceUrl" id="LeadSourceUrl" value="<?php echo esc_attr( home_url() ); ?>">
<input type="hidden" name="LeadSourceType" id="LeadSourceType" value="5">
<input type="hidden" name="Keyword" id="Keyword" value="">
<input type="hidden" name="ReturnLeadId" id="ReturnLeadId" value="<?php echo custom_rfi_form_returns_lead_id() ? '1' : ''; ?>">
<input type="hidden" name="SearchEngine" id="SearchEngine" value="">
<input type="hidden" name="SearchEngineCampaign" id="SearchEngineCampaign" value="">
<input type="hidden" name="VendorAccountID" id="VendorAccountID" value="">
<input type="hidden" name="ClientSourceCode" id="ClientSourceCode" value="">
<input type="hidden" name="DeviceType" id="DeviceType" value="">
<input type="hidden" name="Cookies" id="Cookies" value="">
<input type="hidden" name="CheckAPIKey" id="CheckAPIKey" value="">
<input type="hidden" name="CheckAPIStatus" id="CheckAPIStatus" value="">
<input type="hidden" name="CheckAPIErrorMessage" id="CheckAPIErrorMessage" value="">
