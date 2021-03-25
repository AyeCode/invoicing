<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template Class
 *
 */
class GetPaid_Template {

    /**
     * @param string
     */
    public $templates_dir;

    /**
     * @param string
     */
    public $templates_url;

    /**
	 * Class constructor.
	 *
	 * @since 1.0.19
	 */
	public function __construct() {

        $this->templates_dir = apply_filters( 'getpaid_default_templates_dir', WPINV_PLUGIN_DIR . 'templates' );
        $this->templates_url = apply_filters( 'getpaid_default_templates_url', WPINV_PLUGIN_URL . 'templates' );

        // Oxygen plugin
		if ( defined( 'CT_VERSION' ) ) {
			add_filter( 'wpinv_locate_template', array( $this, 'oxygen_override_template' ), 11, 4 );
		}

    }

    /**
	 * Checks if this is a preview page
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	public function is_preview() {
        return 
            $this->is_divi_preview() ||
            $this->is_elementor_preview() ||
            $this->is_beaver_preview() ||
            $this->is_siteorigin_preview() ||
            $this->is_cornerstone_preview() ||
            $this->is_fusion_preview() ||
            $this->is_oxygen_preview();
    }

    /**
	 * Checks if this is an elementor preview page
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	public function is_elementor_preview() {
		return isset( $_REQUEST['elementor-preview'] ) || ( is_admin() && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'elementor' ) || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'elementor_ajax' );
	}

	/**
	 * Checks if this is a DIVI preview page
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	public function is_divi_preview() {
		return isset( $_REQUEST['et_fb'] ) || isset( $_REQUEST['et_pb_preview'] ) || ( is_admin() && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'et_pb' );
	}

	/**
	 * Checks if this is a beaver builder preview page
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	public function is_beaver_preview() {
		return isset( $_REQUEST['fl_builder'] );
	}

	/**
	 * Checks if this is a siteorigin builder preview page
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	public function is_siteorigin_preview() {
		return ! empty( $_REQUEST['siteorigin_panels_live_editor'] );
	}

	/**
	 * Checks if this is a cornerstone builder preview page
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	public function is_cornerstone_preview() {
		return ! empty( $_REQUEST['cornerstone_preview'] ) || basename( $_SERVER['REQUEST_URI'] ) == 'cornerstone-endpoint';
	}

	/**
	 * Checks if this is a fusion builder preview page
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	public function is_fusion_preview() {
		return ! empty( $_REQUEST['fb-edit'] ) || ! empty( $_REQUEST['fusion_load_nonce'] );
	}

	/**
	 * Checks if this is an oxygen builder preview page
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	public function is_oxygen_preview() {
		return ! empty( $_REQUEST['ct_builder'] ) || ( ! empty( $_REQUEST['action'] ) && ( substr( $_REQUEST['action'], 0, 11 ) === "oxy_render_" || substr( $_REQUEST['action'], 0, 10 ) === "ct_render_" ) );
    }

    /**
     * Locates a template path.
     * 
     * @param string $template_name e.g payment-forms/cart.php The template to locate.
     * @param string $template_path The template path relative to the theme's root dir. Defaults to 'invoicing'.
     * @param string $default_path The root path to the default template. Defaults to invoicing/templates
     */
	public function locate_template( $template_name, $template_path = '', $default_path = '' ) {

        // Load the defaults for the template path and default path.
        $template_path = empty( $template_path ) ? 'invoicing' : $template_path;
        $default_path  = empty( $default_path ) ? $this->templates_dir : $default_path;
        $default_path  = apply_filters( 'getpaid_template_default_template_path', $default_path, $template_name );

        // Is it overidden?
        $template = locate_template(
            array( trailingslashit( $template_path ) . $template_name, 'wpinv-' . $template_name )
        );

        // If not, load the default template.
        if ( empty( $template ) ) {
            $template = trailingslashit( $default_path ) . $template_name;
        }

        return apply_filters( 'wpinv_locate_template', $template, $template_name, $template_path, $default_path );
    }
    
    /**
	 * Loads a template
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	protected function load_template( $template_name, $template_path, $args ) {

        if ( is_array( $args ) ){
            extract( $args );
        }

        // Fires before loading a template.
	    do_action( 'wpinv_before_template_part', $template_name, $template_path, $args );

        // Load the template.
	    include( $template_path );

        // Fires after loading a template.
        do_action( 'wpinv_after_template_part', $template_name, $template_path, $args );

    }

    /**
     * Displays a template.
     * 
     * First checks if there is a template overide, if not it loads the default template.
     * 
     * @param string $template_name e.g payment-forms/cart.php The template to locate.
     * @param array $args An array of args to pass to the template.
     * @param string $template_path The templates directory relative to the theme's root dir. Defaults to 'invoicing'.
     * @param string $default_path The root path to the default template. Defaults to invoicing/templates
     */
	public function display_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {

        // Locate the template.
        $located = $this->locate_template( $template_name, $template_path, $default_path );

        // Abort if the file does not exist.
        if ( ! file_exists( $located ) ) {
            getpaid_doing_it_wrong( __METHOD__, sprintf( '<code>%s</code> does not exist.', $located ), '2.0.0' );
            return;
        }

        $this->load_template( $template_name, $located, $args );

    }
    
    /**
     * Retrieves a template.
     * 
     * First checks if there is a template overide, if not it loads the default template.
     * 
     * @param string $template_name e.g payment-forms/cart.php The template to locate.
     * @param array $args An array of args to pass to the template.
     * @param string $template_path The templates directory relative to the theme's root dir. Defaults to 'invoicing'.
     * @param string $default_path The root path to the default template. Defaults to invoicing/templates
     */
	public function get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
        ob_start();
        $this->display_template( $template_name, $args, $template_path, $default_path );
        return ob_get_clean();
    }

    /**
	 * Get the geodirectory templates theme path.
	 *
	 *
	 * @return string Template path.
	 */
	public static function get_theme_template_path() {
		$template   = get_template();
		$theme_root = get_theme_root( $template );

		return $theme_root . '/' . $template . '/' . untrailingslashit( wpinv_get_theme_template_dir_name() );

	}

	/**
	 * Oxygen locate theme template.
	 *
	 * @param string $template The template.
	 * @return string The theme template.
	 */
	public static function oxygen_locate_template( $template ) {

		if ( empty( $template ) ) {
			return '';
		}

		$has_filter = has_filter( 'template', 'ct_oxygen_template_name' );

		// Remove template filter
		if ( $has_filter ) {
			remove_filter( 'template', 'ct_oxygen_template_name' );
		}

		$template = self::get_theme_template_path() . '/' . $template;

		if ( ! file_exists( $template ) ) {
			$template = '';
		}

		// Add template filter
		if ( $has_filter ) {
			add_filter( 'template', 'ct_oxygen_template_name' );
		}

		return $template;
	}

	/**
	 * Oxygen override theme template.
	 *
	 * @param string $located Located template.
	 * @param string $template_name Template name.
	 * @return string Located template.
	 */
	public function oxygen_override_template( $located, $template_name ) {

        $oxygen_overide = self::oxygen_locate_template( $template_name );
		if ( ! empty( $oxygen_overide ) ) {
			return $oxygen_overide;
		}

		return $located;
	}

}
