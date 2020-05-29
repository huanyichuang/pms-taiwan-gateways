<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Extends the payment gateway base class for ECPay
 *
 */
add_action( 'plugins_loaded', 'pms_ecpay_extend' ); 
//把 Class 的順序降低，等到其他外掛載入完之後再繼承 Class，不然會炸裂
function pms_ecpay_extend() {
    if ( class_exists( 'PMS_Payment_Gateway' ) ) {
        Class PMS_Payment_Gateway_TW_ECPay extends PMS_Payment_Gateway {
            /**
             * The features supported by the payment gateway
             *
             * @access public
             * @var array
             *
             */
            public $supports;


            /**
             * Fires just after constructor
             *
             */
            public function init() {

                $this->supports = apply_filters( 'pms_payment_gateway_paypal_standard_supports', array( 'gateway_scheduled_payments' ) );

            }


            /*
            * Process for all register payments that are not free
            *
            */
            public function process_sign_up() {

                // Do nothing if the payment id wasn't sent
                if( ! $this->payment_id )
                    return;

                $settings = get_option( 'pms_payments_settings' );

                //Update payment type
                $payment = pms_get_payment( $this->payment_id );
                $payment->update( array( 'type' => apply_filters( 'pms_paypal_standard_payment_type', 'web_accept_paypal_standard', $this, $settings ) ) );


                // Set the notify URL
                $notify_url = home_url() . '/?pay_gate_listener=paypal_ipn';

                if( pms_is_payment_test_mode() )
                    $paypal_link = 'https://www.sandbox.paypal.com/cgi-bin/webscr/?';
                else
                    $paypal_link = 'https://www.paypal.com/cgi-bin/webscr/?';

                $paypal_args = array(
                    'cmd'           => '_xclick',
                    'business'      => trim( pms_get_paypal_email() ),
                    'email'         => $this->user_email,
                    'item_name'     => $this->subscription_plan->name,
                    'item_number'   => $this->subscription_plan->id,
                    'currency_code' => $this->currency,
                    'amount'        => $this->amount,
                    'tax'           => 0,
                    'custom'        => $this->payment_id,
                    'notify_url'    => $notify_url,
                    'return'        => add_query_arg( array( 'pms_gateway_payment_id' => base64_encode($this->payment_id), 'pmsscscd' => base64_encode('subscription_plans') ), $this->redirect_url ),
                    'bn'            => 'Cozmoslabs_SP',
                    'charset'       => 'UTF-8',
                    'no_shipping'   => 1
                );

                $paypal_link .= http_build_query( apply_filters( 'pms_paypal_standard_args', $paypal_args, $this, $settings ) );


                do_action( 'pms_before_paypal_redirect', $paypal_link, $this, $settings );

                $payment->log_data( 'paypal_to_checkout' );

                if ( $payment->status != 'completed' && $payment->amount != 0 )
                    $payment->log_data( 'paypal_ipn_waiting' );

                // Redirect only if tkn is set
                if( isset( $_POST['pmstkn'] ) ) {
                    wp_redirect( $paypal_link );
                    exit;
                }

            }


            /*
            * Process IPN sent by PayPal
            *
            */
            public function process_webhooks() {

                if( !isset( $_GET['pay_gate_listener'] ) || $_GET['pay_gate_listener'] != 'paypal_ipn' )
                    return;

                // Init IPN Verifier
                $ipn_verifier = new PMS_IPN_Verifier();

                if( pms_is_payment_test_mode() )
                    $ipn_verifier->is_sandbox = true;


                $verified = false;

                // Process the IPN
                try {
                    if( $ipn_verifier->checkRequestPost() )
                        $verified = $ipn_verifier->validate();
                } catch ( Exception $e ) {

                }


                if( $verified ) {

                    $post_data = $_POST;

                    // Get payment id from custom variable sent by IPN
                    $payment_id = isset( $post_data['custom'] ) ? $post_data['custom'] : 0;

                    // Get the payment
                    $payment = pms_get_payment( $payment_id );

                    // Get user id from the payment
                    $user_id = $payment->user_id;

                    $payment_data = apply_filters( 'pms_paypal_ipn_payment_data', array(
                        'payment_id'     => $payment_id,
                        'user_id'        => $user_id,
                        'type'           => $post_data['txn_type'],
                        'status'         => strtolower($post_data['payment_status']),
                        'transaction_id' => $post_data['txn_id'],
                        'amount'         => $post_data['mc_gross'],
                        'date'           => $post_data['payment_date'],
                        'subscription_id'=> $post_data['item_number']
                    ), $post_data );


                    // web_accept is returned for A Direct Credit Card (Pro) transaction,
                    // A Buy Now, Donation or Smart Logo for eBay auctions button
                    if( $payment_data['type'] == 'web_accept' ) {

                        // If the payment has already been completed do nothing
                        if( $payment->status == 'completed' )
                            return;

                        // If the status is completed update the payment and also activate the member subscriptions
                        if( $payment_data['status'] == 'completed' ) {

                            $payment->log_data( 'paypal_ipn_received', array( 'data' => $post_data, 'desc' => 'paypal IPN' ) );

                            // Complete payment
                            $payment->update( array( 'status' => $payment_data['status'], 'transaction_id' => $payment_data['transaction_id'] ) );

                            // Get member subscription
                            $member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $payment_data['user_id'], 'subscription_plan_id' => $payment_data['subscription_id'] ) );

                            foreach( $member_subscriptions as $member_subscription ) {

                                $subscription_plan = pms_get_subscription_plan( $member_subscription->subscription_plan_id );

                                // If subscription is pending it is a new one
                                if( $member_subscription->status == 'pending' ) {
                                    $member_subscription_expiration_date = $subscription_plan->get_expiration_date();

                                // This is an old subscription
                                } else {

                                    if( strtotime( $member_subscription->expiration_date ) < time() || $subscription_plan->duration === 0 )
                                        $member_subscription_expiration_date = $subscription_plan->get_expiration_date();
                                    else
                                        $member_subscription_expiration_date = date( 'Y-m-d 23:59:59', strtotime( $member_subscription->expiration_date . '+' . $subscription_plan->duration . ' ' . $subscription_plan->duration_unit ) );

                                }

                                // Update subscription
                                $member_subscription->update( array( 'expiration_date' => $member_subscription_expiration_date, 'status' => 'active' ) );

                                if( $member_subscription->status == 'pending' )
                                    pms_add_member_subscription_log( $member_subscription->id, 'subscription_activated', array( 'until' => $member_subscription_expiration_date ) );
                                else
                                    pms_add_member_subscription_log( $member_subscription->id, 'subscription_renewed_manually', array( 'until' => $member_subscription_expiration_date ) );

                                //Can be a renewal payment or a new payment
                                do_action( 'pms_paypal_web_accept_after_subscription_activation', $member_subscription, $payment_data, $post_data );
                            }

                            /*
                            * If the subscription plan id sent by the IPN is not found in the members subscriptions
                            * then it could be an update to an existing one
                            *
                            * If one of the member subscriptions is in the same group as the payment subscription id,
                            * the payment subscription id is an upgrade to the member subscription one
                            *
                            */

                            $current_subscription = pms_get_current_subscription_from_tier( $payment_data['user_id'], $payment_data['subscription_id'] );

                            if( !empty( $current_subscription ) && $current_subscription->subscription_plan_id != $payment_data['subscription_id'] ) {

                                $old_plan_id = $current_subscription->subscription_plan_id;

                                $new_subscription_plan = pms_get_subscription_plan( $payment_data['subscription_id'] );

                                $subscription_data = array(
                                    'user_id'              => $payment_data['user_id'],
                                    'subscription_plan_id' => $new_subscription_plan->id,
                                    'start_date'           => date( 'Y-m-d H:i:s' ),
                                    'expiration_date'      => $new_subscription_plan->get_expiration_date(),
                                    'status'               => 'active'
                                );

                                $current_subscription->update( $subscription_data );

                                pms_add_member_subscription_log( $current_subscription->id, 'subscription_upgrade_success', array( 'old_plan' => $old_plan_id, 'new_plan' => $new_subscription_plan->id ) );

                                do_action( 'pms_paypal_web_accept_after_upgrade_subscription', $member_subscription_plan->id, $payment_data, $post_data );

                            }

                        // If payment status is not complete, something happened, so log it in the payment
                        } else {

                            $payment->log_data( 'payment_failed', array( 'data' => $post_data, 'desc' => 'ipn response') );

                            // Add the transaction ID
                            $payment->update( array( 'transaction_id' => $payment_data['transaction_id'], 'status' => 'failed' ) );

                        }

                    }

                    do_action( 'pms_paypal_ipn_listener_verified', $payment_data, $post_data );

                }

            }


            /*
            * Verify that the payment gateway is setup correctly
            *
            */
            public function validate_credentials() {

                if ( pms_get_paypal_email() === false )
                    pms_errors()->add( 'form_general', __( 'The selected gateway is not configured correctly: <strong>PayPal Address is missing</strong>. Contact the system administrator.', 'paid-member-subscriptions' ) );

            }

        }
    }
}

