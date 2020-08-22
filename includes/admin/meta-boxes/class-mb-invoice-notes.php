<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

class WPInv_Meta_Box_Notes {
    public static function output( $post ) {
        global $post;

        $notes = wpinv_get_invoice_notes( $post->ID );

        echo '<ul class="invoice_notes">';

        if ( $notes ) {
            foreach( $notes as $note ) {
                wpinv_get_invoice_note_line_item( $note );
            }

        } else {
            echo '<li>' . __( 'There are no notes yet.', 'invoicing' ) . '</li>';
        }

        echo '</ul>';
        ?>
        <div class="add_note">
            <h4><?php _e( 'Add note', 'invoicing' ); ?></h4>
            <p>
                <textarea type="text" name="invoice_note" id="add_invoice_note" class="input-text" cols="20" rows="5"></textarea>
            </p>
            <p>
                <select name="invoice_note_type" id="invoice_note_type" class="regular-text">
                    <option value=""><?php _e( 'Private note', 'invoicing' ); ?></option>
                    <option value="customer"><?php _e( 'Note to customer', 'invoicing' ); ?></option>
                </select>
                <a href="#" class="add_note button"><?php _e( 'Add', 'invoicing' ); ?></a> <span class="description"><?php _e( 'Add a note for your reference, or add a customer note (the user will be notified).', 'invoicing' ); ?></span>
            </p>
        </div>
        <?php
    }
}
