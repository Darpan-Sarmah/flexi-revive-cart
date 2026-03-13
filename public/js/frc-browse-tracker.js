/* global frcBrowse, jQuery */
( function ( $ ) {
	'use strict';

	// Track product page view once.
	if ( frcBrowse && frcBrowse.productId ) {
		$.post(
			frcBrowse.ajaxUrl,
			{
				action     : 'frc_track_browse',
				nonce      : frcBrowse.nonce,
				product_id : frcBrowse.productId,
			}
		);
	}

} )( jQuery );
