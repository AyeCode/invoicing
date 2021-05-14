<?php
/**
 * Contains the notification email sending class.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * This function is responsible for sending emails.
 *
 */
class GetPaid_Notification_Email_Sender {

    /**
	 * Whether or not we should inline CSS into the email.
	 */
	public $inline_css = true;

    /**
	 * The wp_mail() data.
	 */
    public $wp_mail_data = null;

    /**
	 * Sends a new email.
     * 
     * @param string|array $to The recipients email or an array of recipient emails.
     * @param string $subject The email's subject.
     * @param string $email The email body.
     * @param array $attachments The email attachments.
     * 
     * @return bool
	 */
	public function send( $to, $subject, $email, $attachments = array() ) {

		/*
		 * Allow to filter data on per-email basis.
		 */
		$data = apply_filters(
			'getpaid_email_data',
			array(
				'to'          => array_filter( array_unique( wpinv_parse_list( $to ) ) ),
				'subject'     => htmlspecialchars_decode( strip_tags( $subject ), ENT_QUOTES ),
				'email'       => apply_filters( 'wpinv_mail_content', $email ),
				'headers'     => $this->get_headers(),
				'attachments' => $attachments,
			),
			$this
		);

        // Remove slashes.
        $data               = (array) wp_unslash( $data );

        // Cache it.
		$this->wp_mail_data = $data;

		// Attach our own hooks.
		$this->before_sending();

        $result = false;

        foreach ( $this->wp_mail_data['to'] as $to ) {
			$result = $this->_send( $to, $data );
        }

		// Remove our hooks.
		$this->after_sending();		

		$this->wp_mail_data = null;

		return $result;
	}

	/**
	 * Does the actual sending.
     * 
     * @param string $to The recipient's email.
     * @param array $data The email's data.
     * @param string $email The email body.
     * @param array $attachments The email attachments.
     * 
     * @return bool
	 */
	protected function _send( $to, $data ) {

		// Prepare the sending function.
		$sending_function = apply_filters( 'getpaid_email_email_sending_function', 'wp_mail' );

		// Send the actual email.
		$result = call_user_func(
			$sending_function,
			$to,
			html_entity_decode( $data['subject'], ENT_QUOTES, get_bloginfo( 'charset' ) ),
			$data['email'],
			$data['headers'],
			$data['attachments']
		);

		if ( ! $result ) {
			$log_message = wp_sprintf( __( "\nTime: %s\nTo: %s\nSubject: %s\n", 'invoicing' ), date_i18n( 'F j Y H:i:s', current_time( 'timestamp' ) ), $to, $data['subject'] );
			wpinv_error_log( $log_message, __( 'Email from Invoicing plugin failed to send', 'invoicing' ), __FILE__, __LINE__ );
		}

		return $result;
	}
    
    /**
	 * Retrieves email headers.
	 */
	public function get_headers() {

		$name       = $this->get_from_name();
		$reply_to   = $this->get_reply_to();
		$headers    = array( "Reply-To:$name <$reply_to>" );

		return apply_filters( 'getpaid_email_headers',  $headers, $this );

	}

    /**
	 * Fires before an email is sent
	 *
	 * @since 1.0.0
	 */
	public function before_sending() {

        do_action( 'getpaid_before_send_email', $this );
		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ), 1000 );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ), 1000 );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ), 1000 );
		add_filter( 'wp_mail', array( $this, 'ensure_email_content' ), 1000 );

	}

    /**
	 * Returns the from name.
	 */
	public function get_from_name() {

        $from_name = wpinv_get_option( 'email_from_name', get_bloginfo( 'name' ) );

		if ( empty( $from_name ) ) {
			$from_name =  get_bloginfo( 'name' );
        }

		return wp_specialchars_decode( $from_name, ENT_QUOTES );
    }

    /**
	 * Returns the from email.
	 */
	public function get_from_address() {

        $from_address = wpinv_get_option( 'email_from', $this->default_from_address() );

		if ( ! is_email( $from_address ) ) {
			$from_address =  $this->default_from_address();
        }

        return $from_address;

    }

    /**
	 * The default emails from address.
	 * 
	 * Defaults to wordpress@$sitename
	 * Some hosts will block outgoing mail from this address if it doesn't exist,
	 * but there's no easy alternative. Defaulting to admin_email might appear to be
	 * another option, but some hosts may refuse to relay mail from an unknown domain.
	 *
	 */
	public function default_from_address() {

		// Get the site domain and get rid of www.
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		$from_email = 'wordpress@' . $sitename;

		return apply_filters( 'getpaid_default_from_address', $from_email );

    }

    /**
	 * Get the email reply-to.
	 *
	 *
	 * @return string The email reply-to address.
	 */
	public function get_reply_to() {

		$reply_to = wpinv_get_admin_email();

		if ( ! is_email( $reply_to ) ) {
			$reply_to =  get_option( 'admin_email' );
		}

		return $reply_to;
    }

    /**
	 * Get the email content type.
	 *
	 */
	public function get_content_type() {
		return apply_filters( 'getpaid_email_content_type', 'text/html', $this );
    }
    
    /**
	 * Ensures that our email messages are not messed up by template plugins.
	 *
	 * @return array wp_mail_data.
	 */
	public function ensure_email_content( $args ) {
		$args['message'] = $this->wp_mail_data['email'];
		return $args;
    }
    
    /**
	 * A little house keeping after an email is sent.
	 *
 	 */
	public function after_sending() {

        do_action( 'getpaid_after_send_email', $this->wp_mail_data );
		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ), 1000 );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ), 1000 );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ), 1000 );
		remove_filter( 'wp_mail', array( $this, 'ensure_email_content' ), 1000 );

	}

}
