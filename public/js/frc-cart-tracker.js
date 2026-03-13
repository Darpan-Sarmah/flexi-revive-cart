/* global frcTracker, jQuery */
( function ( $ ) {
	'use strict';

	var heartbeatInterval = 30000; // 30 seconds
	var $cartForm         = $( 'form.woocommerce-cart-form, form.checkout, .woocommerce-cart' );

	/**
	 * Build a simplified cart items snapshot from WC cart form.
	 * @returns {string} JSON string.
	 */
	function buildCartSnapshot() {
		var items = [];
		$( '.woocommerce-cart-form__cart-item, .cart_item' ).each( function () {
			var $row = $( this );
			var productId = $row.find( '[name^="cart["]' ).attr( 'name' );
			if ( productId ) {
				productId = productId.replace( /.*\[(\d+)\].*/, '$1' );
			}
			var qty   = parseInt( $row.find( '.qty' ).val(), 10 ) || 1;
			var price = parseFloat( $row.find( '.woocommerce-Price-amount' ).first().text().replace( /[^0-9.]/g, '' ) ) || 0;
			if ( productId ) {
				items.push( { product_id: productId, quantity: qty, line_total: price * qty } );
			}
		} );
		return JSON.stringify( items );
	}

	/**
	 * Send a heartbeat to the server.
	 */
	function sendHeartbeat() {
		var cartTotal = parseFloat( $( '.cart-subtotal .woocommerce-Price-amount' ).first().text().replace( /[^0-9.]/g, '' ) ) || 0;

		if ( cartTotal <= 0 ) {
			return;
		}

		$.post(
			frcTracker.ajaxUrl,
			{
				action        : 'frc_track_cart',
				nonce         : frcTracker.nonce,
				cart_total    : cartTotal,
				cart_contents : buildCartSnapshot(),
			}
		);
	}

	// Start heartbeat if the cart is present.
	if ( $cartForm.length || $( '.woocommerce-cart-form' ).length ) {
		sendHeartbeat();
		setInterval( sendHeartbeat, heartbeatInterval );
	}

} )( jQuery );
