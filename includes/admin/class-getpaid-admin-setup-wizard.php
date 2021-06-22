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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GetPaid_Admin_Setup_Wizard class.
 */
class GetPaid_Admin_Setup_Wizard {

	/** @var string Current Step */
	private $step = '';

	/** @var array Steps for the setup wizard */
	private $steps = array();

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		if ( apply_filters( 'getpaid_enable_setup_wizard', true ) && current_user_can( 'manage_options' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menus' ) );
			add_action( 'current_screen', array( $this, 'setup_wizard' ) );

			// add default content action
			add_action( 'geodir_wizard_content_dummy_data', array( __CLASS__, 'content_dummy_data' ) );
			add_action( 'geodir_wizard_content_sidebars', array( __CLASS__, 'content_sidebars' ) );
			add_action( 'geodir_wizard_content_menus', array( __CLASS__, 'content_menus' ) );
		}
	}

	/**
	 * Add admin menus/screens.
	 */
	public function admin_menus() {
		add_dashboard_page( '', '', 'manage_options', 'gp-setup', '' );
	}

	/**
	 * Show the setup wizard.
	 *
	 * @since 2.0.0
	 */
	public function setup_wizard() {
		if ( empty( $_GET['page'] ) || 'gp-setup' !== $_GET['page'] ) {
			return;
		}
		$default_steps = array(
			'introduction'     => array(
				'name'    => __( 'Introduction', 'invoicing' ),
				'view'    => array( $this, 'setup_introduction' ),
				'handler' => '',
			),
			'business_details'             => array(
				'name'    => __( "Business Details", 'invoicing' ),
				'view'    => array( $this, 'setup_business' ),
				'handler' => array( $this, 'setup_business_save' ),
			),
			'currency' => array(
				'name'    => __( 'Currency', 'invoicing' ),
				'view'    => array( $this, 'setup_currency' ),
				'handler' => array( $this, 'setup_currency_save' ),
			),
			'payments'        => array(
				'name'    => __( 'Payment Gateways', 'invoicing' ),
				'view'    => array( $this, 'setup_payments' ),
				'handler' => array( $this, 'setup_payments_save' ),
			),
			'recommend'          => array(
				'name'    => __( 'Recommend', 'invoicing' ),
				'view'    => array( $this, 'setup_recommend' ),
				'handler' => array( $this, 'setup_recommend_save' ),
			),
			'next_steps'       => array(
				'name'    => __( 'Get Paid', 'invoicing' ),
				'view'    => array( $this, 'setup_ready' ),
				'handler' => '',
			),
		);

		$this->steps     = apply_filters( 'getpaid_setup_wizard_steps', $default_steps );
		$this->step      = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );


		// enqueue the script
		$aui_settings = AyeCode_UI_Settings::instance();
		$aui_settings->enqueue_scripts();
		$aui_settings->enqueue_style();



//		if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
//			call_user_func( $this->steps[ $this->step ]['handler'], $this );
//		}

//		if ( ! empty( $_REQUEST['settings-updated'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
//			call_user_func( $this->steps[ $this->step ]['handler'], $this );
//		}

		ob_start();
		$this->setup_wizard_header();
//		$this->setup_wizard_steps();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		exit;
	}

	/**
	 * Setup Wizard Header.
	 *
	 * @since 2.0.0
	 */
