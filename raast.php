<?php
/*
 * Plugin Name: Payment Gateway for Raast on Woocommerce
 * Plugin URI: https://husnaincodes.com/wp-plugins/wc-raast-payment-gateway/
 * Description: This plugin helps you integrate Raast Payment Gateway for Woocommerce.
 * Author: Husnain Mustafa
 * Author URI: https://husnaincodes.com
 * Version: 1.0.0
 */

add_filter( 'woocommerce_payment_gateways', 'add_raast_class' );
function add_raast_class( $gateways ) {
	$gateways[] = 'Raast_Payment_Gateway'; 
	return $gateways;
}


add_action( 'plugins_loaded', 'initiate_raast_class' );
function initiate_raast_class() {

	class Raast_Payment_Gateway extends WC_Payment_Gateway {

 		
 		public function __construct() {

            $this->id = 'raast'; 
            $this->icon = 'https://www.raastid.com/assets/images/logoIcon/light_logo.png'; 
            $this->has_fields = false; 
            $this->method_title = 'Raast Payment Gateway';
            $this->method_description = 'Let Your Customers Pay with Raast ID'; 
        
            
            
            $this->supports = array(
                'products'
            );
        
            
            $this->init_form_fields();
        
            
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->success_url = $this->get_option( 'success_url' );
            $this->cancel_url = $this->get_option( 'cancel_url' );
            $this->logo_url = $this->get_option( 'logo_url' );
            $this->ipn_url = plugin_dir_url( __FILE__ ) . 'ipn.php';
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            $this->private_key = $this->get_option( 'private_key' );
            $this->publishable_key = $this->get_option( 'publishable_key' );

            if( empty($this->success_url) || $this->success_url == null ){
                $this->success_url = get_site_url();
            }

            if( empty($this->cancel_url) || $this->cancel_url == null ){
                $this->cancel_url = get_site_url();
            }

            if( empty($this->logo_url) || $this->logo_url == null ){
                $this->logo_url = 'https://www.raastid.com/assets/images/logoIcon/light_logo.png';
            }
        
            
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
            
            
            

 		}

		
 		public function init_form_fields(){

            $this->form_fields = array(
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'Raast',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => 'Pay with your Raast ID',
                ),
                'testmode' => array(
                    'title'       => 'Test mode',
                    'label'       => 'Enable Test Mode',
                    'type'        => 'checkbox',
                    'description' => 'Tick this to enable sandbox/test mode. According to RAAST documentaion, test mail is: test_mode@mail.com and test verification code is: 222666',
                    'default'     => 'yes',
                ),
                'success_url' => array(
                    'title'       => 'Success URL',
                    'description' => 'This is the URL where Raast will redirect after payment was SUCCESSFUL. If you leave this field empty, it will be redirected to your site. Example: https://www.example.com/',
                    'type'        => 'text',
                    'default'     => ''
                ),
                'cancel_url' => array(
                    'title'       => 'Cancel URL',
                    'description' => 'This is the URL where Raast will redirect if payment was CANCELLED. If you leave this field empty, it will be redirected to your site. Example: https://www.example.com/',
                    'type'        => 'text',
                    'default'     => ''
                ),
                'logo_url' => array(
                    'title'       => 'Logo Url',
                    'description' => 'Your Site Logo URL. If you leave this field empty, it will use RAAST icon.  Example: https://www.example.com/image.png',
                    'type'        => 'text',
                    'default'     => ''
                ),
                'publishable_key' => array(
                    'title'       => 'Your Raast Public Key',
                    'type'        => 'text',
                    'default'     => ''
                ),
                'private_key' => array(
                    'title'       => 'Your Raast Private Key',
                    'type'        => 'password',
                    'default'     => ''
                )
            );
	
	 	}

		
		public function payment_fields() {

				 
		}

		
	 	public function payment_scripts() {

            

            if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
                return;
            }
        
        
            // no reason to enqueue JavaScript if API keys are not set
            if ( empty( $this->private_key ) || empty( $this->publishable_key ) ) {
                return;
            }
        
            // do not work with card detailes without SSL unless your website is in a test mode
            if ( ! $this->testmode && ! is_ssl() ) {
                return;
            }
	
	 	}

		
		public function validate_fields() {


		}

		
		public function process_payment( $order_id ) {
            global $woocommerce;
 
	
	        $order = wc_get_order( $order_id );
            $amount = $order->get_total();
            $name = $order->get_billing_first_name() . $order->get_billing_first_name();
            $email = $order->get_billing_email();


            ///live end point
            $url = 'https://www.raastid.com/payment/initiate';

            if($this->testmode){
                //test end point
                $url = 'https://www.raastid.com/sandbox/payment/initiate';
            }


            // Paramenters to API Call
            $parameters = [
                'identifier' => $order_id,
                'currency' => 'USD',
                'amount' => $amount,
                'details' => 'Order Placed',
                'ipn_url' => $this->ipn_url,
                'cancel_url' => $this->cancel_url,
                'success_url' => $this->success_url,
                'public_key' => $this->publishable_key,
                'site_logo' => $this->logo_url,
                'checkout_theme' => 'dark',
                'customer_name' => $name,
                'customer_email' => $email,
            
            ];
            

            //live end point
            $url = 'https://www.raastid.com/payment/initiate';

            if($this->testmode){
                //test end point
                $url = 'https://www.raastid.com/sandbox/payment/initiate';
            }
            


            // API Call
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS,  $parameters);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($result, true);



            if($result['success'] == 'ok'){

                $woocommerce->cart->empty_cart();
                wc_add_notice('Order is Placed Successfully!');

                return array(
                    'result' => 'success',
                    'redirect' => $result['url']
                );


            }
            else{


                // To debug result

                /* 
                if (is_array($result) || is_object($result)) {
                    error_log(print_r($result, true));
                } 
                else {
                    error_log($result);
                }
                */
            }
					
	 	}

		
		public function webhook() {

					
	 	}
 	}
}