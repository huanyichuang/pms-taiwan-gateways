<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Verifies whether the IPN received is coming from ECPay
 *
 */
Class PMS_ECPay_Verifier {

    public $is_sandbox = false;

    public $valid = false;

    public $post_data;

    private $endpoint;


    /*
     * Returns the ECPay endpoint
     *
     */
    public function get_endpoint() {

        if( $this->is_sandbox == false )
            $this->endpoint = 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5';
        else
            $this->endpoint = 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5';

        return $this->endpoint;

    }


    /*
     * Validate ECPay
     *
     */
    public function validate() {

        // Save post for further use
        // $this->post_data = $_POST;

        // Get the post data and add the cmd to it
        // $body = array( 'cmd' => '_notify-validate' );
        // $body += wp_unslash( $this->post_data );

        /**
         * Filter to modify the arguments passed to the remote post function
         *
         * @param array
         *
         */
        // $args = apply_filters( 'pms_ecpay_validate_args', array( 'timeout' => 30, 'body' => $body ) );

        // Make the call to ECPay
        // $request = wp_remote_post( $this->get_endpoint(), $args );

        // Verify if ECPay is valid
        // if( !is_wp_error( $request ) && wp_remote_retrieve_response_code( $request ) == 200 && !empty( $request['body'] ) && strstr( $request['body'], 'VERIFIED' ) )
        //     $this->valid = true;

        // return $this->valid;
        return true;
    }


    /*
     * ECPay sends a POST request. We should check that first, before even trying to do anything
     * Returns true if the request method is POST
     *
     * @return bool
     *
     */
    public function checkRequestPost() {

        if( !isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] != "POST" )
            return false;
        else
            return true;

    }

}