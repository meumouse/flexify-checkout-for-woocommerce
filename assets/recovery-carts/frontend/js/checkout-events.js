( function($) {
	"use strict";

	/**
	 * Get checkout events params
	 * 
	 * @since 1.0.0
	 * @return Object
	 */
	const params = fcrc_checkout_params || {};

	/**
	 * Checkout events object variable
	 * 
	 * @since 1.0.0
	 * @package MeuMouse.com
	 */
	var Checkout_Events = {
        
        /**
         * Set cookie value
         * 
         * @since 1.0.0
         * @param {string} name | Cookie name
         * @param {string} value | Cookie value
         * @param {int} days | Expiration time in days
         */
        setCookie: function(name, value, days) {
            let expires = "";

            if (days) {
                let date = new Date();

                date.setTime( date.getTime() + ( days * 24 * 60 * 60 * 1000 ) );
                expires = "; expires=" + date.toUTCString();
            }

            document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/";
        },

        /**
         * Get cookie value by name
         * 
         * @since 1.0.0
         * @param {string} name | Cookie name
         * @returns Cookie value
         */
        getCookie: function(name) {
            let matches = document.cookie.match(new RegExp(
                "(?:^|; )" + name.replace(/([\.\$?*|{}\(\)\[\]\/+^])/g, '\\$1') + "=([^;]*)"
            ));

            return matches ? decodeURIComponent(matches[1]) : undefined;
        },

        /**
		 * Bind checkout field events
		 * 
		 * @since 1.0.0
		 */
		bindEvents: function() {
			let self = this;
			let timeout = null;

			// Detect changes in the checkout fields
			$('#billing_first_name, #billing_last_name, #billing_phone, #billing_email').on('input', function() {
				clearTimeout(timeout);
				
				timeout = setTimeout( function() {
					self.collectLeadData();
				}, 2000); // Delay to prevent multiple requests
			});
		},

		/**
		 * Fill  checkout fields on load page
		 * 
		 * @since 1.1.0
		 */
		fillCheckoutFields: function() {
			let first_name = Checkout_Events.getCookie('fcrc_first_name');
			let last_name = Checkout_Events.getCookie('fcrc_last_name');
			let phone = Checkout_Events.getCookie('fcrc_phone');
			let email = Checkout_Events.getCookie('fcrc_email');

			if ( $('#billing_first_name').val() === '' ) {
				$('#billing_first_name').val(first_name);
			}

			if ( $('#billing_last_name').val() === '' ) {
				$('#billing_last_name').val(last_name);
			}

			if ( $('#billing_phone').val() === '' ) {
				$('#billing_phone').val(phone);
			}

			if ( $('#billing_email').val() === '' ) {
				$('#billing_email').val(email);
			}
		},

		/**
		 * Collect lead data and send via AJAX
		 * 
		 * @since 1.0.0
		 * @version 1.3.0
		 */
		collectLeadData: function() {
			let first_name = $('#billing_first_name').val();
			let last_name = $('#billing_last_name').val();
			let phone = $('#billing_phone').val();
			let email = $('#billing_email').val();

			// Validate required fields
			if ( ! first_name || ! phone || ! email ) {
				return;
			}

			// Avoid duplicate submission using cookies
			let stored_email = Checkout_Events.getCookie('fcrc_lead_email');

			if ( stored_email && stored_email === email ) {
				return;
			}

			// Save email in cookie to prevent duplicate submissions
			Checkout_Events.setCookie('fcrc_lead_email', email, 7);

			// Send AJAX request
			$.ajax({
				url: params.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'fcrc_save_checkout_lead',
					first_name: first_name,
					last_name: last_name,
					phone: phone,
					email: email,
					cart_id: Checkout_Events.getCookie('fcrc_cart_id'),
					ip_data: JSON.stringify( Events.getCookie('fcrc_location') ),
				},
				success: function(response) {
					if (response.success) {
						console.log('Lead data saved successfully');
					} else {
						console.log('Failed to save lead:', response.data);
					}
				},
				error: function(xhr, status, error) {
					console.log('AJAX error:', error);
				},
			});
		},

		/**
		 * Initialize object functions
		 * 
		 * @since 1.0.0
		 * @version 1.1.0
		 */
		init: function() {
			this.bindEvents();
			this.fillCheckoutFields();
		},
    }

    // Initialize the object on ready event
	jQuery(document).ready( function($) {
		Checkout_Events.init();
	});
})(jQuery);