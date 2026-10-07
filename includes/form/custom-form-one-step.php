<?php
/**
 * One-step form template, rendered by Custom_RFI_Form_Shortcode::render_shortcode().
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="requestinfo" class="request-info is-one-step">
    <p class="form-header" role="heading" aria-level="3"><?php echo esc_html( $form_title ); ?></p>
    <form name="rfiform" id="rfiform" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="rfiform one-step" novalidate>
        <input type="hidden" name="action" value="custom_rfi_form_submit">
        <?php wp_nonce_field( 'custom_rfi_form_submit', 'custom_rfi_form_nonce' ); ?>

        <?php
        include __DIR__ . '/components/program-fields.php';

        if ( in_array( 'highestEducation', $fields, true ) ) {
            include __DIR__ . '/components/education-level.php';
        }

        include __DIR__ . '/components/contact-fields.php';

        if ( in_array( 'startTerm', $fields, true ) ) {
            include __DIR__ . '/components/start-term.php';
        }
        ?>

        <div class="actions">
            <div class="field">
                <button name="submitBtn" id="submitBtn" type="submit" class="form-button btn"><?php echo esc_html( $submit_button_text ); ?></button>
            </div>
        </div>

        <?php include __DIR__ . '/components/hidden-fields.php'; ?>
    </form>
    <p class="disclaimer"><?php echo esc_html( $form_opt['disclaimer'] ); ?></p>
</div>