public function setup_wizard_header() {
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?> class="bsui">
	<head>
		<meta name="viewport" content="width=device-width"/>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<title><?php esc_html_e( 'GetPaid &rsaquo; Setup Wizard', 'invoicing' ); ?></title>
		<?php

		wp_register_style( 'font-awesome', 'https://use.fontawesome.com/releases/v5.13.0/css/all.css', array(  ), WPINV_VERSION );
		wp_enqueue_style( 'font-awesome' );
		do_action( 'admin_print_styles' ); ?>
		<?php do_action( 'admin_head' ); ?>
		<style>
			body,p{
				font-size: 16px;
				font-weight: normal;
			}

			<?php
				$aui_settings = AyeCode_UI_Settings::instance();
				echo $aui_settings::css_primary('#009874',true);
			 ?>


		</style>
	</head>
	<body class="gp-setup wp-core-ui bg-lightx mx-auto text-dark scrollbars-ios" style="background: #f3f6ff;">
	<?php
	if(isset($_REQUEST['step'])){
	$this->setup_wizard_steps();
	}else{
	echo "<div class='mb-3'>&nbsp;</div>";
	}

	?>
	<h1 class="h2 text-center pb-3">
		<a class=" text-decoration-none" href="https://wpgetpaid.com/">
			<span class="text-black-50">
				<img class="ml-n3x" src="<?php echo WPINV_PLUGIN_URL . 'assets/images/getpaid-logo.png';?>" />
			</span>
		</a>
	</h1>
	<?php
	}

	/**
	 * Output the steps.
	 *
	 * @since 2.0.0
	 */
	public function setup_wizard_steps() {
		$ouput_steps = $this->steps;
		array_shift( $ouput_steps );
		?>
		<ol class="gp-setup-steps mb-0 pb-4 mw-100 list-group list-group-horizontal text-center">
			<?php
			$current = '';
			foreach ( $ouput_steps as $step_key => $step ) : ?>
				<li class="list-group-item flex-fill rounded-0 <?php
				if ( $step_key === $this->step ) {
					$current = $this->step;
					echo 'active';
				} elseif ( array_search( $this->step, array_keys( $this->steps ) ) > array_search( $step_key, array_keys( $this->steps ) ) ) {
					echo 'done';
				}
				$done = !$current ? 'text-success' : '';
				?>"><i class="far fa-check-circle <?php echo $done ;?>"></i> <?php echo esc_html( $step['name'] ); ?></li>
			<?php endforeach; ?>
		</ol>
		<?php
	}

	/**
	 * Output the content for the current step.
	 *
	 * @since 2.0.0
	 */
	public function setup_wizard_content() {
		echo '<div class="gp-setup-content rowx mw-100 text-center mb-3">';
		echo '<div class="col-5 m-auto">';
		echo '<div class="card shadow-sm">';
		call_user_func( $this->steps[ $this->step ]['view'], $this );
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Setup Wizard Footer.
	 *
	 * @since 2.0.0
	 */
	public function setup_wizard_footer() {
	?>
	<?php if ( 'next_steps' === $this->step ){ ?>
		<p class="gd-return-to-dashboard-wrap"><a class="gd-return-to-dashboard btn btn-link d-block text-muted"
		                                          href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'Return to the WordPress Dashboard', 'invoicing' ); ?></a>
		</p>
	<?php }else{ ?>
	<p class="gd-return-to-dashboard-wrap"><a href="<?php echo esc_url( $this->get_next_step_link() ); ?>"
		                class="btn btn-link d-block text-muted"><?php esc_html_e( 'Skip this step', 'invoicing' ); ?></a></p>
	<?php } ?>
	</body>
	</html>
	<?php
}

	/**
	 * Introduction step.
	 *
	 * @since 2.0.0
	 */
	public function setup_introduction() {
		?>
		<h1 class="h4 card-header bg-white border-bottom-0 pt-4 pb-1"><?php esc_html_e( 'Welcome to GetPaid!', 'invoicing' ); ?></h1>
		<div class="card-body text-muted ">
			<p class=""><?php _e( 'Thank you for choosing GetPaid - The most Powerful Payments Plugin for WordPress', 'invoicing' ); ?></p>
			<hr class="mt-4 pt-3 pb-0" />
			<p class="small"><?php _e( 'This quick setup wizard will help you <b>configure the basic settings</b>. It’s <b>completely optional</b> and shouldn’t take longer than <b>five minutes<b/>.', 'invoicing' ); ?></p>
		</div>
		<div class="card-footer mb-0 bg-white gp-setup-actions step border-top-0">
			<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>"
			   class="btn btn-primary button-next"><?php esc_html_e( 'Let\'s go!', 'invoicing' ); ?></a>
			<a href="<?php echo esc_url( admin_url() ); ?>"
			   class="btn btn-link d-block mt-2 "><?php esc_html_e( 'Not right now', 'invoicing' ); ?></a>
		</div>


		</div>
		<div class="card shadow-sm my-5">
		<h1 class="h4 card-header bg-white border-bottom-0  pt-4 pb-1"><?php esc_html_e( 'GetPaid Features & Addons!', 'invoicing' ); ?></h1>
		<div class="card-body text-muted overflow-hidden">
			<p class=""><?php _e( 'Collect one time & recurring payments online within minutes. No complex setup required.', 'invoicing' ); ?></p>
			<hr class="">

			<div class="row row row-cols-2 text-left">
				<div class="col mt-3">
					<div class="media">
					  <img src="<?php echo WPINV_PLUGIN_URL . 'assets/images/buy.svg';?>" class="mr-3" alt="...">
					  <div class="media-body">
					    <h6 class="mt-0 font-weight-bold"><?php _e('GetPaid via Buy Now Buttons','invoicing');?></h6>
					    <small><?php _e('Sell via buy now buttons anywhere on your site','invoicing');?></small>
					  </div>
					</div>
				</div>
			    <div class="col mt-3">
					<div class="media">
					  <img src="<?php echo WPINV_PLUGIN_URL . 'assets/images/report.svg';?>" class="mr-3" alt="...">
					  <div class="media-body">
					    <h6 class="mt-0 font-weight-bold"><?php _e('GetPaid via payment form','invoicing');?></h6>
					    <small><?php _e('Payment forms are conversion-optimized checkout forms','invoicing');?></small>
					  </div>
					</div>
				</div>
				<div class="col mt-3">
					<div class="media">
					  <img src="<?php echo WPINV_PLUGIN_URL . 'assets/images/invoices.svg';?>" class="mr-3" alt="...">
					  <div class="media-body">
					    <h6 class="mt-0 font-weight-bold"><?php _e('GetPaid via Invoice','invoicing');?></h6>
					    <small><?php _e('Create and send invoices for just about anything from the WOrdPress dashboard','invoicing');?></small>
					  </div>
					</div>
				</div>
				<div class="col mt-3">
					<div class="media">
					  <img src="<?php echo WPINV_PLUGIN_URL . 'assets/images/payment.svg';?>" class="mr-3" alt="...">
					  <div class="media-body">
					    <h6 class="mt-0 font-weight-bold"><?php _e('Affordable payment gateways','invoicing');?></h6>
					    <small><?php _e('On average our gateways are over 66% cheaper than our competition','invoicing');?></small>
					  </div>
					</div>
				</div>
			</div>

			<div class="mt-5">
				<a href="https://wpgetpaid.com/features-list/"
				   class="btn btn-primary"><?php esc_html_e( 'View All Features!', 'invoicing' ); ?></a>
			</div>
			<div class="mt-5 mx-n4 py-4" style="background:#eafaf6;">
				<h4 class="mt-0 font-weight-bold text-dark mb-4"><?php _e('More with Membership!','invoicing');?></h4>
				<div class="row row-cols-2 text-left px-5">
					<div class="col">
						<ul class="list-unstyled">
							<li class="my-2"><i class="far fa-check-circle text-success"></i> PDF Invoices</li>
							<li class="my-2"><i class="far fa-check-circle text-success"></i> Gravity Forms</li>
							<li class="my-2"><i class="far fa-check-circle text-success"></i> Contact form 7</li>
							<li class="my-2"><i class="far fa-check-circle text-success"></i> AffiliateWP Integration</li>
						</ul>
					</div>
					<div class="col">
						<ul class="list-unstyled">
							<li class="my-2"><i class="far fa-check-circle text-success"></i> Ninja forms</li>
							<li class="my-2"><i class="far fa-check-circle text-success"></i> Digital Downloads</li>
							<li class="my-2"><i class="far fa-check-circle text-success"></i> Wallet</li>
						</ul>
					</div>
				</div>

				<h5 class="mt-4 font-weight-bold text-dark mb-3"><?php _e('Membership Starts From','invoicing');?></h5>
				<h1 class="mt-0 font-weight-bold text-dark mb-4 display-3">$49</h1>

				<div class="mt-2">
				<a href="https://wpgetpaid.com/downloads/membership/"
				   class="btn btn-primary"><?php esc_html_e( 'Buy Membership Now!', 'invoicing' ); ?></a>
			</div>


			</div>

		</div>
		<div class="card-footer mb-0 bg-white gp-setup-actions step border-top-0">
			<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>"
			   class="btn btn-outline-primary button-next"><?php esc_html_e( 'Launch the Setup Wizard!', 'invoicing' ); ?></a>
			   <a href="https://docs.wpgetpaid.com/"
			   class="btn btn-outline-primary ml-4"><?php esc_html_e( 'Documentation', 'invoicing' ); ?></a>
			<a href="<?php echo esc_url( admin_url() ); ?>"
			   class="btn btn-link d-block mt-2 "><?php esc_html_e( 'Not right now', 'invoicing' ); ?></a>
		</div>
		<?php
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
		?>
		<form method="post" class="text-left card-body" action="options.php">
			<?php

			settings_fields( 'wpinv_settings' );

			// override http referer to make it send back to the next step
				?>
			<input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( $this->get_next_step_link() ); ?>">

			<table class="gp-setup-maps w-100 " cellspacing="0">

				<tbody>

				<?php

				global $wp_settings_fields;

				$page = 'wpinv_settings_general_main';
				$section = 'wpinv_settings_general_main';
				if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
			        return;
			    }

			    $settings =  $wp_settings_fields[ $page ][ $section ];

				// unset title
				unset($settings["wpinv_settings[location_settings]"]);

			    $this->output_fields($settings);
				?>


				</tbody>
			</table>


			<p class="gp-setup-actions step text-center mt-4">
				<input type="submit" class="btn btn-primary button-next"
				       value="<?php esc_attr_e( 'Continue', 'invoicing' ); ?>" name="save_step"/>
			</p>
		</form>
		<?php
	}

	public function output_fields($settings){

	    if ( empty($settings)) {
	        return;
	    }

//print_r($settings);
	    foreach ( (array) $settings as $key => $field ) {


	        $class = '';

	        if ( ! empty( $field['args']['class'] ) ) {
	            $class = esc_attr( $field['args']['class'] );
	        }

	       // echo '<div class="form-group '.$class.'">';


	        if ( ! empty( $field['args']['label_for'] ) ) {
	            $for = ' for="' . esc_attr( $field['args']['label_for'] ) . '" ';
	        } else {
	            $for = '';
	        }

			$value  = isset( $field['args']['std'] ) ? $field['args']['std'] : '';
			$value  = wpinv_get_option( $field['args']['id'], $value );

			if($field['callback'] == 'wpinv_text_callback' || $field['callback'] == 'wpinv_number_callback' ){


			// hide the logo inputs, we need to set them as hidden so they don't blank the current values.
			$help_text = isset($field['args']['desc']) ? esc_attr($field['args']['desc']) : '';
			$type = $field['callback'] == 'wpinv_number_callback'  ? 'number' : 'text';
			$label = isset($field['args']['name']) ? esc_attr($field['args']['name']) : '';

			if(in_array($field['id'],array('wpinv_settings[logo]','wpinv_settings[logo_width]','wpinv_settings[logo_height]'))){
				$type = 'hidden';
				$help_text = '';
				$label = '';
			}

				echo aui()->input(array(
									'type'  =>  $type,
									'id'    =>  isset($field['args']['id']) ? esc_attr($field['args']['id']) : '',
									'name'    =>  isset($field['id']) ? esc_attr($field['id']) : '',
									'value' =>   is_scalar( $value ) ? esc_attr( $value ) : '',
									'required'  => false,
									'help_text' => $help_text,
									'label' => $label,
									'label_type'    => 'floating'
								));
			}elseif($field['callback'] == 'wpinv_select_callback' || $field['callback'] == 'wpinv_country_states_callback'){

if($field['id']=='wpinv_settings[default_state]'){
			$country_value  = wpinv_get_option( 'wpinv_settings[default_country]', 'US');
$options = wpinv_get_country_states($country_value);//echo $value .'###'.$country_value;
}else{
$options = isset($field['args']['options']) ? $field['args']['options'] : array();
}

//print_r($options );echo '###';

				echo aui()->select( array(
					'id'              =>  isset($field['args']['id']) ? esc_attr($field['args']['id']) : '',
					'name'            =>  isset($field['id']) ? esc_attr($field['id']) : '',
					'placeholder'     => '',
//					'title'           => $site_title,
					'value'           => is_scalar( $value ) ? esc_attr( $value ) : '',
					'required'        => false,
					'help_text'       => isset($field['args']['desc']) ? esc_attr($field['args']['desc']) : '',
					'label'           => isset($field['args']['name']) ? esc_attr($field['args']['name']) : '',
					'options'         => $options,
					'select2'         => true,
					'label_type'    => 'floating'
//					'wrap_class'      => isset( $field->css_class ) ? $field->css_class : '',
				) );
			}elseif($field['callback'] == 'wpinv_textarea_callback'){
				$textarea =  aui()->textarea( array(
					'id'              => isset($field['args']['id']) ? esc_attr($field['args']['id']) : '',
					'name'            => isset($field['id']) ? esc_attr($field['id']) : '',
					'placeholder'     => '',
//					'title'           => $site_title,
					'value'           => is_scalar( $value ) ? esc_attr( $value ) : '',
					'required'        => false,
					'help_text'       => isset($field['args']['desc']) ? esc_attr($field['args']['desc']) : '',
					'label'           => isset($field['args']['name']) ? esc_attr($field['args']['name']) : '',
					'rows'            => '4',
					'label_type'    => 'floating'
//					'wrap_class'      => isset( $field->css_class ) ? $field->css_class : '',
				) );

				// bug fixed in AUI 0.1.51 for name stripping []
				$textarea = str_replace(sanitize_html_class($field['args']['id']),esc_attr($field['args']['id']),$textarea );

				echo $textarea;
			}

			//echo "<div>";

	    }
	}

	/**
	 * Save Maps Settings.
	 *
	 * @since 2.0.0
	 */
	public function setup_business_save() {

	// nothing required here as options.php will send to next step
		//check_admin_referer( 'gp-setup' );

//print_r($_POST);exit;
//		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
//		exit;
	}

	/**
	 * Default Location settings.
	 *
	 * @since 2.0.0
	 */
	public function setup_currency() {

		?>

		<form method="post" class="text-left card-body" action="options.php">
			<?php


			settings_fields( 'wpinv_settings' );

			// override http referer to make it send back to the next step
				?>
			<input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( $this->get_next_step_link() ); ?>">

			<table class="gp-setup-maps w-100 " cellspacing="0">

				<tbody>

				<?php

				global $wp_settings_fields;

				$page = 'wpinv_settings_general_currency_section';
				$section = 'wpinv_settings_general_currency_section';
				if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
			        return;
			    }

			    $settings =  $wp_settings_fields[ $page ][ $section ];

