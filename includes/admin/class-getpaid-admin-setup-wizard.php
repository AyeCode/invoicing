<?php
/**
 * Setup Wizard Class
 *
 * Takes new users through some basic steps to setup GetPaid.
 *
 * @author      AyeCode
 * @category    Admin
 * @package     GetPaid/Admin
 * @version     2.4.0
 * @info        GetPaid Setup Wizard.
 */
defined( 'ABSPATH' ) || exit;

/**
 * GetPaid_Admin_Setup_Wizard class.
 */
class GetPaid_Admin_Setup_Wizard {

	/**
	 * @var string Current Step
	 */
	protected $step = '';

	/**
	 * @var string|false Previous Step
	 */
	protected $previous_step = '';

	/**
	 * @var string|false Next Step
	 */
	protected $next_step = '';

	/**
	 * @var array All available steps for the setup wizard
	 */
	protected $steps = array();

	/**
	 * Class constructor.
	 *
	 * @since 2.4.0
	 */
	public function __construct() {

		if ( apply_filters( 'getpaid_enable_setup_wizard', true ) && wpinv_current_user_can_manage_invoicing() ) {
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
			add_action( 'current_screen', array( $this, 'setup_wizard' ) );
		}

	}

	/**
	 * Add admin menus/screens.
	 *
	 * @since 2.4.0
	 */
	public function add_menu() {
		add_dashboard_page( '', '', wpinv_get_capability(), 'gp-setup', '' );
	}

	/**
	 * Sets up the setup wizard.
	 *
	 * @since 2.4.0
	 */
	public function setup_wizard() {

		if ( isset( $_GET['page'] ) && 'gp-setup' === $_GET['page'] ) {
			$this->setup_globals();
			$this->maybe_save_current_step();
			$this->display_wizard();
			exit;
		}

	}

	/**
	 * Sets up class variables.
	 *
	 * @since 2.4.0
	 */
	protected function setup_globals() {
		$this->steps         = $this->get_setup_steps();
		$this->step          = $this->get_current_step();
		$this->previous_step = $this->get_previous_step();
		$this->next_step     = $this->get_next_step();
	}

	/**
	 * Saves the current step.
	 *
	 * @since 2.4.0
	 */
	protected function maybe_save_current_step() {
		if ( ! empty( $_POST['save_step'] ) && is_callable( $this->steps[ $this->step ]['handler'] ) ) {
			call_user_func( $this->steps[ $this->step ]['handler'], $this );
		}
	}

	/**
	 * Returns the setup steps.
	 *
	 * @since 2.4.0
	 * @return array
	 */
	protected function get_setup_steps() {

		$steps = array(

			'introduction'     => array(
				'name'    => __( 'Introduction', 'invoicing' ),
				'view'    => array( $this, 'setup_introduction' ),
				'handler' => '',
			),

			'business_details'             => array(
				'name'    => __( "Business Details", 'invoicing' ),
				'view'    => array( $this, 'setup_business' ),
				'handler' => '',
			),

			'currency' => array(
				'name'    => __( 'Currency', 'invoicing' ),
				'view'    => array( $this, 'setup_currency' ),
				'handler' => '',
			),

			'payments'        => array(
				'name'    => __( 'Payment Gateways', 'invoicing' ),
				'view'    => array( $this, 'setup_payments' ),
				'handler' => array( $this, 'setup_payments_save' ),
			),

			'recommend'          => array(
				'name'    => __( 'Recommend', 'invoicing' ),
				'view'    => array( $this, 'setup_recommend' ),
				'handler' => '',
			),

			'next_steps'       => array(
				'name'    => __( 'Get Paid', 'invoicing' ),
				'view'    => array( $this, 'setup_ready' ),
				'handler' => '',
			),

		);

		return apply_filters( 'getpaid_setup_wizard_steps', $steps );

	}

	/**
	 * Returns the current step.
	 *
	 * @since 2.4.0
	 * @return string
	 */
	protected function get_current_step() {
		$step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : '';
		return ! empty( $step ) && in_array( $step, array_keys( $this->steps ) ) ? $step : current( array_keys( $this->steps ) );
	}

	/**
	 * Returns the previous step.
	 *
	 * @since 2.4.0
	 * @return string|false
	 */
	protected function get_previous_step() {

		$previous = false;
		$current  = $this->step;
		foreach ( array_keys( $this->steps ) as $step ) {
			if ( $current === $step ) {
				return $previous;
			}

			$previous = $step;
		}

		return false;
	}

	/**
	 * Returns the next step.
	 *
	 * @since 2.4.0
	 * @return string|false
	 */
	protected function get_next_step() {

		$on_current = false;
		$current    = $this->step;
		foreach ( array_keys( $this->steps ) as $step ) {

			if ( $on_current ) {
				return $step;
			}

			if ( $current === $step ) {
				return $on_current = true;
			}

		}

		return false;
	}

	/**
	 * Displays the setup wizard.
	 *
	 * @since 2.4.0
	 */
	public function display_wizard() {
		$this->display_header();
		$this->display_current_step();
		$this->display_footer();
	}

	/**
	 * Displays the Wizard Header.
	 *
	 * @since 2.0.0
	 */
	public function display_header() {
		$steps     = $this->steps;
		$current   = $this->step;
		$next_step = $this->next_step;
		array_shift( $steps );
		include plugin_dir_path( __FILE__ ) . 'views/wizard-header.php';
	}

