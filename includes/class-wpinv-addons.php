<?php
/**
 * Invoicing extensions screen related functions
 *
 * All Invoicing extensions screen related functions can be found here.
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPInv_Admin_Addons Class.
 */
class WPInv_Admin_Addons extends Ayecode_Addons {


	/**
	 * Get the extensions page tabs.
	 *
	 * @return array of tabs.
	 */
	public function get_tabs(){
		$tabs = array(
			'addons' => __("Addons", "invoicing"),
            'gateways' => __("Payment Gateways", "invoicing"),
            'recommended_plugins' => __("Recommended plugins", "invoicing"),
            'membership' => __("Membership", "invoicing"),
		);

		return $tabs;
	}

	/**
	 * Get section content for the addons screen.
	 *
	 * @param  string $section_id
	 *
	 * @return array
	 */
	public function get_section_data( $section_id ) {
		$section      = self::get_tab( $section_id );
		$api_url = "https://wpinvoicing.com/edd-api/v2/products/";
		$section_data = new stdClass();

		if($section_id=='recommended_plugins'){
			$section_data->products = self::get_recommend_wp_plugins_edd_formatted();
		}
		elseif ( ! empty( $section ) ) {
			if ( false === ( $section_data = get_transient( 'wpi_addons_section_' . $section_id ) ) ) { //@todo restore after testing
			//if ( 1==1) {

				$query_args = array( 'category' => $section_id, 'number' => 100);
				$query_args = apply_filters('wpeu_edd_api_query_args',$query_args,$api_url,$section_id);

				$raw_section = wp_safe_remote_get( esc_url_raw( add_query_arg($query_args ,$api_url) ), array( 'user-agent' => 'Invoicing Addons Page','timeout'     => 15, ) );

				if ( ! is_wp_error( $raw_section ) ) {
					$section_data = json_decode( wp_remote_retrieve_body( $raw_section ) );

					if ( ! empty( $section_data->products ) ) {
						set_transient( 'wpi_addons_section_' . $section_id, $section_data, DAY_IN_SECONDS );
					}
				}
			}

		}

		$products = isset($section_data->products) ? $section_data->products : array();
		if ( 'addons' == $section_id ) {

			$quotes = new stdClass();
			$quotes->info = new stdClass();
			$quotes->info->id = '';
			$quotes->info->slug = 'invoicing-quotes';
			$quotes->info->title = __( 'Quotes', 'invoicing' );
			$quotes->info->excerpt = __( 'Create quotes and estimates', 'invoicing' );
			$quotes->info->link = 'https://wordpress.org/plugins/invoicing-quotes/';
			$quotes->info->thumbnail = 'https://wpgetpaid.com/wp-content/uploads/sites/13/edd/2019/11/Quotes-1-768x384.png';

			$products[] = $quotes;
		}
		
		return apply_filters( 'wpi_addons_section_data', $products, $section_id );
	}

