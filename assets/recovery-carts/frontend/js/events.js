( function($) {
	"use strict";

	/**
	 * Flexify Checkout Recovery Carts events params
	 * 
	 * @since 1.0.0
	 * @return Object
	 */
	const params = fcrc_events_params || {};

    if ( params.debug_mode ) {
        console.log('[DEBUG] FCRC events params:', params);
    }

    /**
     * Get country data
     * 
     * @since 1.0.0
     * @returns {object}
     */
    var country = {};

	/**
	 * Flexify Checkout Recovery Carts events object variable
	 * 
	 * @since 1.0.0
	 * @package MeuMouse.com
	 */
	const Events = {
        
        /**
		 * Keep button width and height state
		 * 
		 * @since 1.0.0
		 * @param {object} btn | Button object
		 * @returns {object}
		 */
		keepButtonState: function(btn) {
			var btn_width = btn.width();
			var btn_height = btn.height();
			var btn_html = btn.html();
	  
			// keep original width and height
			btn.width(btn_width);
			btn.height(btn_height);
	  
			return {
				width: btn_width,
				height: btn_height,
				html: btn_html,
			};
	  	},

        /**
         * Open pre checkout modal
         * 
         * @since 1.0.0
         * @version 1.1.0
         */
        openModal: function() {
            var triggers = params.triggers_list;

            // open modal using event delegation
            $(triggers).hover( function(e) {
                // check if lead was already collected or rejected
                if ( $('body').hasClass('fcrc-lead-collected') || $('body').hasClass('fcrc-lead-rejected') || Events.getCookie('fcrc_lead_collected') === 'yes' ) {
                    return;
                }

                $('.fcrc-popup-container.lead-capture-modal').addClass('show');
            });

            // close modal
            $(document).on('click', '.fcrc-popup-close', function(e) {
                e.preventDefault();

                $('.fcrc-popup-container.lead-capture-modal').removeClass('show');
                $('body').addClass('fcrc-lead-rejected')
            });
        },

        /**
         * Collect lead event
         * 
         * @since 1.0.0
         * @version 1.3.0
         */
        collectLead: function() {
            // send request on click trigger button
            $(document).on('click', '.fcrc-trigger-send-lead', function(e) {
                e.preventDefault();

                const btn = $(this);
                const btn_state = Events.keepButtonState(btn);
                var get_first_name = $('.fcrc-get-first-name').val() || Events.getCookie('fcrc_first_name');
                var get_last_name = $('.fcrc-get-last-name').val() || Events.getCookie('fcrc_last_name');
                var get_phone = $('.fcrc-get-phone').val() || Events.getCookie('fcrc_phone');
                var get_email = $('.fcrc-get-email').val() || Events.getCookie('fcrc_email');
                var get_cart_id = Events.getCookie('fcrc_cart_id') || '';

                // send ajax request
                $.ajax({
                    url: params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'fcrc_lead_collected',
                        first_name: get_first_name,
                        last_name: get_last_name,
                        phone: get_phone,
                        email: get_email,
                        country_data: JSON.stringify(country),
                        cart_id: get_cart_id,
                        ip_data: JSON.stringify( Events.getCookie('fcrc_location') ),
                    },
                    beforeSend: function() {
                        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                    },
                    success: function (response) {
                        if ( params.debug_mode ) {
                            console.log(response);
                        }
                        
                        try {
                            if ( response.status === 'success' ) {
                                $('body').addClass('fcrc-lead-collected');
                                $('.fcrc-popup-container.lead-capture-modal').removeClass('show');

                                // save post id on cookie for 7 days
                                Events.setCookie('fcrc_cart_id', response.cart_id, 7);

                                // save lead data on cookie for 30 days
                                Events.setCookie('fcrc_first_name', get_first_name, 365);
                                Events.setCookie('fcrc_last_name', get_last_name, 365);
                                Events.setCookie('fcrc_phone', get_phone, 365);
                                Events.setCookie('fcrc_email', get_email, 365);
                                Events.setCookie('fcrc_lead_collected', 'yes', 365);
                            } else {
                                $('body').removeClass('fcrc-lead-collected');
                            }
                        } catch (error) {
                            console.log(error);
                        }
                    },
                    error: function () {
                        console.log('Error on send lead data');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(btn_state.html);
                    },
                });
            });
        },

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
         * Delete cookie by name
         * 
         * @since 1.3.0
         * @param {string} name | Cookie name
         */
        deleteCookie: function(name) {
            document.cookie = name + '=; Max-Age=0; path=/; domain=' + window.location.hostname;
        },

        /**
         * Initialize international phone input
         * 
         * @since 1.0.0
         * @version 1.3.0
         */
        internationalPhone: function() {
            const input = document.querySelector('.fcrc-input.fcrc-get-phone');

            if ( ! input ) {
                if ( params.debug_mode ) {
                    console.warn('fcrc-get-phone input not found');
                }
                
                return;
            }

            // initialize intl tel input
            const iti = window.intlTelInput( input, {
                loadUtilsOnInit: params.path_to_utils,
                autoPlaceholder: "aggressive",
                containerClass: "fcrc-international-phone-selector",
                initialCountry: "auto",
                /**
                 * Get user country code for phone input based on IP location
                 * 
                 * @since 1.0.0
                 * @version 1.3.0
                 * @param {function} success - Callback function to execute on success with country code
                 * @param {function} failure - Callback function to execute on failure with fallback country code
                 */
                geoIpLookup: async function(success, failure) {
                    const cached_code = Events.getCookie('fcrc_phone_country_code');

                    if ( cached_code ) {
                        success(cached_code);
                    } else {
                        const location = await Events.getLocationData();

                        if ( location && location.country_code ) {
                            Events.setCookie('fcrc_phone_country_code', location.country_code, 7);
                            success(location.country_code);
                        } else {
                            failure('br');
                        }
                    }
                },
                i18n: {
                    searchPlaceholder: params.i18n.intl_search_input_placeholder,
                },
            });

            // Get current country data after initialization
            setTimeout(() => {
                country = iti.getSelectedCountryData();
            }, 500);
        },

        /**
         * Get IP address
         * 
         * @since 1.3.0
         * @returns {Promise} | Returns a Promise with the IP address
         */
        getIpAddress: function() {
            const url = params.ip_settings.get_ip;

            return fetch(url, { cache: 'no-store' })
                .then(response => {
                    if ( ! response.ok ) {
                        throw new Error('Network response was not ok');
                    }

                    return response.json();
                })
                .then(data => {
                    return data.ip;
                })
                .catch(error => {
                    console.error('Failed to fetch IP:', error);
                    return null;
                });
        },

        /**
         * Get user location data
         * 
         * @since 1.3.0
         * @returns {Promise<object>} | Returns a Promise with the country data
         */
        getLocationData: async function() {
            let location_data = Events.getCookie('fcrc_location');

            if ( params.debug_mode ) {
                console.log('Country data cached:', location_data);
            }

            // Check if cached data exists and is valid
            if ( location_data ) {
                try {
                    location_data = JSON.parse(location_data);

                    const is_invalid = ! location_data.ip || location_data.ip === 'undefined' || location_data.ip === 'null';

                    if ( ! is_invalid ) {
                        location_data.cache = true;

                        return location_data;
                    } else {
                        Events.deleteCookie('fcrc_location');
                        console.log('Invalid location cookie detected and removed.');
                    }
                } catch (e) {
                    Events.deleteCookie('fcrc_location');
                    console.log('Corrupted location cookie detected and removed.');
                }
            }

            // If no valid cache, fetch new data
            try {
                const get_ip = await Events.getIpAddress();
                const ip_data = params.ip_settings;
                const ip_api_url = ip_data.ip_url.replace('{ip_address}', get_ip) + '?_=' + new Date().getTime();

                const response = await fetch(ip_api_url, { cache: 'no-store' });
                const data = await response.json();

                const country_data = {
                    country_code: data[ip_data.country_code] || '',
                    country_name: data[ip_data.country_name] || '',
                    region: data[ip_data.state_name] || '',
                    city: data[ip_data.city_name] || '',
                    ip: data[ip_data.ip_returned] || '',
                };

                if ( params.debug_mode ) {
                    console.log('Country data:', country_data);
                }

                Events.setCookie('fcrc_location', JSON.stringify(country_data), 7);

                return country_data;

            } catch (error) {
                console.error("Error fetching location:", error);
                return null;
            }
        },

        /**
         * Get user location via IP and send data to backend
         * 
         * @since 1.0.1
         * @version 1.3.0
         */
        getUserLocation: async function() {
            const location = await Events.getLocationData();

            if ( location ) {
                Events.sendLocationData(location);
            }
        },

        /**
         * Send user location data to backend via AJAX
         * 
         * @since 1.0.1
         * @param {object} countryData
         */
        sendLocationData: function(countryData) {
            let cart_id = Events.getCookie('fcrc_cart_id');

            if ( ! cart_id ) {
                return;
            }

            // send request
            $.ajax({
                url: params.ajax_url,
                type: 'POST',
                data: {
                    action: 'fcrc_update_location',
                    cart_id: cart_id,
                    country_data: JSON.stringify(countryData)
                },
                success: function(response) {
                    if (params.debug_mode) {
                        console.log("Location data sent:", response);
                    }
                },
                error: function() {
                    console.error("Error sending location data.");
                }
            });
        },

        /**
		 * Initialize object functions
		 * 
		 * @since 1.0.0
         * @version 1.3.0
		 */
		init: function() {
			this.collectLead();

            // check if collect data from IP is enabled
            if ( params.ip_settings.enabled === 'yes' ) {
                this.getUserLocation();
            }

            // check if current page has product
            if ( params.is_product ) {
                this.openModal();
            }

            // check if international phone input is enabled
            if ( params.enable_international_phone === 'yes' && params.is_product ) {
                this.internationalPhone();
            }
		},
    }

    // Initialize the Settings object on ready event
	jQuery(document).ready( function($) {
		Events.init();
	});
})(jQuery);