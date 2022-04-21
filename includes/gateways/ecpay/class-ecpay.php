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

                $this->supports = apply_filters( 'pms_payment_gateway_ecpay_supports', array( 'gateway_scheduled_payments', 'recurring_payments' ) );

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
                $payment-> update( array( 'type' => apply_filters( 'pms_ecpay_payment_type', 'web_accept_ecpay', $this, $settings ) ) );


                // Set the notify URL
                $notify_url = home_url() . '/?pay_gate_listener=ecpay';

				// Set the thank you page URL
				$result_url = $settings['gateways']['ecpay']['result_url'];

                if( pms_is_payment_test_mode() )
                    $ecpay_link = 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5';
                else
                    $ecpay_link = 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5';

                $ecpay_args = array(
                    // 'cmd'           => '_xclick',
                    'business'      => trim( pms_get_ecpay_merchant() ),
                    'hash_key'      => trim( $settings['gateways']['ecpay']['hash_key'] ),
                    'hash_iv'       => trim( $settings['gateways']['ecpay']['hash_iv'] ),
                    'email'         => $this->user_email,
                    'item_name'     => $this->subscription_plan->name,
                    'item_number'   => $this->subscription_plan->id,
                    'currency_code' => $this->currency,
                    'amount'        => $this->amount,
                    'tax'           => 0,
                    'custom'        => $this->payment_id,
                    'return_url'    => isset( $notify_url ) ? $notify_url : '',
                    'result_url'    => isset( $result_url ) ? $result_url : '',
                    //'return'        => add_query_arg( array( 'pms_gateway_payment_id' => base64_encode($this->payment_id), 'pmsscscd' => base64_encode('subscription_plans') ), $this->redirect_url ),
                    // 'bn'            => 'Cozmoslabs_SP',
                    'charset'       => 'UTF-8',
                    'no_shipping'   => 1,
                );
				if( pms_is_payment_test_mode() ) {
					$ecpay_args['business'] = 2000132;
					$ecpay_args['hash_key'] = '5294y06JbISpM5x9';
					$ecpay_args['hash_iv']  = 'v77hoKGq4kWxNNIS';
				}
				if( !empty( $settings['recurring'] ) ) {
					$duration = $this->subscription_plan->duration;
					$duration_map = array(
						'day'      => 'D',
						'week'     => 'D',
						'month'    => 'M',
						'year'     => 'Y',
					);
					$duration_unit = $duration_map[$this->subscription_plan->duration_unit];

					if ( 'week' === $this->subscription_plan->duration_unit ) {
						$duration = $duration * 7;
					}

					$ecpay_args['PeriodAmount']    = $this->amount;
					$ecpay_args['PeriodType']      = $duration_unit;
					$ecpay_args['Frequency']       = $duration;
					$ecpay_args['ExecTimes']       = isset( $settings['gateways']['ecpay']['exec_time'] ) ? $settings['gateways']['ecpay']['exec_time'] : 12;
					$ecpay_args['PeriodReturnURL'] = $ecpay_args['return_url'] ?: '';

					// $payment->log_data( 'ecpay_period', array(
					// 	'data' => $ecpay_args,
					// 	'sub'  => $this->subscription_plan,
					// ) );
				}

                try {
                    /** 組合綠界參數
                     * CustomField1 儲存 Payment ID 資料，用來比對交易
                     * CustomField2 儲存方案 ID，用來比對方案
                     */
                    $obj              = new ECPay_AllInOne();
					$prefix           = isset( $settings['gateways']['ecpay']['prefix'] ) ? $settings['gateways']['ecpay']['prefix'] : '';
                    //服務參數
                    $obj->ServiceURL  = $ecpay_link;                 //服務位置
                    $obj->HashKey     = $ecpay_args['hash_key'];     //測試用Hashkey，請自行帶入ECPay提供的HashKey
                    $obj->HashIV      = $ecpay_args['hash_iv'];      //測試用HashIV，請自行帶入ECPay提供的HashIV
                    $obj->MerchantID  = $ecpay_args['business'];     //測試用MerchantID，請自行帶入ECPay提供的MerchantID
                    $obj->EncryptType = '1';                                                          //CheckMacValue加密類型，請固定填入1，使用SHA256加密
                    $obj->Send['ReturnURL']         = $ecpay_args['return_url'];
                    $obj->Send['OrderResultURL']    = $ecpay_args['result_url'];
                    $obj->Send['MerchantTradeNo']   = $prefix . time() . $this->payment_id;                           //訂單編號
                    $obj->Send['MerchantTradeDate'] = date('Y/m/d H:i:s');                        //交易時間
                    $obj->Send['TotalAmount']       = $this->amount;                                       //交易金額
                    $obj->Send['TradeDesc']         = "Merchant";                                //交易描述
                    $obj->Send['ChoosePayment']     = ECPay_PaymentMethod::Credit;                  //付款方式: 僅信用卡
                    $obj->Send['CustomField1']      = $ecpay_args['custom'];
                    $obj->Send['CustomField2']      = $ecpay_args['item_number'];
                    array_push($obj->Send['Items'], array(
                        'Name'     => $this->subscription_plan->name, 
                        'Price'    => $this->amount,
                        'Currency' => $this->currency, 
                        'Quantity' => 1, 
                        'URL'      => get_permalink( $this->subscription_plan->id ),
                    ));
					if( !empty( $settings['recurring'] ) ) {
						$obj->SendExtend['PeriodAmount']    = $this->amount;
						$obj->SendExtend['PeriodType']      = $ecpay_args['PeriodType'];
						$obj->SendExtend['Frequency']       = $ecpay_args['Frequency'];
						$obj->SendExtend['ExecTimes']       = $ecpay_args['ExecTimes'];
						$obj->SendExtend['PeriodReturnURL'] = $ecpay_args['PeriodReturnURL'];
					}
					if ( 'zh_TW' !== get_locale() ) {
						$obj->SendExtend['Language']      = 'ENG';
					}

					if ( 'ja' === get_locale() ) {
						$obj->SendExtend['Language']      = 'JPN';
					}
                    
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
                $payment->log_data( 'ecpay_to_checkout' );

                if ( $payment->status != 'completed' && $payment->amount != 0 )
                    $payment->log_data( 'ecpay_waiting' );

                // Redirect only if tkn is set
                if( isset( $_POST['pmstkn'] ) ) {
                    $obj->CheckOut();
                }

                do_action( 'pms_before_ecpay_redirect', $ecpay_link, $this, $settings );

            }


            /*
            * Process Post Codes sent by ECPay
            *
            */
            public function process_webhooks() {

                // if( !isset( $_GET['pay_gate_listener'] ) || $_GET['pay_gate_listener'] != 'ecpay' )
                //     return;

                // Init ECPay Verifier
                $ecpay_verifier = new PMS_ECPay_Verifier();

                if( pms_is_payment_test_mode() )
                    $ecpay_verifier->is_sandbox = true;


                $verified = true;

                // Process the ECPay
                try {
                    if( $ecpay_verifier->checkRequestPost() )
                        $verified = $ecpay_verifier->validate();
                } catch ( Exception $e ) {

                }


                if( $verified ) {

                    $post_data = $_POST;
                    
                    // Get payment id from custom variable sent by ECPay
                    $payment_id = isset( $post_data['CustomField1'] ) ? $post_data['CustomField1'] : 0;

                    // Get the payment
                    $payment = pms_get_payment( $payment_id );

                    // Get user id from the payment
                    $user_id = $payment->user_id;

                    /** 綠界回傳資料 */
                    $payment_data = apply_filters( 'pms_ecpay_payment_data', array(
                        'payment_id'     => $payment_id,
                        'user_id'        => $user_id,
                        'type'           => $post_data['PaymentType'],
                        'status'         => $post_data['RtnCode'] == 1 ? 'completed' : 'failed',
                        'transaction_id' => $post_data['MerchantTradeNo'],
                        'amount'         => $post_data['TradeAmt'],
                        'date'           => $post_data['PaymentDate'],
                        'subscription_id'=> $post_data['CustomField2']
                    ), $post_data );

                    if( preg_match( '/^Credit.*/', $payment_data['type'] ) ) {
                        
                        // If the payment has already been completed do nothing
                        // if( $payment->status == 1 )
                        //     return;

                        // If the status is completed update the payment and also activate the member subscriptions
                        if( 'completed' == $payment_data['status'] ) {

                            $payment->log_data( 'ecpay_waiting', array( 
                                'data' => $post_data, 
                                'desc' => 'ECPay Response' 
                            ) );

                            // Complete payment
                            $payment->update( array( 
                                'status' => $payment_data['status'], 
                                'transaction_id' => $payment_data['transaction_id'] 
                            ) );

                            // Get member subscription
                            $member_subscriptions = pms_get_member_subscriptions( array( 
                                'user_id' => $payment_data['user_id'], 
                                'subscription_plan_id' => $payment_data['subscription_id'] ) );

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
                                    pms_add_member_subscription_log( $member_subscription->id, 'subscription_activated', array( 
                                        'until' => $member_subscription_expiration_date ) );
                                else
                                    pms_add_member_subscription_log( $member_subscription->id, 'subscription_renewed_manually', array( 
                                        'until' => $member_subscription_expiration_date ) );

                                //Can be a renewal payment or a new payment
                                do_action( 'pms_ecpay_accept_after_subscription_activation', $member_subscription, $payment_data, $post_data );
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

                                do_action( 'pms_ecpay_accept_after_upgrade_subscription', $member_subscription_plan->id, $payment_data, $post_data );

                            }

                        // If payment status is not complete, something happened, so log it in the payment
                        } else {

                            $payment->log_data( 'payment_failed', array( 
                                'data' => $post_data, 
                                'desc' => 'ipn response'
                            ) );

                            // Add the transaction ID
                            $payment->update( array( 
                                'transaction_id' => $payment_data['transaction_id'], 
                                'status' => 'failed' 
                            ) );

                        }

                    }

                    do_action( 'pms_ecpay_listener_verified', $payment_data, $post_data );

                }

            }


            /*
            * Verify that the payment gateway is setup correctly
            *
            */
            public function validate_credentials() {

                if ( pms_get_ecpay_merchant() === false )
                    pms_errors()->add( 'form_general', __( 'The selected gateway is not configured correctly: <strong>PayPal Address is missing</strong>. Contact the system administrator.', 'paid-member-subscriptions' ) );

            }

        }
    }
}