	/**
	 * Outputs a button.
	 *ccc
	 * @param string $url
	 * @param string $text
	 * @param string $theme
	 * @param string $plugin
	 */
	public function output_button( $addon ) {
		$current_tab     = empty( $_GET['tab'] ) ? 'addons' : sanitize_title( $_GET['tab'] );
//		$button_text = __('Free','invoicing');
//		$licensing = false;
//		$installed = false;
//		$price = '';
//		$license = '';
//		$slug = '';
//		$url = isset($addon->info->link) ? $addon->info->link : '';
//		$class = 'button-primary';
//		$install_status = 'get';
//		$onclick = '';

		$wp_org_themes = array('supreme-directory','directory-starter');

		$button_args = array(
			'type' => ($current_tab == 'addons' || $current_tab =='gateways') ? 'addons' : $current_tab,
			'id' => isset($addon->info->id) ? absint($addon->info->id) : '',
			'title' => isset($addon->info->title) ? $addon->info->title : '',
			'button_text' => __('Free','invoicing'),
			'price_text' => __('Free','invoicing'),
			'link' => isset($addon->info->link) ? $addon->info->link : '', // link to product
			'url' => isset($addon->info->link) ? $addon->info->link : '', // button url
			'class' => 'button-primary',
			'install_status' => 'get',
			'installed' => false,
			'price' => '',
			'licensing' => isset($addon->licensing->enabled) && $addon->licensing->enabled ? true : false,
			'license' => isset($addon->licensing->license) && $addon->licensing->license ? $addon->licensing->license : '',
			'onclick' => '',
			'slug' => isset($addon->info->slug) ? $addon->info->slug : '',
			'active' => false,
			'file' => '',
			'update_url' => '',
		);

		if( 'invoicing-quotes' == $addon->info->slug || 'getpaid-stripe-payments' == $addon->info->slug || ( $current_tab == 'recommended_plugins' && isset($addon->info->slug) && $addon->info->slug )){
			include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); //for plugins_api..
			$status = install_plugin_install_status(array("slug"=>$button_args['slug'],"version"=>""));
			$button_args['install_status'] = isset($status['status']) ? $status['status'] : 'install';
			$button_args['file'] = isset($status['file']) ? $status['file'] : '';
		}elseif( ($current_tab == 'addons' || $current_tab =='gateways') && isset($addon->info->id) && $addon->info->id){
			include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); //for plugins_api..
			if(!empty($addon->licensing->edd_slug)){$button_args['slug'] = $addon->licensing->edd_slug;}
			$status = self::install_plugin_install_status($addon);
			$button_args['file'] = isset($status['file']) ? $status['file'] : '';
			if(isset($status['status'])){$button_args['install_status'] = $status['status'];}
			$button_args['update_url'] = "https://wpinvoicing.com";
		}elseif($current_tab == 'themes' && isset($addon->info->id) && $addon->info->id) {
			if(!empty($addon->licensing->edd_slug)){$button_args['slug'] = $addon->licensing->edd_slug;}
			$button_args['installed'] = self::is_theme_installed($addon);
			if(!in_array($button_args['slug'],$wp_org_themes)){
				$button_args['update_url'] = "https://wpinvoicing.com";
			}
		}

		// set price
		if(isset($addon->pricing) && !empty($addon->pricing)){
			if(is_object($addon->pricing)){
				$prices = (Array)$addon->pricing;
				$button_args['price'] = reset($prices);
			}elseif(isset($addon->pricing)){
				$button_args['price'] = $addon->pricing;
			}
		}

		// set price text
		if( $button_args['price'] && $button_args['price'] != '0.00' ){
			$button_args['price_text'] = sprintf( __('From: $%d', 'invoicing'), $button_args['price']);
		}


		// set if installed
		if(in_array($button_args['install_status'], array('installed','latest_installed','update_available','newer_installed'))){
			$button_args['installed'] = true;
		}

//		print_r($button_args);
		// set if active
		if($button_args['installed'] && ($button_args['file'] || $button_args['type'] == 'themes')){
			if($button_args['type'] != 'themes'){
				$button_args['active'] = is_plugin_active($button_args['file']);
			}else{
				$button_args['active'] = self::is_theme_active($addon);
			}
		}

		// set button text and class
		if($button_args['active']){
			$button_args['button_text'] = __('Active','invoicing');
			$button_args['class'] = ' button-secondary disabled ';
		}elseif($button_args['installed']){
			$button_args['button_text'] = __('Activate','invoicing');

			if($button_args['type'] != 'themes'){
				if ( current_user_can( 'manage_options' ) ) {
					$button_args['url'] = wp_nonce_url(admin_url('plugins.php?action=activate&plugin='.$button_args['file']), 'activate-plugin_' . $button_args['file']);
				}else{
					$button_args['url'] = '#';
				}
			}else{
				if ( current_user_can( 'switch_themes' ) ) {
					$button_args['url'] = self::get_theme_activation_url($addon);
				}else{
					$button_args['url'] = '#';
				}
			}

		}else{
			if($button_args['type'] == 'recommended_plugins'){
				$button_args['button_text'] = __('Install','invoicing');
			}else{
				$button_args['button_text'] = __('Get it','invoicing');

				/*if($button_args['type'] == 'themes' && in_array($button_args['slug'],$wp_org_themes) ){
					$button_args['button_text'] = __('Install','invoicing');
					$button_args['url'] = self::get_theme_install_url($button_args['slug']);
					$button_args['onclick'] = 'gd_set_button_installing(this);';
				}*/

			}
		}

		
		// filter the button arguments
		$button_args = apply_filters('edd_api_button_args',$button_args);
