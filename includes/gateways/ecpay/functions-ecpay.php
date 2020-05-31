<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Function that adds the HTML for ECPay Standard in the payments tab from the Settings page
 *
 * @param array $options    - The saved option settings
 *
 */
if ( !function_exists( 'pms_add_settings_content_ecpay' ) ) :
    function pms_add_settings_content_ecpay( $options ) {
        ?>

        <div class="pms-payment-gateway-wrapper">
            <h4 class="pms-payment-gateway-title"><?php echo apply_filters( 'pms_settings_page_payment_gateway_ecpay_title', __( 'ECPay Standard', 'pms-taiwan-gateways' ) ); ?></h4>

            <div class="pms-form-field-wrapper">
                <label class="pms-form-field-label" for="ecpay-merchant"><?php _e( 'ECPay Merchant ID', 'pms-taiwan-gateways' ); ?></label>
                <input id="ecpay-merchant" type="text" name="pms_payments_settings[gateways][ecpay][merchant]" value="<?php echo isset( $options['gateways']['ecpay']['merchant' ]) ? $options['gateways']['ecpay']['merchant'] : ''; ?>" class="widefat" />

                <input type="hidden" name="pms_payments_settings[gateways][ecpay][name]" value="ECPay" />

                <p class="description"><?php _e( 'Enter your ECPay e-mail address', 'pms-taiwan-gateways' ); ?></p>
            </div>

            <div class="pms-form-field-wrapper">
                <label class="pms-form-field-label" for="ecpay-hash-key"><?php _e( 'ECPay Hash Key', 'pms-taiwan-gateways' ); ?></label>
                <input id="ecpay-hash-key" type="text" name="pms_payments_settings[gateways][ecpay][hash_key]" value="<?php echo isset( $options['gateways']['ecpay']['hash_key' ]) ? $options['gateways']['ecpay']['hash_key'] : ''; ?>" class="widefat" />

                <p class="description"><?php _e( 'ECPay E-mail address to use for test transactions', 'pms-taiwan-gateways' ); ?></p>
            </div>

            <div class="pms-form-field-wrapper">
                <label class="pms-form-field-label" for="ecpay-hash-iv"><?php _e( 'ECPay Hash IV', 'pms-taiwan-gateways' ); ?></label>
                <input id="ecpay-hash-iv" type="text" name="pms_payments_settings[gateways][ecpay][hash_iv]" value="<?php echo isset( $options['gateways']['ecpay']['hash_iv' ]) ? $options['gateways']['ecpay']['hash_iv'] : ''; ?>" class="widefat" />

                <p class="description"><?php _e( 'Enter your ECPay e-mail address', 'pms-taiwan-gateways' ); ?></p>
            </div>

            <div class="pms-form-field-wrapper">
                <label class="pms-form-field-label" for="ecpay-return-url"><?php _e( 'ECPay Return URL', 'pms-taiwan-gateways' ); ?></label>
                <input id="ecpay-return-url" type="text" name="pms_payments_settings[gateways][ecpay][return_url]" value="<?php echo isset( $options['gateways']['ecpay']['return_url' ]) ? $options['gateways']['ecpay']['return_url'] : ''; ?>" class="widefat" />

                <p class="description"><?php _e( 'Enter your ECPay e-mail address', 'pms-taiwan-gateways' ); ?></p>
            </div>

            <?php do_action( 'pms_settings_page_payment_gateway_ecpay_extra_fields', $options ); ?>

        </div>

        <?php
    }
endif;
add_action( 'pms-settings-page_payment_gateways_content', 'pms_add_settings_content_ecpay' );

/*
 * Display a warning to administrators if the ECPay Email is not entered in settings
 *
 */
if ( !function_exists( 'pms_ecpay_email_address_admin_warning' ) ) :
function pms_ecpay_email_address_admin_warning() {

    if( !current_user_can( 'manage_options' ) )
        return;

    $are_active = array_intersect( array( 'ecpay', 'ecpay_express', 'ecpay_pro' ), pms_get_active_payment_gateways() );

    if( !empty( $are_active ) && pms_get_ecpay_merchant() === false ) {

        echo '<div class="pms-warning-message-wrapper">';
            echo '<p>' . sprintf( __( 'Your <strong>ECPay Email Address</strong> is missing. In order to make payments you will need to add the Email Address of your ECPay account %1$s here %2$s.', 'pms-taiwan-gateways' ), '<a href="' . admin_url( 'admin.php?page=pms-settings-page&tab=payments' ) .'" target="_blank">', '</a>' ) . '</p>';
            echo '<p><em>' . __( 'This message is visible only by Administrators.', 'pms-taiwan-gateways' ) . '</em></p>';
        echo '</div>';

    }

}
add_action( 'pms_register_form_top', 'pms_ecpay_email_address_admin_warning' );
add_action( 'pms_new_subscription_form_top', 'pms_ecpay_email_address_admin_warning' );
add_action( 'pms_upgrade_subscription_form_top', 'pms_ecpay_email_address_admin_warning' );
add_action( 'pms_renew_subscription_form_top', 'pms_ecpay_email_address_admin_warning' );
add_action( 'pms_retry_payment_form_top', 'pms_ecpay_email_address_admin_warning' );
endif;

