<?php

    require_once('../../../wp-load.php');

    include_once plugin_dir_path( __DIR__ ) . '/woocommerce/woocommerce.php';

    $raast = WC()->payment_gateways->payment_gateways()['raast'];
    
    // Receive the response parameter
    $status = $_POST['status'];
    $signature = $_POST['signature'];
    $identifier = $_POST['identifier'];
    $data = $_POST['data'];
    

    // Get Order Object
    $order = wc_get_order( $identifier );
    

    // Generate your signature
    $customKey = $data['amount'].$identifier;
    $secret = $raast->private_key;


    $mySignature = strtoupper(hash_hmac('sha256', $customKey , $secret));

    if($status == "success" && $signature == $mySignature){
        // Payment successful
        // You can use it for email or phone number notification of PAYMENT RECEIVED
        // For bare care, we are just changing the status of order to PROCESSING

        $order->update_status( 'processing' );

        
        
    }
?>