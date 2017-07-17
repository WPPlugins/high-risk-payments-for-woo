<h2 id="credit-cards" style="margin-top:40px;"><?php _e( 'My Credit Cards', 'woocommerce-cardpay-solutions' ); ?></h2>
<div class="woocommerce-message cardpay-success-message"><?php _e( 'Your request has been successfully processed.', 'woocommerce-cardpay-solutions' ) ?></div>
<div class="woocommerce-error cardpay-error-message"><?php _e( 'There was an error processing your request.', 'woocommerce-cardpay-solutions' ) ?></div>
<table class="shop_table shop_table_responsive credit_cards" id="credit-cards-table">
	<thead>
		<tr>
			<th><?php _e( 'Card Details', 'woocommerce-cardpay-solutions' ); ?></th>
			<th><?php _e( 'Expires', 'woocommerce-cardpay-solutions' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $cards as $card ):
			$card_meta = get_post_meta( $card->ID, '_cardpay_card', true );
			$card_type = $card_meta['cardtype'];
			if ( 'American Express' == $card_type ) {
				$card_type_img = 'amex';
			} elseif ( 'Diners Club' == $card_type ) {
				$card_type_img = 'diners';
			} else {
				$card_type_img = strtolower( $card_type );
			}
			$cc_last4 = $card_meta['cc_last4'];
			$is_default = $card_meta['is_default'];
			$cc_exp = $card_meta['expiry'];
		?>
		<tr>
			<td>
				<img src="<?php echo WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/' . $card_type_img . '.png' ) ?>" alt=""/>
				<?php printf( __( '%s ending in %s %s', 'woocommerce-cardpay-solutions' ), $card_type, $cc_last4, 'yes' == $is_default ? '(default)' : '' ) ?>
			</td>
			<td><?php printf( __( '%s/%s' ), substr( $cc_exp, 0, 2 ), substr( $cc_exp, -2 ) ) ?></td>
			<td>
				<a href="#" data-id="<?php echo esc_attr( $card->ID ) ?>" data-title="<?php printf( __( 'Edit %s ending in %s', 'woocommerce-cardpay-solutions' ), $card_type, $cc_last4 ) ?>" data-exp="<?php printf( __( '%s / %s' ), substr( $cc_exp, 0, 2 ), substr( $cc_exp, -2 ) ) ?>" data-default="<?php echo esc_attr( $is_default ) ?>" class="edit-card"><?php _e( 'Edit', 'woocommerce-cardpay-solutions' ) ?></a> |
				<a href="#" data-id="<?php echo esc_attr( $card->ID ) ?>" data-nonce="<?php echo wp_create_nonce( 'delete_card_nonce' ) ?>" class="delete-card"><?php _e( 'Delete', 'woocommerce-cardpay-solutions' ); ?></a>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<p><a href="#" class="button add-card"><?php _e( 'Add New Card', 'woocommerce-cardpay-solutions' ) ?></a></p>

<h3 class="add-card-heading"><?php _e( 'Add Credit Card', 'woocommerce-cardpay-solutions' ) ?></h3>
<h3 class="edit-card-heading"></h3>
<div id="credit-card" class="cardpay-credit-card">
	<form type="post" action="", id="cardpay-cc-form">
		<fieldset id="cardpay-cc-fields">
			<input id="_wpnonce" type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'add_card_nonce' ) ?>" />
			<input id="cardpay-card-id" type="hidden" name="cardpay-card-id" value="" />
			<p class="form-row form-row-wide">
				<label for="cardpay-card-number"><?php _e( 'Card Number ', 'woocommerce-cardpay-solutions' ) ?><span class="required">*</span></label>
				<input id="cardpay-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="cardpay-card-number" />
			</p>
			<p class="form-row form-row-first">
				<label for="cardpay-card-expiry"><?php _e( 'Expiry (MM/YY) ', 'woocommerce-cardpay-solutions' ) ?><span class="required">*</span></label>
				<input id="cardpay-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="MM / YY" name="cardpay-card-expiry" />
			</p>
			<p class="form-row form-row-last">
				<label for="cardpay-card-cvc"><?php _e( 'Card Code ', 'woocommerce-cardpay-solutions' ) ?><span class="required">*</span></label>
				<input id="cardpay-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="CVC" name="cardpay-card-cvc" />
			</p>
			<p class="form-row form-row-wide make-default">
				<label for="cardpay-make-default"><?php _e( 'Make Default? ', 'woocommerce-cardpay-solutions' ) ?></label>
				<input id="cardpay-make-default" class="input-text wc-credit-card-form-make-default" type="checkbox" name="cardpay-make-default" />
			</p>
			<p class="form-row form-row">
				<input type="submit" value="Submit" class="button" />
				<a href="#" class="cc-form-cancel"><?php _e( 'Cancel ', 'woocommerce-cardpay-solutions' ) ?></a>
			</p>
		</fieldset>
	</form>
</div>
