<?php
// don't load directly
if ( !defined('ABSPATH') )
    die('-1');

$email_footer = apply_filters( 'wpinv_email_footer_text', wpinv_get_option( 'email_footer_text', get_bloginfo( 'name', 'display' ) . ' - ' . __( 'Powered by GetPaid', 'invoicing' ) ) );
$email_footer = $email_footer ? wpautop( wp_kses_post( wptexturize( $email_footer ) ) ) : '';
?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <!-- End Content -->
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Body -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- Footer -->
                                    <table border="0" cellpadding="10" cellspacing="0" width="100%" id="template_footer">
                                        <tr>
                                            <td valign="top">
                                                <table border="0" cellpadding="10" cellspacing="0" width="100%">
                                                    <tr>
                                                        <td colspan="2" valign="middle" id="credit">
                                                            <?php echo $email_footer; ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Footer -->
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </body>
 </html>