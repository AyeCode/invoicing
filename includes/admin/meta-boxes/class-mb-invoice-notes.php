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
                $note_classes = get_comment_meta( $note->comment_ID, 'is_customer_note', true ) ? array( 'customer-note', 'note' ) : array( 'note' );
                $note_classes = apply_filters( 'wpinv_note_class', $note_classes, $note );

                ?>
                <li rel="<?php echo absint( $note->comment_ID ) ; ?>" class="<?php echo esc_attr( implode( ' ', $note_classes ) ); ?>">
                    <div class="note_content">
                        <?php echo wpautop( wptexturize( wp_kses_post( $note->comment_content ) ) ); ?>
                    </div>
                    <p class="meta">
                        <abbr class="exact-date" title="<?php echo $note->comment_date; ?>"><?php printf( __( '%1$s - %2$s at %3$s', 'invoicing' ), $note->comment_author, date_i18n( get_option( 'date_format' ), strtotime( $note->comment_date ) ), date_i18n( get_option( 'time_format' ), strtotime( $note->comment_date ) ) ); ?></abbr>&nbsp;&nbsp;<a href="#" class="delete_note"><?php _e( 'Delete note', 'invoicing' ); ?></a>
                    </p>
                </li>
                <?php
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
                <select name="invoice_note_type" id="invoice_note_type">
                    <option value=""><?php _e( 'Private note', 'invoicing' ); ?></option>
                    <option value="customer"><?php _e( 'Note to customer', 'invoicing' ); ?></option>
                </select>
                <a href="#" class="add_note button"><?php _e( 'Add', 'invoicing' ); ?></a>
            </p>
        </div>
        <?php
    }
}
