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
				<div class="membership-content">
<!--
				<h2>With our WPInvoicing Membership you get access to all our products!</h2>
				<p><a class="button button-primary" href="https://wpinvoicing.com/downloads/membership/">View Memberships</a></p>-->
				<?php if(defined('WP_EASY_UPDATES_ACTIVE')){?>
					<h2><?php _e("Have a membership key?","invoicing");?></h2>
					<p>
						<?php
						$wpeu_admin = new External_Updates_Admin('wpinvoicing.com','1');
						echo $wpeu_admin->render_licence_actions('wpinvoicing.com', 'membership',array(95, 106, 108,12351));
						?>
					</p>
				<?php }?>

				<div class="membership-cta-contet">
					<div class="main-cta">
							<h2><?php _e("Membership benefits Include:","invoicing");?></h2>
							<div class="feature-list">
								<ul>
									<?php
									$addon_obj = new WPInv_Admin_Addons();
									if ($addons = $addon_obj->get_section_data( 'addons' ) ) {
										foreach ( $addons as $addon ) {
											echo '<li><i class="far fa-check-circle fa-sm"></i> '.esc_html( $addon->info->title ).'</li>';
										}
									}
									?>
									</ul>

									<div class="feature-cta">
										<h3><?php _e("Membership Starts from","invoicing");?></h3>
										<h4>$99</h4>
										<a href="https://wpinvoicing.com/downloads/membership/" target="_blank"><?php _e("Buy Membership","invoicing");?></a>
									</div>
									<h3><?php _e("Included Gateways:","invoicing");?></h3>
									<ul>
										<?php
										if ($addons = $addon_obj->get_section_data( 'gateways' ) ) {
											foreach ( $addons as $addon ) {
												echo '<li><i class="far fa-check-circle fa-sm"></i> '.esc_html( $addon->info->title ).'</li>';
											}
										}
										?>
								</ul>
							</div>


					</div>
					<div class="member-testimonials">
						<h3>Testimonials</h3>
						<div class="testimonial-content">
							<div class="t-image">
								<?php
									echo '<img src="' . plugins_url( 'images/t-image2.png', dirname(__FILE__) ) . '" > ';
								?>
							</div>
							<div class="t-content">
								<p>
									It works perfectly right out of the box and above all it’s VAT compliant, something crucial for everyone doing business with EU B2C and B2B customers.<br><br>

Then I had a minor issue which required their support and they delivered an unparalleled example of how excellent support works.<br><br>

Response was super fast, they analyzed the issue, delivered a patch in record time and solved this issue for good in the next release.<br><br>

Many commercial plugins and theme companies from the WordPress scene should learn from them.
								</p>
								<p><strong>Pedstone </strong> (@pedstone)</p>
							</div>
						</div>

						<div class="testimonial-content">
							<div class="t-image">
								<?php
									echo '<img src="' . plugins_url( 'images/t-image1.png', dirname(__FILE__) ) . '" > ';
								?>
							</div>
							<div class="t-content">
								<p>
									I have been looking for a basic invoicing system that will allow recurring invoices.
This plugin is far from basic, with some nice in-depth options yet a great easy to use interface.<br><br>

I tried numerous plugins in an attempt to give me what I needed, this is by far the best and there was no need to buy premium plugins to get the features I required.<br><br>
Great job so far guys, can’t wait to see where this goes!
								</p>
								<p><strong>Coldcutt </strong>(@coldcutt)</p>
							</div>
						</div>
					</div>
					<div class="member-footer">
						<a class="footer-btn" href="https://wpinvoicing.com/downloads/membership/" target="_blank"><?php _e("Buy Membership","invoicing");?></a>
						<a class="footer-link" href="post-new.php?post_type=wpi_invoice"><?php _e("Create Invoice","invoicing");?></a>
					</div>
				</div>


			</div>
		</div>
			<?php
		}else{
			$installed_plugins = get_plugins();
            $addon_obj = new WPInv_Admin_Addons();
			if ($addons = $addon_obj->get_section_data( $current_tab ) ) :
				//print_r($addons);
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

									if ( 'stripe-payment-gateway' == $addon->info->slug ) {
										$addon->info->slug = 'getpaid-stripe-payments';
										$addon->info->link = 'https://wordpress.org/plugins/getpaid-stripe-payments/';
									}

									if(isset($addon->info->link) && substr( $addon->info->link, 0, 21 ) === "https://wordpress.org"){
										echo '<a href="'.admin_url('/plugin-install.php?tab=plugin-information&plugin='.$addon->info->slug).'&width=770&height=660&TB_iframe=true" class="thickbox" >';
										echo '<span class="wpi-product-info">'.__('More info','invoicing').'</span>';
										echo '</a>';
									}elseif(isset($addon->info->link) && ( substr( $addon->info->link, 0, 23 ) === "https://wpinvoicing.com" || substr( $addon->info->link, 0, 21 ) === "https://wpgetpaid.com" ) ){
										if(defined('WP_EASY_UPDATES_ACTIVE')){
											$url = admin_url('/plugin-install.php?tab=plugin-information&plugin='.$addon->info->slug.'&width=770&height=660&item_id='.$addon->info->id.'&update_url=https://wpgetpaid.com&TB_iframe=true');
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