if ( !function_exists( 'pms_wppb_ecpay_email_address_admin_warning' ) ) :

function pms_wppb_ecpay_email_address_admin_warning() {

    if( !current_user_can( 'manage_options' ) )
        return;

    $fields = get_option( 'wppb_manage_fields' );

    if ( empty( $fields ) )
        return;

    $are_active = array_intersect( array( 'ecpay', 'ecpay_express', 'ecpay_pro' ), pms_get_active_payment_gateways() );

    foreach( $fields as $field ) {
        if ( $field['field'] == 'Subscription Plans' && !empty( $are_active ) && pms_get_ecpay_merchant() === false ) {
            echo '<div class="pms-warning-message-wrapper">';
                echo '<p>' . sprintf( __( 'Your <strong>ECPay Email Address</strong> is missing. In order to make payments you will need to add the Email Address of your ECPay account %1$s here %2$s.', 'pms-taiwan-gateways' ), '<a href="' . admin_url( 'admin.php?page=pms-settings-page&tab=payments' ) .'" target="_blank">', '</a>' ) . '</p>';
                echo '<p><em>' . __( 'This message is visible only by Administrators.', 'pms-taiwan-gateways' ) . '</em></p>';
            echo '</div>';

            break;
        }
    }

}
add_action( 'wppb_before_register_fields', 'pms_wppb_ecpay_email_address_admin_warning' );
endif;

/**
 * Returns the ECPay Email Address
 *
 * @since 1.0.0
 */
if ( !function_exists( 'pms_get_ecpay_merchant' ) ) :
function pms_get_ecpay_merchant() {
    $settings = get_option( 'pms_payments_settings' );

    $slug = 'merchant';

    if( isset( $settings['test_mode'] ) && $settings['test_mode'] == '1' )
        $slug = 'merchant';

    if ( !empty( $settings['gateways']['ecpay'][$slug] ) )
        return $settings['gateways']['ecpay'][$slug];

    return false;
}
endif;

/**
 * Add custom log messages for the ECPay Standard gateway
 * @since 1.0.0
 * @param string $message can be manually modified
 */
if ( !function_exists( 'pms_ecpay_payment_logs_system_error_messages' ) ) :
function pms_ecpay_payment_logs_system_error_messages( $message, $log ) {

    if ( empty( $log['type'] ) )
        return $message;

    $kses_args = array(
        'strong' => array()
    );

    switch( $log['type'] ) {
        case 'ecpay_to_checkout':
            $message = __( 'User sent to <strong>ECPay Checkout</strong> to continue the payment process.', 'pms-taiwan-gateways' );
            break;
        case 'ecpay_waiting':
            $message = __( 'Waiting to receive notifications from <strong>ECPay</strong>.', 'pms-taiwan-gateways' );
            break;
        case 'ecpay_received':
            $message = __( 'Payment received from ECPay.', 'pms-taiwan-gateways' );
            break;
        case 'ecpay_not_received':
            $message = __( 'Payment not received from ECPay.', 'pms-taiwan-gateways' );
            break;
    }

    return apply_filters( 'pms_ecpay_payment_logs_system_error_messages', wp_kses( $message, $kses_args ), $log );

}
add_filter( 'pms_payment_logs_system_error_messages', 'pms_ecpay_payment_logs_system_error_messages', 10, 2 );
endif;

/**
 * Add Results to Thankyou page.
 */
function ecpay_thankyou() {
    if ( isset( $_POST['RtnCode'] ) && 1 == $_POST['RtnCode'] ) {
        echo sprintf( esc_html__( 'ECPay completed, transactionID is %s', 'pms-taiwan-gateways', $_POST['MerchantTradeNo'] ) );
    } else {
        echo sprintf( 'ECPay is not completed' );
    }
    ?>

<?php }
add_action( 'pms_member_account_before_subscriptions_tab','ecpay_thankyou');