/* global frcGuestCapture, jQuery */
( function ( $ ) {
	'use strict';

	var COOKIE_KEY      = 'frc_gc_shown';
	var $overlay        = $( '#frc-guest-capture-overlay' );
	var popupShown      = false;

	if ( ! $overlay.length ) {
		return;
	}

	/**
	 * Check if a cookie is set.
	 * @param {string} name Cookie name.
	 * @returns {boolean}
	 */
	function hasCookie( name ) {
		return document.cookie.split( '; ' ).some( function ( c ) {
			return c.startsWith( name + '=' );
		} );
	}

	/**
	 * Set a cookie for 24 hours.
	 * @param {string} name Cookie name.
	 */
	function setCookie( name ) {
		var expires = new Date( Date.now() + 24 * 60 * 60 * 1000 ).toUTCString();
		document.cookie = name + '=1; expires=' + expires + '; path=/; SameSite=Lax';
	}

	/**
	 * Show the popup.
	 */
	function showPopup() {
		if ( popupShown || hasCookie( COOKIE_KEY ) ) {
			return;
		}
		popupShown = true;
		setCookie( COOKIE_KEY );
		$overlay.addClass( 'frc-popup-visible' );
	}

	/**
	 * Hide the popup.
	 */
	function hidePopup() {
		$overlay.removeClass( 'frc-popup-visible' );
	}

	// Show after configured delay.
	setTimeout( showPopup, ( frcGuestCapture.delaySeconds || 30 ) * 1000 );

	// Close handlers.
	$overlay.on( 'click', '.frc-popup-close, .frc-popup-dismiss, .frc-popup-overlay', function ( e ) {
		if ( $( e.target ).is( '.frc-popup-overlay, .frc-popup-close, .frc-popup-dismiss' ) ) {
			hidePopup();
		}
	} );

	// Form submit.
	$overlay.on( 'submit', '#frc-guest-capture-form', function ( e ) {
		e.preventDefault();

		var email    = $( '#frc-gc-email' ).val().trim();
		var $msg     = $( '#frc-gc-message' );

		if ( ! email ) {
			return;
		}

		$.post(
			frcGuestCapture.ajaxUrl,
			{
				action : 'frc_capture_guest_email',
				nonce  : frcGuestCapture.nonce,
				email  : email,
			},
			function ( response ) {
				if ( response.success ) {
					$msg.addClass( 'frc-success' ).text( response.data.message );
					setTimeout( hidePopup, 1500 );
				} else {
					$msg.addClass( 'frc-error' ).text( response.data.message );
				}
			}
		);
	} );

} )( jQuery );
