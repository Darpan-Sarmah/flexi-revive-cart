/* global frcCharts, jQuery, Chart */
( function ( $ ) {
	'use strict';

	if ( typeof Chart === 'undefined' ) {
		return;
	}

	// Fetch chart data from the server, then render.
	$.post(
		frcCharts.ajaxUrl,
		{
			action : 'frc_get_chart_data',
			nonce  : frcCharts.nonce,
		},
		function ( response ) {
			if ( ! response.success ) {
				return;
			}

			var data = response.data;

			// ── Line chart: Abandoned vs Recovered ──────────────────────────────
			var lineCtx = document.getElementById( 'frc-line-chart' );
			if ( lineCtx && data.line ) {
				new Chart( lineCtx, {
					type : 'line',
					data : {
						labels   : data.line.labels,
						datasets : [
							{
								label           : 'Abandoned',
								data            : data.line.abandoned,
								borderColor     : '#d63638',
								backgroundColor : 'rgba(214,54,56,.1)',
								tension         : 0.3,
								fill            : true,
							},
							{
								label           : 'Recovered',
								data            : data.line.recovered,
								borderColor     : '#46b450',
								backgroundColor : 'rgba(70,180,80,.1)',
								tension         : 0.3,
								fill            : true,
							},
						],
					},
					options : {
						responsive          : true,
						maintainAspectRatio : false,
						plugins : { legend : { position : 'bottom' } },
						scales  : { y : { beginAtZero : true } },
					},
				} );
			}

			// ── Pie chart: Recovery by channel ──────────────────────────────────
			var pieCtx = document.getElementById( 'frc-pie-chart' );
			if ( pieCtx && data.pie && data.pie.length ) {
				var pieLabels = data.pie.map( function ( d ) { return d.recovery_channel || 'unknown'; } );
				var pieCounts = data.pie.map( function ( d ) { return d.count; } );
				new Chart( pieCtx, {
					type : 'pie',
					data : {
						labels   : pieLabels,
						datasets : [ {
							data            : pieCounts,
							backgroundColor : [ '#7f54b3', '#46b450', '#00a0d2', '#f56e28' ],
						} ],
					},
					options : {
						responsive          : true,
						maintainAspectRatio : false,
						plugins : { legend : { position : 'bottom' } },
					},
				} );
			}

			// ── Bar chart: Recovery rate by email stage ──────────────────────────
			var barCtx = document.getElementById( 'frc-bar-chart' );
			if ( barCtx && data.bar ) {
				var barLabels = Object.keys( data.bar );
				var barValues = Object.values( data.bar );
				new Chart( barCtx, {
					type : 'bar',
					data : {
						labels   : barLabels,
						datasets : [ {
							label           : 'Recoveries',
							data            : barValues,
							backgroundColor : '#7f54b3',
						} ],
					},
					options : {
						responsive          : true,
						maintainAspectRatio : false,
						plugins : { legend : { display : false } },
						scales  : { y : { beginAtZero : true } },
					},
				} );
			}
		}
	);

} )( jQuery );
