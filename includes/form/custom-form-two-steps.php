<?php
/**
 * Two-step form template, rendered by Custom_RFI_Form_Shortcode::render_shortcode().
 * Step one collects the program choice, step two the contact details.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="requestinfo" class="request-info is-two-step">
    <p class="form-header" role="heading" aria-level="3"><?php echo esc_html( $form_title ); ?></p>
    <form name="rfiform" id="rfiform" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="rfiform two-step" novalidate>
        <input type="hidden" name="action" value="custom_rfi_form_submit">
        <?php wp_nonce_field( 'custom_rfi_form_submit', 'custom_rfi_form_nonce' ); ?>

        <div class="step-one">
            <div class="step-one-images">
                <!-- Step one Arrow SVG -->
                <svg version="1.2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 169 17" width="169" height="17">
                    <path id="Path_121" class="s0" d="m0 0h160.4l8.2 8.4-8.2 8.4h-160.4z" fill="var(--progress-bar-color)"/>
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" width="157.194" height="16.776" viewBox="0 0 157.194 16.776">
                    <path id="Path_122" data-name="Path 122" d="M772,1921H929.194v16.776H772l8.294-8.55Z" transform="translate(-772 -1921)" fill="#A7A8AA"/>
                </svg>
            </div>
            <p class="steps-text"><strong>Step 1 of 2</strong></p>

            <?php
            include __DIR__ . '/components/program-fields.php';

            if ( in_array( 'highestEducation', $fields, true ) ) {
                include __DIR__ . '/components/education-level.php';
            }

            if ( in_array( 'startTerm', $fields, true ) ) {
                include __DIR__ . '/components/start-term.php';
            }
            ?>

            <button class="next-step form-button btn" type="button">Next Step</button>
        </div>

        <div class="step-two hide-step">
            <div class="step-two-images">
                <svg version="1.2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 169 17" width="169" height="17">
                    <path id="Path_121" class="s0" d="m0 0h160.4l8.2 8.4-8.2 8.4h-160.4z" fill="var(--progress-bar-color)"/>
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" width="157.194" height="16.776" viewBox="0 0 157.194 16.776">
                    <path id="Path_122" data-name="Path 122" d="M772,1921H929.194v16.776H772l8.294-8.55Z" transform="translate(-772 -1921)" fill="var(--progress-bar-color)"/>
                </svg>
            </div>
            <p class="steps-text"><strong>Step 2 of 2</strong></p>

            <?php include __DIR__ . '/components/contact-fields.php'; ?>

            <div class="actions">
                <div class="field">
                    <span class="goback laststep">« Go Back</span>
                    <button name="submitBtn" id="submitBtn" type="submit" class="form-button btn"><?php echo esc_html( $submit_button_text ); ?></button>
                </div>
            </div>
            <p class="disclaimer"><?php echo esc_html( $form_opt['disclaimer'] ); ?></p>
        </div>

        <?php include __DIR__ . '/components/hidden-fields.php'; ?>
    </form>
</div>
