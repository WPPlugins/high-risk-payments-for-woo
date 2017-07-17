<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Cardpay_Solutions_Gateway_Addons class.
 *
 * @extends WC_Cardpay_Solutions_Gateway
 */
class WC_Cardpay_Solutions_Gateway_Addons extends WC_Cardpay_Solutions_Gateway {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		if ( class_exists( 'WC_Subscriptions_Order' ) ) {
			add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'scheduled_subscription_payment' ), 10, 2 );
			add_action( 'woocommerce_subscription_failing_payment_method_updated_' . $this->id, array( $this, 'update_failing_payment_method' ), 10, 2 );

			add_action( 'wcs_resubscribe_order_created', array( $this, 'delete_resubscribe_meta' ), 10 );

			// Allow store managers to manually set Cardpay Solutions as the payment method on a subscription
			add_filter( 'woocommerce_subscription_payment_meta', array( $this, 'add_subscription_payment_meta' ), 10, 2 );
			add_filter( 'woocommerce_subscription_validate_payment_meta', array( $this, 'validate_subscription_payment_meta' ), 10, 2 );
		}

		if ( class_exists( 'WC_Pre_Orders_Order' ) ) {
			add_action( 'wc_pre_orders_process_pre_order_completion_payment_' . $this->id, array( $this, 'process_pre_order_release_payment' ) );
		}
	}

	/**
	 * Check if order contains subscriptions.
	 *
	 * @param  int $order_id
	 * @return bool
	 */
	protected function order_contains_subscription( $order_id ) {
		return function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order_id ) || wcs_order_contains_renewal( $order_id ) );
	}

	/**
	 * Check if order contains pre-orders.
	 *
	 * @param  int $order_id
	 * @return bool
	 */
	protected function order_contains_pre_order( $order_id ) {
		return class_exists( 'WC_Pre_Orders_Order' ) && WC_Pre_Orders_Order::order_contains_pre_order( $order_id );
	}

	/**
	 * Process the subscription
	 *
	 * @param int $order_id
	 * 
	 * @return array
	 */
	protected function process_subscription( $order_id ) {
		try {
			$order = wc_get_order( $order_id );
			$amount = $order->get_total();
			if ( isset( $_POST['cardpay-token'] ) && !empty( $_POST['cardpay-token'] ) ) {
				$post_id = wc_clean( $_POST['cardpay-token'] );
				$post = get_post( $post_id );
				$card_meta = get_post_meta( $post->ID, '_cardpay_card', true);
				$this->save_subscription_meta( $order->id, $card_meta );
			} else {
				$card = '';
				$cardpay = new WC_Cardpay_Solutions_API();
				$response = $cardpay->verify( $this );

				if ( is_wp_error( $response ) ) {
					throw new Exception( $response->get_error_message() );
				}

				if ( isset( $response['response'] ) && 1 == $response['response'] ) {
					$card_meta = array(
						'token' =>  $response['customer_vault_id'],
					);
					$this->save_subscription_meta( $order->id, $card_meta );
				} else {
					$error_msg = __( 'Payment was declined - please try another card.', 'woocommerce-cardpay-solutions' );
					throw new Exception( $error_msg );
				}
			}

			if ( $amount > 0 ) {
				$payment_response = $this->process_subscription_payment( $order, $order->get_total() );

				if ( is_wp_error( $payment_response ) ) {
					throw new Exception( $payment_response->get_error_message() );
				}
			} else {
				$order->payment_complete();
			}
			// Remove cart
			WC()->cart->empty_cart();

			// Return thank you page redirect
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order )
			);
		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );

			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}
	}

	/**
	 * Store the Cardpay Solutions card data on the order and subscriptions in the order
	 *
	 * @param int $order_id
	 * @param array $card
	 */
	protected function save_subscription_meta( $order_id, $card ) {
		update_post_meta( $order_id, '_cardpay_token', $card['token'] );

		// Also store it on the subscriptions being purchased in the order
		foreach( wcs_get_subscriptions_for_order( $order_id ) as $subscription ) {
			update_post_meta( $subscription->id, '_cardpay_token', $card['token'] );
		}
	}

	/**
	 * Process the pre-order
	 *
	 * @param int $order_id
	 * @return array
	 */
	protected function process_pre_order( $order_id ) {
		if ( WC_Pre_Orders_Order::order_requires_payment_tokenization( $order_id ) ) {
			
			try {
				$order = wc_get_order( $order_id );
				if ( isset( $_POST['cardpay-token'] ) && !empty( $_POST['cardpay-token'] ) ) {
					$post_id = wc_clean( $_POST['cardpay-token'] );
					$post = get_post( $post_id );
					$card_meta = get_post_meta( $post->ID, '_cardpay_card', true);
				} else {
					$card = '';
					$cardpay = new WC_Cardpay_Solutions_API();
					$response = $cardpay->verify( $this );

					if ( is_wp_error( $response ) ) {
						throw new Exception( $response->get_error_message() );
					}

					if ( isset( $response['response'] ) && 1 == $response['response'] ) {
						$card_meta = array(
							'token' =>  $response['customer_vault_id'],
						);
					} else {
						$error_msg = __( 'Payment was declined - please try another card.', 'woocommerce-cardpay-solutions' );
						throw new Exception( $error_msg );
					}
				}

				// Store the ID in the order
				update_post_meta( $order->id, '_cardpay_token', $card_meta['token'] );

				// Reduce stock levels
				$order->reduce_order_stock();

				// Remove cart
				WC()->cart->empty_cart();

				// Is pre ordered!
				WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );

				// Return thank you page redirect
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order )
				);
			} catch ( Exception $e ) {
				wc_add_notice( $e->getMessage(), 'error' );

				return array(
					'result'   => 'fail',
					'redirect' => '',
				);
			}
		} else {
			return parent::process_payment( $order_id );
		}
	}

	/**
	 * Process the payment
	 *
	 * @param  int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		// Processing subscription
		if ( $this->order_contains_subscription( $order_id ) || ( function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $order_id ) ) ) {
			return $this->process_subscription( $order_id );

		// Processing pre-order
		} elseif ( $this->order_contains_pre_order( $order_id ) ) {
			return $this->process_pre_order( $order_id );

		// Processing regular product
		} else {
			return parent::process_payment( $order_id );
		}
	}

	/**
	 * process_subscription_payment function.
	 *
	 * @param WC_order $order
	 * @param integer $amount (default: 0)
	 * 
	 * @return bool|WP_Error
	 */
	public function process_subscription_payment( $order, $amount = 0 ) {
		$card = array(
			'token' => get_post_meta( $order->id, '_cardpay_token', true ),
		);

		if ( ! $card ) {
			return new WP_Error( 'cardpay_error', __( 'Customer not found', 'woocommerce-cardpay-solutions' ) );
		}

		$cardpay = new WC_Cardpay_Solutions_API();
		if ( 'authorize' == $this->transaction_type ) {
			$response = $cardpay->authorize( $this, $order, $amount, $card );
		} else {
			$response = $cardpay->purchase( $this, $order, $amount, $card );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( isset( $response['response'] ) && 1 == $response['response'] ) {
			$order->payment_complete();
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
			add_post_meta( $order->id, '_cardpay_transaction', $tran_meta );
			return true;
		} else {
			$order->add_order_note( __( 'Cardpay Solutions payment declined', 'woocommerce-cardpay-solutions' ) );

			return new WP_Error( 'cardpay_payment_declined', __( 'Payment was declined - please try another card.', 'woocommerce-cardpay-solutions' ) );
		}
	}

	/**
	 * scheduled_subscription_payment function.
	 *
	 * @param float $amount_to_charge The amount to charge.
	 * @param WC_Order $renewal_order A WC_Order object created to record the renewal payment.
	 * @access public
	 * @return void
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {
		$result = $this->process_subscription_payment( $renewal_order, $amount_to_charge );

		if ( is_wp_error( $result ) ) {
			$renewal_order->update_status( 'failed', sprintf( __( 'Cardpay Solutions Transaction Failed (%s)', 'woocommerce-cardpay-solutions' ), $result->get_error_message() ) );
		}
	}

	/**
	 * Update the card meta for a subscription after using Cardpay Solutions to complete a payment to make up for
	 * an automatic renewal payment which previously failed.
	 *
	 * @access public
	 * @param WC_Subscription $subscription The subscription for which the failing payment method relates.
	 * @param WC_Order $renewal_order The order which recorded the successful payment (to make up for the failed automatic payment).
	 * @return void
	 */
	public function update_failing_payment_method( $subscription, $renewal_order ) {
		update_post_meta( $subscription->id, '_cardpay_token', get_post_meta( $renewal_order->id, '_cardpay_token', true ) );
	}

	/**
	 * Include the payment meta data required to process automatic recurring payments so that store managers can
	 * manually set up automatic recurring payments for a customer via the Edit Subscription screen in Subscriptions v2.0+.
	 *
	 * @since 2.4
	 * @param array $payment_meta associative array of meta data required for automatic payments
	 * @param WC_Subscription $subscription An instance of a subscription object
	 * @return array
	 */
	public function add_subscription_payment_meta( $payment_meta, $subscription ) {
		$payment_meta[ $this->id ] = array(
			'post_meta' => array(
				'_cardpay_token' => array(
					'value' => get_post_meta( $subscription->id, '_cardpay_token', true ),
					'label' => 'Cardpay Solutions Token',
				),
			),
		);

		return $payment_meta;
	}

	/**
	 * Validate the payment meta data required to process automatic recurring payments so that store managers can
	 * manually set up automatic recurring payments for a customer via the Edit Subscription screen in Subscriptions 2.0+.
	 *
	 * @since 2.4
	 * @param string $payment_method_id The ID of the payment method to validate
	 * @param array $payment_meta associative array of meta data required for automatic payments
	 * @return array
	 */
	public function validate_subscription_payment_meta( $payment_method_id, $payment_meta ) {
		if ( $this->id === $payment_method_id ) {
			if ( ! isset( $payment_meta['post_meta']['_cardpay_token']['value'] ) || empty( $payment_meta['post_meta']['_cardpay_token']['value'] ) ) {
				throw new Exception( 'A Cardpay Solutions Token value is required.' );
			}
		}
	}

	/**
	 * Don't transfer customer meta to resubscribe orders.
	 *
	 * @access public
	 * @param int $resubscribe_order The order created for the customer to resubscribe to the old expired/cancelled subscription
	 * @return void
	 */
	public function delete_resubscribe_meta( $resubscribe_order ) {
		delete_post_meta( $resubscribe_order->id, '_cardpay_token' );
	}

	/**
	 * Process a pre-order payment when the pre-order is released
	 *
	 * @param WC_Order $order
	 * @return wp_error|void
	 */
	public function process_pre_order_release_payment( $order ) {
		$amount = $order->get_total();
		$card = array(
			'token' => get_post_meta( $order->id, '_cardpay_token', true ),
		);

		if ( ! $card ) {
			return new WP_Error( 'cardpay_error', __( 'Customer not found', 'woocommerce-cardpay-solutions' ) );
		}

		$cardpay = new WC_Cardpay_Solutions_API();
		if ( 'authorize' == $this->transaction_type ) {
			$response = $cardpay->authorize( $this, $order, $amount, $card );
		} else {
			$response = $cardpay->purchase( $this, $order, $amount, $card );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( isset( $response['response'] ) && 1 == $response['response'] ) {
			$order->payment_complete();
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
		} else {
			$order->add_order_note( __( 'Cardpay Solutions payment declined', 'woocommerce-cardpay-solutions' ) );

			return new WP_Error( 'cardpay_payment_declined', __( 'Payment was declined - please try another card.', 'woocommerce-cardpay-solutions' ) );
		}
	}
}
