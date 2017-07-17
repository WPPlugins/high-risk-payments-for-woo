jQuery(document).ajaxComplete(function () {
	var initToken = jQuery('#cardpay-token').val();

	if (initToken) {
		jQuery('.hide-if-token').hide();
	}

	jQuery('#cardpay-token').change(function () {
		var token = jQuery('#cardpay-token').val();
		if (token) {
			jQuery('.hide-if-token').hide();
		} else {
			jQuery('.hide-if-token').show();
		}
	});
});

jQuery(document).ready(function () {
	var initToken = jQuery('#cardpay-token').val();

	if (initToken) {
		jQuery('.hide-if-token').hide();
	}

	jQuery('#cardpay-token').change(function () {
		var token = jQuery('#cardpay-token').val();
		if (token) {
			jQuery('.hide-if-token').hide();
		} else {
			jQuery('.hide-if-token').show();
		}
	});
});
