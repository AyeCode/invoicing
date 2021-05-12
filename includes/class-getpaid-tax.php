<?php
/**
 * Contains the main tax class.
 *
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class GetPaid_Tax
 *
 */
class GetPaid_Tax {

	/**
	 * Calculates tax for a line item.
	 *
	 * @param  float   $price              The price to calc tax on.
	 * @param  array   $rates              The rates to apply.
	 * @param  boolean $price_includes_tax Whether the passed price has taxes included.
	 * @return array                       Array of tax name => tax amount.
	 */
	public static function calc_tax( $price, $rates, $price_includes_tax = false ) {

		if ( $price_includes_tax ) {
			$taxes = self::calc_inclusive_tax( $price, $rates );
		} else {
			$taxes = self::calc_exclusive_tax( $price, $rates );
		}

		return apply_filters( 'getpaid_calc_tax', $taxes, $price, $rates, $price_includes_tax );

	}

	/**
	 * Calc tax from inclusive price.
	 *
	 * @param  float $price Price to calculate tax for.
	 * @param  array $rates Array of tax rates.
	 * @return array
	 */
	public static function calc_inclusive_tax( $price, $rates ) {
		$taxes     = array();
		$tax_rates = wp_list_pluck( $rates, 'rate', 'name' );

		// Add tax rates.
		$tax_rate  = 1 + ( array_sum( $tax_rates ) / 100 );

		foreach ( $tax_rates as $name => $rate ) {
			$the_rate       = ( $rate / 100 ) / $tax_rate;
			$net_price      = $price - ( $the_rate * $price );
			$tax_amount     = apply_filters( 'getpaid_price_inc_tax_amount', $price - $net_price, $name, $rate, $price );
			$taxes[ $name ] = $tax_amount;
		}

		// Round all taxes to precision (4DP) before passing them back.
		$taxes = array_map( array( __CLASS__, 'round' ), $taxes );

		return $taxes;
	}

	/**
	 * Calc tax from exclusive price.
	 *
	 * @param  float $price Price to calculate tax for.
	 * @param  array $rates Array of tax rates.
	 * @return array
	 */
	public static function calc_exclusive_tax( $price, $rates ) {
		$taxes     = array();
		$tax_rates = wp_list_pluck( $rates, 'rate', 'name' );

		foreach ( $tax_rates as $name => $rate ) {

			$tax_amount     = $price * ( $rate / 100 );
			$taxes[ $name ] = apply_filters( 'getpaid_price_ex_tax_amount', $tax_amount, $name, $rate, $price );

		}

		// Round all taxes to precision (4DP) before passing them back.
		$taxes = array_map( array( __CLASS__, 'round' ), $taxes );

		return $taxes;
	}

	/**
	 * Get's an array of all tax rates.
	 *
	 * @return array
	 */
	public static function get_all_tax_rates() {

		$rates = get_option( 'wpinv_tax_rates', array() );

		return apply_filters(
			'getpaid_get_all_tax_rates',
			array_filter( wpinv_parse_list( $rates ) )
		);

	}

	/**
	 * Get's an array of default tax rates.
	 *
	 * @return array
	 */
	public static function get_default_tax_rates() {

		return apply_filters(
			'getpaid_get_default_tax_rates',
			array(
				array(
					'country'   => wpinv_get_default_country(),
					'state'     => wpinv_get_default_state(),
					'global'    => true,
					'rate'      => wpinv_get_default_tax_rate(),
					'name'      => __( 'Base Tax', 'invoicing' ),
				)
			)
		);

	}

	/**
	 * Get's an array of tax rates for a given address.
	 *
	 * @param string $country
	 * @param string $state
	 * @return array
	 */
	public static function get_address_tax_rates( $country, $state ) {

		$all_tax_rates  = self::get_all_tax_rates();
		$matching_rates = array_merge(
			wp_list_filter( $all_tax_rates, array( 'country' => $country ) ),
			wp_list_filter( $all_tax_rates, array( 'country' => '' ) )
		);

		foreach ( $matching_rates as $i => $rate ) {

			$states = array_filter( wpinv_clean( explode( ',', strtolower( $rate['state'] ) ) ) );
			if ( empty( $rate['global'] ) && ! in_array( strtolower( $state ), $states ) ) {
				unset( $matching_rates[ $i ] );
			}

		}

		return apply_filters( 'getpaid_get_address_tax_rates', $matching_rates, $country, $state );

	}

	/**
	 * Sums a set of taxes to form a single total. Result is rounded to precision.
	 *
	 * @param  array $taxes Array of taxes.
	 * @return float
	 */
	public static function get_tax_total( $taxes ) {
		return self::round( array_sum( $taxes ) );
	}

	/**
	 * Round to precision.
	 *
	 * Filter example: to return rounding to .5 cents you'd use:
	 *
	 * function euro_5cent_rounding( $in ) {
	 *      return round( $in / 5, 2 ) * 5;
	 * }
	 * add_filter( 'getpaid_tax_round', 'euro_5cent_rounding' );
	 *
	 * @param float|int $in Value to round.
	 * @return float
	 */
	public static function round( $in ) {
		return apply_filters( 'getpaid_tax_round', round( $in, 4 ), $in );
	}

}
