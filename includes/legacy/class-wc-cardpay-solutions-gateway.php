<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Cardpay_Solutions_Gateway
 * 
 * @extends WC_Payment_Gateway
 */
class WC_Cardpay_Solutions_Gateway extends WC_Payment_Gateway
{
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id = 'cardpay';
		$this->has_fields = true;
		$this->method_title = 'Cardpay Solutions';

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define the supported features
		$this->supports = array(
			'products',
			'refunds',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
			'multiple_subscriptions',
			'pre-orders',
			'default_credit_card_form',
		);

		// Define user set variables
		$this->enabled = $this->get_option( 'enabled' );
		$this->title = $this->get_option( 'title' );
		$this->sandbox = $this->get_option( 'sandbox' );
		$this->username = $this->get_option( 'username' );
		$this->password = $this->get_option( 'password' );
		$this->transaction_type = $this->get_option( 'transaction_type' );
		$this->auto_capture = $this->get_option( 'auto_capture' );
		$this->customer_vault = $this->get_option( 'customer_vault' );
		$this->cardtypes = $this->get_option( 'cardtypes' );

		// Add test mode warning if sandbox
		if ( 'yes' == $this->sandbox ) {
			$this->description = __( 'TEST MODE ENABLED. Use test card number 4111111111111111 with any 3-digit CVC and a future expiration date.', 'woocommerce-cardpay-solutions' );
		}

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Admin notices
	 */
	public function admin_notices() {
		if ( 'no' == $this->enabled ) {
			return;
		}

		// Show message if username is empty in live mode
		if ( ! $this->username && 'no' == $this->sandbox ) {
			echo '<div class="error"><p>' . sprintf( __( 'Cardpay Solutions error: The Username is required. <a href="%s">Click here to update your Cardpay Solutions settings.</a>', 'woocommerce-cardpay-solutions' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_cardpay_solutions_gateway' ) ) . '</p></div>';
			return;
		}
		
		// Show message if password is empty in live mode
		if ( ! $this->password && 'no' == $this->sandbox ) {
			echo '<div class="error"><p>' . sprintf( __( 'Cardpay Solutions error: The Password is required. <a href="%s">Click here to update your Cardpay Solutions settings.</a>', 'woocommerce-cardpay-solutions' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_cardpay_solutions_gateway' ) ) . '</p></div>';
			return;
		}

		// Show message when in live mode and no SSL on the checkout page
		if ( 'no' == $this->sandbox && get_option( 'woocommerce_force_ssl_checkout' ) == 'no' && ! class_exists( 'WordPressHTTPS' ) ) {
			echo '<div class="error"><p>' . sprintf( __( 'Cardpay Solutions is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate.', 'woocommerce-cardpay-solutions'), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . '</p></div>';
		}
	}

	/**
	 * Administrator area options
	 */
	public function admin_options() {
		?>
		<h3><img src="<?php echo WC_HTTPS::force_https_url( WC_CARDPAY_PLUGIN_URL . '/assets/images/cardpay_logo_sm.png' ) ?>" alt="Cardpay Solutions" /></h3>
		<div class="cardpay-description" style="width:50%;">
			<p>
				Cardpay Solutions makes accepting credit cards simple.  Accept all major credit cards including Visa, MasterCard, American Express, Discover, JCB, and Diners Club.
				The Cardpay Solutions extension allows your logged in customers to securely store and re-use credit card profiles to speed up the checkout process.
				We also support Subscription and Pre-Order features.
			</p>
		</div>
		<p><a href="http://www.cardpaymerchant.com/woocommerce?pid=317d5f0aa67f1638" target="_blank" class="button-primary">Click Here To Sign Up!</a></p>
		<hr>
		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table><!--/.form-table-->
		<?php
	}

	/**
	 * Init payment gateway settings form fields
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'       => __( 'Enable/Disable', 'woocommerce-cardpay-solutions' ),
				'label'       => __( 'Enable Cardpay Solutions', 'woocommerce-cardpay-solutions' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-cardpay-solutions' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-cardpay-solutions' ),
				'default'     => __( 'Credit Card', 'woocommerce-cardpay-solutions' ),
				'desc_tip'    => true,
			),
			'sandbox' => array(
				'title'       => __( 'Use Sandbox', 'woocommerce-cardpay-solutions' ),
				'label'       => __( 'Enable sandbox mode - live payments will not be taken if enabled.', 'woocommerce-cardpay-solutions' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'username' => array(
				'title'       => __( 'Username', 'woocommerce-cardpay-solutions' ),
				'type'        => 'text',
				'description' => __( 'Contact sales at (866) 588-0503 if you have not received your Username. Not required for Sandbox mode.', 'woocommerce-cardpay-solutions' ),
				'default'     => '',
			),
			'password' => array(
				'title'       => __( 'Password', 'woocommerce-cardpay-solutions' ),
				'type'        => 'text',
				'description' => __( 'Contact sales at (866) 588-0503 if you have not received your Password. Not required for Sandbox mode.', 'woocommerce-cardpay-solutions' ),
				'default'     => '',
			),
			'transaction_type' => array(
				'title'       => __( 'Transaction Type', 'woocommerce-cardpay-solutions' ),
				'type'        => 'select',
				'description' => '',
				'default'     => 'purchase',
				'options'     => array(
					'purchase'  => 'Authorize & Capture',
					'authorize' => 'Authorize Only',
				),
			),
			'auto_capture' => array(
				'title'       => __( 'Auto Capture', 'woocommerce-cardpay-solutions' ),
				'label'       => __( 'Automatically attempt to capture transactions that are processed as Authorize Only when order is marked complete.', 'woocommerce-cardpay-solutions' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'customer_vault' => array(
				'title'       => __( 'Allow Stored Cards', 'woocommerce-cardpay-solutions' ),
				'label'       => __( 'Allow logged in customers to save credit card profiles to use for future purchases', 'woocommerce-cardpay-solutions' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'yes',
			),
			'cardtypes' => array(
				'title'    => __( 'Accepted Cards', 'woocommerce-cardpay-solutions' ),
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'css'      => 'width: 350px;',
				'desc_tip' => __( 'Select the card types to accept.', 'woocommerce-cardpay-solutions' ),
				'options'  => array(
					'visa'       => 'Visa',
					'mastercard' => 'MasterCard',
					'amex'       => 'American Express',
					'discover'   => 'Discover',
					'jcb'        => 'JCB',
					'diners'     => 'Diners Club',
				),
				'default' => array( 'visa', 'mastercard', 'amex', 'discover' ),
			),
		);
	}

	/**
	 * get_icon function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_icon() {
		$icon = '';
		if( is_array( $this->cardtypes ) ) {
			$card_types = array_reverse( $this->cardtypes );
			foreach ( $card_types as $card_type ) {
				$icon .= '<img src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/' . $card_type . '.png' ) . '" alt="' . $card_type . '" />';
			}
		}
		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	/**
	 * process_payment function.
	 *
	 * @access public
	 * @param mixed $order_id
	 * @return void
	 */
	public function process_payment( $order_id ) {
		try {
			global $woocommerce;
			$order = wc_get_order( $order_id );
			$amount = $order->get_total();
			$card = '';
			if ( isset( $_POST['cardpay-token'] ) && !empty( $_POST['cardpay-token'] ) ) {
				$post_id = wc_clean( $_POST['cardpay-token'] );
				$post = get_post( $post_id );
				$card = get_post_meta( $post->ID, '_cardpay_card', true);
			}

			$cardpay = new WC_Cardpay_Solutions_API();
			if ( 'authorize' == $this->transaction_type ) {
				$response = $cardpay->authorize( $this, $order, $amount, $card );
			} else {
				$response = $cardpay->purchase( $this, $order, $amount, $card );
			}

			if ( is_wp_error( $response ) ) {
				$order->add_order_note( $response->get_error_message() );
				throw new Exception( $response->get_error_message() );
			}

			if ( isset( $response['response'] ) && 1 == $response['response'] ) {
				$order->payment_complete();
				$woocommerce->cart->empty_cart();
				$amount_approved = number_format( $amount, '2', '.', '' );
				$message = 'authorize' == $this->transaction_type ? 'authorized' : 'completed';
				$order->add_order_note(
					sprintf(
						__( "Cardpay Solutions payment %s for %s. Transaction ID: %s.\n\n <strong>AVS Response:</strong> %s.\n\n <strong>CVV2 Response:</strong> %s.", 'woocommerce-cardpay-solutions' ), 
						$message,
						$amount_approved,
						$response['transactionid'],
						$this->get_avs_message( $response['avsresponse'] ),
						$this->get_cvv_message( $response['cvvresponse'] )
					)
				);
				$tran_meta = array(
					'transaction_id' => $response['transactionid'],
					'transaction_type' => $this->transaction_type,
				);
				add_post_meta( $order_id, '_cardpay_transaction', $tran_meta );
				// Save the card if possible
				if ( isset( $_POST['cardpay-save-card'] ) && is_user_logged_in() && 'yes' == $this->customer_vault ) {
					$this->save_card( $response );
				}
				// Return thankyou redirect
				return array(
					'result' => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			} else {
				$order->add_order_note( $response['responsetext'] );

				throw new Exception( $response['responsetext'] );
			}
		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );

			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}
	}

	/**
	 * process_refund function.
	 *
	 * @access public
	 * @param int $order_id
	 * @param float $amount
	 * @param string $reason
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = NULL, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( $amount > 0 ) {
			try {
				$cardpay = new WC_Cardpay_Solutions_API();
				$response = $cardpay->refund( $this, $order, $amount );

				if ( is_wp_error( $response ) ) {
					throw new Exception( $response->get_error_message() );
				}

				if ( isset( $response['response'] ) && 1 == $response['response'] ) {
					$refunded_amount = number_format( $amount, '2', '.', '' );
					$order->add_order_note( sprintf( __( 'Cardpay Solutinos refund completed for %s. Refund ID: %s', 'woocommerce-cardpay-solutions' ), $refunded_amount, $response['transactionid'] ) );
					return true;
				} else {
					throw new Exception( __( 'Refund error: ' . $response['responsetext'], 'woocommerce-cardpay-solutions' ) );
				}
			} catch ( Exception $e ) {
				$order->add_order_note( $e->getMessage() );
				return new WP_Error( 'cardpay_error', $e->getMessage() );
			}
		} else {
			return false;
		}
	}

	/**
	 * process_capture function.
	 *
	 * @access public
	 * @param int $order_id
	 * @return bool
	 */
	public function process_capture( $order_id ) {
		$order = wc_get_order( $order_id );

		// Return if another payment method was used
		if ( $order->payment_method != $this->id ) {
			return;
		}

		// Attempt to process the capture
		$tran_meta = get_post_meta( $order_id, '_cardpay_transaction', true);
		$orig_tran_type = $tran_meta['transaction_type'];
		$amount = $order->get_total();

		if ( 'authorize' == $orig_tran_type && 'yes' == $this->auto_capture ) {
			try {
				$cardpay = new WC_Cardpay_Solutions_API();
				$response = $cardpay->capture( $this, $order, $amount );

				if ( is_wp_error( $response ) ) {
					throw new Exception( $response->get_error_message() );
				}

				if ( isset( $response['response'] ) && 1 == $response['response'] ) {
					$captured_amount = number_format( $amount, '2', '.', '' );
					$order->add_order_note( sprintf( __( 'Cardpay Solutions auto capture completed for %s. Capture ID: %s', 'woocommerce-cardpay-solutions' ), $captured_amount, $response['transactionid'] ) );
					$tran_meta = array(
						'transaction_id' => $response['transactionid'],
						'transaction_type' => 'capture',
					);
					update_post_meta( $order_id, '_cardpay_transaction', $tran_meta );
					return true;
				} else {
					throw new Exception( __( 'Cardpay Solutions auto capture failed. Log into your gateway to manually process the capture.', 'woocommerce-cardpay-solutions' )  );
				}
			} catch ( Exception $e ) {
				$order->add_order_note( $e->getMessage() );
				return true;
			}
		}
	}

	/**
	 * save_card function.
	 *
	 * @access public
	 * @param Object $response
	 * @return void
	 */
	public function save_card( $response ) {
		$current_cards = count( $this->get_saved_cards() );
		$card_number = str_replace( ' ', '', $_POST['cardpay-card-number'] );
		$exp_date_array = explode( "/", $_POST['cardpay-card-expiry'] );
		$exp_month = trim( $exp_date_array[0] );
		$exp_year = trim( $exp_date_array[1] );
		$exp_date = $exp_month . substr( $exp_year, -2 );
		$card = array(
			'post_type' => 'cardpay_credit_card',
			'post_title' => sprintf( __( 'Token %s &ndash; %s', 'woocommerce-cardpay-solutions' ), $response['customer_vault_id'], strftime( _x( '%b %d, %Y @ %I:%M %p', 'Token date parsed by strftime', 'woocommerce-cardpay-solutions' ) ) ),
			'post_content' => '',
			'post_status' => 'publish',
			'ping_status' => 'closed',
			'post_author' => get_current_user_id(),
			'post_password' => uniqid('card_'),
			'post_category' => '',
		);
		$post_id = wp_insert_post( $card );
		$card_meta = array(
			'token' =>  $response['customer_vault_id'],
			'cc_last4' => substr( $card_number, -4 ),
			'expiry' => $exp_date,
			'cardtype' =>  $this->get_card_type( $card_number ),
			'is_default' => $current_cards ? 'no' : 'yes',
		);
		add_post_meta( $post_id, '_cardpay_card', $card_meta );
	}

	/**
	 * Credit card form.
	 *
	 * @param  array $args
	 * @param  array $fields
	 */
	public function credit_card_form( $args = array(), $fields = array() ) {

		wp_enqueue_script( 'wc-credit-card-form' );
		wp_enqueue_script( 'cardpay-credit-card-form', WC_CARDPAY_PLUGIN_URL . '/assets/js/cardpay-credit-card-form.js', array(), false, true );

		$default_args = array(
			'fields_have_names' => true
		);

		$args = wp_parse_args( $args, apply_filters( 'woocommerce_credit_card_form_args', $default_args, $this->id ) );

		$default_fields = array(
			'card-number-field' => '<p class="form-row form-row-wide hide-if-token">
				<label for="' . esc_attr( $this->id ) . '-card-number">' . __( 'Card Number', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="' . ( $args['fields_have_names'] ? $this->id . '-card-number' : '' ) . '" />
			</p>',
			'card-expiry-field' => '<p class="form-row form-row-first hide-if-token">
				<label for="' . esc_attr( $this->id ) . '-card-expiry">' . __( 'Expiry (MM/YY)', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="' . esc_attr__( 'MM / YY', 'woocommerce' ) . '" name="' . ( $args['fields_have_names'] ? $this->id . '-card-expiry' : '' ) . '" />
			</p>',
			'card-cvc-field' => '<p class="form-row form-row-last hide-if-token">
				<label for="' . esc_attr( $this->id ) . '-card-cvc">' . __( 'Card Code', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="' . esc_attr__( 'CVC', 'woocommerce' ) . '" name="' . ( $args['fields_have_names'] ? $this->id . '-card-cvc' : '' ) . '" />
			</p>',
		);

		if ( 'yes' == $this->customer_vault && is_user_logged_in() ) {
			$saved_cards = $this->get_saved_cards();

			array_push(
				$default_fields,
				'<p class="form-row form-row-wide hide-if-token">
					<label for="' . esc_attr( $this->id ) . '-save-card"><input id="' . esc_attr( $this->id ) . '-save-card" class="input-checkbox wc-credit-card-form-save-card" type="checkbox" name="' . ( $args['fields_have_names'] ? $this->id . '-save-card' : '' ) . '" /><span>' . __( 'Save card for future use?', 'woocommerce-cardpay-solutions' ) . ' </span></label>
				</p>'
			);
			if ( count( $saved_cards )) {
				$option_values = '';
				foreach ( $saved_cards as $card ) {
					$card_meta = get_post_meta( $card->ID, '_cardpay_card', true );
					$card_desc = '************' . $card_meta['cc_last4'] . ' - ' . $card_meta['cardtype'] . ' - Exp: ' . $card_meta['expiry'];
					$option_values .= '<option value="' . esc_attr( $card->ID ) . '"' . ( 'yes' == $card_meta['is_default'] ? 'selected="selected"' : '' ) . '>' . esc_attr( $card_desc ) . '</option>';
				}
				$option_values .= '<option value="">' . __('Add new card', 'woocommerce-cardpay-solutions' ) . '</option>';
				array_unshift(
					$default_fields,
					'<p class="form-row form-row-wide">
						<label for="' . esc_attr( $this->id ) . '-token">' . __( 'Payment Information', 'woocommerce-cardpay-solutions' ) . ' <span class="required">*</span></label>
						<select id="' . esc_attr( $this->id ) . '-token" class="wc-credit-card-form-token" name="' . ( $args['fields_have_names'] ? $this->id . '-token' : '' ) . '" >' .
						$option_values . '</select>
					</p>'
				);
			}
		}

		$fields = wp_parse_args( $fields, apply_filters( 'woocommerce_credit_card_form_fields', $default_fields, $this->id ) );
		?>
		<fieldset id="<?php echo $this->id; ?>-cc-form">
			<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
			<?php
				foreach ( $fields as $field ) {
					echo $field;
				}
			?>
			<?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
			<div class="clear"></div>
		</fieldset>
		<?php
	}

	/**
	 * get_saved_cards function.
	 *
	 * @access private
	 * @return array
	 */
	private function get_saved_cards() {
		$args = array(
			'post_type' => 'cardpay_credit_card',
			'author' => get_current_user_id(),
			'orderby' => 'post_date',
			'order' => 'ASC',
		);
		$cards = get_posts( $args );
		return $cards;
	}

	/**
	 * get_avs_message function.
	 * 
	 * @access public
	 * @param string $code
	 * @return string
	 */
	public function get_avs_message( $code ) {
		$avs_messages = array(
			'X' => __( 'Exact match, 9-character numeric ZIP', 'woocommerce-cardpay-solutions' ),
            'Y' => __( 'Exact match, 5-character numeric ZIP', 'woocommerce-cardpay-solutions' ),
            'D' => __( 'Exact match, 5-character numeric ZIP', 'woocommerce-cardpay-solutions' ),
            'M' => __( 'Exact match, 5-character numeric ZIP', 'woocommerce-cardpay-solutions' ),
            'A' => __( 'Address match only', 'woocommerce-cardpay-solutions' ),
            'B' => __( 'Address match only', 'woocommerce-cardpay-solutions' ),
            'W' => __( '9-character numeric ZIP match only', 'woocommerce-cardpay-solutions' ),
            'Z' => __( '5-character ZIP match only', 'woocommerce-cardpay-solutions' ),
            'P' => __( '5-character ZIP match only', 'woocommerce-cardpay-solutions' ),
            'L' => __( '5-character ZIP match only', 'woocommerce-cardpay-solutions' ),
            'N' => __( 'No address or ZIP match only', 'woocommerce-cardpay-solutions' ),
            'C' => __( 'No address or ZIP match only', 'woocommerce-cardpay-solutions' ),
            'U' => __( 'Address unavailable', 'woocommerce-cardpay-solutions' ),
            'G' => __( 'Non-U.S. issuer does not participate', 'woocommerce-cardpay-solutions' ),
            'I' => __( 'Non-U.S. issuer does not participate', 'woocommerce-cardpay-solutions' ),
            'R' => __( 'Issuer system unavailable', 'woocommerce-cardpay-solutions' ),
            'E' => __( 'Not a mail/phone order', 'woocommerce-cardpay-solutions' ),
            'S' => __( 'Service not supported', 'woocommerce-cardpay-solutions' ),
            'O' => __( 'AVS not available', 'woocommerce-cardpay-solutions' ),
		);
		if ( array_key_exists( $code, $avs_messages ) ) {
			return $avs_messages[$code];
		} else {
			return '';
		}
	}

	/**
	 * get_cvv_message function.
	 * 
	 * @access public
	 * @param string $code
	 * @return string
	 */
	public function get_cvv_message( $code ) {
		$cvv_messages = array(
			'M' => __( 'CVV2/CVC2 match', 'woocommerce-cardpay-solutions' ),
            'N' => __( 'CVV2/CVC2 no match', 'woocommerce-cardpay-solutions' ),
            'P' => __( 'Not processed', 'woocommerce-cardpay-solutions' ),
            'S' => __( 'Merchant has indicated that CVV2/CVC2 is not present on card', 'woocommerce-cardpay-solutions' ),
            'U' => __( 'Issuer is not certified and/or has not provided Visa encryption keys', 'woocommerce-cardpay-solutions' ),
		);
		if ( array_key_exists( $code, $cvv_messages ) ) {
			return $cvv_messages[$code];
		} else {
			return '';
		}
	}

	/**
	 * get_card_type function
	 * 
	 * @param string $number
	 * 
	 * @return string
	 */
	private function get_card_type( $number ) {
		if ( preg_match( '/^4\d{12}(\d{3})?(\d{3})?$/', $number ) ) {
			return 'Visa';
		} elseif ( preg_match( '/^3[47]\d{13}$/', $number ) ) {
			return 'American Express';
		} elseif ( preg_match( '/^(5[1-5]\d{4}|677189|222[1-9]\d{2}|22[3-9]\d{3}|2[3-6]\d{4}|27[01]\d{3}|2720\d{2})\d{10}$/', $number ) ) {
			return 'MasterCard';
		} elseif ( preg_match( '/^(6011|65\d{2}|64[4-9]\d)\d{12}|(62\d{14})$/', $number ) ) {
			return 'Discover';
		} elseif  (preg_match( '/^35(28|29|[3-8]\d)\d{12}$/', $number ) ) {
			return 'JCB';
		} elseif ( preg_match( '/^3(0[0-5]|[68]\d)\d{11}$/', $number ) ) {
			return 'Diners Club';
		}
	}
}
