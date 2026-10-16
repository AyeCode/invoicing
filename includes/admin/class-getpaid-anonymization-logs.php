<?php
/**
 * GetPaid Anonymization Logs Admin
 *
 * @package GetPaid
 * @subpackage Admin
 * @since 2.8.22
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid_Anonymization_Logs Class
 */
class GetPaid_Anonymization_Logs {

    /**
     * Anonymization logs page
     */
    public function display_logs() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Get current page number
        $page     = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $per_page = 20;

        // Selected month (YYYYMM).
        $month = isset( $_GET['m'] ) ? preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_GET['m'] ) ) ) : '';
        $month = 6 === strlen( $month ) ? $month : '';

        // Fetch logs
        $logs = $this->get_logs( $page, $per_page, $month );
        $total_logs = $this->get_total_logs( $month );

        // Prepare pagination
        $pagination = paginate_links(
            array(
				'base'      => add_query_arg( 'paged', '%#%' ),
				'format'    => '',
				'prev_text' => __( '&laquo;', 'invoicing' ),
				'next_text' => __( '&raquo;', 'invoicing' ),
				'total'     => ceil( $total_logs / $per_page ),
				'current'   => $page,
            )
        );

        ?>
        <div class="wrap getpaid-anonymization-logs">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get">
                        <input type="hidden" name="page" value="wpinv-anonymization-logs">
                        <label for="filter-by-date" class="screen-reader-text"><?php esc_html_e( 'Filter by date', 'invoicing' ); ?></label>
                        <select name="m" id="filter-by-date">
                            <option value="0"><?php esc_html_e( 'All dates', 'invoicing' ); ?></option>
                            <?php
                            $months = $this->get_log_months();
                            foreach ( $months as $log_month ) {
                                echo '<option value="' . esc_attr( $log_month->log_year_month ) . '"' . selected( $month, $log_month->log_year_month, false ) . '>' . esc_html( $log_month->month_name . ' ' . $log_month->year ) . '</option>';
                            }
                            ?>
                        </select>
                        <?php submit_button( __( 'Filter', 'invoicing' ), '', 'filter_action', false ); ?>
                    </form>
                </div>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Log ID', 'invoicing' ); ?></th>
                        <th><?php esc_html_e( 'User', 'invoicing' ); ?></th>
                        <th><?php esc_html_e( 'Action', 'invoicing' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'invoicing' ); ?></th>
                        <th><?php esc_html_e( 'Details', 'invoicing' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $logs ) ) : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e( 'No anonymization logs found.', 'invoicing' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php
                        foreach ( $logs as $log ) :
                            $additional_info = json_decode( $log->additional_info, true );
                        ?>
                            <tr>
                                <td><?php echo esc_html( $log->log_id ); ?></td>
                                <td>
                                    <?php
                                    $user_edit_link = get_edit_user_link( $log->user_id );
                                    if ( $user_edit_link ) {
                                        echo '<a href="' . esc_url( $user_edit_link ) . '">' . esc_html( $log->user_id ) . '</a>';
                                    } else {
                                        echo esc_html( $log->user_id );
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html( ucfirst( $log->action ) ); ?></td>
                                <td><?php echo esc_html( get_date_from_gmt( $log->timestamp, 'F j, Y g:i a' ) ); ?></td>
                                <td>
                                    <button class="button-link toggle-details" type="button" aria-expanded="false">
                                        <span class="screen-reader-text"><?php esc_html_e( 'Show more details', 'invoicing' ); ?></span>
                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    </button>
                                </td>
                            </tr>
                            <tr class="log-details" style="display:none;">
                                <td colspan="5">
                                    <div class="log-details-content">
                                        <table class="widefat fixed">
                                            <tbody>
                                                <tr>
                                                    <th><?php esc_html_e( 'Data Type', 'invoicing' ); ?></th>
                                                    <td><?php echo esc_html( $log->data_type ); ?></td>
                                                </tr>
                                                <?php if ( is_array( $additional_info ) ) : ?>
                                                    <tr>
                                                        <th><?php esc_html_e( 'Additional Information', 'invoicing' ); ?></th>
                                                        <td>
                                                            <table class="widefat fixed">
                                                                <tbody>
                                                                    <?php foreach ( $additional_info as $key => $value ) : ?>
                                                                        <tr>
                                                                            <th><?php echo esc_html( $key ); ?></th>
                                                                            <td><?php echo esc_html( $value ); ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if ( $pagination ) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php echo wp_kses_post( $pagination ); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get logs from the database
     *
     * @param int $page Current page number
     * @param int $per_page Number of logs per page
	 * @param string $month Optional month to filter by, as YYYYMM.
     * @return array
     */
    private function get_logs( $page, $per_page, $month = '' ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'getpaid_anonymization_logs';
        $offset = ( max( 1, (int) $page ) - 1 ) * $per_page;

        if ( ! empty( $month ) ) {
            $query = $wpdb->prepare(
                "SELECT * FROM $table_name WHERE DATE_FORMAT(timestamp, '%%Y%%m') = %s ORDER BY timestamp DESC LIMIT %d OFFSET %d",
                $month,
                $per_page,
                $offset
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            );
        }

        return $wpdb->get_results( $query );
    }

    /**
     * Get total number of logs
     *
	 * @param string $month Optional month to filter by, as YYYYMM.
     * @return int
     */
    private function get_total_logs( $month = '' ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'getpaid_anonymization_logs';

        if ( ! empty( $month ) ) {
            return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE DATE_FORMAT(timestamp, '%%Y%%m') = %s", $month ) );
        }

        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
    }

    /**
     * Get log months for filtering
     *
     * @return array
     */
    private function get_log_months() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'getpaid_anonymization_logs';

        return $wpdb->get_results(
            "SELECT DISTINCT
                YEAR(timestamp) AS year,
                MONTH(timestamp) AS month_num,
                DATE_FORMAT(timestamp, '%M') AS month_name,
                DATE_FORMAT(timestamp, '%Y%m') AS log_year_month
            FROM $table_name
            ORDER BY `year` DESC, `month_num` DESC"
        );
    }
}