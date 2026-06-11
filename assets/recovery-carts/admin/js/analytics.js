( function($) {
	"use strict";

	/**
	 * Get checkout events params
	 * 
	 * @since 1.3.0
	 * @return Object
	 */
	const params = fcrc_analytics_params || {};

	/**
	 * Checkout events object variable
	 * 
	 * @since 1.3.0
	 * @package MeuMouse.com
	 */
	var Analytics = {

        /**
		 * Add query params on URL
		 * 
		 * @since 1.3.0
		 * @param {string} param | Parameter name
		 * @param {string} value | Parameter value
         * @return void
		 */
		addQueryParam: function(param, value) {
			// get current URL
			var url = new URL(window.location.href);

			// add or update URL params
			url.searchParams.set( param, value );

			// update URL without reload page
			window.history.pushState( {}, '', url );
		},
    
        /**
		 * Get URL parameter by name
		 * 
		 * @since 1.3.0
		 * @param {string} name | Parameter name
		 * @returns Parameter value
		 */
		getParamByName: function(name) {
			let url = window.location.href;
			name = name.replace(/[\[\]]/g, "\\{text}");
			let regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"), results = regex.exec(url);

			if (!results) return null;
			if (!results[2]) return '';
			
			return decodeURIComponent( results[2].replace(/\+/g, " ") );
		},

        /**
         * Select period filter for analytics
         * 
         * @since 1.3.0
         * @return void
         */
        selectPeriodFilter: function() {
            let get_period = Analytics.getParamByName('period');

            // set active period filter
            if ( get_period ) {
                $(`.period-filter-item[data-period="${get_period}"]`).addClass('active');
            } else {
                $('.period-filter-item').first().addClass('active');
            }

            // set period filter
            $(document).on('click', '.period-filter-item', function(e) {
                e.preventDefault();

                let btn = $(this);
                let get_period = $(this).data('period');

                $('.period-filter-item').removeClass('active');
                $('.fcrc-analytics-widget').addClass('placeholder-content');
                btn.addClass('active');

                // add query param to URL
                Analytics.addQueryParam( 'period', get_period );
                Analytics.fetchData(get_period);
            });
        },

        /**
         * Fetch analytics data from backend
         * 
         * @since 1.3.0
         * @version 1.3.5
         * @param {int} days | Number of days to fetch data
         * @return void
         */
        fetchData: function( days ) {
            $.ajax({
                url: params.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'fcrc_get_analytics_data',
                    nonce: params.ajax_nonce,
                    period: days,
                },
                success: function(response) {
                    if (response.success) {
                        $('.fcrc-analytics-widget').removeClass('placeholder-content');
                        $('.fcrc-analytics-container-item.get-total-recovered').html(response.data.total_recovered_widget);

                        // render chart for total recovered
                        Analytics.recoveredTotalChart(
                            response.data.recovered_chart.labels,
                            response.data.recovered_chart.series
                        );

                        const cart_status_widget = $('.fcrc-analytics-container-item.get-cart-status');

                        cart_status_widget.html(response.data.cart_statuses_widget);

                        // add cart statuses data
                        cart_status_widget.find('.fcrc-carts-group-item.shopping').children('.fcrc-cart-item-title').html(response.data.counts.shopping);
                        cart_status_widget.find('.fcrc-carts-group-item.abandoned').children('.fcrc-cart-item-title').html(response.data.counts.abandoned);
                        cart_status_widget.find('.fcrc-carts-group-item.recovered').children('.fcrc-cart-item-title').html(response.data.counts.recovered);
                        cart_status_widget.find('.fcrc-carts-group-item.lost').children('.fcrc-cart-item-title').html(response.data.counts.lost);
                        cart_status_widget.find('.fcrc-carts-group-item.leads').children('.fcrc-cart-item-title').html(response.data.counts.lead);
                        cart_status_widget.find('.fcrc-carts-group-item.order_abandoned').children('.fcrc-cart-item-title').html(response.data.counts.order_abandoned);

                        // render sent notifications widget
                        $('.fcrc-analytics-container-item.sent-notifications').html(response.data.notifications_chart_widget);

                        // render chart for sent notifications widget
                        Analytics.sentNotificationsChart(
                            response.data.notifications_chart.categories,
                            response.data.notifications_chart.series
                        );
                    }
                },
                error: function() {
                    console.error('Error on load analytics data from backend');
                },
            });
        },

        /**
         * Render recovered total chart
         * 
         * @since 1.3.0
         * @param {array} labels | Chart labels
         * @param {array} seriesData | Chart series data
         * @return void
         */
        recoveredTotalChart: function( labels, seriesData ) {
            const options = {
                chart: {
                    type: 'line',
                    height: 320,
                    toolbar: {
                        show: false
                    },
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                series: [{
                    name: params.i18n.total_recovered,
                    data: seriesData,
                }],
                xaxis: {
                    categories: labels,
                    labels: {
                        style: {
                            fontSize: '13px'
                        },
                    },
                },
                yaxis: {
                    labels: {
                        formatter: function(val) {
                            return Analytics.formatCurrency(val);
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return Analytics.formatCurrency(val);
                        }
                    }
                }
            };

            this.totalChart = new ApexCharts( document.querySelector("#fcrc-recovered-chart"), options );
            this.totalChart.render();
        },

        /**
         * Render sent notifications chart
         *
         * @since 1.3.0
         * @param {string[]} labels | Array de datas (YYYY-MM-DD)
         * @param {Object[]} seriesData | Array de { name: canal, data: [número,...] }
         * @return void
         */
        sentNotificationsChart: function( labels, seriesData ) {
            const options = {
                chart: {
                    type: 'bar',
                    height: 320,
                    toolbar: {
                        show: false,
                    },
                },
                series: seriesData.map(serie => ({
                    name: serie.name,
                    data: serie.data,
                })),
                xaxis: {
                    categories: labels,
                    labels: {
                        style: {
                            fontSize: '13px'
                        },
                    },
                },
                yaxis: {
                    title: {
                        text: params.i18n.notifications_chart,
                    },
                },
                tooltip: {
                    y: {
                        formatter: val => String(val),
                    },
                },
            };

            this.sentChart = new ApexCharts( document.querySelector("#fcrc_sent_notifications_chart"), options );
            this.sentChart.render();
        },

        /**
         * Format currency value according to WooCommerce settings
         * 
         * @since 1.3.0
         * @param {number} value | Value to format
         * @returns {string}
         */
        formatCurrency: function( value ) {
            const currency = params.currency || {};
            const symbol = currency.symbol || 'R$';
            const position = currency.position || 'left';
            const decimal = currency.decimal_separator || ',';
            const thousand = currency.thousand_separator || '.';
            const decimals = typeof currency.decimals === 'undefined' ? 2 : currency.decimals;

            let val = parseFloat(value).toFixed(decimals).toString();

            // Separate integer and decimal parts
            let parts = val.split('.');
            let integer = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousand);
            let decimal_part = parts[1] ? decimal + parts[1] : '';

            let formatted = integer + decimal_part;

            switch ( position ) {
                case 'left':
                    return symbol + formatted;
                case 'right':
                    return formatted + symbol;
                case 'left_space':
                    return symbol + ' ' + formatted;
                case 'right_space':
                    return formatted + ' ' + symbol;
                default:
                    return symbol + formatted;
            }
        },

		/**
		 * Initialize object functions
		 * 
		 * @since 1.3.0
         * @return void
		 */
		init: function() {
            this.selectPeriodFilter();

            let get_period = Analytics.getParamByName('period') || 7;
            this.fetchData(get_period);
		},
    }

    // Initialize the object on ready event
	jQuery(document).ready( function($) {
		Analytics.init();
	});
})(jQuery);