	/**
	 * Displays the content for the current step.
	 *
	 * @since 2.4.0
	 */
	public function display_current_step() {
		?>
			<div class="gp-setup-content rowx mw-100 text-center mb-3">
				<div class="col-12 col-md-5 m-auto">
					<?php call_user_func( $this->steps[ $this->step ]['view'], $this ); ?>
				</div>
			</div>
		<?php
	}

	/**
	 * Setup Wizard Footer.
	 *
	 * @since 2.4.0
	 */
	public function display_footer() {

		if ( isset( $_GET['step'] ) ) {
			$next_url = esc_url( $this->get_next_step_link() );
			$label    = $this->step == 'next_steps' ? __( 'Return to the WordPress Dashboard', 'invoicing' ) : __( 'Skip this step', 'invoicing' );

			echo '<p class="gd-return-to-dashboard-wrap"> <a href="' . $next_url . '" class="gd-return-to-dashboard btn btn-link d-block text-muted">' . $label . '</a></p>';
		}

		echo '</body></html>';
	}

	/**
	 * Introduction step.
	 *
	 * @since 2.0.0
	 */
	public function setup_introduction() {
		$next_url = $this->get_next_step_link();
		include plugin_dir_path( __FILE__ ) . 'views/wizard-introduction.php';
	}

	/**
	 * Get the URL for the next step's screen.
	 *
	 * @param string step   slug (default: current step)
	 *
	 * @return string       URL for next step if a next step exists.
	 *                      Admin URL if it's the last step.
	 *                      Empty string on failure.
	 * @since 3.0.0
	 */
	public function get_next_step_link( $step = '' ) {
		if ( ! $step ) {
			$step = $this->step;
		}

		$keys = array_keys( $this->steps );
		if ( end( $keys ) === $step ) {
			return admin_url();
		}

		$step_index = array_search( $step, $keys );
		if ( false === $step_index ) {
			return '';
		}

		return remove_query_arg('settings-updated', add_query_arg( 'step', $keys[ $step_index + 1 ] ));
	}

	/**
	 * Setup maps api.
	 *
	 * @since 2.0.0
	 */
	public function setup_business() {
		$next_url = $this->get_next_step_link();
		$wizard   = $this;
		$page     = 'wpinv_settings_general_main';
		$section  = 'wpinv_settings_general_main';
		include plugin_dir_path( __FILE__ ) . 'views/wizard-settings.php';
	}

	/**
	 * Default Location settings.
	 *
	 * @since 2.0.0
	 */
	public function setup_currency() {
		$next_url = $this->get_next_step_link();
		$wizard   = $this;
		$page     = 'wpinv_settings_general_currency_section';
		$section  = 'wpinv_settings_general_currency_section';
		include plugin_dir_path( __FILE__ ) . 'views/wizard-settings.php';
	}

	/**
	 * Installation of recommended plugins.
	 *
	 * @since 1.0.0
	 */
	public function setup_recommend() {
		$next_url            = $this->get_next_step_link();
		$recommended_plugins = self::get_recommend_wp_plugins();
		include plugin_dir_path( __FILE__ ) . 'views/wizard-plugins.php';
	}

	/**
	 * A list of recommended wp.org plugins.
	 * @return array
	 */
	public static function get_recommend_wp_plugins(){
		return array(
			'ayecode-connect' => array(
				'file'   => 'ayecode-connect/ayecode-connect.php',
				'url'    => 'https://wordpress.org/plugins/ayecode-connect/',
				'slug'   => 'ayecode-connect',
				'name'   => 'AyeCode Connect',
				'desc'   => __( 'Documentation and Support from within your WordPress admin.', 'geodirectory' ),
			),
			'invoicing-quotes' => array(
				'file'   => 'invoicing-quotes/wpinv-quote.php',
				'url'    => 'https://wordpress.org/plugins/invoicing-quotes/',
				'slug'   => 'invoicing-quotes',
				'name'   => 'Customer Quotes',
				'desc'   => __('Create & Send Quotes to Customers and have them accept and pay.','geodirectory'),
			),
			'userswp'    => array(
				'file'   => 'userswp/userswp.php',
				'url'    => 'https://wordpress.org/plugins/userswp/',
				'slug'   => 'userswp',
				'name'   => 'UsersWP',
				'desc'   => __('Frontend user login and registration as well as slick profile pages.','geodirectory'),
			),
		);
	}

	/**
	 * Dummy Data setup.
	 *
	 * @since 2.4.0
	 */
	public function setup_payments() {
		$next_url = $this->get_next_step_link();
		include plugin_dir_path( __FILE__ ) . 'views/wizard-gateways.php';
	}

	/**
	 * Dummy data save.
	 *
	 * This is done via ajax so we just pass onto the next step.
	 *
	 * @since 2.0.0
	 */
	public function setup_payments_save() {
		check_admin_referer( 'getpaid-setup-wizard', 'getpaid-setup-wizard' );
		wpinv_update_option( 'manual_active', ! empty( $_POST['enable-manual-gateway'] ) );

		if ( ! empty( $_POST['paypal-email'] ) ) {
			wpinv_update_option( 'paypal_email', sanitize_email( $_POST['paypal-email'] ) );
			wpinv_update_option( 'paypal_active', 1 );
			wpinv_update_option( 'paypal_sandbox', 0 );
		}

		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Final step.
	 *
	 * @since 2.0.0
	 */
	public function setup_ready() {
		include plugin_dir_path( __FILE__ ) . 'views/wizard-thank-you.php';
	}

}

new GetPaid_Admin_Setup_Wizard();
