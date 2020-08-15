<?php
/*
**
** Plugin Name: WooCommerce VivaPayments Gateway
** Plugin URI: https://www.wpcare.gr
** Description: Adds Vivapayments Gateway to WooCommerce.
** Version: 1.0.6
** Author: WordPress Care
** Author URI: https://www.wpcare.gr
** License: GNU General Public License v3.0
**
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('plugins_loaded', 'woocommerce_vivapayments_init', 0);

function woocommerce_vivapayments_init(){
  if(!class_exists('WC_Payment_Gateway')) return;

class WC_Gateway_VivaPayments extends WC_Payment_Gateway {

    /*
    ** Constructor for VivaPayments Gateway.
    */
	public function __construct() {
		$this->id                 = 'vivapayments';
		$this->has_fields         = false;
		$this->method_title       = __( 'VivaPayments', 'woocommerce' );
		$this->method_description = __( 'Allows Customers to pay via secure VivaPayments service with their credit card.
		<b>Important:</b> Please go to "Transaction Sources" in your VivaPayments Account and add as Successful and Failed transaction url the following:
		<b>'.get_site_url().'/wc-api/WC_Gateway_VivaPayments/</b>', 'woocommerce' );
		$this->order_button_text = $this->get_option( 'button' );

		// Load settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions', $this->description );

		// Custom VivaPayments variables
		$this->enable_demo = $this->get_option( 'demo' );

			if ($this->enable_demo == "yes") {
					$this->api_url  = "http://demo.vivapayments.com/api/orders/";
					$this->response_url  = "http://demo.vivapayments.com/web/checkout?ref=";
			} else {
					$this->api_url  = "https://www.vivapayments.com/api/orders/";
					$this->response_url  = "https://www.vivapayments.com/web/checkout?ref=";
			}

		$this->merchand_id  	= $this->get_option( 'merchand_id' );
		$this->api_id  			= $this->get_option( 'api_id' );
		$this->thank_you_msg  	= $this->get_option( 'thank_you_msg' );
		$this->error_msg  		= $this->get_option( 'error_msg' );

		// Plugin Default Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    	add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_api_wc_gateway_' . $this->id, array( $this, 'check_ipn_response' ) );

    }

	/*
	** Check VivaPayments response and set order as completed if
	** payment received or redirect to checkout page for trying again.
	*/
	function check_ipn_response() {

		@ob_clean();

		$ipn_response = ! empty( $_GET['s'] ) ? $_GET['s'] : false;

		if ( $ipn_response ) {

			global $wpdb;
			global $woocommerce;
			$order_id = $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_value = '".$_GET['s']."'" );
			$order = new WC_Order( $order_id );

			if (isset($_GET['t']) && strlen($_GET['t']) > 0) {
				$woocommerce->add_message( $this->thank_you_msg );
				$woocommerce->cart->empty_cart();
				$order->payment_complete();
				wp_redirect(get_site_url()."/checkout/order-received/".$order_id."/?key=".$order->order_key);
				exit;
			} else {
				$woocommerce->add_error( $this->error_msg );
				wp_redirect(get_site_url()."/checkout/");
				exit;
			}

		} else {
			//Respond '1' if function called from someone else except VivaPayments.
			echo "1";

		}

	}


	/*
	** Initialize VivaPayments Admin Settings.
	*/
    public function init_form_fields() {

    	$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable VivaPayments', 'woocommerce' ),
				'default' => 'yes'
			),
			'demo' => array(
				'title'   => __( 'Enable VivaPayments Demo API', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Demo API', 'woocommerce' ),
				'default' => 'yes'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'VivaPayments', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
				'default'     => __( 'You will be transfered to VivaPayments secure site in order to pay with your credit card.', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'The instructions that customer will see after succesfull order placement.', 'woocommerce' ),
				'default'     => 'We received your payment, your order is now processing. Thank You.',
				'desc_tip'    => true,
			),
			'merchand_id' => array(
				'title'       => __( 'Merchand ID', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Your VivaPayments Merchand ID.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'api_id' => array(
				'title'       => __( 'API Key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Your VivaPayments API Key.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'thank_you_msg' => array(
				'title'       => __( 'Success Message', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'The message that customer will get after succesfull payment.', 'woocommerce' ),
				'default'     => 'Thank You! We received your payment!',
				'desc_tip'    => true,
			),
			'error_msg' => array(
				'title'       => __( 'Error Message', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'The message that customer will get after payment declined for any reason.', 'woocommerce' ),
				'default'     => 'Sorry! Something went wrong with payment, please try again.',
				'desc_tip'    => true,
			),
			'button' => array(
				'title'       => __( 'Payment Button Text', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'The text on payment button.', 'woocommerce' ),
				'default'     => 'Proceed to VivaPayments',
				'desc_tip'    => true,
			),
		);
    }

	/*
	** Send our instructions to WooCommerce Thank You Page (after succesfull payment).
	*/
	public function thankyou_page() {
		if ( $this->instructions )
        	echo wpautop( wptexturize( $this->instructions ) );
	}

	/*
	** Send our customer to VivaPayments for Credit Card Payment.
	*/
	public function process_payment( $order_id ) {

		$order = new WC_Order( $order_id );

		global $woocommerce;
		$totalamount = $woocommerce->cart->total*100;

		$api_url = $this->api_url;
		$merchand_id = $this->merchand_id;
		$api_id = $this->api_id;
		$response_url = $this->response_url;

		$postargs = 'Amount='.urlencode($totalamount).'&DisableCash=true&CustomerTrns='.urlencode("Order: ".$order_id);
		$session = curl_init($api_url);
		curl_setopt($session, CURLOPT_POST, true);
		curl_setopt($session, CURLOPT_POSTFIELDS, $postargs);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_USERPWD, $merchand_id.':'.$api_id);
		$response = curl_exec($session);
		curl_close($session);

		try {
			if(is_object(json_decode($response))){
			$resultObj=json_decode($response);
		}else{
			throw new Exception("Result is not a json object: ");
		}
		} catch( Exception $e ) {
			$viva_msg = $e->getMessage();
		}

		if ($resultObj->ErrorCode==0){	//success when ErrorCode = 0
			$orderId = $resultObj->OrderCode;
			$error = 0;
		}

		else{
			$error = $resultObj->ErrorText;
		}


		if	($error == 0 AND !empty($orderId)) {
			add_post_meta( $order->id, '_viva_payment', $orderId );
			return array(
				'result' 	=> 'success',
				'redirect'	=>  $response_url.$orderId
			);
		} else {
				wc_add_notice($this->error_msg, $notice_type = 'error' );
				//$woocommerce->add_error( $this->error_msg );
				return array(
					'result' 	=> 'success',
					'redirect'	=>  get_site_url().'/checkout/'
				);
		}

	}
}

    function woocommerce_add_vivapayments_gateway($methods) {
        $methods[] = 'WC_Gateway_VivaPayments';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_vivapayments_gateway' );

}


/*
** Small snippet to add payment methods to admin email notification on new order.

add_action( 'woocommerce_email_after_order_table', 'add_payment_method_to_admin_new_order', 15, 2 );

function add_payment_method_to_admin_new_order( $order, $is_admin_email ) {
  if ( $is_admin_email ) {
    echo '<p><strong>Payment Method:</strong> ' . $order->payment_method_title . '</p>';
  }
}*/
