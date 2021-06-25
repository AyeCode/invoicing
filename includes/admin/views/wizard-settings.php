<?php
/**
 * Displays the set-up wizard bussiness settings.
 *
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="card shadow-sm my-5">

    <form method="post" class="text-left card-body" action="options.php">
        <?php settings_fields( 'wpinv_settings' ); ?>
        <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( $next_url ); ?>">

        <table class="gp-setup-maps w-100 " cellspacing="0">
            <tbody>
                <?php

                    global $wp_settings_fields;

                    if ( isset( $wp_settings_fields[ $page ][ $section ] ) ) {
                        $settings =  $wp_settings_fields[ $page ][ $section ];

                        foreach ( $settings as $field ) {

                            $name      = esc_attr( $field['id'] );
                            $id        = sanitize_key( $name );
                            $class     = '';
                            $value     = isset( $field['args']['std'] ) ? $field['args']['std'] : '';
                            $value     = wpinv_clean( wpinv_get_option( $field['args']['id'], $value ) );
                            $help_text = isset( $field['args']['desc'] ) ? wp_kses_post( $field['args']['desc'] ) : '';
                            $type      = str_replace( 'wpinv_', '', str_replace( '_callback', '', $field['callback'] ) );
                            $label     = isset( $field['args']['name'] ) ? wp_kses_post( $field['args']['name'] ) : '';
                            $options   = isset( $field['args']['options'] ) ? $field['args']['options'] : array();
                        
                            if ( false !== strpos( $name, 'logo') ) {
                                $type = 'hidden';
                            }
                        
                            if ( 'country_states' == $type ) {
                        
                                if ( 0 == count( wpinv_get_country_states( wpinv_get_default_country() ) ) ) {
                                    $type = 'text';
                                } else {
                                    $type = 'select';
                                }
                        
                                $class = 'getpaid_js_field-state';
                            }
                        
                            if ( 'wpinv_settings[default_country]' == $name ) {
                                $class = 'getpaid_js_field-country';
                            }
                        
                            switch ( $type ) {
                        
                                case 'hidden':
                                    echo "<input type='hidden' id='$id' name='$name' value='$value' />";
                                    break;
                                case 'text':
                                case 'number':
                                    echo aui()->input(
                                        array(
                                            'type'       => $type,
                                            'id'         => $id,
                                            'name'       => $name,
                                            'value'      => is_scalar( $value ) ? esc_attr( $value ) : '',
                                            'required'   => false,
                                            'help_text'  => $help_text,
                                            'label'      => $label,
                                            'class'      => $class,
                                            'label_type' => 'floating',
                                            'label_class' => 'settings-label',
                                        )
                                    );
                                    break;
                                case 'textarea':
                        
                                    $textarea = aui()->textarea(
                                        array(
                                            'id'              => $id,
                                            'name'            => $name,
                                            'value'           => is_scalar( $value ) ? esc_textarea( $value ) : '',
                                            'required'        => false,
                                            'help_text'       => $help_text,
                                            'label'           => $label,
                                            'rows'            => '4',
                                            'class'           => $class,
                                            'label_type'      => 'floating',
                                            'label_class'     => 'settings-label',
                                        )
                                    );
                        
                                    // Bug fixed in AUI 0.1.51 for name stripping []
                                    echo str_replace( sanitize_html_class( $name ), esc_attr( $name ), $textarea );
                        
                                    break;
                                case 'select':
                                    echo aui()->select(
                                        array(
                                            'id'              =>  $id,
                                            'name'            =>  $name,
                                            'placeholder'     => '',
                                            'value'           => is_scalar( $value ) ? esc_attr( $value ) : '',
                                            'required'        => false,
                                            'help_text'       => $help_text,
                                            'label'           => $label,
                                            'options'         => $options,
                                            'label_type'      => 'floating',
                                            'label_class'     => 'settings-label',
                                            'class'           => $class,
                                        )
                                    );
                                    break;
                                default:
                                    // Do something.
                                    break;
                            }
                        }
                    }

                ?>
            </tbody>

            <p class="gp-setup-actions step text-center mt-4">
				<input
                    type="submit"
                    class="btn btn-primary button-next"
				    value="<?php esc_attr_e( 'Continue', 'invoicing' ); ?>" name="save_step"/>
			</p>
        </table>
    </form>

</div>