//				print_r($settings);exit;

			    $this->output_fields($settings);
				?>


				</tbody>
			</table>


			<p class="gp-setup-actions step text-center mt-4">
				<input type="submit" class="btn btn-primary"
				       value="<?php esc_attr_e( 'Continue', 'invoicing' ); ?>" name="save_step"/>
			</p>
		</form>

		<?php
	}


	/**
	 * Save Default Location Settings.
	 *
	 * @since 2.0.0
	 */
	public function setup_currency_save() {
		check_admin_referer( 'gp-setup' );

		$generalSettings = new GeoDir_Settings_General();
		$settings        = $generalSettings->get_settings( 'location' );
		GeoDir_Admin_Settings::save_fields( $settings );

		do_action( 'geodir_setup_wizard_default_location_saved', $settings );

		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Dummy Data setup.
	 *
	 * @since 2.0.0
	 */
	public function setup_recommend() {

		?>
		<form method="post" class="text-center card-body">
			<div class="gd-wizard-recommend">

				<h2 class="gd-settings-title h3 "><?php _e( "Recommend Plugins", "geodirectory" ); ?></h2>

				<p><?php _e( "Below are a few of our own plugins that may help you.", "geodirectory" ); ?></p>


				<?php

				include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); //for plugins_api..

				$recommend_wp_plugins = self::get_recommend_wp_plugins();

				//			$status = install_plugin_install_status( array("slug"=>"two-factor","version"=>""));
				//			print_r($status);

				if ( ! empty( $recommend_wp_plugins ) ) {

				?>
				<ul class="list-group">
					<?php
						foreach ( $recommend_wp_plugins as $plugin ) {
						$status = install_plugin_install_status( array( "slug" => $plugin['slug'], "version" => "" ) );
						$plugin_status = isset( $status['status'] ) ? $status['status'] : '';
						?>
							<li class="list-group-item d-flex justify-content-between align-items-center flex-wrap text-left">
							    <span class="mr-auto"><?php echo esc_attr($plugin['name']); ?></span>
								<div class="spinner-border spinner-border-sm mr-2 d-none text-muted" role="status">
									<span class="sr-only">Loading...</span>
								</div>
								<div class="custom-control custom-switch  mr-n2">
									<input type="checkbox" class="custom-control-input"  <?php if( is_plugin_active( $plugin['slug'] ) ){echo "checked";} ?> onclick="if(jQuery(this).is(':checked')){}else{}">
									<label class="custom-control-label" for="ac-setting-updates"></label>
								</div>
								<small class="w-100"><?php echo esc_attr($plugin['desc'] );?></small>
							 </li>
						<?php
						}
                    ?>
				</ul>
				<?php

				}



				?>



			</div>

			<p class="gp-setup-actions step text-center mt-4">
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="btn btn-primary"><?php esc_attr_e( 'Continue', 'invoicing' ); ?></a>
			</p>
		</form>
		<?php
	}

		/**
	 * A list of recommended wp.org plugins.
	 * @return array
	 */
	public static function get_recommend_wp_plugins(){
		$plugins = array(
			'ayecode-connect' => array(
				'url'   => 'https://wordpress.org/plugins/ayecode-connect/',
				'slug'   => 'ayecode-connect',
				'name'   => 'AyeCode Connect',
				'desc'   => __( 'Documentation and Support from within your WordPress admin.', 'geodirectory' ),
			),
			'ninja-forms' => array(
				'url'   => 'https://wordpress.org/plugins/invoicing-quotes/',
				'slug'   => 'invoicing-quotes',
				'name'   => 'Customer Quotes',
				'desc'   => __('Create & Send Quotes to Customers and have them accept and pay.','geodirectory'),
			),
			'userswp' => array(
				'url'   => 'https://wordpress.org/plugins/userswp/',
				'slug'   => 'userswp',
				'name'   => 'UsersWP',
				'desc'   => __('Frontend user login and registration as well as slick profile pages.','geodirectory'),
			),
		);

		return $plugins;
	}

	/**
	 * Dummy data save.
	 *
	 * This is done via ajax so we just pass onto the next step.
	 *
	 * @since 2.0.0
	 */
	public function setup_recommend_save() {
		check_admin_referer( 'gp-setup' );
		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Dummy Data setup.
	 *
	 * @since 2.0.0
	 */
	public function setup_payments() {
		?>
		<form method="post" class="text-center card-body">
			<div class="gp-wizard-payments">

				<h2 class="gd-settings-title h3 "><?php _e( "Gateway Setup", "geodirectory" ); ?></h2>

				<p><?php _e( "Below are a few gateways that can be setup in a few seconds.", "geodirectory" ); ?><br><?php _e( "We have 20+ Gateways that can be setup later.", "geodirectory" ); ?></p>

				<ul class="list-group">
				  <li class="list-group-item d-flex justify-content-between align-items-center">
				    <span class="mr-auto"><img src="<?php echo WPINV_PLUGIN_URL . 'assets/images/stripe-verified.svg';?>" class="ml-n2" alt="Stripe"></span>
					<div class="spinner-border spinner-border-sm mr-2 d-none text-muted" role="status">
						<span class="sr-only">Loading...</span>
					</div>
				    <span class="btn btn-sm btn-outline-primary">Connect</span>
				  </li>
				  <li class="list-group-item d-flex justify-content-between align-items-center">
				    <span class="mr-auto"><img src="<?php echo WPINV_PLUGIN_URL . 'assets/images/pp-logo-150px.webp';?>" class="" alt="PayPal" height="25"></span>
					<div class="spinner-border spinner-border-sm mr-2 d-none text-muted" role="status">
						<span class="sr-only">Loading...</span>
					</div>
				    <span class="btn btn-sm btn-outline-primary">Connect</span>
				  </li>
				  <li class="list-group-item d-flex justify-content-between align-items-center">
				    <span class="mr-auto">Test Gateway</span>
					<div class="spinner-border spinner-border-sm mr-2 d-none text-muted" role="status">
						<span class="sr-only">Loading...</span>
					</div>
					<div class="custom-control custom-switch">
						<input type="checkbox" class="custom-control-input" id="ac-setting-updates" checked="" onclick="if(jQuery(this).is(':checked')){}else{}">
						<label class="custom-control-label" for="ac-setting-updates"></label>
					</div>
				  </li>
				</ul>

				<?php





				?>


			</div>

			<p class="gp-setup-actions step text-center mt-4">
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="btn btn-primary"><?php esc_attr_e( 'Continue', 'invoicing' ); ?></a>
			</p>
		</form>
		<?php
	}

	/**
	 * Dummy data save.
	 *
	 * This is done via ajax so we just pass onto the next step.
	 *
	 * @since 2.0.0
	 */
	public function setup_payments_save() {
		check_admin_referer( 'gp-setup' );
		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Final step.
	 *
	 * @since 2.0.0
	 */
	public function setup_ready() {
		$this->setup_ready_actions();
		?>

		<div class="text-center card-body">
			<h1 class="h3"><?php esc_html_e( 'Awesome, you are ready to GetPaid', 'invoicing' ); ?></h1>


			<div class="geodirectory-message geodirectory-tracker">
				<p><?php _e( 'Thank you for choosing GetPaid!', 'invoicing' ); ?> <i class="far fa-smile-beam"></i></p>
			</div>

			<div class="gp-setup-next-steps">
				<div class="gp-setup-next-steps-first mb-4">
					<h2 class="h3"><?php esc_html_e( 'Next steps', 'invoicing' ); ?></h2>
					<a class="btn btn-primary btn-sm"
						                             href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpi_item' ) ); ?>"><?php esc_html_e( 'Create your first Item!', 'invoicing' ); ?></a>
				</div>
				<div class="gp-setup-next-steps-first mb-4">
					<h2 class="h3"><?php esc_html_e( 'Examples', 'invoicing' ); ?></h2>
					<a class="btn btn-primary btn-sm"
					target="_blank" href="https://demos.ayecode.io/getpaid/"><?php esc_html_e( "View What's Possible", 'invoicing' ); ?></a>

                     <a class="btn btn-outline-primary btn-sm"
                     target="_blank" href="https://demos.ayecode.io/getpaid/"><?php esc_html_e( "View What's Possible", 'invoicing' ); ?></a>
				</div>
				<div class="gp-setup-next-steps-last">
					<h2 class="h3"><?php _e( 'Learn more', 'invoicing' ); ?></h2>
					<a class="btn btn-outline-primary btn-sm" href="https://docs.wpgetpaid.com/collection/114-getting-started?utm_source=setupwizard&utm_medium=product&utm_content=getting-started&utm_campaign=invoicingplugin"
								target="_blank"><?php esc_html_e( 'Getting Started', 'invoicing' ); ?></a>
						<a class="btn btn-outline-primary btn-sm"
								href="https://docs.wpgetpaid.com/?utm_source=setupwizard&utm_medium=product&utm_content=docs&utm_campaign=invoicingplugin"
								target="_blank"><?php esc_html_e( 'Documentation', 'invoicing' ); ?></a>
						<a class="btn btn-outline-primary btn-sm"
								href="https://wpgetpaid.com/support/?utm_source=setupwizard&utm_medium=product&utm_content=docs&utm_campaign=invoicingyplugin"
								target="_blank"><?php esc_html_e( 'Support', 'invoicing' ); ?></a>
								<a class="btn btn-outline-primary btn-sm"
								href="https://demos.ayecode.io/getpaid/?utm_source=setupwizard&utm_medium=product&utm_content=demos&utm_campaign=invoicingyplugin"
								target="_blank"><?php esc_html_e( 'Demos', 'invoicing' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Actions on the final step.
	 *
	 * @since 2.0.0
	 */
	private function setup_ready_actions() {
		GeoDir_Admin_Notices::remove_notice( 'install' );

		if ( isset( $_GET['gd_tracker_optin'] ) && isset( $_GET['gd_tracker_nonce'] ) && wp_verify_nonce( $_GET['gd_tracker_nonce'], 'gd_tracker_optin' ) ) {
			geodir_update_option( 'usage_tracking', true );
			GeoDir_Admin_Tracker::send_tracking_data( true );

		} elseif ( isset( $_GET['gd_tracker_optout'] ) && isset( $_GET['gd_tracker_nonce'] ) && wp_verify_nonce( $_GET['gd_tracker_nonce'], 'gd_tracker_optout' ) ) {
			geodir_update_option( 'usage_tracking', false );
		}
	}


}

new GetPaid_Admin_Setup_Wizard();
