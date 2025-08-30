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
            if ( type === 'error' ) {
                type = 'danger';
            }

            const toast = `<div class="toast toast-${type} show">
                <div class="toast-header bg-${type} text-white">
                    <svg class="icon icon-white me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="#fff" d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2z"/><path fill="#fff" d="M10 13.6 7.7 11.3l-1.4 1.4L10 16.7l6.7-6.7-1.4-1.4z"/></svg>
                    <span class="me-auto">${header || ''}</span>
                    <button class="btn-close btn-close-white ms-2 hide-toast" type="button" aria-label="${params.close_aria_label_notice || 'Close'}"></button>
                </div>
                <div class="toast-body">${body || ''}</div>
            </div>`;

            $('.flexify-checkout-wrapper').before(toast);

            setTimeout( function() {
                $(`.toast-${type}`).fadeOut('fast', function() {
                    $(this).remove();
                });
            }, 3000);
        },

        /**
         * Hide toasts on click or auto
         * 
         * @since 1.0.0
         * @version 5.1.0
         */
        hideToasts: function() {
            $(document).on('click', '.hide-toast', function() {
                $('.updated-option-success, .update-notice-flexify-checkout, .toast').fadeOut('fast');
            });

            setTimeout( function() {
                $('.update-notice-flexify-checkout').fadeOut('fast');
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
                        action: 'admin_ajax_save_options',
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
                            }
                        } catch (err) {
                            console.log(err);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("AJAX request failed:", textStatus, errorThrown);
                    },
                    complete: function() {
                        btn.html(state.html).prop('disabled', false);
                    },
                });
            });
        },

        /**
		 * Visibility controllers for toggles/switches
		 * 
		 * @since 1.0.0
		 * @version 5.1.1
		 */
		setupVisibilityControllers: function() {
			/**
			 * Attach a simple visibility controller:
			 * show/hide a container based on a trigger value.
			 * 
			 * @since 5.1.1
			 * @param {string} triggerSel
			 * @param {string} targetSel
			 */
			const attach = (triggerSel, targetSel) => {
				const apply = () => {
					const $trigger = $(triggerSel);
					let on = false;

					if ( $trigger.is(':checkbox') ) {
						on = $trigger.is(':checked');
					} else {
						const val = $trigger.val();
						on = ( val !== 'no' && val !== 'false' && val !== '' );
					}

					$(targetSel).toggleClass('d-none', ! on);
				};

				// Bind events
				$(document).on('change', triggerSel, apply);

				// Run on load
				apply();
			};

			// Map original calls
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
         * @version 5.1.0
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
         * @version 5.1.0
         */
        preLicenseActions: function() {
            $('.pro-version').prop('disabled', true);

            $(document).on('click', '#active_license_form', function() {
                $('#popup-pro-notice').removeClass('show');
                $('.flexify-checkout-wrapper a.nav-tab[href="#about"]').click();
                window.scrollTo(0, 0);
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
                form_data.append('action', 'alternative_activation_license');
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
         * Initialize all modules
         * 
         * @since 5.1.0
         */
        init: function() {
            this.hideToasts();
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
			this.themeSelector();
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