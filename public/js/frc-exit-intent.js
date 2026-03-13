/* global frcExitIntent, jQuery */
( function ( $ ) {
	'use strict';

	var SESSION_KEY  = 'frc_ei_shown';
	var $overlay     = $( '#frc-exit-intent-overlay' );
	var triggered    = false;

	if ( ! $overlay.length ) {
		return;
	}

	if ( sessionStorage.getItem( SESSION_KEY ) ) {
		return;
	}

	/**
	 * Show the exit-intent popup.
	 */
	function showPopup() {
		if ( triggered ) { return; }
		triggered = true;
		sessionStorage.setItem( SESSION_KEY, '1' );
		$overlay.addClass( 'frc-popup-visible' );
	}

	/**
	 * Hide the popup.
	 */
	function hidePopup() {
		$overlay.removeClass( 'frc-popup-visible' );
	}

	// Desktop: detect mouse leaving viewport via top edge.
	$( document ).on( 'mouseleave', function ( e ) {
		if ( e.clientY < 5 ) {
			showPopup();
		}
	} );

	// Mobile: detect rapid scroll-up (back button intent).
	var lastScrollY    = window.scrollY;
	var scrollUpCount  = 0;

	$( window ).on( 'scroll', function () {
		var currentY = window.scrollY;
		if ( currentY < lastScrollY ) {
			scrollUpCount++;
			if ( scrollUpCount >= 3 ) {
				showPopup();
			}
		} else {
			scrollUpCount = 0;
		}
		lastScrollY = currentY;
	} );

	// Close handlers.
	$overlay.on( 'click', '.frc-popup-close, .frc-popup-dismiss, .frc-popup-overlay', function ( e ) {
		if ( $( e.target ).is( '.frc-popup-overlay, .frc-popup-close, .frc-popup-dismiss' ) ) {
			hidePopup();
		}
	} );

	// Form submit (reuses guest-capture AJAX action).
	$overlay.on( 'submit', '#frc-exit-intent-form', function ( e ) {
		e.preventDefault();

		var email = $( '#frc-ei-email' ).val().trim();
		var $msg  = $( '#frc-ei-message' );

		if ( ! email ) { return; }

		$.post(
			frcExitIntent.ajaxUrl,
			{
				action : 'frc_capture_guest_email',
				nonce  : frcExitIntent.nonce,
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
