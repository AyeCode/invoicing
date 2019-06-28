<?php
/**
 * Admin View: Page - Addons
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
add_ThickBox();
?>
<div class="wrap wpi_addons_wrap">
	<h1><?php echo get_admin_page_title(); ?></h1>

	<?php if ( $tabs ){ ?>
		<nav class="nav-tab-wrapper wpi-nav-tab-wrapper">
			<?php
			foreach ( $tabs as $name => $label ) {
				echo '<a href="' . admin_url( 'admin.php?page=wpi-addons&tab=' . $name ) . '" class="nav-tab ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) . '">' . $label . '</a>';
			}
			do_action( 'wpi_addons_tabs' );
			?>
		</nav>

		<?php

		if($current_tab == 'membership'){

			?>

			<div class="wpi-membership-tab-conatiner">
				<h2>With our WPInvoicing Membership you get access to all our products!</h2>
				<p><a class="button button-primary" href="https://wpinvoicing.com/downloads/membership/">View Memberships</a></p>
				<?php if(defined('WP_EASY_UPDATES_ACTIVE')){?>

					<h2>Have a membership key?</h2>

					<p>
						<?php
						$wpeu_admin = new External_Updates_Admin('wpinvoicing.com','1');
						echo $wpeu_admin->render_licence_actions('wpinvoicing.com', 'membership',array(95, 106, 108));
						?>
					</p>
				<?php }?>
			</div>

			<?php
		}else{
			$installed_plugins = get_plugins();
            $addon_obj = new WPInv_Admin_Addons();
			if ($addons = $addon_obj->get_section_data( $current_tab ) ) :
				?>
				<ul class="wpi-products"><?php foreach ( $addons as $addon ) :
                        if(965==$addon->info->id){continue;}// don't show quote add on
						?><li class="wpi-product">
								<div class="wpi-product-title">
									<h3><?php
										if ( ! empty( $addon->info->excerpt) ){
											echo wpi_help_tip( $addon->info->excerpt );
										}
										echo esc_html( $addon->info->title ); ?></h3>
								</div>

								<span class="wpi-product-image">
									<?php if ( ! empty( $addon->info->thumbnail) ) : ?>
										<img src="<?php echo esc_attr( $addon->info->thumbnail ); ?>"/>
									<?php endif;

									if(isset($addon->info->link) && substr( $addon->info->link, 0, 21 ) === "https://wordpress.org"){
										echo '<a href="'.admin_url('/plugin-install.php?tab=plugin-information&plugin='.$addon->info->slug).'&TB_iframe=true&width=770&height=660" class="thickbox" >';
										echo '<span class="wpi-product-info">'.__('More info','invoicing').'</span>';
										echo '</a>';
									}elseif(isset($addon->info->link) && substr( $addon->info->link, 0, 23 ) === "https://wpinvoicing.com"){
										if(defined('WP_EASY_UPDATES_ACTIVE')){
											$url = admin_url('/plugin-install.php?tab=plugin-information&plugin='.$addon->info->slug.'&TB_iframe=true&width=770&height=660&item_id='.$addon->info->id.'&update_url=https://wpinvoicing.com');
										}else{
											// if installed show activation link
											if(isset($installed_plugins['wp-easy-updates/external-updates.php'])){
												$url = '#TB_inline?width=600&height=50&inlineId=wpi-wpeu-required-activation';
											}else{
												$url = '#TB_inline?width=600&height=50&inlineId=wpi-wpeu-required-for-external';
											}
										}
										echo '<a href="'.$url.'" class="thickbox">';
										echo '<span class="wpi-product-info">'.__('More info','invoicing').'</span>';
										echo '</a>';
									}

									?>

								</span>


								<span class="wpi-product-button">
									<?php
                                    $addon_obj->output_button( $addon );
									?>
								</span>

								<span class="wpi-price"><?php //print_r($addon); //echo wp_kses_post( $addon->price ); ?></span></li><?php endforeach; ?></ul>
			<?php endif;
		}

	}
	?>


	<div class="clearfix" ></div>

	<?php if($current_tab =='addons'){ ?>
	<p><?php printf( __( 'All of our Invoicing Addons can be found on WPInvoicing.com here: <a href="%s">Invoicing Addons</a>', 'invoicing' ), 'https://wpinvoicing.com/downloads/category/addons/' ); ?></p>
	<?php } if($current_tab =='gateways'){  ?>
    <p><?php printf( __( 'All of our Invoicing Payment Gateways can be found on WPInvoicing.com here: <a href="%s">Invoicing Payment Gateways</a>', 'invoicing' ), 'https://wpinvoicing.com/downloads/category/gateways/' ); ?></p>
    <?php } ?>

	<div id="wpi-wpeu-required-activation" style="display:none;"><span class="wpi-notification "><?php printf( __("The plugin <a href='https://wpeasyupdates.com/' target='_blank'>WP Easy Updates</a> is required to check for and update some installed plugins/themes, please <a href='%s'>activate</a> it now.",'invoicing'),wp_nonce_url(admin_url('plugins.php?action=activate&plugin=wp-easy-updates/external-updates.php'), 'activate-plugin_wp-easy-updates/external-updates.php'));?></span></div>
	<div id="wpi-wpeu-required-for-external" style="display:none;"><span class="wpi-notification "><?php printf(  __("The plugin <a href='https://wpeasyupdates.com/' target='_blank'>WP Easy Updates</a> is required to check for and update some installed plugins/themes, please <a href='%s' onclick='window.open(\"https://wpeasyupdates.com/wp-easy-updates.zip\", \"_blank\");' >download</a> and install it now.",'invoicing'),admin_url("plugin-install.php?tab=upload&wpeu-install=true"));?></span></div>
	<div id="wpeu-licence-popup" style="display:none;">
		<span class="wpi-notification noti-white">
			<h3 class="wpeu-licence-title"><?php _e("Licence key",'invoicing');?></h3>
			<input class="wpeu-licence-key" type="text" placeholder="<?php _e("Enter your licence key",'invoicing');?>"> <button class="button-primary wpeu-licence-popup-button" ><?php _e("Install",'invoicing');?></button>
			<br>
			<?php
			echo sprintf( __('%sFind your licence key here%s OR %sBuy one here%s', 'invoicing'), '<a href="https://wpinvoicing.com/your-account/" target="_blank">','</a>','<a class="wpeu-licence-link" href="https://wpinvoicing.com/downloads/category/addons/" target="_blank">','</a>' );
			?>
		</span>
	</div>

</div>
