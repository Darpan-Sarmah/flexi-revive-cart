/* global frcDeactivate, jQuery */
( function ( $ ) {
	'use strict';

	/**
	 * Deactivation-feedback modal for Flexi Revive Cart.
	 *
	 * Intercepts the "Deactivate" link on the Plugins page and shows
	 * a dialog asking whether to delete all plugin data or keep it.
	 */
	$( document ).ready( function () {
		var deactivateUrl = '';
		var pluginSlug    = frcDeactivate.basename;

		// Build the modal markup.
		var modalHtml =
			'<div id="frc-deactivate-overlay" class="frc-deactivate-overlay" style="display:none;">' +
				'<div class="frc-deactivate-modal">' +
					'<h2>' + frcDeactivate.i18n.title + '</h2>' +
					'<p>' + frcDeactivate.i18n.message + '</p>' +
					'<div class="frc-deactivate-actions">' +
						'<button id="frc-deactivate-delete" class="button button-link-delete">' + frcDeactivate.i18n.deleteBtn + '</button>' +
						'<button id="frc-deactivate-keep" class="button button-primary">' + frcDeactivate.i18n.keepBtn + '</button>' +
						'<button id="frc-deactivate-cancel" class="button">' + frcDeactivate.i18n.cancelBtn + '</button>' +
					'</div>' +
				'</div>' +
			'</div>';

		$( 'body' ).append( modalHtml );

		var $overlay = $( '#frc-deactivate-overlay' );

		// Locate the deactivate link for this plugin.
		var $deactivateLink = $( '#deactivate-' + pluginSlug.replace( /\//g, '-' ).replace( /\./g, '-' ) );

		// Fallback: find any deactivate link within our plugin's row.
		if ( ! $deactivateLink.length ) {
			var pluginDir = pluginSlug.split( '/' )[ 0 ] || '';
			$deactivateLink = $( 'tr[data-plugin="' + pluginSlug + '"] .deactivate a,' +
				'tr[data-slug="' + pluginDir + '"] .deactivate a' );
		}

		if ( ! $deactivateLink.length ) {
			return;
		}

		$deactivateLink.on( 'click', function ( e ) {
			e.preventDefault();
			deactivateUrl = $( this ).attr( 'href' );
			$overlay.fadeIn( 150 );
		} );

		// Cancel – close modal.
		$( '#frc-deactivate-cancel' ).on( 'click', function () {
			$overlay.fadeOut( 150 );
		} );

		// Close on overlay background click.
		$overlay.on( 'click', function ( e ) {
			if ( e.target === this ) {
				$overlay.fadeOut( 150 );
			}
		} );

		// Keep data – just deactivate.
		$( '#frc-deactivate-keep' ).on( 'click', function () {
			window.location.href = deactivateUrl;
		} );

		// Delete data – AJAX cleanup then deactivate.
		$( '#frc-deactivate-delete' ).on( 'click', function () {
			var $btn = $( this );
			$btn.prop( 'disabled', true ).text( frcDeactivate.i18n.cleaning );
			$( '#frc-deactivate-keep, #frc-deactivate-cancel' ).prop( 'disabled', true );

			$.post(
				frcDeactivate.ajaxUrl,
				{
					action : 'frc_cleanup_data',
					nonce  : frcDeactivate.nonce,
				},
				function ( response ) {
					if ( response.success ) {
						window.location.href = deactivateUrl;
					} else {
						window.alert( frcDeactivate.i18n.cleanError );
						$btn.prop( 'disabled', false ).text( frcDeactivate.i18n.deleteBtn );
						$( '#frc-deactivate-keep, #frc-deactivate-cancel' ).prop( 'disabled', false );
					}
				}
			).fail( function () {
				window.alert( frcDeactivate.i18n.cleanError );
				$btn.prop( 'disabled', false ).text( frcDeactivate.i18n.deleteBtn );
				$( '#frc-deactivate-keep, #frc-deactivate-cancel' ).prop( 'disabled', false );
			} );
		} );
	} );
} )( jQuery );
