/**
 * Auto-refresh the recovery carts admin table when a new cart
 * (status: shopping/lead) is created. Detection is server-side via the
 * cart-creation hook; this script polls a lightweight endpoint and only
 * fetches the table HTML when the change marker advances.
 *
 * @since 1.4.0
 */
(function($) {
	'use strict';

	var params = window.fcrc_carts_table_params || {};
	var $wrapper = $('#fcrc-carts-table-wrapper');

	if ( ! $wrapper.length || ! params.ajax_url ) {
		return;
	}

	var lastChange = parseInt( $wrapper.attr('data-last-change'), 10 ) || 0;
	var pollInterval = Math.max( 3000, parseInt( params.poll_interval, 10 ) || 10000 );
	var refreshing = false;

	function getFilters() {
		var search = new URLSearchParams( window.location.search );

		return {
			page: search.get('page') || 'fc-recovery-carts-list',
			post_status: search.get('post_status') || '',
			paged: search.get('paged') || '',
			orderby: search.get('orderby') || '',
			order: search.get('order') || '',
			s: $wrapper.find('input[name="s"]').val() || search.get('s') || '',
		};
	}

	function refreshTable() {
		if ( refreshing ) {
			return;
		}

		refreshing = true;

		$.ajax({
			url: params.ajax_url,
			type: 'POST',
			data: $.extend({
				action: 'fcrc_refresh_carts_table',
				nonce: params.ajax_nonce,
			}, getFilters()),
			success: function(response) {
				if ( response && response.status === 'success' && response.html ) {
					$wrapper.html( response.html );

					if ( response.last_change ) {
						lastChange = parseInt( response.last_change, 10 ) || lastChange;
						$wrapper.attr( 'data-last-change', lastChange );
					}
				}
			},
			error: function(xhr) {
				if ( params.debug_mode ) {
					console.error( 'fcrc_refresh_carts_table failed', xhr.responseText );
				}
			},
			complete: function() {
				refreshing = false;
			},
		});
	}

	function checkForChanges() {
		$.ajax({
			url: params.ajax_url,
			type: 'POST',
			data: {
				action: 'fcrc_get_carts_table_changes',
				nonce: params.ajax_nonce,
			},
			success: function(response) {
				if ( ! response || response.status !== 'success' ) {
					return;
				}

				var latest = parseInt( response.last_change, 10 ) || 0;

				if ( latest > lastChange ) {
					refreshTable();
				}
			},
		});
	}

	setInterval( checkForChanges, pollInterval );
})(jQuery);
