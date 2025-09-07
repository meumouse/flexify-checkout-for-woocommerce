( function($) {
    "use strict";

    /**
     * Get global parameters
     * 
     * @since 5.1.0
     */
    const params = window.flexify_checkout_params || {};

    /**
     * Admin controller for Flexify Checkout
     * 
     * @since 5.1.0
     * @package MeuMouse.com
     */
    var Flexify_Checkout_Admin = {

        /**
         * Keep button width/height/html state (to avoid layout shift)
         * 
         * @since 3.9.8
         * @version 5.1.0
         * @param {jQuery} btn | Button element
         * @returns {{width:number,height:number,html:string}}
         */
        keepButtonState: function(btn) {
            var w = btn.width();
            var h = btn.height();
            var html = btn.html();

            btn.width(w);
            btn.height(h);

            return { width: w, height: h, html: html };
        },

        /**
         * Display toast messages in the wrapper
         * 
         * @since 3.8.0
         * @version 5.1.0
         * @param {'success'|'danger'|'warning'|'error'} type 
         * @param {string} header 
         * @param {string} body 
         */
        displayToast: function(type, header, body) {
			let icon = '';

			if ( type === 'success' ) {
				icon = '<svg class="icon icon-white me-2" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(255, 255, 255, 1);"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path d="M9.999 13.587 7.7 11.292l-1.412 1.416 3.713 3.705 6.706-6.706-1.414-1.414z"></path></svg>';
			} else {
				icon = '<svg class="icon icon-white me-2" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(255, 255, 255, 1);transform: ;msFilter:;"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path d="M11 11h2v6h-2zm0-4h2v2h-2z"></path></svg>';
			}

			if ( type === 'error' ) {
				type = 'danger';
			}

            const toast = `<div class="toast toast-${type} show">
                <div class="toast-header bg-${type} text-white">
					${icon}
					<span class="me-auto">${header || ''}</span>
                    <button class="btn-close btn-close-white ms-2" type="button" aria-label="${params.close_aria_label_notice || 'Close'}"></button>
                </div>
                <div class="toast-body">${body || ''}</div>
            </div>`;

            $('.flexify-checkout-wrapper').before(toast);

			// Hide toasts on click
			$(document).on('click', '.toast .btn-close', function() {
                $('.toast.show').fadeOut('fast');
            });

			// Hide toast after 3 seconds
            setTimeout( function() {
                $(`.toast-${type}`).fadeOut('fast', function() {
                    $(this).remove();
                });
            }, 3000);
        },

        /**
         * Tabs: restore last tab from hash/localStorage and bind click
         * 
         * @since 1.0.0
         * @version 5.1.0
         */
        initTabs: function() {
            // on load
            $(function() {
                let url_hash = window.location.hash;
                let active_tab_index = localStorage.getItem('flexify_checkout_get_admin_tab_index');

                if ( url_hash ) {
                    let target = $(`.flexify-checkout-wrapper a.nav-tab[href="${url_hash}"]`);
                    if ( target.length ) target.click();
                } else if ( active_tab_index !== null ) {
                    $('.flexify-checkout-wrapper a.nav-tab').eq(active_tab_index).click();
                } else {
                    $(`.flexify-checkout-wrapper a.nav-tab[href="#general"]`).click();
                }
            });

            // on click
            $(document).on('click', '.flexify-checkout-wrapper a.nav-tab', function() {
                let idx = $(this).index();
                localStorage.setItem('flexify_checkout_get_admin_tab_index', idx);

                let href = $(this).attr('href');

                $('.flexify-checkout-wrapper a.nav-tab').removeClass('nav-tab-active');
                $('.flexify-checkout-form .nav-content').removeClass('active');
                $(this).addClass('nav-tab-active');
                $('.flexify-checkout-form').find(href).addClass('active');

                return false;
            });
        },

        /**
         * AJAX: save options and enable save button only on changes
         * 
         * @since 1.0.0
         * @version 5.1.0
         */
        saveOptions: function() {
            let settings_form = $('form[name="flexify-checkout"]');
            let original_values = settings_form.serialize();

            // Enable/disable save button on changes
            settings_form.on('change input', 'input, select, textarea', () => {
                $('#flexify_checkout_save_options').prop( 'disabled', settings_form.serialize() === original_values );
            });

            // Save button
            $(document).on('click', '#flexify_checkout_save_options', (e) => {
                e.preventDefault();

                let btn = $(e.currentTarget);
                let state = this.keepButtonState(btn);

                $.ajax({
                    url: params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'flexify_checkout_save_settings',
                        form_data: settings_form.serialize(),
                    },
                    beforeSend: function() {
                        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                    },
                    success: (response) => {
                        try {
                            if ( response.status === 'success' ) {
                                original_values = settings_form.serialize();

								Flexify_Checkout_Admin.displayToast( 'success', response.toast_header_title, response.toast_body_title );
                            } else {
								Flexify_Checkout_Admin.displayToast( 'danger', response.toast_header_title, response.toast_body_title );
							}
                        } catch (err) {
                            console.log(err);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("AJAX request failed:", textStatus, errorThrown);
                    },
                    complete: function() {
                        btn.html(state.html);
                    },
                });
            });
        },

        /**
		 * Generic visibility controllers (checkboxes, selects, arrays, maps)
		 * 
		 * @since 1.0.0
		 * @version 5.1.1
		 */
		setupVisibilityControllers: function() {

			/**
			 * Attach a generic visibility controller
			 * 
			 * - Checkbox: show/hide target if checked
			 * - Select + string target: show if value !== "no"/"false"/""
			 * - Select + array target: show container if value in array
			 * - Select + object target: show container for matching option
			 * 
			 * @since 5.1.0
			 * @param {string|jQuery} triggerSel | Trigger element selector
			 * @param {string|Array|Object} target | Target selector, array of allowed values, or map of options→containers
			 * @param {string} [containerSel] | Optional container when using array mode
			 */
			const attach = (triggerSel, target, containerSel = null) => {
				const $trigger = $(triggerSel);

				const apply = () => {
					const val = $trigger.val();

					// Checkbox
					if ( $trigger.is(':checkbox') ) {
						const on = $trigger.is(':checked');
						
						if ( typeof target === 'string' ) {
							$(target).toggleClass('d-none', ! on);
						}

						return;
					}

					// Map of options → containers
					if ( typeof target === 'object' && ! Array.isArray(target) ) {
						$.each(target, function(option, container) {
							$(container).toggleClass('d-none', val !== option);
						});

						return;
					}

					// Array of allowed values
					if ( Array.isArray(target) && containerSel ) {
						const on = target.includes(val);
						$(containerSel).toggleClass('d-none', ! on);
						return;
					}

					// Simple case (string target)
					if ( typeof target === 'string' ) {
						const on = ( val !== 'no' && val !== 'false' && val !== '' );
						$(target).toggleClass('d-none', ! on);
					}
				};

				// Bind event
				$(document).off('change', triggerSel).on('change', triggerSel, apply);

				// Initial state
				apply();
			};

			// Simple checkboxes
			attach('#enable_auto_apply_coupon_code', '.show-coupon-code-enabled');
			attach('#enable_inter_bank_pix_api', '.inter-bank-pix');
			attach('#enable_inter_bank_ticket_api', '.inter-bank-slip');
			attach('#enable_fill_address', '.require-auto-fill-address');
			attach('#enable_manage_fields', '.step-checkout-fields-container');
			attach('#enable_field_masks', '.require-input-mask');
			attach('#email_providers_suggestion', '.require-email-suggestions-enabled');
			attach('#enable_inter_bank_pix_api', '.require-enabled-inter-pix');
			attach('#enable_inter_bank_ticket_api', '.require-enabled-inter-slip-bank');
			attach('#enable_animation_process_purchase', '.require-process-animations-enabled');

			// Select with option map
			attach('#add_new_condition_component', {
				'field': '.specific-component-fields',
				'payment': '.specific-component-payment',
				'shipping': '.specific-component-shipping',
			});

			attach('#add_new_condition_user_function', {
				'specific_user': '.specific-users-container',
				'specific_role': '.specific-roles-container',
			});

			attach('#add_new_condition_product_filter', {
				'specific_products': '.specific-products',
				'specific_categories': '.specific-categories',
				'specific_attributes': '.specific-attributes',
			});

			attach('#add_new_condition_component_verification', {
				'field': '.specific-checkout-fields',
			});

			attach('#checkout_header_type', {
				'logo': '.header-styles-option-logo',
				'text': '.header-styles-option-text',
			});

			// Select with array of allowed values
			attach('#add_new_condition_component_type', [
				'is', 'is_not', 'contains', 'not_contain', 
				'start_with', 'finish_with', 'bigger_then', 'less_than'
			], '.condition-value');

            // display input for custom link on contact page for thankyou page
            attach('#contact_page_thankyou', {
				'custom_link': '.require-custom-link-enabled',
			});

            // display modal for set checkout countdown
            attach('#enable_checkout_countdown', '.require-countdown-enabled');
		},

        /**
         * Enforce minimum value = 1 on numeric inputs
         * 
         * @since 1.0.0
         * @version 5.1.0
         */
        enforceMinOne: function() {
            const sel = '.allow-numbers-be-1';

            $(document).on('input', sel, function() {
                let val = parseFloat($(this).val());
                if ( isNaN(val) || val < 1 ) {
                    $(this).val(1);
                }
            });
        },

        /**
         * Allow only numbers, dot and dash on design parameters
         * 
         * @since 1.0.0
         * @version 5.1.0
         */
        filterDesignParameters: function() {
            $(document).on('keydown', '.design-parameters', function(e) {
                let key = e.charCode || e.keyCode || 0;

                // numeric keypad, top row, dot, dash, backspace
                const allowed = (
                    (key >= 96 && key <= 105) ||
                    (key >= 48 && key <= 57) ||
                    key === 190 ||
                    key === 189 || key === 109 ||
                    key === 8
                );

                return allowed;
            });
        },

        /**
         * Open WordPress media library and set selected URL to input
         * 
         * @since 1.0.0
         * @version 5.1.0
         */
        mediaSelectors: function() {
            /**
             * Setup a media selector
             * 
             * @since 5.1.0
             * @param {string} trigger_selector 
             * @param {string} input_selector 
             * @param {string} modal_title 
             * @param {string} button_text 
             * @param {boolean} multiple_files 
             */
            const setup_media_selector = (trigger_selector, input_selector, modal_title, button_text, multiple_files = false) => {
                let file_frame;

                $(document).on('click', trigger_selector, function(e) {
                    e.preventDefault();

                    if ( file_frame ) {
                        file_frame.open();
                        return;
                    }

                    file_frame = wp.media.frames.file_frame = wp.media({
                        title: modal_title,
                        button: { text: button_text },
                        multiple: multiple_files,
                    });

                    file_frame.on('select', function() {
                        const attachment = file_frame.state().get('selection').first().toJSON();
                        $(input_selector).val(attachment.url).trigger('change');
                    });

                    file_frame.open();
                });
            };

            // Header image
            setup_media_selector(
                '#flexify-checkout-search-header-logo',
                'input[name="search_image_header_checkout"]',
                params.set_logo_modal_title,
                params.use_this_image_title
            );

            // Process animations
            setup_media_selector('#animation_process_purchase_file_1_trigger','input[name="animation_process_purchase_file_1"]', params.set_animation_modal_title, params.set_animation_button_title);
            setup_media_selector('#animation_process_purchase_file_2_trigger','input[name="animation_process_purchase_file_2"]', params.set_animation_modal_title, params.set_animation_button_title);
            setup_media_selector('#animation_process_purchase_file_3_trigger','input[name="animation_process_purchase_file_3"]', params.set_animation_modal_title, params.set_animation_button_title);
        },

        /**
         * Generic popup binder (open/close/outside click)
         * 
         * @since 2.3.0
         * @version 5.1.0
         * @param {string|jQuery} trigger 
         * @param {string|jQuery} container 
         * @param {string|jQuery} close 
         */
        displayModal: function(trigger, container, close) {
			// open modal on click to trigger
			$(document).on('click', trigger, function(e) {
				e.preventDefault();
				$(container).addClass('show');
			});

			// close modal on click outside container
			$(document).on('click', container, function(e) {
				if (e.target === this) {
					$(this).removeClass('show');
				}
			});

			// close modal on click close button
			$(document).on('click', close, function(e) {
				e.preventDefault();
				$(container).removeClass('show');
			});
        },

        /**
         * Register all popups needed by the admin
         * 
         * @since 2.3.0
         * @version 5.1.1
         */
        popups: function() {
            this.displayModal('#inter_bank_credencials_settings', '#inter_bank_credendials_container', '#inter_bank_credendials_close');
            this.displayModal('#inter_bank_pix_settings', '#inter_bank_pix_container', '#inter_bank_pix_close');
            this.displayModal('#inter_bank_slip_settings', '#inter_bank_slip_container', '#inter_bank_slip_close');
            this.displayModal('#require_inter_bank_module_trigger', '#require_inter_bank_module_container', '#require_inter_bank_module_close');
            this.displayModal('.require-pro', '#popup-pro-notice', '.require-pro-close');
            this.displayModal('#set_ip_api_service_trigger', '.set-api-service-container', '.set-api-service-close');
            this.displayModal('#add_new_checkout_fields_trigger', '.add-new-checkout-fields-container', '.add-new-checkout-fields-close');
            this.displayModal('#auto_fill_address_api_trigger', '.auto-fill-address-api-container', '.auto-fill-address-api-close');
            this.displayModal('#set_new_font_family_trigger', '#set_new_font_family_container', '#close_new_font_family');
            this.displayModal('#fcw_reset_settings_trigger', '#fcw_reset_settings_container', '#fcw_close_reset');
            this.displayModal('#add_new_checkout_condition_trigger', '#add_new_checkout_condition_container', '#close_add_new_checkout_condition');
            this.displayModal('#set_email_providers_trigger', '#set_email_providers_container', '#close_set_email_providers');
            this.displayModal('#set_process_purchase_animation_trigger', '#set_process_purchase_animation_container', '#close_set_process_purchase_animation');
            this.displayModal('#set_countdown_trigger', '#set_countdown_container', '#close_set_countdown');
        },

        /**
         * Color helpers (pair text input + color input + reset)
         * 
         * @since 2.4.0
         * @version 5.1.0
         */
        colorHelpers: function() {
            $(document).on('input', '.get-color-selected', function() {
                let color = $(this).val();
                $(this).closest('.color-container').find('.form-control-color').val(color);
            });

            $(document).on('input', '.form-control-color', function() {
                let color = $(this).val();
                $(this).closest('.color-container').find('.get-color-selected').val(color);
            });

            $(document).on('click', '.reset-color', function(e) {
                e.preventDefault();
                let color = $(this).data('color');
                const box = $(this).closest('.color-container');
                box.find('.form-control-color').val(color);
                box.find('.get-color-selected').val(color).trigger('change');
            });
        },

        /**
         * Bootstrap datepicker initializer (supports dynamically focused fields)
         * 
         * @since 3.2.0
         * @version 5.1.0
         */
        datepicker: function() {
            const initPicker = (el) => {
                $(el).datepicker({
                    format: 'dd/mm/yyyy',
                    todayHighlight: true,
                    language: 'pt-BR',
                });
            };

            $('.dateselect').each( function() {
                initPicker(this);
            });

            $(document).on('focus', '.dateselect', function() {
                if ( ! $(this).data('datepicker') ) {
                    initPicker(this);
                }
            });
        },

        /**
         * Fields manager: sortables, open/close editors, toggle active, remove field
         * + add new field flow and preview helpers
         * 
         * @since 3.0.0
         * @version 5.1.0
         */
        fieldsManager: function() {
            var step_1 = $('#flexify_checkout_step_1').sortable({
                connectWith: '#flexify_checkout_step_2',
                update: (event, ui) => {
                    update_fields_priority(event, ui, '1');
                },
            });

            var step_2 = $('#flexify_checkout_step_2').sortable({
                connectWith: '#flexify_checkout_step_1',
                update: (event, ui) => {
                    update_fields_priority(event, ui, '2');
                },
            });

            sort_fields_by_priority('flexify_checkout_step_1');
            sort_fields_by_priority('flexify_checkout_step_2');

            /**
             * Update priorities after drag & drop
             * 
             * @since 3.0.0
             * @version 5.1.0
             * @param {object} event 
             * @param {object} ui 
             * @param {string} step 
             */
            function update_fields_priority(event, ui, step) {
                var container = ui.item.closest('.step-container');

                $(container).find('.field-item').each( function(index) {
                    $(this).find('.change-priority').val(index + 1).trigger('change');
                });

                $(ui.item).parent().siblings('.step-title').each( function() {
                    $(this).closest('.step-container').find('.field-item').find('.change-step').val(step).trigger('change');
                });
            }

            /**
             * Sort fields by priority value (admin preview)
             * 
             * @since 3.0.0
             * @version 5.1.0
             * @param {string} containerId 
             */
            function sort_fields_by_priority(containerId) {
                var $container = $('#' + containerId);
                var items = $container.find('.field-item');

                items.sort( function(a, b) {
                    var pa = parseInt($(a).find('.change-priority').val() || 0, 10);
                    var pb = parseInt($(b).find('.change-priority').val() || 0, 10);
                    return pa - pb;
                });

                items.detach().appendTo($container);
            }

            // Open editor popup
            $(document).on('click', '.flexify-checkout-step-trigger', function(e) {
                e.preventDefault();
                let $field = $(e.target).closest('.field-item').addClass('active');
                $field.children('.flexify-checkout-step-container').addClass('show');
                $(step_1).sortable('option', 'disabled', true);
                $(step_2).sortable('option', 'disabled', true);
            });

            // Close editor popup (button)
            $(document).on('click', '.flexify-checkout-step-close-popup', function(e) {
                e.preventDefault();
                $(this).closest('.flexify-checkout-step-container').removeClass('show');
                $('.flexify-checkout-step-trigger').closest('.field-item').removeClass('active');
                $(step_1).sortable('option', 'disabled', false);
                $(step_2).sortable('option', 'disabled', false);
            });

            // Close editor popup (outside)
            $(document).on('click', '.flexify-checkout-step-container', function(e) {
                if ( e.target === this ) {
                    $(this).removeClass('show');
                    $('.flexify-checkout-step-trigger').closest('.field-item').removeClass('active');
                    $(step_1).sortable('option', 'disabled', false);
                    $(step_2).sortable('option', 'disabled', false);
                }
            });

            // Toggle active/inactive on switch
            $(document).on('click', '.toggle-active-field', function(e) {
                let checked = $(e.target).prop('checked');
                let target  = $('.field-item.active');
                target.toggleClass('active', checked).removeClass('inactive');
                target.toggleClass('inactive', !checked).removeClass('active');
            });

            // Disable drag if there are pro-only fields (same behavior kept)
            if ( $('.field-item').hasClass('require-pro') ) {
                $(step_1).sortable('option', 'disabled', true);
                $(step_2).sortable('option', 'disabled', true);
            }

            // Remove field (AJAX)
            $(document).on('click', '.exclude-field', (e) => {
                e.preventDefault();

                if ( ! confirm(params.confirm_exclude_field) ) {
                    return;
                }

                var btn = $(e.currentTarget);
                var state = this.keepButtonState(btn);
                var index = btn.data('exclude');

                btn.html('<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: params.ajax_url,
                    type: 'POST',
                    data: { action: 'remove_checkout_fields', field_to_remove: index },
                    success: (response) => {
                        try {
                            if ( response && response.status === 'success' ) {
                                $('#' + response.field).fadeOut(500, function() { $(this).remove(); });

                                this.displayToast('success', response.toast_header_title, response.toast_body_title);
                            } else {
                                this.displayToast('danger', response.toast_header_title, response.toast_body_title);
                                console.error('Invalid JSON response or missing "status" field:', response);
                            }
                        } catch (err) {
                            console.error('Error parsing JSON:', err);
                        }
                    },
                    error: function(xhr, textStatus, errorThrown) {
                        console.error('AJAX request failed:', textStatus, errorThrown);
                    },
                    complete: () => {
                        btn.html(state.html).width(state.width).height(state.height);
                    },
                });
            });

            // On blur: check field id availability
            $(document).on('blur', '#checkout_field_name', function() {
                var concat_field = `billing_${$(this).val()}`;

                if ( $(this).val() !== '' ) {
                    $('#checkout_field_name').before('<span id="check_field_id" class="spinner-border spinner-border-sm" style="position:absolute;padding:0.3rem;border-radius:100%!important;right:1rem;z-index:2;top:0.7rem;"></span>');

                    $.ajax({
                        url: params.ajax_url,
                        type: 'POST',
                        data: { action: 'check_field_availability', field_name: concat_field },
                        success: function(response) {
                            try {
                                if ( response.status === 'success' ) {
                                    $('#check_field_id').remove();

                                    if ( response.available === true ) {
                                        $('#check_field_availability').addClass('d-none');
                                        $('#set_field_id').removeClass('invalid-option');
                                        $('#checkout_field_name_concat').val(concat_field);
                                    } else {
                                        $('#check_field_availability').removeClass('d-none');
                                        $('#set_field_id').addClass('invalid-option');
                                        $('#checkout_field_name_concat').val('');
                                    }
                                }
                            } catch (err) {
                                console.error('Error parsing JSON:', err);
                            }
                        },
                        error: function(xhr, textStatus, errorThrown) {
                            console.error('AJAX request failed:', textStatus, errorThrown);
                        }
                    });
                }
            });

            // Toggle extra containers based on field type
            $(document).on('change', '#checkout_field_type', function() {
                let type = $(this).val();

                if ( type === 'select' ) {
                    $('.require-add-new-field-select').removeClass('d-none');
                    $('.require-add-new-field-multicheckbox').addClass('d-none');
                } else if ( type === 'multicheckbox' ) {
                    $('.require-add-new-field-multicheckbox').removeClass('d-none');
                    $('.require-add-new-field-select').addClass('d-none');
                } else {
                    $('.require-add-new-field-select, .require-add-new-field-multicheckbox').addClass('d-none');
                }
            });

            // Add new option (preview) to select builder
            $(document).on('click', '#add_new_options_to_select', function(e) {
                e.preventDefault();

                let value = $('#add_new_field_select_option_value');
                let title = $('#add_new_field_select_option_title');

                let preview = `<div class="d-flex align-items-center mb-3 option-container" data-option="${value.val()}">
                    <div class="input-group me-3">
                        <span class="input-group-text d-flex align-items-center justify-content-center w-25">${value.val()}</span>
                        <span class="input-group-text d-flex align-items-center justify-content-center w-75">${title.val()}</span>
                    </div>
                    <button class="btn btn-outline-danger btn-icon rounded-3 exclude-option-select" data-exclude="${value.val()}">
                        <svg class="icon icon-danger" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
                    </button>
                </div>`;

                $('#preview_options_container').append(preview);
                $('#preview_select_new_field').append(new Option(title.val(), value.val()));
                value.val(''); title.val('');
            });

            // Remove preview option from builder
            $(document).on('click', '.exclude-option-select', function(e) {
                e.preventDefault();
                let exclude = $(this).data('exclude');
                $(this).closest('.option-container').remove();
                $(`#preview_select_new_field > option[value="${exclude}"]`).remove();
            });

            // Add new option (preview) to multicheckbox builder
            $(document).on('click', '#add_new_options_to_multicheckbox', function(e) {
                e.preventDefault();

                let id = $('#add_new_field_multicheckbox_option_id');
                let title = $('#add_new_field_multicheckbox_option_title');

                let preview = `<div class="form-check mb-2 multicheckbox-container">
                    <input class="form-check-input" type="checkbox" id="${id.val()}">
                    <label class="form-check-label" for="${id.val()}">${title.val()}</label>
                    <button class="btn btn-outline-danger btn-icon rounded-3 ms-3 exclude-option-multicheckbox" data-exclude="${id.val()}">
                        <svg class="icon icon-danger" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
                    </button>
                </div>`;

                $('#preview_multicheckbox_container').append(preview);
                id.val(''); title.val('');
            });

            // Remove preview option from multicheckbox builder
            $(document).on('click', '.exclude-option-multicheckbox', function(e) {
                e.preventDefault();
                $(this).closest('.multicheckbox-container').remove();
            });

            // Required switch -> value yes/no
            $(document).on('change', '#required_field', function() {
                $(this).val( $(this).is(':checked') ? 'yes' : 'no' );
            });

            // Live field name mirror (popup title + list)
            $(document).on('keyup input', '.get-name-field', function() {
                let val = $(this).val();
                let modal = $(this).closest('.flexify-checkout-step-container.show');
                modal.find('h5.popup-title .field-name').text(val);
                modal.parent('.field-item').children('.field-name').text(val);
            });

            // Submit new field to backend
            $(document).on('click', '#fcw_add_new_field', (e) => {
                e.preventDefault();

                var btn = $(e.currentTarget);
                var text = btn.text();
                var state = this.keepButtonState(btn);

                btn.html('<span class="spinner-border spinner-border-sm"></span>');

                var id = $('#checkout_field_name_concat').val();
                var type = $('#checkout_field_type').val();
                var label = $('#checkout_field_title').val();
                var required = $('#required_field').val();
                var position = $('#field_position').val();
                var classes = $('#field_classes').val();
                var label_cls = $('#field_label_classes').val();
                var step = $('#field_step').val();
                var source = $('#field_source').val();
                var mask = $('#field_input_mask').val();
                var priority = (params.get_array_checkout_fields ? params.get_array_checkout_fields.length : 0) + 1;
                var select_options = [];

                if ( type === 'select' ) {
                    $('#preview_select_new_field option').each( function() {
                        select_options.push({ value: $(this).val(), text: $(this).text() });
                    });
                }

                $.ajax({
                    url: params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'add_new_field_to_checkout',
                        get_field_id: id,
                        get_field_type: type,
                        get_field_label: label,
                        get_field_required: required,
                        get_field_position: position,
                        get_field_classes: classes,
                        get_field_label_classes: label_cls,
                        get_field_step: step,
                        get_field_source: source,
                        get_field_priority: priority,
                        get_field_options_for_select: select_options,
                        input_mask: mask,
                    },
                    success: (response) => {
                        try {
                            if ( response.status === 'success' ) {
                                $('#fcw_add_new_field').text(text);

                                var $existing = $('#' + id);

                                if ( $existing.length ) {
                                    $existing.replaceWith(response.field_html);
                                } else {
                                    if ( step === '1' ) {
                                        $('#flexify_checkout_step_1').append(response.field_html);
                                    } else if ( step === '2' ) {
                                        $('#flexify_checkout_step_2').append(response.field_html);
                                    }
                                }

                                this.displayToast('success', response.toast_header_title, response.toast_body_title);

                                // Close popup and reset builder
                                $('.add-new-checkout-fields-container').removeClass('show');

                                $('#checkout_field_name').val('');
                                $('#checkout_field_name_concat').val('');
                                $('#checkout_field_type').val('text');
                                $('#checkout_field_title').val('');
                                $('#required_field').val('no').prop('checked', false);
                                $('#field_position').val('left');
                                $('#field_classes').val('');
                                $('#field_label_classes').val('');
                                $('#field_step').val('1');
                                $('#field_source').val('');
                                $('#field_input_mask').val('');
                                $('.require-add-new-field-select').addClass('d-none');
                                $('#preview_select_new_field > option').remove();
                                $('#preview_options_container').html('');
                            } else {
                                this.displayToast('danger', response.toast_header_title, response.toast_body_title);
                                console.error('Invalid JSON response or missing "status" field:', response);
                            }
                        } catch (err) {
                            console.error('Error parsing JSON:', err);
                        }
                    },
                    error: function(xhr, textStatus, errorThrown) {
                        console.error('AJAX request failed:', textStatus, errorThrown);
                    },
                    complete: () => {
                        btn.html(state.html).width(state.width).height(state.height);
                    },
                });
            });

            // Remove option from a LIVE select (saved field)
            $(document).on('click', '.exclude-option-select-live', (e) => {
                e.preventDefault();

                if ( ! confirm(params.confirm_remove_option) ) {
                    return;
                }

                let btn = $(e.currentTarget);
                let state = this.keepButtonState(btn);

                let field_id = btn.data('field-id');
                let option   = btn.data('option');

                btn.html('<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: params.ajax_url,
                    type: 'POST',
                    data: { action: 'remove_select_option', field_id: field_id, exclude_option: option },
                    success: (response) => {
                        try {
                            if ( response.status === 'success' ) {
                                btn.closest('.option-container-live').remove();
                                this.displayToast('success', response.toast_header_title, response.toast_body_title);
                            } else {
                                this.displayToast('danger', response.toast_header_title, response.toast_body_title);
                                console.error('Invalid JSON response or missing "status" field:', response);
                            }
                        } catch (err) {
                            console.error('Error parsing JSON:', err);
                        }
                    },
                    error: function(xhr, textStatus, errorThrown) {
                        console.error('AJAX request failed:', textStatus, errorThrown);
                    },
                    complete: () => {
                        btn.html(state.html).width(state.width).height(state.height);
                    },
                });
            });

            // Add a new LIVE option (inline editor)
            $(document).on('click', '#add_new_select_option_live', function(e) {
                e.preventDefault();

                const template = `<div id="new_select_option_live_preview" class="d-flex align-items-center justify-content-between mb-4">
                    <div class="d-grid me-3">
                        <div class="input-group mb-3">
                            <span class="input-group-text w-fit">${params.new_option_value}</span>
                            <input type="text" id="add_new_field_select_option_value_live" class="form-control input-control-wd-12" value="" placeholder="${params.placeholder_new_option_value}">
                        </div>
                        <div class="input-group">
                            <span class="input-group-text w-fit">${params.new_option_title}</span>
                            <input type="text" id="add_new_field_select_option_title_live" class="form-control input-control-wd-12" value="" placeholder="${params.placeholder_new_option_title}">
                        </div>
                    </div>
                    <button id="add_new_options_to_select_live" class="btn btn-icon btn-icon-lg btn-outline-secondary">
                        <svg class="icon icon-dark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 11h-6V5h-2v6H5v2h6v6h2v-6h6z"></path></svg>
                    </button>
                </div>`;

                $(this).prop('disabled', true).before(template);
            });

            // Confirm new LIVE option submit
            $(document).on('click', '#add_new_options_to_select_live', (e) => {
                e.preventDefault();

                let btn = $(e.currentTarget);
                let state = this.keepButtonState(btn);

                let field_id = $('.field-item.active').attr('id');
                let value = $('#add_new_field_select_option_value_live').val();
                let title = $('#add_new_field_select_option_title_live').val();

                const preview = `<div class="d-flex align-items-center mb-3 option-container-live" data-option="${value}">
                    <div class="input-group me-3">
                        <span class="input-group-text d-flex align-items-center justify-content-center py-2 w-25">${value}</span>
                        <span class="input-group-text d-flex align-items-center justify-content-center w-75">${title}</span>
                    </div>
                    <button class="btn btn-outline-danger btn-icon rounded-3 exclude-option-select-live" data-field-id="${field_id}" data-option="${value}">
                        <svg class="icon icon-danger" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
                    </button>
                </div>`;

                btn.html('<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'add_new_option_select_live',
                        field_id: field_id,
                        option_value: value,
                        option_title: title,
                    },
                    success: (response) => {
                        try {
                            if ( response.status === 'success' ) {
                                $('#add_new_select_option_live').prop('disabled', false);
                                $('#new_select_option_live_preview').remove();
                                $('.field-item.active .options-container-live').append(preview);
                                this.displayToast('success', response.toast_header_title, response.toast_body_title);
                            } else {
                                this.displayToast('danger', response.toast_header_title, response.toast_body_title);
                                console.error('Invalid JSON response or missing "status" field:', response);
                            }
                        } catch (err) {
                            console.error('Error parsing JSON:', err);
                        }
                    },
                    error: function(xhr, textStatus, errorThrown) {
                        console.error('AJAX request failed:', textStatus, errorThrown);
                    },
                    complete: () => {
                        btn.html(state.html).width(state.width).height(state.height);
                    },
                });
            });
        },

        /**
         * Pre-license actions: disable .pro-version and redirect to About
         * 
         * @since 3.0.0
         * @version 5.1.1
         */
        preLicenseActions: function() {
            $('.pro-version').prop('disabled', true);

            $(document).on('click', '#active_license_form', function() {
                $('#popup-pro-notice').removeClass('show');
                $('.flexify-checkout-wrapper a.nav-tab[href="#about"]').click();
                
                // scroll at the license form view
				$('html, body').animate({
					scrollTop: $('#enable_auto_updates').offset().top
				}, 300);
            });
        },

        /**
         * Alternative license upload (drag & drop + file input)
         * 
         * @since 3.3.0
         * @version 5.1.0
         */
        altLicenseUpload: function() {
            const dropSel = '#license_key_zone';
            const fileSel = '#upload_license_key';

            // dragover / dragleave
            $(document).on('dragover dragleave', dropSel, function(e) {
                e.preventDefault();
                $(this).toggleClass('drag-over', e.type === 'dragover');
            });

            // drop
            $(document).on('drop', dropSel, (e) => {
                e.preventDefault();
                var file = e.originalEvent.dataTransfer.files[0];
                if ( ! $(e.currentTarget).hasClass('file-uploaded') ) {
                    handle_file(file, $(e.currentTarget));
                }
            });

            // input change
            $(document).on('change', fileSel, function(e) {
                e.preventDefault();
                var file = e.target.files[0];
                handle_file(file, $(this).parents('.dropzone'));
            });

            /**
             * Handle file upload logic
             * 
             * @since 3.3.0
             * @version 5.1.0
             * @param {File} file 
             * @param {jQuery} dropzone 
             */
            function handle_file(file, dropzone) {
                if ( ! file ) return;

                var filename = file.name;

                // UI state
                dropzone.children('.file-list').removeClass('d-none').text(filename);
                dropzone.addClass('file-processing');
                dropzone.append('<div class="spinner-border"></div>');
                dropzone.children('.drag-text, .drag-and-drop-file, .form-inter-bank-files').addClass('d-none');

                // Prepare form
                var form_data = new FormData();
                form_data.append('action', 'flexify_checkout_alternative_activation');
                form_data.append('file', file);

                $.ajax({
                    url: params.ajax_url,
                    type: 'POST',
                    data: form_data,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            if ( response.status === 'success' ) {
                                dropzone.addClass('file-uploaded').removeClass('file-processing');
                                dropzone.children('.spinner-border').remove();
                                dropzone.append('<div class="upload-notice d-flex flex-column align-items-center"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="#22c55e" d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path fill="#22c55e" d="M9.999 13.587 7.7 11.292l-1.412 1.416 3.713 3.705 6.706-6.706-1.414-1.414z"></path></svg><span>' + (response.dropfile_message || '') + '</span></div>');
                                dropzone.children('.file-list').addClass('d-none');

                                setTimeout( function() { location.reload(); }, 1000 );
                            } else {
                                // invalid file
                                dropzone.addClass('invalid-file').removeClass('file-processing');
                                dropzone.children('.spinner-border').remove();
                                dropzone.children('.drag-text, .drag-and-drop-file, .form-inter-bank-files').removeClass('d-none');
                                dropzone.children('.file-list').addClass('d-none');

                                // feedback toast if available
                                if ( response.toast_header || response.toast_body ) {
                                    Flexify_Checkout_Admin.displayToast('danger', response.toast_header, response.toast_body);
                                }
                            }
                        } catch (err) {
                            console.log(err);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        dropzone.addClass('fail-upload').removeClass('file-processing');
                        console.error('AJAX Error:', textStatus, errorThrown);
                    },
                });
            }
        },

        /**
         * Reset settings
         * 
         * @since 3.8.0
         * @version 5.1.0
         */
        resetSettings: function() {
            $(document).on('click', '#confirm_reset_settings', function(e) {
                e.preventDefault();
                
                let btn = $(this);
                let state = Flexify_Checkout_Admin.keepButtonState(btn);
                
                $.ajax({
                    url: flexify_checkout_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'flexify_checkout_reset_plugin_action',
                    },
                    beforeSend: function() {
                        btn.html('<span class="spinner-border spinner-border-sm"></span>');
                    },
                    success: function(response) {
                        try {
                            if ( response.status === 'success' ) {
                                btn.html(state.html);

                                $('#fcw_close_reset').click();

                                Flexify_Checkout_Admin.displayToast( 'success', response.toast_header_title, response.toast_body_title );

                                setTimeout( function() {
                                    location.reload();
                                }, 1000);
                            } else {
                                Flexify_Checkout_Admin.displayToast( 'error', response.toast_header_title, response.toast_body_title );
                            }
                        } catch (error) {
                            console.log(error);
                        }
                    }
                });
            });
        },

		/**
		 * Update the checkout theme when a card is clicked
		 * 
		 * @since 5.0.0
		 * @version 5.1.0
		 */
		themeSelector: function() {
			$(document).on('click', '.card-theme-item', function(e) {
				let card = $(this);
				let theme = card.data('theme');

				if ( card.hasClass('coming-soon') ) {
					return;
				}

				$('.card-theme-item').removeClass('active');
				card.addClass('active');

				$('input[name="flexify_checkout_theme"]').val(theme).change();
			});
		},

        /**
         * Handle checkout conditions
         * 
         * @since 3.5.0
         * @version 5.1.1
         */
        handleConditions: function() {
            /**
             * Simple debounce
             *
             * @since 5.1.0
             * @param {Function} fn
             * @param {number} wait
             * @returns {Function}
             */
            const debounce = (fn, wait = 250) => {
                let t;
                return function(...args) {
                    clearTimeout(t);
                    t = setTimeout(() => fn.apply(this, args), wait);
                };
            };

            /**
             * Get value safely from a select/input
             *
             * @since 5.1.0
             * @param {string} sel
             * @returns {string}
             */
            const val = (sel) => $(sel).val() || 'none';

            /**
             * Build FormData fresh from DOM
             *
             * @since 5.1.0
             * @version 5.1.1
             * @returns {FormData}
             */
            const buildPayload = () => {
                const fd = new FormData();
                fd.set('action', 'add_new_checkout_condition');

                // base selects/inputs
                fd.set('type_rule', val(S.type_rule));
                fd.set('component', val(S.component));
                fd.set('component_field', val(S.component_field));
                fd.set('verification_condition', val(S.verification_condition));
                fd.set('verification_condition_field', val(S.verification_condition_field));

                const condType = val(S.condition);
                fd.set('condition', condType);

                if ( ['checked', 'not_checked'].includes(condType) ) {
                    fd.set('condition_value', '' );
                } else {
                    fd.set('condition_value', $(S.condition_value).val() || '' );
                }

                // specifics
                fd.set('payment_method', val(S.payment));
                fd.set('shipping_method', val(S.shipping));

                // user filter
                const userFunc = val(S.user_function);
                fd.set('filter_user', val(S.user_role));

                if ( userFunc === 'specific_user' ) {
                    fd.set('specific_user', JSON.stringify([...specificUsers]));
                } else if ( userFunc === 'specific_role' ) {
                    fd.set('specific_role', val(S.user_role));
                }

                // product filter
                const pf = val(S.product_filter);
                fd.set('product_filter', pf);

                if ( pf === 'specific_products' ) {
                    fd.set('specific_products', JSON.stringify([...specificProducts]));
                }

                if ( pf === 'specific_categories' ) {
                    fd.set('specific_categories', JSON.stringify([...specificCategories]));
                }

                if ( pf === 'specific_attributes' ) {
                    fd.set('specific_attributes', JSON.stringify([...specificAttributes]));
                }

                return fd;
            };

            /**
             * Enable/disable submit based on current UI state
             *
             * @since 5.1.0
             */
            const toggleSubmit = () => {
                const typeRule = val(S.type_rule);
                const component = val(S.component);
                const compField = val(S.component_field);
                const compPay = val(S.payment);
                const compShip = val(S.shipping);
                const verifyCond = val(S.verification_condition);
                const verifyFld = val(S.verification_condition_field);
                const condType = val(S.condition);
                const condVal = $(S.condition_value).val() || '';

                const needsValue = ['is', 'is_not', 'contains', 'not_contain', 'start_with', 'finish_with', 'bigger_then', 'less_than'];

                let ok = typeRule !== 'none' && component !== 'none' && condType !== 'none';

                if ( component === 'field' && compField === 'none' ) {
                    ok = false;
                }

                if ( component === 'payment' && compPay   === 'none' ) {
                    ok = false;
                }

                if ( component === 'shipping'&& compShip  === 'none' ) {
                    ok = false;
                }

                if ( verifyCond === 'field'  && verifyFld === 'none' ) {
                    ok = false;
                }

                if ( needsValue.includes(condType) && condVal.trim() === '' ) {
                    ok = false;
                }

                $(S.submit).prop('disabled', ! ok);
            };

            /**
            * Reset all selects/inputs inside a container
            *
            * @since 5.1.1
            * @param {string} container
            */
            const resetForm = (container) => {
                const $c = $(container);

                $c.find('select').each( function() {
                    const first = $(this).find('option:first').val();
                    $(this).val(first).trigger('change');
                });

                $c.find('input[type="text"], input[type="search"], input[type="number"]').val('');

                // also limpa listas de seleção
                $(S.products_box).empty().removeClass('has-items');
                $(S.categories_box).empty().removeClass('has-items');
                $(S.attributes_box).empty().removeClass('has-items');
                $(S.users_box).empty().removeClass('has-items');
                specificProducts.clear();
                specificCategories.clear();
                specificAttributes.clear();
                specificUsers.clear();
                toggleSubmit();
            };

            /**
            * Live search (AJAX) with delegation + debouce
            *
            * @since 5.1.1
            * @param {string} inputSel
            * @param {string} boxSel
            * @param {string} action
            * @param {"product-id"|"category-id"|"attribute-id"|"user-id"} dataKey
            * @param {Set} setRef
            */
            const bindSearch = (inputSel, boxSel, action, dataKey, setRef) => {
                const run = debounce(function(e) {
                    const query = $(e.target).val().trim();
                    const $box = $(boxSel);

                    if (query.length < 3) {
                        $box.empty().removeClass('has-items');
                        return;
                    }

                    if (!$box.next('.specific-search-spinner').length) {
                        $(e.target).after('<i class="spinner-border specific-search-spinner"></i>');
                    }

                    $.ajax({
                        url: flexify_checkout_params.ajax_url,
                        type: 'POST',
                        dataType: 'html',
                        data: {
                            action,
                            search_query: query,
                        },
                    })
                    .done((html) => {
                        $(inputSel).parent('div').find('.specific-search-spinner').remove();
                        $box.html(html).addClass('has-items');
                    })
                    .fail((xhr, t, err) => {
                        console.error('AJAX search failed:', t, err);
                        $(inputSel).parent('div').find('.specific-search-spinner').remove();
                    });
                }, 300);

                $(document).off('keyup', inputSel).on('keyup', inputSel, run);

                // toggle selection
                $(document).off('click', `${boxSel} li.list-group-item`).on('click', `${boxSel} li.list-group-item`, function() {
                    const id = $(this).data(dataKey);

                    if ( ! id && id !== 0 ) {
                        return;
                    }

                    if ( $(this).toggleClass('selected').hasClass('selected') ) {
                        setRef.add(id);
                    } else {
                        setRef.delete(id);
                    }
                });
            };

            const S = {
                container: '#add_new_condition_container_master',
                submit: '#add_new_condition_submit',

                // base controls
                type_rule: '#add_new_condition_type_rule',
                component: '#add_new_condition_component',
                component_field: '#add_new_condition_specific_field_component',
                verification_condition: '#add_new_condition_component_verification',
                verification_condition_field: '#add_new_condition_specific_field',
                condition: '#add_new_condition_component_type',
                condition_value: '#add_new_condition_get_condition_value',
                payment: '#add_new_condition_specific_payment_component',
                shipping: '#add_new_condition_specific_shipping_component',

                // user
                user_function: '#add_new_condition_user_function',
                user_role: '#add_new_condition_specific_user_role',

                // products
                product_filter: '#add_new_condition_product_filter',

                // search inputs
                product_input: '.product-search',
                category_input: '.category-search',
                attribute_input: '.attribute-search',
                user_input: '.user-search',

                // search result boxes
                products_box: '#get_specific_products',
                categories_box: '#get_specific_categories',
                attributes_box: '#get_specific_attribute',
                users_box: '#get_specific_users',

                // close
                close_modal_btn: '#close_add_new_checkout_condition',
            };

            const specificProducts = new Set();
            const specificCategories = new Set();
            const specificAttributes = new Set();
            const specificUsers = new Set();

            // listener for any change in selects/inputs to toggle submit button
            $(document).off('change keyup', `${S.container} select, ${S.container} input`).on('change keyup', `${S.container} select, ${S.container} input`, toggleSubmit);

            // when changing the product filter, update the button state
            $(document).off('change', S.product_filter).on('change', S.product_filter, toggleSubmit);

            const allConditionOptions = $(S.condition).find('option').clone();

            $(document).on('change', S.verification_condition_field, function() {
                const type = $(this).find('option:selected').data('type');
                const $cond = $(S.condition);
                $cond.html(allConditionOptions.clone());

                if ( type === 'checkbox' ) {
                    $cond.find('option').each( function() {
                        const val = $(this).val();
                        if ( ! ['none', 'checked', 'not_checked'].includes(val) ) {
                            $(this).remove();
                        }
                    });
                }

                $cond.val('none').trigger('change');
            });

            $(document).on('change', S.verification_condition, function() {
                if ( $(this).val() !== 'field' ) {
                    $(S.condition).html(allConditionOptions.clone()).val('none').trigger('change');
                } else {
                    $(S.verification_condition_field).trigger('change');
                }
            });

            // searching with debounce
            bindSearch( S.product_input, S.products_box, 'get_woo_products_ajax', 'product-id', specificProducts );
            bindSearch( S.category_input, S.categories_box, 'get_woo_categories_ajax', 'category-id', specificCategories );
            bindSearch( S.attribute_input, S.attributes_box, 'get_woo_attributes_ajax', 'attribute-id', specificAttributes );
            bindSearch( S.user_input, S.users_box, 'search_users_ajax', 'user-id', specificUsers);

            $(document).off('click', S.submit).on('click', S.submit, (e) => {
                e.preventDefault();

                const $btn = $(e.currentTarget);
                const state  = Flexify_Checkout_Admin.keepButtonState($btn);

                $btn.html('<span class="spinner-border spinner-border-sm"></span>');

                const fd = buildPayload();

                $.ajax({
                    url:  flexify_checkout_params.ajax_url,
                    type: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                })
                .done((res) => {
                    if (res && res.status === 'success') {
                        Flexify_Checkout_Admin.displayToast('success', res.toast_header_title, res.toast_body_title);

                        const $wrap = $('#display_conditions');
                        const item  = `<li class="list-group-item d-flex align-items-center justify-content-between">
                            <div class="d-grid">
                                <div class="mb-2">${res.condition_line_1 || ''}</div>
                                <div>${res.condition_line_2 || ''}</div>
                            </div>
                            <button class="exclude-condition btn btn-icon btn-sm btn-outline-danger rounded-3 ms-3">
                                <svg class="icon icon-sm icon-danger" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
                            </button>
                        </li>`;

                        if ( res[0] && res[0].empty_conditions === 'yes' ) {
                            const $td = $('#empty_conditions').parent('td');
                            $('#empty_conditions').remove();
                            $td.append(`<div id="display_conditions" class="mb-3"><ul class="list-group">${item}</ul></div>`);
                        } else {
                            if ($wrap.length) {
                                $wrap.find('ul.list-group').append(item);
                            }
                        }

                        // close modal and reset form
                        setTimeout(() => {
                            $(S.close_modal_btn).trigger('click');
                            resetForm(S.container);
                        }, 300);

                    } else {
                        Flexify_Checkout_Admin.displayToast('danger', res?.toast_header_title || 'Erro', res?.toast_body_title || (res?.error_message || 'Falha ao adicionar condição.'));
                    }
                })
                .fail((xhr, t, err) => {
                    console.error('AJAX failed:', t, err);
                    Flexify_Checkout_Admin.displayToast('danger', 'Erro', 'Não foi possível enviar a condição.');
                })
                .always(() => {
                    $btn.html(state.html).width(state.width).height(state.height);
                });
            });

            // remove condition item
            $(document).off('click', '.exclude-condition').on('click', '.exclude-condition', (e) => {
                e.preventDefault();

                const $btn = $(e.currentTarget);
                const $li = $btn.closest('.list-group-item');
                const idx = $li.data('condition');
                const state = Flexify_Checkout_Admin.keepButtonState($btn);

                $btn.html('<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url:  flexify_checkout_params.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: { action: 'exclude_condition_item', condition_index: idx },
                })
                .done((res) => {
                    if (res && res.status === 'success') {
                        Flexify_Checkout_Admin.displayToast('success', res.toast_header_title, res.toast_body_title);

                        $li.fadeOut(150, function() {
                            $(this).remove();

                            if (res[0] && res[0].empty_conditions === 'yes') {
                                const $td = $('#display_conditions').parent('td');
                                $('#display_conditions').remove();
                                $td.append(`<div id="empty_conditions" class="alert alert-info d-flex align-items-center">
                                    <svg class="icon icon-info me-2" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path d="M11 11h2v6h-2zm0-4h2v2h-2z"></path></svg>
                                    <span>${res[0].empty_conditions_message || ''}</span>
                                </div>`);
                            }
                        });
                    } else {
                        Flexify_Checkout_Admin.displayToast('danger', res?.toast_header_title || 'Erro', res?.toast_body_title || 'Falha ao excluir condição.');
                    }
                })
                .fail((xhr, t, err) => {
                    console.error('AJAX failed:', t, err);
                    Flexify_Checkout_Admin.displayToast('danger', 'Erro', 'Não foi possível excluir a condição.');
                })
                .always(() => {
                    $btn.html(state.html).width(state.width).height(state.height);
                });
            });

            // initial state
            toggleSubmit();
        },

        /**
		 * Sync license information
		 * 
		 * @since 5.1.1
		 */
		syncLicense: function() {
			$(document).on('click', '#flexify_checkout_sync_license', function(e) {
				e.preventDefault();

				let btn = $(this);
				let btn_state = Flexify_Checkout_Admin.keepButtonState(btn);

				// send AJAX request
				$.ajax({
					url: params.ajax_url,
					type: 'POST',
					data: {
						action: 'flexify_checkout_sync_license',
					},
					beforeSend: function() {
						btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

						// add placeholder animation on each license details item
						$('.license-details-item').each( function() {
							$(this).addClass('placeholder-content');
						});
					},
					success: function(response) {
						try {
							if ( response.status === 'success' ) {
								// display notice
								Flexify_Checkout_Admin.displayToast( 'success', response.toast_header_title, response.toast_body_title );

								if ( response.license ) {
									$('#fcw-license-status').html(response.license.status_html);
									$('#fcw-license-features').html(response.license.features_html);
									$('#fcw-license-type').html(response.license.type_html);
									$('#fcw-license-expiry').html(response.license.expire_html);
								}
							} else {
								Flexify_Checkout_Admin.displayToast( 'error', response.toast_header_title, response.toast_body_title );
							}
						} catch (error) {
							console.log(error);
						}
					},
					complete: function() {
						btn.prop('disabled', false).html(btn_state.html);
						$('.license-details-item').removeClass('placeholder-content');
					},
					error: function(jqXHR, textStatus, errorThrown) {
						console.error('AJAX Error:', textStatus, errorThrown);
					},
				});
			});
		},

        /**
		 * Active license process
		 * 
		 * @since 5.1.1
		 */
		activateLicense: function() {
			$('#flexify_checkout_active_license').on('click', function(e) {
				e.preventDefault();

				let btn = $(this);
				let btn_state = Flexify_Checkout_Admin.keepButtonState(btn);

                // send ajax request
				$.ajax({
					url: params.ajax_url,
					method: 'POST',
					data: {
						action: 'flexify_checkout_active_license',
						license_key: $('#flexify_checkout_license_key').val(),
					},
					beforeSend: function() {
						btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
					},
					success: function(response) {
						try {
							if ( response.status === 'success' ) {
                                btn.prop('disabled', true).html(btn_state.html);
                                Flexify_Checkout_Admin.displayToast( 'success', response.toast_header_title, response.toast_body_title );

                                setTimeout( function() {
                                    location.reload();
                                }, 1000);
							} else {
                                Flexify_Checkout_Admin.displayToast( 'error', response.toast_header_title, response.toast_body_title );
							}
						} catch (error) {
							console.log(error);
						}
					},
					complete: function() {
						btn.prop('disabled', false).html(btn_state.html);
					},
					error: function(xhr, status, error) {
						alert('AJAX error: ' + error);
					},
				});
			});
		},

        /**
         * Deactivation license process
         * 
         * @since 3.8.0
         * @version 5.1.1
         */
        deactiveLicense: function() {
            $('#flexify_checkout_deactive_license').on('click', function(e) {
                e.preventDefault();

                var confirm_deactivate_license = confirm(flexify_checkout_params.confirm_deactivate_license);

                if ( ! confirm_deactivate_license ) {
                    return;
                }

                let btn = $(this);
				let btn_state = Flexify_Checkout_Admin.keepButtonState(btn);

                $.ajax({
                    url: params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'flexify_checkout_deactive_license',
                    },
                    beforeSend: function() {
						btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
					},
                    success: function(response) {
                        try {
                            if ( response.status === 'success' ) {
                                btn.prop('disabled', true).html(btn_state.html);
                                Flexify_Checkout_Admin.displayToast( 'success', response.toast_header_title, response.toast_body_title );

                                setTimeout( function() {
                                    location.reload();
                                }, 1000);
                            } else {
                                Flexify_Checkout_Admin.displayToast( 'error', response.toast_header_title, response.toast_body_title );
                            }
                        } catch (error) {
                            console.log(error);
                        }
                    },
                    error: function() {
                        btn.prop('disabled', false).html(btn_state.html);
                    },
                });
            });
        },

        /**
         * Initialize all modules
         * 
         * @since 5.1.0
         * @version 5.1.1
         */
        init: function() {
            this.initTabs();
            this.saveOptions();
            this.setupVisibilityControllers();
            this.enforceMinOne();
            this.filterDesignParameters();
            this.mediaSelectors();
            this.popups();
            this.colorHelpers();
            this.datepicker();
            this.fieldsManager();
            this.preLicenseActions();
            this.altLicenseUpload();
            this.resetSettings();
			this.themeSelector();
            this.handleConditions();
            this.activateLicense();
            this.deactiveLicense();
            this.syncLicense();
        },
    };

    /**
     * Initialize on document ready
     * 
     * @since 5.1.0
     */
    $(document).ready( function() {
        Flexify_Checkout_Admin.init();
    });

    /**
     * Export API to global scope
     * 
     * @since 5.1.0
     */
    window.Flexify_Checkout_Admin = Flexify_Checkout_Admin;

    /**
     * Fire trigger when admin module is ready
     * 
     * @since 5.1.0
     */
    $(document).trigger('flexify_checkout_admin_ready');

})(jQuery);