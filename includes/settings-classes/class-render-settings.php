<?php
// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the settings
 * 
 */

class Custom_RFI_Form_Render_Settings {

    /**
     * Render a radio button field.
     *
     * Outputs HTML for a radio button field with the provided options.
     *
     * @param array $args Arguments for rendering the field.
     * @return void
     */
    
    public static function render_radio_field( $args ) {
        $options     = $args['options'];
        $label_for   = $args['label_for'];
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $required    = isset( $args['required'] ) && $args['required'] ? 'required' : '';

        $settings = custom_rfi_form_get_settings();
        $value    = isset( $settings[ $label_for ] ) ? $settings[ $label_for ] : '';

        foreach ( $options as $key => $label ) {
            ?>
            <label>
                <input type="radio" name="custom_rfi_form_settings[<?php echo esc_attr( $label_for ); ?>]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $value, $key ); ?> <?php echo $required; ?>>
                <?php echo esc_html( $label ); ?>
            </label><br>
            <?php
        }

        if ( $description ) {
            echo '<p class="description">' . esc_html( $description ) . '</p>';
        }
    }

    /**
     * Render a text input field.
     *
     * Outputs HTML for a single-line text input field.
     *
     * @param array $args Arguments for rendering the field.
     * @return void
     */

    public static function render_text_field( $args ) {
        $label_for   = $args['label_for'];
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $required    = isset( $args['required'] ) && $args['required'] ? 'required' : '';

        $settings = custom_rfi_form_get_settings();
        $value    = isset( $settings[ $label_for ] ) ? $settings[ $label_for ] : '';

        ?>
        <input type="text" name="custom_rfi_form_settings[<?php echo esc_attr( $label_for ); ?>]" id="<?php echo esc_attr( $label_for ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" <?php echo $required; ?>>
        <?php
        if ( $description ) {
            echo '<p class="description">' . esc_html( $description ) . '</p>';
        }
    }

    /**
     * Render an endpoint URL field.
     *
     * Read-only when the URL is defined by a constant in wp-config.php.
     *
     * @param array $args Arguments for rendering the field.
     * @return void
     */
    public static function render_endpoint_field( $args ) {
        $label_for   = $args['label_for'];
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
        $constants   = custom_rfi_form_endpoint_constants();
        $constant    = $constants[ $label_for ];

        if ( defined( $constant ) ) {
            $url = custom_rfi_form_get_endpoint( $label_for );
            echo '<code>' . esc_html( $url ? $url : __( '(invalid URL)', 'custom-rfi-form' ) ) . '</code>';
            echo '<p class="description">' . esc_html__( 'Set in wp-config.php by', 'custom-rfi-form' ) . ' <code>' . esc_html( $constant ) . '</code>.</p>';
            return;
        }

        $settings = custom_rfi_form_get_settings();
        $value    = isset( $settings[ $label_for ] ) ? $settings[ $label_for ] : '';
        ?>
        <input type="url" name="custom_rfi_form_settings[<?php echo esc_attr( $label_for ); ?>]" id="<?php echo esc_attr( $label_for ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="regular-text code">
        <?php
        if ( $description ) {
            echo '<p class="description">' . esc_html( $description ) . ' ' . esc_html__( 'Can also be set in wp-config.php with', 'custom-rfi-form' ) . ' <code>' . esc_html( $constant ) . '</code>.</p>';
        }
    }

    /**
     * Render a select dropdown field.
     *
     * Outputs HTML for a select dropdown with the provided options.
     *
     * @param array $args Arguments for rendering the field.
     * @return void
     */

