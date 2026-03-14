/**
 * Flexi Revive Cart Pro – Popup JavaScript
 */
(function($) {
	'use strict';

	if ( typeof frcProPopups === 'undefined' ) {
		return;
	}

	var popupShown = false;
	var STORAGE_KEY = 'frc_popup_dismissed';

	// Don't show if already dismissed in this session.
	if ( sessionStorage.getItem( STORAGE_KEY ) ) {
		return;
	}

	/**
	 * Show the guest capture popup after a delay.
	 */
	function initGuestCapture() {
		if ( ! frcProPopups.enableGuestCapture ) {
			return;
		}

		var delay = ( frcProPopups.guestCaptureDelay || 5 ) * 1000;
		setTimeout( function() {
			if ( ! popupShown ) {
				showPopup( '#frc-guest-capture-overlay' );
			}
		}, delay );
	}

	/**
	 * Show the exit-intent popup when the mouse leaves the viewport.
	 */
	function initExitIntent() {
		if ( ! frcProPopups.enableExitIntent ) {
			return;
		}

		$( document ).on( 'mouseleave', function( e ) {
			if ( e.clientY < 10 && ! popupShown ) {
				showPopup( '#frc-exit-intent-overlay' );
			}
		});
	}

	/**
	 * Show a popup overlay.
	 */
	function showPopup( selector ) {
		var $popup = $( selector );
		if ( $popup.length ) {
			$popup.fadeIn( 300 );
			popupShown = true;
		}
	}

	/**
	 * Close a popup overlay.
	 */
	function closePopup( $overlay ) {
		$overlay.fadeOut( 200 );
		sessionStorage.setItem( STORAGE_KEY, '1' );
	}

	// Close handlers.
	$( '.frc-popup-close, .frc-popup-dismiss' ).on( 'click', function() {
		closePopup( $( this ).closest( '.frc-popup-overlay' ) );
	});

	// Close on overlay click.
	$( '.frc-popup-overlay' ).on( 'click', function( e ) {
		if ( $( e.target ).hasClass( 'frc-popup-overlay' ) ) {
			closePopup( $( this ) );
		}
	});

	/**
	 * Handle form submission for both popups.
	 */
	function handleFormSubmit( formSelector, emailSelector, messageSelector ) {
		$( formSelector ).on( 'submit', function( e ) {
			e.preventDefault();

			var email = $( emailSelector ).val().trim();
			var $message = $( messageSelector );

			if ( ! email || ! /\S+@\S+\.\S+/.test( email ) ) {
				$message.css( 'color', '#d63638' ).text( 'Please enter a valid email address.' );
				return;
			}

			$message.css( 'color', '#666' ).text( 'Saving...' );

			$.post( frcProPopups.ajaxUrl, {
				action: 'frc_capture_guest_email',
				nonce:  frcProPopups.nonce,
				email:  email,
			}, function( response ) {
				if ( response.success ) {
					$message.css( 'color', '#46b450' ).text( response.data.message );
					setTimeout( function() {
						closePopup( $( formSelector ).closest( '.frc-popup-overlay' ) );
					}, 2000 );
				} else {
					$message.css( 'color', '#d63638' ).text( response.data.message );
				}
			}).fail( function() {
				$message.css( 'color', '#d63638' ).text( 'Request failed. Please try again.' );
			});
		});
	}

	// Bind form handlers.
	handleFormSubmit( '#frc-guest-capture-form', '#frc-gc-email', '#frc-gc-message' );
	handleFormSubmit( '#frc-exit-intent-form', '#frc-ei-email', '#frc-ei-message' );

	// Initialize popups.
	$( document ).ready( function() {
		initGuestCapture();
		initExitIntent();
	});

})(jQuery);
