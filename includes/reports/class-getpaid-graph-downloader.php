<?php
/**
 * Contains the class that downloads a single report.
 *
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid_Graph_Downloader Class.
 */
class GetPaid_Graph_Downloader {

	/**
	 * @var GetPaid_Reports_Report
	 */
	public $handler;

	/**
	 * Class constructor.
	 *
	 */
	public function __construct() {
		$this->handler = new GetPaid_Reports_Report();
	}

	/**
	 * Prepares the datastore handler.
	 *
	 * @return GetPaid_Reports_Report_Items|GetPaid_Reports_Report_Gateways|GetPaid_Reports_Report_Discounts
	 */
	public function prepare_handler( $graph ) {

		if ( empty( $this->handler->views[ $graph ] ) ) {
			wp_die( __( 'Invalid Graph', 'invoicing' ), 400 );
		}

		return new $this->handler->views[ $graph ]['class']();

	}

	/**
	 * Prepares the output stream.
	 *
	 * @return resource
	 */
	public function prepare_output() {

		$output  = fopen( 'php://output', 'w' );

		if ( false === $output ) {
			wp_die( __( 'Unsupported server', 'invoicing' ), 500 );
		}

		return $output;
	}

	/**
	 * Prepares the file type.
	 *
	 * @return string
	 */
	public function prepare_file_type( $graph ) {

		$file_type = empty( $_REQUEST['file_type'] ) ? 'csv' : sanitize_text_field( $_REQUEST['file_type'] );
		$file_name = wpinv_sanitize_key( "getpaid-$graph-" . current_time( 'Y-m-d' ) );

		header( "Content-Type:application/$file_type" );
		header( "Content-Disposition:attachment;filename=$file_name.$file_type" );

		return $file_type;
	}

	/**
	 * Handles the actual download.
	 *
	 */
	public function download( $graph ) {
		global $wpdb;

		$handler   = $this->prepare_handler( $graph );
		$stream    = $this->prepare_output();
		$stats     = $wpdb->get_results( $handler->get_sql( $handler->get_range() ) );
		$headers   = array( $handler->field, 'total', 'total_raw' );
		$file_type = $this->prepare_file_type( $graph );

		if ( 'csv' == $file_type ) {
			$this->download_csv( $stats, $stream, $headers );
		} else if( 'xml' == $file_type ) {
			$this->download_xml( $stats, $stream, $headers );
		} else {
			$this->download_json( $stats, $stream, $headers );
		}

		fclose( $stream );
		exit;
	}

	/**
	 * Downloads graph as csv
	 *
	 * @param array $stats The stats being downloaded.
	 * @param resource $stream The stream to output to.
	 * @param array $headers The fields to stream.
	 * @since       1.0.19
	 */
	public function download_csv( $stats, $stream, $headers ) {

		// Output the csv column headers.
		fputcsv( $stream, $headers );

		// Loop through 
		foreach ( $stats as $stat ) {
			$row  = array_values( $this->prepare_row( $stat, $headers ) );
			$row  = array_map( 'maybe_serialize', $row );
			fputcsv( $stream, $row );
		}

	}

	/**
	 * Downloads graph as json
	 *
	 * @param array $stats The stats being downloaded.
	 * @param resource $stream The stream to output to.
	 * @param array $headers The fields to stream.
	 * @since       1.0.19
	 */
	public function download_json( $stats, $stream, $headers ) {

		$prepared = array();

		// Loop through 
		foreach ( $stats as $stat ) {
			$prepared[] = $this->prepare_row( $stat, $headers );
		}

		fwrite( $stream, wp_json_encode( $prepared ) );

	}

	/**
	 * Downloads graph as xml
	 *
	 * @param array $stats The stats being downloaded.
	 * @param resource $stream The stream to output to.
	 * @param array $headers The fields to stream.
	 * @since       1.0.19
	 */
	public function download_xml( $stats, $stream, $headers ) {

		$prepared = array();

		// Loop through 
		foreach ( $stats as $stat ) {
			$prepared[] = $this->prepare_row( $stat, $headers );
		}

		$xml = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
		$this->convert_array_xml( $prepared, $xml );

		fwrite( $stream, $xml->asXML() );

	}

	/**
	 * Converts stats array to xml
	 *
	 * @access      public
	 * @since      1.0.19
	 */
	public function convert_array_xml( $data, $xml ) {

		// Loop through 
		foreach ( $data as $key => $value ) {

			$key = preg_replace( "/[^A-Za-z0-9_\-]/", '', $key );

			if ( is_array( $value ) ) {

				if ( is_numeric( $key ) ){
					$key = 'item'.$key; //dealing with <0/>..<n/> issues
				}

				$subnode = $xml->addChild( $key );
				$this->convert_array_xml( $value, $subnode );

			} else {
				$xml->addChild( $key, htmlspecialchars( $value ) );
			}

		}

	}

	/**
	 * Prepares a single row for download.
	 *
	 * @param stdClass|array $row The row to prepare..
	 * @param array $fields The fields to stream.
	 * @since       1.0.19
	 * @return array
	 */
	public function prepare_row( $row, $fields ) {

		$prepared = array();
		$row      = (array) $row;

		foreach ( $fields as $field ) {

			if ( $field === 'total' ) {
				$prepared[ $field ] = html_entity_decode( strip_tags( wpinv_price( $row['total'] ) ), ENT_QUOTES );
				continue;
			}

			if ( $field === 'total_raw' ) {
				$prepared[ $field ] = wpinv_round_amount( wpinv_sanitize_amount( $row['total'] ) );
				continue;
			}

			$prepared[ $field ] = strip_tags( $row[ $field ] );

		}

		return $prepared;
	}


}
