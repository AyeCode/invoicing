<?php
/**
 * Contains the notification email class.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Represents a single email type.
 *
 */
class GetPaid_Notification_Email {

    /**
	 * Contains the type of this notification email.
	 *
	 * @var string
	 */
    public $id;

    /**
	 * Contains any object to use in filters.
	 *
	 * @var false|WPInv_Invoice|WPInv_Item|WPInv_Subscription
	 */
    public $object;

    /**
	 * Class constructor.
	 *
     * @param string $id Email Type.
     * @param mixed $object Optional. Associated object.
	 */
	public function __construct( $id, $object = false ) {
        $this->id     = $id;
        $this->object = $object;
    }

    /**
	 * Retrieves an option
	 *
     * @return mixed
	 */
	public function get_option( $key ) {

        $key   = "email_{$this->id}_$key";
        $value = wpinv_get_option( $key, null );
        if ( is_null( $value ) ) {
            $options = wpinv_get_emails();

            if ( ! isset( $options[ $this->id ] ) || ! isset( $options[ $this->id ][ $key ] ) ) {
                return '';
            }

            $value = isset( $options[ $this->id ][ $key ]['std'] ) ? $options[ $this->id ][ $key ]['std'] : '';
        }

        return $value;
    }

    /**
	 * Retrieves the email body.
	 *
     * @return string
	 */
	public function get_body() {
        $body = $this->get_option( 'body' );
        return apply_filters( 'getpaid_get_email_body', $body, $this->id, $this->object );
    }

    /**
	 * Retrieves the email subject.
	 *
     * @return string
	 */
	public function get_subject() {
        $subject = $this->get_option( 'subject' );
        return apply_filters( 'getpaid_get_email_subject', $subject, $this->id, $this->object );
    }

    /**
	 * Retrieves the email heading.
	 *
     * @return string
	 */
	public function get_heading() {
        $heading = $this->get_option( 'heading' );
        return apply_filters( 'getpaid_get_email_heading', $heading, $this->id, $this->object );
    }

    /**
	 * Checks if an email is active.
	 *
     * @return bool
	 */
	public function is_active() {
        $is_active = ! empty( $this->get_option( 'active' ) );
        return apply_filters( 'getpaid_email_type_is_active', $is_active, $this->id, $this->object );
    }

    /**
	 * Checks if the site's admin should receive email notifications.
	 *
     * @return bool
	 */
	public function include_admin_bcc() {
        $include_admin_bcc = ! empty( $this->get_option( 'admin_bcc' ) );
        return apply_filters( 'getpaid_email_type_include_admin_bcc', $include_admin_bcc, $this->id, $this->object );
    }

    /**
	 * Checks whether this email should be sent to the customer or admin.
	 *
     * @return bool
	 */
	public function is_admin_email() {
        $is_admin_email = in_array( $this->id, array( 'new_invoice', 'cancelled_invoice', 'failed_invoice' ) );
        return apply_filters( 'getpaid_email_type_is_admin_email', $is_admin_email, $this->id, $this->object );
    }

    /**
	 * Returns email attachments.
	 *
     * @return array
	 */
	public function get_attachments() {
        return apply_filters( 'getpaid_get_email_attachments', array(), $this->id, $this->object );
    }

    /**
	 * Returns an array of merge tags.
	 *
     * @return array
	 */
	public function get_merge_tags() {

        $merge_tags = array(
            '{site_title}' => wpinv_get_blogname(),
            '{date}'       => getpaid_format_date_value( current_time( 'mysql' ) ),
        );

        return apply_filters( 'getpaid_get_email_merge_tags', $merge_tags, $this->object, $this->id );
    }

    /**
	 * Adds merge tags to a text.
	 *
     * @param string string $text
     * @param array $merge_tags
     * @return string
	 */
	public function add_merge_tags( $text, $merge_tags = array() ) {

        foreach ( $merge_tags as $key => $value ) {
            $text = str_replace( $key, $value, $text );
        }

        return wptexturize( $text );
    }

    /**
	 * Returns the email content
	 *
     * @param array $merge_tags
     * @param array $extra_args Extra template args
     * @return string
	 */
	public function get_content( $merge_tags = array(), $extra_args = array() ) {

        $content = wpinv_get_template_html(
            "emails/wpinv-email-{$this->id}.php",
            array_merge(
                $extra_args,
                array(
                    'invoice'       => $this->object, // Backwards compat.
                    'object'        => $this->object,
                    'email_type'    => $this->id,
                    'email_heading' => $this->add_merge_tags( $this->get_heading(), $merge_tags ),
                    'sent_to_admin' => $this->is_admin_email(),
                    'plain_text'    => false,
                    'message_body'  => wpautop( $this->add_merge_tags( $this->get_body(), $merge_tags ) ),
                )
            )
        );

        return wpinv_email_style_body( $content );
    }

}
