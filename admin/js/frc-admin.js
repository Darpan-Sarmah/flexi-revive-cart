/* global frcAdmin, jQuery */
( function ( $ ) {
	'use strict';

	// ── Settings tab navigation ────────────────────────────────────────────────
	// Already handled by href links + WP nav-tab-wrapper, no extra JS needed.

	// ── Send test email ────────────────────────────────────────────────────────
	$( '#frc-send-test-email' ).on( 'click', function () {
		var $btn    = $( this );
		var to      = $( '#frc-test-email-to' ).val();
		var stage   = $( '#frc-test-email-stage' ).val();
		var $result = $( '#frc-test-email-result' );

		// Free version: block stages 2 & 3 on the client side as well.
		if ( ! frcAdmin.proActive && parseInt( stage, 10 ) > 1 ) {
			$result.css( 'color', '#d63638' ).text( 'Urgency and incentive test emails require a Pro license.' );
			return;
		}

		$btn.prop( 'disabled', true ).text( frcAdmin.i18n.testEmailSent );

		$.post(
			frcAdmin.ajaxUrl,
			{
				action : 'frc_send_test_email',
				nonce  : frcAdmin.nonce,
				to     : to,
				stage  : stage,
			},
			function ( response ) {
				if ( response.success ) {
					$result.css( 'color', '#00a32a' ).text( response.data.message );
				} else {
					$result.css( 'color', '#d63638' ).text( response.data && response.data.message ? response.data.message : frcAdmin.i18n.error );
				}
			}
		).always( function () {
			$btn.prop( 'disabled', false ).text( 'Send Test' );
		} );
	} );

	// ── Delete cart ────────────────────────────────────────────────────────────
	$( document ).on( 'click', '.frc-delete-cart', function () {
		if ( ! window.confirm( frcAdmin.i18n.confirmDelete ) ) {
			return;
		}

		var $btn    = $( this );
		var cartId  = $btn.data( 'id' );
		var nonce   = $btn.data( 'nonce' );

		$.post(
			frcAdmin.ajaxUrl,
			{
				action  : 'frc_delete_cart',
				nonce   : nonce,
				cart_id : cartId,
			},
			function ( response ) {
				if ( response.success ) {
					$btn.closest( 'tr' ).fadeOut( 300, function () { $( this ).remove(); } );
				} else {
					window.alert( frcAdmin.i18n.error );
				}
			}
		);
	} );

	// ── Resend reminder ────────────────────────────────────────────────────────
	$( document ).on( 'click', '.frc-resend-reminder', function () {
		var $btn   = $( this );
		var cartId = $btn.data( 'id' );
		var nonce  = $btn.data( 'nonce' );

		$btn.prop( 'disabled', true );

		$.post(
			frcAdmin.ajaxUrl,
			{
				action  : 'frc_resend_reminder',
				nonce   : nonce,
				cart_id : cartId,
			},
			function ( response ) {
				if ( response.success ) {
					window.alert( response.data.message );
				} else {
					window.alert( frcAdmin.i18n.error );
				}
			}
		).always( function () {
			$btn.prop( 'disabled', false );
		} );
	} );

	// ── Email editor: insert variable ──────────────────────────────────────────
	$( document ).on( 'click', '.frc-insert-var', function () {
		var varText    = $( this ).data( 'var' );
		var $subjectEl = $( '#frc_template_subject' );

		// If subject field is focused, insert into subject instead of body.
		if ( $subjectEl.length && $subjectEl.is( ':focus' ) ) {
			var subjectField = $subjectEl[ 0 ];
			var startPos     = subjectField.selectionStart;
			var endPos       = subjectField.selectionEnd;
			var currentVal   = $subjectEl.val();
			$subjectEl.val( currentVal.substring( 0, startPos ) + varText + currentVal.substring( endPos ) );
			subjectField.selectionStart = subjectField.selectionEnd = startPos + varText.length;
			return;
		}

		if ( typeof window.tinyMCE !== 'undefined' && window.tinyMCE.activeEditor ) {
			window.tinyMCE.activeEditor.execCommand( 'mceInsertContent', false, varText );
		} else {
			var $ta = $( '#frc_template_content' );
			if ( $ta.length ) {
				var pos     = $ta[ 0 ].selectionStart;
				var val     = $ta.val();
				$ta.val( val.substring( 0, pos ) + varText + val.substring( pos ) );
			}
		}
	} );

} )( jQuery );