//		print_r($button_args);
		// set price text
		if(isset($button_args['price_text'])){
			?>
			<a
				target="_blank"
				class="addons-price-text"
				href="<?php echo esc_url( $button_args['link'] ); ?>">
				<?php echo esc_html( $button_args['price_text'] ); ?>
			</a>
			<?php
		}


		$target = '';
		if ( ! empty( $button_args['url'] ) ) {
			$target = strpos($button_args['url'], get_site_url()) !== false ? '' : ' target="_blank" ';
		}

		?>
		<a
			data-licence="<?php echo esc_attr($button_args['license']);?>"
			data-licensing="<?php echo $button_args['licensing'] ? 1 : 0;?>"
			data-title="<?php echo esc_attr($button_args['title']);?>"
			data-type="<?php echo esc_attr($button_args['type']);?>"
			data-text-error-message="<?php _e('Something went wrong!','invoicing');?>"
			data-text-activate="<?php _e('Activate','invoicing');?>"
			data-text-activating="<?php _e('Activating','invoicing');?>"
			data-text-deactivate="<?php _e('Deactivate','invoicing');?>"
			data-text-installed="<?php _e('Installed','invoicing');?>"
			data-text-install="<?php _e('Install','invoicing');?>"
			data-text-installing="<?php _e('Installing','invoicing');?>"
			data-text-error="<?php _e('Error','invoicing');?>"
			<?php if(!empty($button_args['onclick'])){echo " onclick='".$button_args['onclick']."' ";}?>
			<?php echo $target;?>
			class="addons-button  <?php echo esc_attr( $button_args['class'] ); ?>"
			href="<?php echo esc_url( $button_args['url'] ); ?>">
			<?php echo esc_html( $button_args['button_text'] ); ?>
		</a>
		<?php


	}


	/**
	 * Handles output of the addons page in admin.
	 */
	public function output() {
		$tabs            = self::get_tabs();
		$sections        = self::get_sections();
		$theme           = wp_get_theme();
		$section_keys    = array_keys( $sections );
		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : current( $section_keys );
		$current_tab     = empty( $_GET['tab'] ) ? 'addons' : sanitize_title( $_GET['tab'] );
		include_once( WPINV_PLUGIN_DIR . '/includes/admin/html-admin-page-addons.php' );
	}

	/**
	 * A list of recommended wp.org plugins.
	 * @return array
	 */
	public function get_recommend_wp_plugins(){
		$plugins = array(
            'invoicing-quotes' => array(
                'url'   => 'https://wordpress.org/plugins/invoicing-quotes/',
                'slug'   => 'invoicing-quotes',
				'name'   => 'Quotes',
				'thumbnail'  => 'https://ps.w.org/invoicing-quotes/assets/banner-772x250.png',
                'desc'   => __('Allows you to create quotes, send them to clients and convert them to Invoices when accepted by the customer.','invoicing'),
            ),
            'geodirectory' => array(
                'url'   => 'https://wordpress.org/plugins/geodirectory/',
                'slug'   => 'geodirectory',
                'name'   => 'GeoDirectory',
                'desc'   => __('Turn any WordPress theme into a global business directory portal.','invoicing'),
            ),
            'userswp' => array(
                'url'   => 'https://wordpress.org/plugins/userswp/',
                'slug'   => 'userswp',
                'name'   => 'UsersWP',
                'desc'   => __('Allow frontend user login and registration as well as have slick profile pages.','invoicing'),
            ),
		);

		return $plugins;
	}
}