    public static function render_select_field( $args ) {
        $options     = isset( $args['options_callback'] ) ? call_user_func( $args['options_callback'] ) : $args['options'];
        $label_for   = $args['label_for'];
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $required    = isset( $args['required'] ) && $args['required'] ? 'required' : '';

        $settings = custom_rfi_form_get_settings();
        $value    = isset( $settings[ $label_for ] ) ? $settings[ $label_for ] : '';

        ?>
        <select name="custom_rfi_form_settings[<?php echo esc_attr( $label_for ); ?>]" id="<?php echo esc_attr( $label_for ); ?>" <?php echo $required; ?>>
            <?php foreach ( $options as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
        if ( $description ) {
            echo '<p class="description">' . esc_html( $description ) . '</p>';
        }

    }

    /**
     * Render a checkbox field.
     *
     * Outputs HTML for a checkbox or group of checkboxes.
     *
     * @param array $args Arguments for rendering the field.
     * @return void
     */
    
    public static function render_checkbox_field( $args ) {
        $options     = $args['options'];
        $label_for   = $args['label_for'];
        $description = isset( $args['description'] ) ? $args['description'] : '';
    
        $settings = custom_rfi_form_get_settings();
        $values   = isset( $settings[ $label_for ] ) ? $settings[ $label_for ] : array();
    
        foreach ( $options as $key => $label ) {
            ?>
            <label style="min-width: 150px;">
                <input type="checkbox" name="custom_rfi_form_settings[<?php echo esc_attr( $label_for ); ?>][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $values ) ); ?>>
                <?php echo esc_html( $label ); ?>
            </label>
            <?php
        }
    
        if ( $description ) {
            echo '<p class="description">' . esc_html( $description ) . '</p>';
        }
    }

    /**
     * Render a color field.
     *
     * Outputs HTML for a group of color fields.
     *
     * @param array $args Arguments for rendering the field.
     * @return void
     */
    public static function render_color_field( $args ) {
        $label_for   = $args['label_for'];
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $options     = isset( $args['options'] ) ? $args['options'] : array();

        $settings = custom_rfi_form_get_settings();
        $values   = isset( $settings[ $label_for ] ) ? $settings[ $label_for ] : array();

        // Define default colors
        $default_colors = custom_rfi_form_default_colors();

        foreach ( $options as $key => $label ) {
            $value = isset( $values[ $key ] ) ? $values[ $key ] : ( isset( $default_colors[ $key ] ) ? $default_colors[ $key ] : '#ffffff' );
            ?>
            <div class="color-picker">
                <input type="color" id="<?php echo esc_attr( $label_for . '_' . $key ); ?>" name="custom_rfi_form_settings[<?php echo esc_attr( $label_for ); ?>][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" class="color-field">
                <label for="<?php echo esc_attr( $label_for . '_' . $key ); ?>"><?php echo esc_html( $label ); ?></label>
            </div>
            <?php
        }

        if ( $description ) {
            echo '<p class="description">' . esc_html( $description ) . '</p>';
        }
    }

    /**
     * Render a repeater field.
     *
     * Outputs HTML for a repeater field, allowing dynamic addition of multiple entries.
     *
     * @param array $args Arguments for rendering the field.
     * @return void
     */

    public static function render_repeater_field( $args ) {
        $label_for   = $args['label_for'];
        $description = isset( $args['description'] ) ? $args['description'] : '';
    
        $settings = custom_rfi_form_get_settings();
        $values   = isset( $settings[ $label_for ] ) ? $settings[ $label_for ] : array();
    
        ?>
        <table id="<?php echo esc_attr( $label_for ); ?>_repeater">
            <thead>
                <tr>
                    <th>Option</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $values ) ) : ?>
                    <?php foreach ( $values as $item ) : ?>
                        <tr>
                        <td>
                         <?php echo '<p class="option-index">'.(array_search($item, $values) + 1) .'</p>' ?>
                        </td>
                            <td>
                                <input type="text" name="custom_rfi_form_settings[<?php echo esc_attr( $label_for ); ?>][][option]" value="<?php echo esc_attr( $item['option'] ); ?>">
                            </td>
                            <td>
                                <button type="button" class="button remove-row">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td>
                            <p class="option-index">1</p>
                        </td>
                        <td>
                            <input type="text" name="custom_rfi_form_settings[<?php echo esc_attr( $label_for ); ?>][][option]" value="">
                        </td>
                        <td>
                            <button type="button" class="button remove-row">Remove</button>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">
                        <button type="button" class="button add-row">Add Row</button>
                    </td>
                    <td>
                        <p class="description"><?php echo esc_html( $description ); ?></p>
                    </td>
                </tr>
            </tfoot>
        </table>
        <?php
    }
    

    public static function render_text_area_field( $args ) {
        $label_for   = $args['label_for'];
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $required    = isset( $args['required'] ) && $args['required'] ? 'required' : '';
    
        $settings = custom_rfi_form_get_settings();
        $value    = isset( $settings[ $label_for ] ) ? $settings[ $label_for ] : '';
    
        ?>
        <textarea name="custom_rfi_form_settings[<?php echo esc_attr( $label_for ); ?>]" id="<?php echo esc_attr( $label_for ); ?>" class="large-text" rows="5" <?php echo $required; ?>><?php echo esc_textarea( $value ); ?></textarea>
        <?php
        if ( $description ) {
            echo '<p class="description">' . esc_html( $description ) . '</p>';
        }
    }
}