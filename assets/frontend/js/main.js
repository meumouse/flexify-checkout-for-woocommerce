( function($) {
    "use strict";

	/**
	 * Get global parameters
	 * 
	 * @since 5.0.0
	 */
	const params = window.flexify_checkout_params || {};

	/**
	 * Object variable for Flexify Checkout scripts
	 * 
	 * @since 5.0.0
	 * @package MeuMouse.com
	 */
	const Flexify_Checkout = {

		/**
		 * Validations object helper
		 * 
		 * @since 1.0.0
		 * @version 5.0.0
		 * @return void
		 */
		Validations: {

			/**
			 * Validation on change
			 * 
			 * By default Woo does not provide inline validation messages. 
			 * We use AJAX to get the correct message and then trigger Woo validation.
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return void
			 */
			onChange: function() {
				var fields = document.querySelectorAll('input, select, textarea');

				Array.from(fields).forEach( function(field) {
					field.addEventListener('change input', function(e) {
						e.preventDefault();

						// @TODO
					//	await Flexify_Checkout.Validations.getFieldErrors(field);

						return false;
					});
				});
			},

			/**
			 * Check if is valid email
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {string} email | Email address
			 * @returns bool
			 */
			isValidEmail: function(email) {
				var pattern = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;

				return email.match(pattern);
			},

			/**
			 * Check fields for errors
			 *
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {array} fields | Fields
			 * @returns bool
			 */
		/*	getFieldErrors: async function( field ) {
				var row = field.closest('.form-row');

				if ( ! row ) {
					return false;
				}

				if ( ! row.attributes['data-label'] || ! row.attributes['data-type'] ) {
					return false;
				}

				var value = Flexify_Checkout.Helpers.get_field_value(field);
				var type = row.attributes['data-type'].value;
				var get_country_element = document.getElementById('billing_country');

				var data = {
					action: 'flexify_check_for_inline_error',
					args: {
						label: row.attributes['data-label'].value,
						required: row.classList.contains('required'),
						type: type,
					},
					country: get_country_element ? get_country_element.value : '',
					key: field.attributes.name.value,
					value: value,
				};

				// Its too slow to trigger every field, so check the more advanced fields with ajax.
				if ('country' === type || 'postcode' === type || 'phone' === type || 'email' === type || 'text' === type ) {
					await Flexify_Checkout.Helpers.ajaxRequest(data, function(response) {
					var value = JSON.parse(response).data;
					var row = $(field).closest('.form-row');
					var update_element_text = value.input_id.replace('billing_', '');

					$('.flexify-review-customer__content').find('.customer-details-info.' + update_element_text).text(value.input_value);

					// Update the inline validation messages for the field.
					field.closest('.form-row').querySelector('.error').innerHTML = value.message;
					field.closest('.form-row').classList.remove('woocommerce-invalid');

					// Trigger Woo Validation.
					if (field.closest('.form-row').classList.contains('validate-required')) {
						$(field).trigger('validate');
					}

					// If a custom message has been returned, mark the row as invalid.
					if (value.isCustom) {
						field.closest('.form-row').classList.add('woocommerce-invalid');
					}

					if ('dont_offer' !== params.allow_login_existing_user) {
						if ('info' === value.messageType) {
                            if (!row.find('.info').length) {
                                row.append('<span class="info" style="display:none"></span>');
                            }

                            let $span = row.find('.info');
                            $span.slideDown();
                            $span.html(value.message);

                            if ('inline_popup' === params.allow_login_existing_user) {
                                _loginButtons__WEBPACK_IMPORTED_MODULE_2__["default"].openPopup(true);
                            }
						} else {
                            let $span = row.find('.info');
                            $span.slideUp();
						}
					}
					});
				} else {
					// Trigger Woo Validation.
					if (field.closest('.form-row').classList.contains('validate-required')) {
					    $(field).trigger('validate');
					}
				}

				var hasError = field.closest('.form-row').classList.contains('woocommerce-invalid');

				if (hasError) {
					Flexify_Checkout.Steps.disableNextSteppers(field.closest('[data-step]').attributes['data-step'].value);
				}

				Flexify_Checkout.Validations.accessibleErrors();

				return hasError;
			},*/

			/**
			 * Display checkout errors
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {string} message | Error message
			 * @return void
			 */
			displayErrors: function( message ) {

				/**
				 * In modern checkout layout, we use CSS to hide the default error messages because that breaks the layout.
				 * So we need to display our own.
				 */
				if ( Flexify_Checkout.Helpers.isModernCheckout() && message ) {
					// Split multiple error messages if necessary
					let messages = message.split('</div>');
					
					messages.forEach( function(message) {
						if ( message.trim() ) {
							message += '</div>';

							Flexify_Checkout.displayErrors( message );
						}
					});
				}
			},

			/**
			 * Remove checkout notices on click button
			 * 
			 * @since 3.5.0
			 * @version 5.0.0
			 */
			closeNotices: function() {
				$(document).on('click', '.close-notice', function(e) {
					e.preventDefault();

					let btn = $(this);
					var notice_wrap = btn.closest('.flexify-checkout-notice');
					
					if ( notice_wrap.length >= 1 ) {
						notice_wrap.addClass('removing-notice').fadeOut('fast');

						setTimeout( function() {
							$('.flexify-checkout-notice.removing-notice').remove();
						}, 500);
					} else {
						btn.parent('li').parent('ul.woocommerce-error').addClass('removing-notice').fadeOut('fast');
						
						setTimeout( function() {
							$('.woocommerce-error.removing-notice').remove();
						}, 500);
					}
				});
			},

			/**
			 * Clear error messages
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {string} Group Clear error messages only for this group. Group is the name of the data-* attribute. Example data-flexify-error.
			 * @return void
			 */
			clearErrorMessages: function( group ) {
				$('.woocommerce-notices-wrapper > div, .woocommerce-notices-wrapper ul').each( function() {
					// if group is provided, only remove the notices beloging to this group.
					if (group) {
						if ( $(this).attr(group) ) {
							$(this).remove();
						}
					} else {
						$(this).remove();
					}
				});

				$('.woocommerce-NoticeGroup').remove();
			},

			/**
			 * Scroll to first error on page
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return void
			 */
			scrollToError: function() {
				let error = document.querySelectorAll('.woocommerce-invalid')[0];

				if ( ! error ) {
					return;
				}

				error.scrollIntoView({
					behavior: 'smooth',
				});
			},

			/**
			 * Accessible errors
			 * Add some accessibility classes to our errors to help those using accessibility tools
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return void
			 */
			accessibleErrors: function() {
				let fields = document.querySelectorAll('input, select, textarea');

				Array.from(fields).forEach( function(field) {
					const row = field.closest('.form-row');

					if ( ! row ) {
						return;
					}

					const error = row.querySelector('.error');

					if ( error ) {
						error.setAttribute('aria-hidden', 'true');
						error.setAttribute('aria-live', 'off');
					}

					if ( row.classList.contains('woocommerce-invalid') ) {
						field.setAttribute('aria-invalid', 'true');
						
						if (error) {
							error.setAttribute('aria-hidden', 'false');
							error.setAttribute('aria-live', 'polite');
						}
					}
				});
			},

			/**
			 * Is this field purposfully marked as hidden by Checkout fields manager plugin
			 *
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {object} field | Field object
			 * @return {boolean}
			 */
			isHiddenConditionalField: function( field ) {
				const row = $(field).closest('.form-row');

				return row.is(':hidden') && row.hasClass('wooccm-conditional-child') || row.hasClass('temp-hidden');
			},

			/**
			 * Display global notice
			 * Render a global validation notice. Useful when an inline message is not possible.
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {string} message | Message
			 * @param {string} type | Type ('error', 'info', 'success')
			 * @param {string} format | Format
			 * @param {object} data | Additional data to add to the notice
			 * @return void
			 */
			displayGlobalNotices: function( message, type, format, data ) {
				// ES5 Support.
				if ( ! type ) {
					type = 'error';
				}
				
				if ( ! format ) {
					format = 'list';
				}

				var notice_area = document.querySelector('.woocommerce-notices-wrapper');

				if ( ! notice_area ) {
					return;
				}

				// Do not clear previous error messages
				// var existingNotices = notice_area.querySelectorAll('.woocommerce-error, .woocommerce-NoticeGroup-checkout');
				// existingNotices.forEach(function(notice) {
				//     notice.remove();
				// });

				var noticeContainer = document.createElement('div');
				var noticeType = 'woocommerce-error';

				if ( type !== 'error' ) {
					noticeType = 'woocommerce-message';
				}

				if ( type === 'info' ) {
					noticeType = 'woocommerce-info';
				}

				if ( typeof data === 'object' && ! Array.isArray(data) && data !== null ) {
					Object.entries(data).forEach( function(object) {
						var key = object[0];
						var value = object[1];

						noticeContainer.setAttribute( key, value );
					});
				}

				if ( format === 'list' ) {
					noticeContainer.classList.add('woocommerce-NoticeGroup');
					noticeContainer.classList.add('woocommerce-NoticeGroup-checkout');
					var noticeContainerList = document.createElement('ul');
					noticeContainerList.setAttribute('role', 'alert');
					noticeContainerList.classList.add(noticeType);
					var noticeListItem = document.createElement('li');
					noticeListItem.innerHTML = message;
					noticeContainerList.append(noticeListItem);
					noticeContainer.append(noticeContainerList);
				} else {
					noticeContainer.setAttribute('role', 'alert');
					noticeContainer.classList.add(noticeType);
					noticeContainer.innerHTML = message;
				}

				notice_area.append(noticeContainer);
			},

			/**
			 * Validate search form
			 * If there is a search form error, display a message
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return void
			 */
			validateSearchForm: function() {
				var addressSearches = document.querySelectorAll('#billing_address_search');

				Array.from(addressSearches).forEach( function(addressSearch) {
					var addressSection = addressSearch.closest('.woocommerce-billing-fields__wrapper').querySelector('.woocommerce-billing-fields');
					var style = window.getComputedStyle(addressSection);
					
					if ( style.display === 'none' ) {
						Flexify_Checkout.Validations.displayGlobalNotices( params.i18n.errorAddressSearch ); // Do global notice.

						// Remove previous notices.
						Array.from( addressSearch.closest('.form-row').querySelectorAll('.error') ).forEach( function(error) {
							error.remove();
						});

						// Do inline notice.
						var row = addressSearch.closest('.form-row');
						row.classList.add('woocommerce-invalid');
						var error = document.createElement('span');
						error.setAttribute('aria-hidden', 'false');
						error.setAttribute('aria-live', 'polite');
						error.classList.add('error');
						error.innerHTML = params.i18n.errorAddressSearch;
						row.append(error);
					}
				});
			},

			/**
			 * Check fields for errors
			 *
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {array} fields | Checkout fields
			 * @returns bool
			 */
			checkFieldsForErrors: function( fields, hasErrors = false ) {
				var inputs = {};
				var error_fields = [];

				// Return true is google address auto-complete field is present and empty.
				for ( var field of fields ) {
					if ( 'billing_address_search' === field.id ) {
						if ('' === field.value.trim() && 'none' === $('.woocommerce-billing-fields').css('display')) {
							field.closest('.form-row').classList.add('woocommerce-invalid');
							$(field).trigger('validate');
							field.closest('.form-row').classList.add('woocommerce-invalid');
							Flexify_Checkout.Components.removeSpinner();

							return true;
						}
					}
				}

				Flexify_Checkout.Components.addSpinner();
				
				// Get all the data so we can do an inline validation.
				Array.from(fields).forEach( function(field) {
					if ( ! field ) {
						return;
					}

					const row = field.closest('.form-row');

					if ( ! row ) {
						return;
					}

					if ( ! field.attributes['name'] || ! row.attributes['data-label'] || ! row.attributes['data-type'] ) {
						return;
					}

					let value = Flexify_Checkout.Helpers.getFieldValue( field );
					
					const billing_country = document.getElementById('billing_country');

					inputs[field.attributes.name.value] = {
						args: {
							label: row.attributes['data-label'].value,
							required: row.classList.contains('required'),
							type: row.attributes['data-type'].value
						},
						country: billing_country ? billing_country.value : '',
						key: field.attributes.name.value,
						value: value,
					};
				});

				// send AJAX request and return jqXHR
				Flexify_Checkout.Helpers.ajaxRequestWoo(
					{
						action: 'flexify_check_for_inline_errors',
						fields: inputs,
						'email': $('#billing_email').val(),
					},
					function(response) {
						var messages = response.data;

						// Update the inline validation messages for each field.
						Object.entries(messages).forEach( function(object) {
							var key = object[0];
							var value = object[1];
							var field = document.querySelector('[name="' + key + '"]');
							
							if ( ! field ) {
								return;
							}

							var update_element_text = value.input_id.replace('billing_', '');

							$('.flexify-review-customer__content').find('.customer-details-info.' + update_element_text).text(value.input_value);

							// If this field is hidden by Conditinal Field of Checkout Fields Manager plugin
							// then skip validation for this field.
							if ( Flexify_Checkout.Validations.isHiddenConditionalField(field) ) {
								return;
							}

							field.closest('.form-row').querySelector('.error').innerHTML = value.message;
							field.closest('.form-row').classList.remove('woocommerce-invalid');

							// Trigger Woo Validation.
							if (field.closest('.form-row').classList.contains('validate-required')) {
								$(field).trigger('validate');
								$(field).trigger('flexify_validate');
							}

							// If a custom message has been returned, mark the row as invalid.
							if ( value.isCustom ) {
								field.closest('.form-row').classList.add('woocommerce-invalid');
							}

							if ( field.closest('.form-row').classList.contains('woocommerce-invalid') ) {
								error_fields.push($(field).attr('id'));
							}
						});

						// update fragments on update checkout
						Flexify_Checkout.Components.updateFragments( messages.fragments );
					}
				);

				Flexify_Checkout.Validations.clearErrorMessages('data-flexify-error');

				// Check password strength if set.
				if ( fields[0] ) {
					var stepContainer = fields[0].closest('[data-step]');
					
					if (stepContainer) {
						var passwords = stepContainer.querySelectorAll('#account_password');

						Array.from(passwords).forEach( function(password) {
							var account_fields = password.closest('.woocommerce-account-fields');
							
							if ( account_fields ) {
								var create_account = account_fields.querySelector('#createaccount');

								if ( create_account && ! create_account.checked ) {
									return;
								}

								if ( ! password.value ) {
									return;
								}

								if ( ! password.closest('.form-row').querySelectorAll('.woocommerce-password-strength.good, .woocommerce-password-strength.strong').length ) {
									hasErrors = true;
								}
							}
						});
					}
				}

				if ( error_fields.length ) {
					var step = fields[0].closest('[data-step]').attributes['data-step'].value;
					// document.querySelector( `[data-stepper-li="${step}"]`).classList.add( 'error' );
					document.querySelector('[data-stepper-li="' + step + '"]').classList.add('error'); // ES5 Support.

					Flexify_Checkout.Steps.disableNextSteppers( step );
					
					Flexify_Checkout.Validations.scrollToError();
					Flexify_Checkout.Validations.validateSearchForm();
					Flexify_Checkout.Shippings.maybeShowShippingForm( error_fields );
				}

				Flexify_Checkout.Validations.accessibleErrors();
				
				Flexify_Checkout.Components.removeSpinner();

				return error_fields.length ? error_fields : false;
			},

			/**
			 * Add mask for each field with input mask defined
			 * 
			 * @since 3.5.0
			 * @version 5.0.0
			 * @return void
			 */
			addMaskOnFields: function() {
				const get_field_masks = params.get_input_masks;

				$.each(get_field_masks, function(id, value) {
					var field_id = $('#' + id);

					$(field_id).mask(value);
				});
			},

			/**
			 * Validate a single intl-tel-input field
			 *
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return {void}
			 */
			validatePhone: function() {
				const input = $(this);
				const val = input.val().trim();
				const row = input.closest('.form-row');
				const iti = input.data('itiInstance');

				// if empty and already invalid for being required, do nothing
				if ( ! val && row.hasClass('woocommerce-invalid-required-field') ) {
					return;
				}

				// this is to avoid validation on empty fields or fields with less than 4 characters
				if ( ! row.hasClass('has-changed') && val.length < 4 ) {
					row.removeClass('woocommerce-validated woocommerce-invalid');
					return;
				}

				// check if phone is valid
				const is_valid = iti && iti.isValidNumber();

				if ( ! is_valid ) {
					row.removeClass('woocommerce-validated').addClass('woocommerce-invalid woocommerce-invalid-phone').find('.error').text( params.i18n.phone.invalid );
				} else {
					row.removeClass('woocommerce-invalid woocommerce-invalid-phone');
				}
			},

			/**
			 * Mark phone as changed to allow full validation
			 *
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return {void}
			 */
			markPhoneChanged: function() {
				if ( $(this).val().trim() ) {
					$(this).closest('.form-row').addClass('has-changed');
				}
			},

			/**
			 * Initialize validations
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 */
			init: function() {
				this.onChange();
				this.closeNotices();

				let billing_email = $('#billing_email').val();

				// Offer to login if a user a user already exits with the matching email
				if ( billing_email && Flexify_Checkout.Validations.isValidEmail( billing_email ) ) {
					// @ TODO
				//	Flexify_Checkout.Validations.getFieldErrors( document.getElementById('billing_email') );
				}

				// Add mask for each field with input mask defined
				if ( params.enable_field_masks === 'yes' ) {
					Flexify_Checkout.Validations.addMaskOnFields();
				}
			},
		},

        /**
         * Handle sidebar actions
         * 
         * @since 1.0.0
         * @version 5.0.0
         */
        Sidebar: {

            /**
             * Add quantity controls
             * 
             * @since 1.0.0
             * @version 5.0.0
             * @return void
             */
            addQuantityControls: function() {
                var quantity_controls = document.querySelectorAll('.quantity input[type="number"]');

                Array.from(quantity_controls).forEach( function(control) {
                    var controlWrapper = control.closest('.quantity');

                    if (0 < $(controlWrapper).find('.quantity__button').length) {
                        return;
                    }

                    let buttonMinus = document.createElement('button');

                    buttonMinus.setAttribute('type', 'button');
                    buttonMinus.classList.add('quantity__button');
                    buttonMinus.classList.add('quantity__button--minus');
                    buttonMinus.innerHTML = '-';
                    controlWrapper.prepend(buttonMinus);
                    
                    buttonMinus.addEventListener('click', function() {
                        control.value = parseInt(control.value) - 1;
                        control.dispatchEvent(new Event('change'));
                    });

                    let buttonPlus = document.createElement('button');
                    buttonPlus.setAttribute('type', 'button');
                    buttonPlus.classList.add('quantity__button');
                    buttonPlus.classList.add('quantity__button--plus');
                    buttonPlus.innerHTML = '+';
                    controlWrapper.appendChild(buttonPlus);

                    buttonPlus.addEventListener('click', function() {
                        control.value = parseInt(control.value) + 1;
                        control.dispatchEvent(new Event('change'));
                    });
                    
                    control.addEventListener('change', async function(e) {
                    e.preventDefault();

                    // PHP side will be able to handle the quantity update.
                    $('body').trigger('update_checkout');
                        return false;
                    });
                });

                $('.quantity input[type="number"]').on('focusin', function() {
                    $(this).closest('.quantity').addClass('quantity--on-focus');
                });

                $('.quantity input[type="number"]').on('focusout', function() {
                    $(this).closest('.quantity').removeClass('quantity--on-focus');
                });
            },
            
            /**
             * Remove button
             * 
             * @since 1.0.0
             * @version 5.0.0
             * @return void
             */
            removeQuantityControls: function() {
                $(document).on('click', '.flexify-checkout__remove-link a.remove', function(e) {
                    e.preventDefault();

                    let btn = $(this);
                    let remove_item_url = btn.attr('href');
                    let cart_item = btn.closest('.cart_item');

                    // send ajax request
                    $.ajax({
                        url: remove_item_url,
                        method: 'GET',
                        beforeSend: function() {
                            $(document.body).trigger('update_checkout');
                        },
                        success: function(response) {
                            $(document.body).trigger('update_checkout');

                            cart_item.fadeOut(300, function() {
                                $(this).remove();
                            });
                        },
                        error: function(error) {
                            console.error('Error on remove item:', error);
                        },
                    });
                });
            },

            /**
             * Move shipping row
             * 
             * Move the shipping row to the top of the order table or
             * to address tab on mobile
             * 
             * @since 1.0.0
             * @version 5.0.0
             * @returns void
             */
            moveShippingRow: function() {
                var is_modern = document.querySelectorAll('.flexify-checkout--modern').length;

                // No need to run this code for classic theme
                if ( ! is_modern ) {
                    return;
                }

                if ( $('.woocommerce-checkout-review-order-table .woocommerce-shipping-totals').length ) {
                    $('.flexify-checkout__shipping-table tbody').html('');
                }

                // Pick the shipping row from content-right/sidebar.
                var shipping_row = $('.flexify-checkout__content-right tr.woocommerce-shipping-totals.shipping');

                if ( ! shipping_row.length ) {
                    return;
                }

                $('.flexify-step--address .flexify-checkout__shipping-table > tbody').html(shipping_row);
            },

            /**
             * Updates the cart count that is shown on the modern theme
             * 
             * @since 1.0.0
             * @version 5.0.0
             * @return void
             */
            updateCartCount: function() {
                var total = 0;

                $('.quantity input.qty').each( function() {
                    total += parseInt( $(this).val(), 10 );
                });

                const cart_count = $('.order_review_heading__count');

                if ( cart_count.length ) {
                    cart_count.html(total);
                }
            },

            /**
             * Update the total when the cart changes
             * 
             * @since 1.0.0
             * @version 5.0.0
             */
            updateSidebarTotal: function() {
                const total = $('.order-total td:last-of-type').html();

                $('.flexify-checkout__sidebar-header-total').html(total);
            },

            /**
             * Display order summary default is active
             * 
             * @since 3.5.0
             * @version 5.0.0
             */
            autoToggleOrderSummary: function() {
                var header = document.querySelector('.flexify-checkout__sidebar-header');
                
                if ( header ) {
                    header.click();
                }
            },

            /**
             * Hide Show Order Summary.
             * Toggle for the checkout summary on mobile view.
             * 
             * @since 1.0.0
             */
            orderSummaryToggle: function(first) {
                if ( ! Flexify_Checkout.Helpers.isMobile() ) {
                    return;
                }
                
                var isModern = document.querySelectorAll('.flexify-checkout--modern').length;
                var linkHide = document.querySelector('.flexify-checkout__sidebar-header-link--hide');
                var linkShow = document.querySelector('.flexify-checkout__sidebar-header-link--show');
                var sidebar = document.querySelector('.flexify-checkout__order-review');

                if ( isModern ) {
                    sidebar = document.querySelector('.flexify-checkout__content-right');
                }

                if ( ! linkHide || ! sidebar ) {
                    return;
                }

                var style = window.getComputedStyle(linkHide);

                if ( style.display === 'none' ) {
                    linkHide.style.display = 'block';
                    linkShow.style.display = 'none';

                    if (true === first) {
                        sidebar.style.display = 'block';
                    } else {
                        Flexify_Checkout.UI.slideDown(sidebar);
                    }

                    Flexify_Checkout.UI.slideDown(sidebar);
                } else {
                    linkHide.style.display = 'none';
                    linkShow.style.display = 'block';

                    if (true === first) {
                        sidebar.style.display = 'none';
                    } else {
                        Flexify_Checkout.UI.slideUp(sidebar);
                    }
                }
            },

            /**
             * Resize order summary
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
             * @return void
             */
            orderSummaryResize: function() {
                const $linkHide = $('.flexify-checkout__sidebar-header-link--hide');
                const $linkShow = $('.flexify-checkout__sidebar-header-link--show');
                const sidebar = $('.flexify-checkout__order-review');

                if ( Flexify_Checkout.Helpers.isModernCheckout() ) {
                    sidebar = $('.flexify-checkout__content-right');
                }

                // We never want to hide sidebar for desktop
                if ( ! Flexify_Checkout.Helpers.isMobile() && sidebar.is(":hidden") ) {
                    sidebar.show();

                    return;
                }
            },

            /**
			 * Initialize process checkout module
			 * 
			 * @since 5.0.0
			 */
			init: function() {
                this.addQuantityControls();
                this.removeQuantityControls();
                this.moveShippingRow();

                Flexify_Checkout.Sidebar.orderSummaryToggle(true);
                var header = document.querySelector('.flexify-checkout__sidebar-header');

                if ( header ) {
                    header.addEventListener('click', Flexify_Checkout.Sidebar.orderSummaryToggle);
                }

                $(window).on('resize', Flexify_Checkout.Sidebar.orderSummaryResize);

                // Adds check to open order summary automatically
                if ( params.opened_default_order_summary === 'yes' ) {
                    Flexify_Checkout.Sidebar.autoToggleOrderSummary();
                }

				let on_resize;

				$(window).on('resize', function() {
					clearTimeout( on_resize );
					on_resize = setTimeout( Flexify_Checkout.Sidebar.moveShippingRow, 250 );
				});
			},
        },

		/**
		 * Process checkout object helper
         * 
         * @since 5.0.0
		 */
		processCheckout: {
            /**
             * Cache object to store button html
             * 
             * @since 1.0.0
             * @version 5.0.0
             * @type {object}
             */
            cache: {
                button_html: '',
            },

            /**
             * On button click
             * 
             * @since 1.0.0
             * @version 5.0.0
             * @return void
             */
            onClick: function() {
                $(document).on('click', '#place_order', function(e) {
                    // Prevent the default behavior of the button
                    e.preventDefault();

                    Flexify_Checkout.Animations.preparePlaceOrderButton();
                    $('#place_order').addClass('flexify-checkout-btn-loading');

                    // enable purchase animation
                    if ( params.enable_animation_process_purchase === 'yes' ) {
                        Flexify_Checkout.Animations.purchaseAnimation.start();
                    }

                    // Simulate the form submission event
                    $('#place_order').closest('form').submit();
                });
            },

            /**
             * The HTML of checkout button would reset to default when payment method is selected
             * This function would change it back
             * 
             * @since 1.0.0
             * @version 5.0.0
             * @return void
             */
            onSelectPaymentMethod: function() {
                $(document.body).on('payment_method_selected', function() {
                    let place_order_html = Flexify_Checkout.processCheckout.cache.button_html;

                    if ( ! place_order_html || 'paypal' === $("[name='payment_method']:checked").val() ) {
                        return;
                    }

                    $('#place_order').html( place_order_html );
                });
            },

			/**
			 * Validate fields on place order
			 * 
			 * @since 3.5.0
			 * @version 5.0.0
			 * @return void
			 */
			onPlaceOrder: function() {
				$('form.checkout').on('checkout_place_order', function() {
					Flexify_Checkout.Conditions.checkFieldVisibility();
				});
			},

			/**
			 * Submit checkout form
			 * 
			 * @since 3.5.0
			 * @version 5.0.0
			 * @return void
			 */
			onSubmitCheckoutForm: function() {
				$('form.checkout').submit( function() {
					Flexify_Checkout.Conditions.checkFieldVisibility();
				});
			},

			/**
			 * Initialize process checkout module
			 * 
			 * @since 5.0.0
			 */
			init: function() {
                if ( $('#payment_method_stripe').length) {
                    return;
                }

                this.onClick();
                this.onSelectPaymentMethod();
				this.onPlaceOrder();
				this.onSubmitCheckoutForm();
			},
		},

		/**
		 * Handle JS event triggers
		 * 
		 * @since 5.0.0
		 */
		Triggers: {

			/**
             * On updated_checkout event. Modify the button html
             *
             * @since 1.0.0
             * @version 5.0.0
             * @param {object} e | Event object
             * @param {data} data |
             */
			onUpdatedCheckout: function() {
                $(document.body).on('updated_checkout', function(e, data) {
                    if ( data.fragments.flexify.total ) {
                        Flexify_Checkout.processCheckout.cache.button_html = `${params.i18n.pay} ${data.fragments.flexify.total}`;
                        $('#place_order').html(Flexify_Checkout.processCheckout.cache.button_html);
                    }

                    // update fragments on update checkout
                    if ( data?.fragments ) {
                        Flexify_Checkout.Components.updateFragments( data );
                    }

                    // update shipping fragments
                    if ( Flexify_Checkout.Helpers.isModernCheckout() ) {
                        Flexify_Checkout.Shippings.addShippingRowOnSummary( data );
                    }

					// if response has global error, display it
					if ( data?.fragments?.flexify?.global_error ) {
						Flexify_Checkout.Validations.displayGlobalNotices( data.fragments.flexify.global_error );
					}

                    // if the cart is empty, show the empty cart message
                    if ( data?.fragments?.flexify?.empty_cart ) {
                        $('.flexify-checkout').append( data.fragments.flexify.empty_cart );

                        setTimeout(() => {
                            window.location = params.shop_page;
                        }, 3000);
                    }

					Flexify_Checkout.Shippings.selectShippingMethod();
					Flexify_Checkout.Helpers.removeDomElements();
					Flexify_Checkout.Sidebar.init();
					Flexify_Checkout.Sidebar.updateSidebarTotal();
                });
			},

			/**
			 * Process actions when checkout has errors
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return void
			 */
			onCheckoutError: function() {
				/**
				 * Checkout error trigger
				 * 
				 * @since 1.0.0
				 * @version 5.0.0
				 * @param {object} e | Event object
				 * @param {string} error | Error message in HTML format
				 */
				$(document.body).on('checkout_error', function(e, error) {
					$('#place_order').removeClass('flexify-checkout-btn-loading');

					// Stop all ongoing animations and reset state
					Flexify_Checkout.Animations.purchaseAnimation.stop();

					Flexify_Checkout.Validations.displayErrors( error );
				});
			},

			/**
			 * Fire when WooCommerce fragments are updated
			 * 
			 * @since 5.0.0
			 * @returns void
			 */
			onFragmentsUpdated: function() {
				$(document.body).on('wc_fragments_refreshed', function() {
					Flexify_Checkout.Helpers.removeDomElements();
					Flexify_Checkout.Sidebar.init();
					Flexify_Checkout.Sidebar.updateSidebarTotal();
				});
			},

			/**
			 * Initialize module
			 * 
			 * @since 5.0.0
			 */
			init: function() {
				this.onUpdatedCheckout();
				this.onCheckoutError();
				this.onFragmentsUpdated();
			},
		},

		/**
		 * Coupons object helper
         * 
         * @since 1.0.0
         * @version 5.0.0
		 */
		Coupons: {

            /**
             * Login buttons on click
             * Handle the show and hide of the login form from a custom button
             * 
             * @since 1.0.0
             * @version 5.0.0
             * @return void
             */
            onClick: function() {
                var buttons = document.querySelectorAll('[data-show-coupon]');

                Array.from(buttons).forEach( function(button) {
                    button.setAttribute('type', 'button');
                    button.addEventListener('click', function(e) {
                        e.preventDefault();

                        var form = e.target.closest('.woocommerce-form-coupon__wrapper').querySelector('.woocommerce-form-coupon');

                        if ('none' === form.style.display) {
                            Flexify_Checkout.UI.slideDown(form);
                        } else {
                            Flexify_Checkout.UI.slideUp(form);
                        }

                        return false;
                    });
                });
            },

            /**
             * Send coupon code to WooCommerce
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return void
             */
            onSubmit: function() {
				$(document).on('click', 'button[name=apply_coupon]', function(e) {
					e.preventDefault();

					var form = $(this).closest('.woocommerce-form-coupon__wrapper');
					var row = form.find('.form-row');
					var btn = $(this);
					var btn_state = Flexify_Checkout.Helpers.keepButtonState(btn);

					// clear messages
					$('.woocommerce-form-coupon__wrapper').find('.error, .success').remove();

					// send AJAX request and return jqXHR
					Flexify_Checkout.Helpers.ajaxRequestWoo(
						{
							action: 'apply_coupon',
							coupon_code: form.find('input[name="coupon_code"]').val(),
							security: wc_checkout_params.apply_coupon_nonce
						},
						function(jqXHR, settings) {
							btn.prop('disabled', true).html('<span class="flexify-btn-processing-inline"></span>');
						},
						function(response) {
							var message = response.replace(/(<([^>]+)>)/gi, '');

							if ( response.includes('woocommerce-error') ) {
								row.addClass('woocommerce-invalid').eq(0).append(`<div class="error" aria-live="polite">${message}</div>`);
							} else {
								$(document.body).trigger('update_checkout', {
									update_shipping_method: false
								});

								$(document.body).one('updated_checkout', function() {
									$('.woocommerce-form-coupon__inner .form-row-first').append(`<div class="success" aria-live="polite">${message}</div>`);
								});
							}
						},
						function(jqXHR, textStatus, errorThrown) {
							console.error('Error applying coupon:', errorThrown);
						}
					).always( function() {
						btn.html(btn_state.html).prop('disabled', false);

						// remove button animation
						Flexify_Checkout.Components.removeSpinner();
					});

					return false;
				});
			},

            /**
             * On change coupon code input
             * 
             * @since 1.0.0
             * @version 5.0.0
             * @return void
             */
            onChange: function() {
                $(document).on('keyup', '#coupon_code', function() {
                    const btn = $(this).closest('.checkout_coupon').find('.flexify-coupon-button');
                    
                    if ( $(this).val().trim() ) {
                        btn.removeClass('flexify-coupon-button--disabled');
                    } else {
                        btn.addClass('flexify-coupon-button--disabled');
                    }
                });
            },

            /**
             * Remove coupon code
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return void
             */
            removeCoupon: function() {
                // Remove WooCommerce's event listener.
                $(document.body).off('click', '.woocommerce-remove-coupon');

                $(document.body).on('click', '.woocommerce-remove-coupon', function(e) {
                    e.preventDefault();

                    let coupon = $(this).data('coupon');

                    // send ajax request
                    $.ajax({
                        type: 'POST',
                        url: wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', 'remove_coupon'),
                        data: {
                            security: wc_checkout_params.remove_coupon_nonce,
                            coupon: coupon,
                        },
                        dataType: 'html',
                        success: function(code) {
                            $('.woocommerce-error, .woocommerce-message').remove();
                            $('.woocommerce-form-coupon__wrapper').find('.error, .success').remove();
                            
                            if (code) {
                                $(document.body).trigger('removed_coupon_in_checkout', [coupon]);

                                $(document.body).trigger('update_checkout', {
                                    update_shipping_method: false,
                                });

                                $(document.body).one('updated_checkout', function() {
                                    $('.woocommerce-form-coupon__inner .form-row-first').append(`<div class="success" aria-hidden="false" aria-live="polite">${params.i18n.coupon_success}</div>`);
                                });

                                // Remove coupon code from coupon field
                                $('form.checkout_coupon').find('input[name="coupon_code"]').val('');
                            }
                        },
                        error: function(jqXHR) {
                            if (params.debug_mode) {
                                console.log(jqXHR.responseText);
                            }
                        },
                    });
                });
            },

            /**
             * Initialize coupons module
             * 
             * @since 1.0.0
             * @version 5.0.0
             */
            init: function() {
                this.onClick();
                this.onSubmit();
                this.onChange();
                this.removeCoupon();

                var sidebar = $('.flexify-checkout--has-sidebar')[0];

                if ( sidebar ) {
                    $(document.body).on('wc_fragments_refreshed', function() {
                        Flexify_Checkout.Coupons.onClick();
                    });

                    $(document.body).on('updated_checkout', function() {
                        Flexify_Checkout.Coupons.onClick();
                    });

                    $(document).on('keydown', '#coupon_code', function(e) {
                        if (e.key === 'Enter' || e.keyCode === 13) {
                            $("[name=apply_coupon]").trigger('click');
                            e.preventDefault();
                        }
                    });

                    // Call removeCoupon after the WooCommerce event listener has been added
                    setTimeout( Flexify_Checkout.Coupons.removeCoupon, 100 );
                }
            },
		},

		/**
		 * Helpers object
		 * 
		 * @since 1.0.0
		 * @version 5.0.0
		 */
		Helpers: {

			/**
			 * Keep button width and height state
			 * 
			 * @since 5.0.0
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
			 * Check if checkout theme is modern
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return {boolean} 
			 */
			isModernCheckout: function() {
				return document.querySelectorAll('.flexify-checkout--modern').length;
			},

			/**
			 * Check if device is mobile
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @returns {boolean}
			 */
			isMobile: function() {
				const width = $(window).width();

				return width < 1024;
			},

			/**
			 * Serialize data
			 * 
			 * Transform a JavaScript object into a serialized string so it can be passed
			 * into PHP and decoded as an array
			 *
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {object} object | Object
			 * @param {string} prefix | Key prefix
			 * @return {string} | Serialized string
			 */
			serializeData: function(object, prefix) {
				var string = [];

				for ( var p in object ) {
					if ( object.hasOwnProperty( p ) ) {
						var key = prefix ? prefix + '[' + p + ']' : p, value = object[p];

						string.push( typeof value === 'object' ? Flexify_Checkout.Helpers.serializeData( value, key ) : encodeURIComponent( key ) + '=' + encodeURIComponent( value ) );
					}
				}

				return string.join('&');
			},

			/**
			 * Get field value
			 * 
			 * Get the value of a field, depending on its type
			 *
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {object} field | Field
			 * @return {string} | Value
			 */
			getFieldValue: function(field) {
				var value = field.value; // @todo account for other field types here.

				return value;
			},

			/**
			 * Do AJAX
			 * A simple AJAX function using $
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {object} data | Data
			 * @param {function} beforeSend | Before send ajax function
			 * @param {function} onSuccess | Success function
			 * @param {function} onError | Error function
			 */
			ajaxRequest: function( data, beforeSend, onSuccess, onError ) {
				// send AJAX request
				$.ajax({
					type: 'POST',
					url: params.ajax_url,
					data: Flexify_Checkout.Helpers.serializeData(data),
					contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
					dataType: 'json',
					beforeSend: function(jqXHR, settings) {
						if ( typeof beforeSend === 'function' ) {
							beforeSend( jqXHR, settings );
						}
					},
				}).done( function(response) {
					if (typeof onSuccess === 'function') {
						onSuccess(response);
					}
				}).fail( function(jqXHR, textStatus, errorThrown) {
					if (typeof onError === 'function') {
						onError(errorThrown);
					} else {
						console.error('AJAX Error:', errorThrown);
					}
				});
			},

			/**
			 * AJAX request to WooCommerce endpoint via jQuery
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {object} data | Data
			 * @param {function} beforeSend | Before send ajax function
			 * @param {function} onSuccess | Success function
			 * @param {function} onError | Error function
			 */
			ajaxRequestWoo: function( data, beforeSend, onSuccess, onError ) {
				// send AJAX request
				$.ajax({
					type: 'POST',
					url: wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', data.action),
					data: Flexify_Checkout.Helpers.serializeData(data),
					contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
					dataType: 'json',
					beforeSend: function(jqXHR, settings) {
						if ( typeof beforeSend === 'function' ) {
							beforeSend( jqXHR, settings );
						}
					},
				}).done( function(response) {
					if (typeof onSuccess === 'function') {
						onSuccess(response);
					}
				}).fail( function(jqXHR, textStatus, errorThrown) {
					if (typeof onError === 'function') {
						onError(errorThrown);
					} else {
						console.error('AJAX Error:', errorThrown);
					}
				});
			},

			/**
			 * Set cookie value
			 * 
			 * @since 5.0.0
			 * @param {string} name | Cookie name
			 * @param {string} value | Cookie value
			 * @param {int} days | Expiration time in days
			 * @returns {void}
			 */
			setCookie: function( name, value, days ) {
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
			 * @since 5.0.0
			 * @param {string} name | Cookie name
			 * @returns Cookie value
			 */
			getCookie: function( name ) {
				let matches = document.cookie.match(new RegExp(
					"(?:^|; )" + name.replace(/([\.\$?*|{}\(\)\[\]\/+^])/g, '\\$1') + "=([^;]*)"
				));

				return matches ? decodeURIComponent(matches[1]) : undefined;
			},

			/**
			 * Delete cookie by name
			 * 
			 * @since 5.0.0
			 * @param {string} name | Cookie name
			 * @return {void}
			 */
			deleteCookie: function( name ) {
				document.cookie = name + '=; Max-Age=0; path=/; domain=' + window.location.hostname;
			},

			/**
			 * Clean up the DOM instead of theme file override
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return {void}
			 */
			removeDomElements: function() {
				var login_toggle = document.querySelector('.woocommerce-form-login-toggle');
				var shopkeeper_login = document.querySelector('.shopkeeper_checkout_login');

				if ( login_toggle ) {
					login_toggle.remove();
				}

				if ( shopkeeper_login ) {
					shopkeeper_login.remove();
				}

				Flexify_Checkout.Helpers.repositionNotices();
			},

			/**
			 * Reposition Notices
			 * For some reason Woo does not always put the notices inside the wrapper, which breaks the layout. This fixes that.
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @returns {void}
			 */
			repositionNotices: function() {
				const is_modern = Flexify_Checkout.Helpers.isModernCheckout();
				const form_notice = document.querySelector('form.woocommerce-checkout > .woocommerce-NoticeGroup.woocommerce-NoticeGroup-checkout');
				const notice_wrapper = document.querySelector('.woocommerce-notices-wrapper');
				
				if ( is_modern && form_notice ) {
					var error = form_notice.querySelector('.woocommerce-error');

					if ( ! error ) {
						return;
					}

					var error_container = document.querySelector('.woocommerce > .woocommerce-notices-wrapper');

					error_container.append(error);
					form_notice.remove();
				}

				if ( is_modern && notice_wrapper ) {
					$('.woocommerce-notices-wrapper').prependTo('.flexify-checkout__steps');
				}
			},
		},

		/**
		 * Save all field's data in localStorage. Load it when page is loaded
		 * 
		 * @since 1.0.0
		 * @version 5.0.0
		 */
		localStorage: {

			/**
			 * Load data from local storage browser when page is loaded
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 */
			loadData: function() {
				const json = localStorage.getItem('flexify_checkout_form_data');
				const form = document.querySelector('form.checkout');

				if ( ! json || ! form ) {
					return;
				}

				const single_checkbox = ['order_notes_switch', 'show_shipping'];
				const data = JSON.parse(json);

				if ( typeof data !== 'object' ) {
					return;
				}

				data.forEach(fieldData => {
					const field = form.querySelector('[name="' + window.CSS.escape(fieldData.name) + '"]');

					if ( ! field ) {
						return;
					}

					if ( params.localstorage_fields.includes(fieldData.name) && fieldData.value && ! field.value ) {
						field.value = fieldData.value;
						field.dispatchEvent( new Event('change') );
					}

					if ( single_checkbox.includes(fieldData.name) && fieldData.value === 'on' ) {
						field.checked = true;
						field.dispatchEvent( new Event('change') );
					}
				});
			},

			/**
			 * Listen changes
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 */
			onChange: function() {
				// map selectors
				const inputs = document.querySelectorAll('form.checkout input, form.checkout textarea, form.checkout select');

				// listen changes
				inputs.forEach( input => {
					input.addEventListener('change', () => {
						const form = input.closest('form');

						if ( ! form ) {
							return;
						}

						const form_data = this.serializeData(form);
						const json = JSON.stringify(form_data);

						localStorage.setItem('flexify_checkout_form_data', json);
					});
				});
			},

			/**
			 * Serialize form data
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {object} formElement | Form element object
			 * @return {array}
			 */
			serializeData: function( formElement ) {
				const values = [];
				const inputs = formElement.elements;

				for ( let i = 0; i < inputs.length; i++ ) {
					if (inputs[i].name) {
						values.push({
							name: inputs[i].name,
							value: inputs[i].type === 'checkbox' ? inputs[i].checked ? 'on' : '' : inputs[i].value,
						});
					}
				}

				return values;
			},

			/**
			 * Initialize module
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 */
			init: function() {
				this.loadData();
				this.onChange();
			},
		},

		/**
		 * Object helper for checkout login
		 * 
		 * @since 1.0.0
		 * @version 5.0.0
		 */
		loginForm: {

			/**
			 * Show notice for the login form
			 *
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {string} message | The message to display
			 * @param {string} type | 'error' or 'success'
			 */
			showNotice: function(message, type) {
				if ( ! type ) {
					type = 'error';
				}

				var notice_wrapper = $('.flexify-login-notice');
				var typeClass = `flexify-login-notice--${type}`;
				
				notice_wrapper.removeClass('flexify-login-notice--success flexify-login-notice--error flexify-login-notice--info');
				notice_wrapper.addClass(typeClass);
				notice_wrapper.html(message);
			},

			/**
			 * Open modal for login on checkout
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {boolean} openAuto | Open modal automatically
			 */
			openModal: function( openAuto ) {
				let billing_email = $('#billing_email').val();

				if ( billing_email ) {
					$('.woocommerce-form-login #username').val(billing_email).trigger('change');
				}

				if ( openAuto ) {
					Flexify_Checkout.loginForm.showNotice( params.i18n.account_exists, 'info' );
				}

				window.setTimeout( function() {
					$('.woocommerce-form-login #password').focus().trigger('focus');
				}, 300);
				
				// open login modal
				$.magnificPopup.open({
					items: {
						src: '.woocommerce-form-login',
						type: 'inline',
					},
				});
			},
			
			/**
			 * Handle the show and hide of the login form from a custom button
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 */
			onClick: function() {
				// Remove the event listener added by WooCommerce, as it returns false,
				// causing our event listener to never run.
				setTimeout(() => {
					$(document.body).off('click', 'a.showlogin');
				}, 100);

				$(document).on('click', '[data-login], .showlogin', function(e) {
					e.preventDefault();

					Flexify_Checkout.loginForm.openModal();
				});
			},

			/**
			 * Change input password visibility
			 * 
			 * @since 1.9.0
			 * @version 5.0.0
			 */
			passwordVisibility: function() {
				$('.toggle-password-visibility .toggle').on('click', function() {
					var inputLoginPass = $('.flexify-login-password');
					var showPasswordIcon = $('.toggle-password-visibility .show-password');
					var hidePasswordIcon = $('.toggle-password-visibility .hide-password');

					if ( inputLoginPass.attr('type') === 'password' ) {
						inputLoginPass.attr('type', 'text');
						showPasswordIcon.hide();
						hidePasswordIcon.show();
					} else {
						inputLoginPass.attr('type', 'password');
						showPasswordIcon.show();
						hidePasswordIcon.hide();
					}
				});
			},

			/**
			 * Handle submit login event
			 *
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {object} e | Event object
			 */
			onSubmit: function(e) {
				e.preventDefault();

				const form = $('.woocommerce-form-login');

				const data = {
					action: 'flexify_checkout_login',
					username: form.find('#username').val(),
					password: form.find('#password').val(),
					remember: form.find('#rememberme').val(),
					_wpnonce: form.find('#woocommerce-login-nonce').val(),
				};
				
				let btn = $('.flexify-button.woocommerce-button.button.woocommerce-form-login__submit');
				let btn_state = Flexify_Checkout.Helpers.keepButtonState( btn );

				// send ajax request
				Flexify_Checkout.Helpers.ajaxRequest(
					data,
					function beforeSend() {
						btn.prop('disabled', true).html('<span class="flexify-btn-processing-inline"></span>');
					},
					function onSuccess(response) {
						if ( response.success ) {
							Flexify_Checkout.loginForm.showNotice( params.i18n.login_successful, 'success' );

							btn.prop('disabled', true).addClass('btn-success').html(`<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill:#fff"><path d="m10 15.586-3.293-3.293-1.414 1.414L10 18.414l9.707-9.707-1.414-1.414z"/></svg>`);

							window.location.reload();
						} else {
							Flexify_Checkout.loginForm.showNotice( response.data.error, 'error' );
						}
					},
					function onError(error) {
						console.error( 'Error on try login user: ', error );
						Flexify_Checkout.loginForm.showNotice( params.i18n.error, 'error' );
					},
				).always( function() {
					btn.html(btn_state.html).prop('disabled', false);
				});
			},

			/**
			 * Initialize module
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 */
			init: function() {
				this.onClick();
				this.passwordVisibility();

				/**
				 * If auto-open class is present in the login for i.e. user has entered a wrong password,
				 * Then open the login form automatically.
				 */
				if ( $('.woocommerce-form-login').hasClass('woocommerce-form-login--auto-open') ) {
					window.setTimeout( function() {
						Flexify_Checkout.loginForm.openModal();
					}, 1000);
				}

				$('.woocommerce-form-login > h2:first').append('<div class="flexify-login-notice"></div>');
      			$('.woocommerce-form-login').on('submit', Flexify_Checkout.loginForm.onSubmit);
			},
		},

		/**
		 * Animations object helper
		 * 
		 * @since 5.0.0
		 * @return void
		 */
		Animations: {

            /**
             * Prepare place order button DOM for animation
             * 
             * @since 1.0.0
             * @version 5.0.0
             * @return void
             */
            preparePlaceOrderButton: function() {
                if ( $("#place_order").find('.flexify-submit-dots').length ) {
                    return;
                }

                // add dots to the button
                $("#place_order").html( $("#place_order").html() + `<span class="flexify-submit-dots">
                    <i class="flexify-submit-dot flexify-submit-dot__1"></i>
                    <i class="flexify-submit-dot flexify-submit-dot__1"></i>
                    <i class="flexify-submit-dot flexify-submit-dot__1"></i>
                </span>`);
            },

			/**
			 * Process purchase animation
			 * 
			 * @since 3.9.4
			 * @version 5.0.0
			 * @return object
			 */
			purchaseAnimation: function() {
				let currentStep = 1; // Start at the first animation
				const totalSteps = 3; // Total number of steps
				const maxProgress = 95; // Maximum progress bar width
				let progressWidth = 0; // Current progress bar width
				let animationInterval; // Interval for animations
				let progressBarInterval; // Interval for progress bar
				let isAnimating = false; // Flag to track animation state

				// Start purchase animation
				const start_purchase_animation = function() {
					const animationGroup = $('#flexify_checkout_purchase_animation');
					const progressBar = animationGroup.find('.animation-progress-bar');

					// Prevent multiple animations from starting
					if (isAnimating) {
						return;
					}

					isAnimating = true;

					// Reset progress and step
					reset_animation();
					progressBar.css('width', '0%');

					// Add "active" class to main group
					animationGroup.addClass('active');

					// Remove WooCommerce overlay
					setTimeout(() => {
						$('.woocommerce-checkout.processing').find('.blockOverlay').css('display', 'none');
					}, 10);

					// Start animation sequence
					loop_animations();

					// Start progress bar updates
					progressBarInterval = setInterval(() => {
						update_progress_bar(progressBar);
					}, 1200);
				};

				// Loop through animations
				const loop_animations = function() {
					const animationGroup = $('#flexify_checkout_purchase_animation');

					// Clear previous interval
					clearInterval(animationInterval);

					// Start looping through steps
					animationInterval = setInterval(() => {
						// Remove "active" class from all steps
						animationGroup.find('.purchase-animation-item').removeClass('active');

						// Get the current animation item
						const currentItem = animationGroup.find(`.purchase-animation-item.animation-${currentStep}`);
						currentItem.addClass('active');

						// Play Lordicon animation
						const icon = currentItem.find('lord-icon')[0];
						
						if (icon && icon.playerInstance) {
							icon.playerInstance.playFromBeginning();
						}

						// Move to the next step
						currentStep = (currentStep % totalSteps) + 1;
					}, 2000); // 2 seconds between each animation
				};

				// Update progress bar
				const update_progress_bar = function(progressBar) {
					// Increment progress by a random value between 15% and 25%
					const increment = Math.floor(Math.random() * (25 - 15 + 1)) + 15;
					progressWidth += increment;

					if (progressWidth > maxProgress) {
						progressWidth = maxProgress;
						clearInterval(progressBarInterval); // Stop updates when max is reached
					}

					progressBar.css('width', `${progressWidth}%`);
				};

				// Stop all animations
				const stop_all_animations = function() {
					const animationGroup = $('#flexify_checkout_purchase_animation');
					const progressBar = animationGroup.find('.animation-progress-bar');

					// Clear intervals and reset progress
					clearInterval(animationInterval);
					clearInterval(progressBarInterval);
					isAnimating = false;
					progressWidth = 0;

					// Reset progress bar and animation steps
					progressBar.css('width', '0%');
					animationGroup.find('.purchase-animation-item').removeClass('active');
					animationGroup.removeClass('active');
				};

				// Reset animation state
				const reset_animation = function() {
					currentStep = 1; // Reset to the first animation
					progressWidth = 0; // Reset progress bar
					clearInterval(animationInterval);
					clearInterval(progressBarInterval);
				};

				return {
					start: start_purchase_animation,
					stop: stop_all_animations,
				};
			},
		},

		/**
		 * Handle with step changes
		 * 
		 * @since 5.0.0
		 */
		Steps: {

			/**
			 * Set steps hash
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @returns {object}
			 */
			steps_hash: params.steps,
			
			/**
			 * Get all the fields that are relevant to the current step
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {element} parent | Parent element
			 * @return {array} | Array of fields
			 */
			getFields: function(parent) {
				var $parent = $(parent);
				var account_fields = $parent.find('.create-account input, .create-account select, .create-account textarea');
				var shipping_fields = $parent.find('.woocommerce-shipping-fields input, .woocommerce-shipping-fields select, .woocommerce-shipping-fields textarea');
				var additional_fields = $parent.find('.woocommerce-additional-fields input, .woocommerce-additional-fields select, .woocommerce-additional-fields textarea');
				var fields = [];

				$parent.find('input, select, textarea').each( function() {
					var field = $(this);

					if ( ! $parent.find('input[name=createaccount]:checked').length && ! $parent.find('.create-account').filter( function() {
							return $(this).css('display') === 'block';
						}).length && account_fields.is(field) ) {
							return;
					}

					if ( ! $parent.find('input[name=ship_to_different_address]:checked').length && shipping_fields.is(field) ) {
						return;
					}

					if ( ! $parent.find('input[name=show_additional_fields]:checked').length && additional_fields.is(field) ) {
						return;
					}

					// Don't validate this field.
					if ( 'billing_phone_full_number' === field.attr('name') ) {
						return;
					}

					fields.push(this);
				});

				return fields;
			},

			/**
			 * On proceed to next step
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 */
			onStepperClick: function() {
				$('[data-stepper]').each( function() {
					$(this).on('click', async function(e) {
						e.preventDefault();

						// clear error messages
						Flexify_Checkout.Validations.clearErrorMessages();;
						
						let step_number = $(this).data('stepper');
						let is_active = $(this).closest('[data-stepper-li]').hasClass('selected');

						if ( is_active ) {
							return false;
						}

						// Check current step fields
						if (step_number > 1) {
							let fields = Flexify_Checkout.Steps.getFields( $(`[data-step="${step_number - 1}"]`) );
							let has_errors = Flexify_Checkout.Validations.checkFieldsForErrors(fields);

							console.log( has_errors );
						}

						if ( has_errors ) {
							return false;
						}

						// Only change the hash. Panels will be toggled by hashchange event listener.
						window.location.hash = '#' + Flexify_Checkout.Steps.steps_hash[step_number];

						// Woo trigger select2 reload.
						$(document.body).trigger('country_to_state_changed');

						return false;
					});
				});
			},

			/**
			 * Validation on step change
			 * 
			 * By default Woo does not provide inline validation messages. 
			 * We use AJAX to get the correct message and then trigger Woo validation
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 */
			onNextClick: function() {
				$('[data-step-next]').each( function() {
					$(this).on('click', function(e) {
						e.preventDefault();

						// add button spinner
						Flexify_Checkout.Components.addSpinner();

						Flexify_Checkout.Validations.clearErrorMessages();;

						var fields = Flexify_Checkout.Steps.getFields( $(this).closest('[data-step]') );
						var error_fields = Flexify_Checkout.Validations.checkFieldsForErrors(fields);

						if ( error_fields ) {
							return false;
						}

						var next_step_number = $(this).data('step-show');
						var next_step = $('[data-step="' + next_step_number + '"]');

						if ( ! next_step.length ) {
							return false;
						}

						// Only change the hash. Panels will be toggled by hashchange event listener.
						window.location.hash = '#' + Flexify_Checkout.Steps.steps_hash[next_step_number];

						// Woo trigger select2 reload.
						$(document.body).trigger('country_to_state_changed');

						return false;
					});
				});
			},

			/**
			 * Disable next steppers
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {int} currentStepNumber | The current step number
			 * @param {int} nextStepNumber | The next step number
			 * @return void
			 */
			switchStepper: function( currentStepNumber, nextStepNumber ) {
				const currentStepper = $(`[data-stepper-li="${currentStepNumber}"]`);
				const next_stepper = $(`[data-stepper-li="${nextStepNumber}"]`);

				// Handle steppers
				currentStepper.removeClass('error disabled selected');
				currentStepper.find('button').removeAttr('disabled aria-disabled');
				
				next_stepper.removeClass('error disabled').addClass('selected');
				next_stepper.find('button').removeAttr('disabled aria-disabled');
				
				Flexify_Checkout.Steps.completePreviousSteppers( nextStepNumber );
			},

			/**
			 * Disable next steppers
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {int} step_number | The step number
			 * @return void
			 */
			disableNextSteppers: function(step_number) {
				$('[data-stepper-li]').each( function() {
					var stepper = $(this);
					var stepper_value = stepper.data('stepper-li');

					if (step_number === stepper_value) {
						stepper.removeClass('complete');
					}

					if ( step_number >= stepper_value ) {
						return;
					}

					stepper.addClass('disabled').removeClass('complete');

					stepper.find('[data-stepper]').attr({
						'disabled': 'disabled',
						'aria-disabled': 'true',
					});
				});
			},

			/**
			 * Complete previous steps
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {int} stepNumber | The step number
			 * @return void
			 */
			completePreviousSteppers: function( stepNumber ) {
				$('[data-stepper-li]').each( function() {
					var stepper = $(this);
					var stepper_value = stepper.data('stepper-li');

					if ( stepper_value >= stepNumber ) {
						return;
					}

					stepper.addClass('complete');
				});
			},

			/**
			 * Switch panels
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {int} currentStepNumber | The current step number
			 * @param {int} nextStepNumber | The next step number
			 * @return void
			 */
			switchPanels: function( currentStepNumber, nextStepNumber ) {
				let currentStep = $(`[data-step="${currentStepNumber}"]`);
				let next_step = $(`[data-step="${nextStepNumber}"]`);

				currentStep.css('display', 'none').attr('aria-hidden', 'true');
				next_step.css('display', '').attr('aria-hidden', 'false');
				
				$(window).scrollTop(0);
			},

			/**
			 * 
			 */
			onHashChange: function(e) {
				if ( ! window.location.hash ) {
					return;
				}

				var hash, parts, step, scrollElement, goingForward;

				hash = window.location.hash.replace('#', '');
				goingForward = Flexify_Checkout.Steps.isHashGoingForward(e);

				if ( hash.includes("|") ) {
					parts = hash.split("|");
					step = parts[0];
					scrollElement = parts[1];
				} else {
					step = hash;
				}

				var next_stepper = document.querySelector('[data-hash="' + step + '"]');

				if ( ! next_stepper ) {
					return;
				}

				var next_step_number = next_stepper.attributes['data-stepper'].value;
				var stepper = document.querySelector('.flexify-stepper__step.selected .flexify-stepper__button');
				var currentStepNumber = stepper.attributes['data-stepper'].value;
				var step_number = stepper.attributes['data-stepper'].value;
				var is_active = next_step_number === currentStepNumber;

				if ( goingForward ) {
					Flexify_Checkout.Validations.clearErrorMessages();;
				}

				if ( is_active ) {
					Flexify_Checkout.Steps.scrollToElement( scrollElement );

					return false;
				}

				Flexify_Checkout.Steps.switchPanels(step_number, next_step_number);
				Flexify_Checkout.Steps.switchStepper(step_number, next_step_number);
				Flexify_Checkout.Steps.scrollToElement(scrollElement);

				// Woo trigger select2 reload.
				$(document.body).trigger('country_to_state_changed');

				// Trigger custom event.
				$(document.body).trigger('flexify_step_change');

				if (document.getElementById("billing_phone")) {
					document.getElementById("billing_phone").dispatchEvent(new Event('keyup'));
				}
			},

			/**
			 * Change step on load page
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return void
			 */
			handeStepOnPageLoad: function() {
				if ( ! window.location.hash ) {
					window.location.hash = Flexify_Checkout.Steps.steps_hash[1];
					return;
				}

				Flexify_Checkout.Steps.onHashChange();
			},

			/**
			 * Return index of the provided step slug
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {string} stepSlug | Step slug
			 * @return {boolean}
			 */
			findHashIndex: function( stepSlug ) {
				stepSlug = stepSlug.replace('#', '');

				for ( var idx in this.steps_hash ) {
					if ( this.steps_hash[idx] === stepSlug ) {
						return idx;
					}
				}

				return false;
			},

			/**
			 * Should be called on hashchange event. It tells if we are navigating to the
			 * next step by returning true.
			 *
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {object} e | Event Object
			 * @return {boolean}
			 */
			isHashGoingForward: function(e) {
				if ( ! e ) {
					return false;
				}

				let newUrl = new URL( e.newURL );
				let oldUrl = new URL( e.oldURL );
				let newHashIndex = Flexify_Checkout.Steps.findHashIndex( newUrl.hash );
				let oldHashIndex = Flexify_Checkout.Steps.findHashIndex( oldUrl.hash );

				return parseInt( newHashIndex ) > parseInt( oldHashIndex );
			},

			/**
			 * Scroll to element
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {string} scrollElement | Element to scroll
			 * @return void
			 */
			scrollToElement: function( scrollElement ) {
				if ( scrollElement && $(`#${scrollElement}`).length ) {
					$('html, body').animate({
						scrollTop: $(`#${scrollElement}`).offset().top - 60
					}, 'fast');
				}
			},

			/**
			 * Initialize module
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 */
			init: function() {
				this.handeStepOnPageLoad();
				this.onNextClick();
				this.onStepperClick();

				window.addEventListener( 'hashchange', Flexify_Checkout.Steps.onHashChange );
			},
		},

		/**
		 * Fields object helper
		 * 
		 * @since 5.0.0
		 */
		Fields: {

			/**
			 * Update hidden field
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {string} inputValue | The value of the input field
			 * @returns {void}
			 */
			updateInternationalPhoneHiddenField: function( inputValue ) {
				let hidden_name = $(this).attr('name') + '_full_number';
				let hidden_field = $(`[name=${hidden_name}]`);

				if ( hidden_field.length ) {
					hidden_field.val( inputValue );
				}
			},

			/**
			 * Instance intl-tel-input for phone fields and hook up validation
			 *
			 * @since 1.0.0
			 * @version 5.0.0
			 * @returns {void}
			 */
			internationalPhone: function() {
				const selector = '.flexify-intl-phone input[type="tel"], .flexify-intl-phone input[type="text"]';
				const inputs = $(selector);

				if ( ! inputs.length ) {
					return;
				}

				inputs.each((_, el) => {
					const $el = $(el);
					const iti = window.intlTelInput(el, {
						utilsScript: params.path_to_utils,
						autoPlaceholder: 'polite',
						containerClass: 'flexify-intl-phone--init',
						nationalMode: true,
						separateDialCode: true,
						initialCountry: params.base_country || 'BR',
						onlyCountries: params.allowed_countries || ['BR'],
						i18n: {
							searchPlaceholder: params.i18n.intl_search_input_placeholder,
						}
					});

					// storage the instance in the input element for later use
					$el.data('itiInstance', iti);

					// add init class to the row
					$el.closest('.form-row').addClass('flexify-intl-phone--init');

					// update the hidden full-number field when country changes
					el.addEventListener('countrychange', () => {
						Flexify_Checkout.Fields.updateInternationalPhoneHiddenField( iti.getNumber() );
					});

					// validate events
					$el.on('blur', Flexify_Checkout.Validations.markPhoneChanged).on('blur validate flexify_validate keyup', Flexify_Checkout.Validations.validatePhone);

					// Disable wc_checkout_form.validate_field() event listener on input event.
      				$('form.checkout').off('input', '**');
				});

				// listen change billing country
				$('#billing_country').on('change', function() {
					const code = $(this).val().toLowerCase();

					inputs.each((_, el) => {
						const iti = $(el).data('itiInstance');
						if (iti) iti.setCountry(code);
					});
				});
			},

			/**
			 * Does this field have a permanent placeholder?
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {jQuery} row | The field row to check
			 * @return {boolean}
			 */
			hasPermanentPlaceholder: function( row ) {
				const $label = row.find('label');

				if ( ! $label.length ) {
					return false;
				}

				const field_id = $label.attr('for');
				
				const always_placeholder = [
					'billing_address_info',
					'shipping_address_info',
					''
				];

				// Always placeholder these fields
				if ( always_placeholder.includes(field_id) ) {
					return true;
				}

				// Placeholder billing_phone when intl phone enabled
				if ( field_id === 'billing_phone' && params.international_phone === 'yes' ) {
					return true;
				}

				return false;
			},

			/**
			 * Add focus class to form row when input is focused
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return {void}
			 */
			addRemoveFocusClass: function() {
				$(document).on('focus', '.form-row input', function() {
					$(this).closest('.form-row').addClass('form-row--focus');
				});

				$(document).on('blur', '.form-row input', function() {
					$(this).closest('.form-row').removeClass('form-row--focus');
				});
			},

			/**
			 * Prepare fields on page load: add/remove `is-active` class based on content or permanent placeholder
			 *
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return {void}
			 */
			prepareFields: function() {
				$('.form-row').each( function() {
					const row = $(this);
					const input = row.find('input, select').first();
					
					// se for placeholder permanente ou tiver valor (ou for <select>)
					const is_permanent = Flexify_Checkout.Fields.hasPermanentPlaceholder(row);
					const has_value = input.is('select') || !! input.val();
					
					row.toggleClass('is-active', is_permanent || has_value);
				});
			},

			/**
			 * Toggle visibility and validation classes on a field row
			 *
			 * @since 5.0.0
			 * @param {string} selector | jQuery selector for the input
			 * @param {boolean} show | true to show, false to hide
			 * @param {boolean} required | true to set required attr, false to unset
			 * @param {boolean} addValidation true to add validate-required/required-field on show
			 * @return {void}
			 */
			toggleField: function( selector, show, required, addValidation = true ) {
				const field = $(selector);
				const row = field.closest('.form-row');

				field.prop('required', required);

				if ( show ) {
					row.removeClass('temp-hidden');

					if ( addValidation ) {
						row.addClass('validate-required required-field');
					}

					row.show();
				} else {
					if ( addValidation ) {
						row.removeClass('validate-required required-field');
					}

					row.addClass('temp-hidden').hide();
				}
			},
			
			/**
			 * Fetch address from API and fill the fields
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {string} type 'billing' ou 'shipping'
			 * @return {void}
			 */
			autoFillAddress: function( type ) {
				const postcode = $(`#${type}_postcode`);
				const code = postcode.val().replace(/\D/g, '');

				// map fields to add loading placeholder
				const fields = ['address_1','neighborhood','city','state'].map( field => {
					const input = document.getElementById(`${type}_${field}`);

					return input?.closest('p.form-row') || null; // return the closest form-row element
				}).filter(el => el); // remove null values

				// if has postcode, then try to auto fill address
				if ( code.length === 8 ) {
					// send request to API service
					$.ajax({
						type: 'GET',
						url: params.fill_address.api_service.replace( '{postcode}', code ),
						dataType: 'json',
						contentType: 'application/json',
						beforeSend: function() {
							// add loading placeholders
							Flexify_Checkout.UI.togglePlaceholder( fields, true );
						},
						success: function(response) {
							if ( response ) {
								this.fillAddressFields(type, response);
							}
						},
						error: function(error) {
							console.error('Auto fill address error: ', error);

							// remove loading placeholder
							Flexify_Checkout.UI.togglePlaceholder( fields, false );
						},
						complete: () => {
							// remove loading placeholder
							Flexify_Checkout.UI.togglePlaceholder( fields, false );
						},
					});
				}
			},

			/**
			 * Fill address fields with data returned by the API
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {string} type 'billing' or 'shipping'
			 * @param {object} data | JSON data returned by the API
			 * @return {void}
			 */
			fillAddressFields: function(type, data) {
				const p = params.fill_address;

				$(`#${type}_address_1`).val( data[p.address_param] ).change();
				$(`#${type}_neighborhood`).val(data[p.neightborhood_param]).change();
				$(`#${type}_city`).val( data[p.city_param] ).change();
				$(`#${type}_state`).val( data[p.state_param] ).change();
			},

			/**
			 * Initialize auto-fill for billing and shipping postcodes
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return {void}
			 */
			initAutoFillAddress: function() {
				const types = ['billing', 'shipping'];

				types.forEach( type => {
					const postcode = $(`#${type}_postcode`);
					const address_1 = $(`#${type}_address_1`);

					// if has postcode filled and address is empty, try to autofill
					if ( postcode.val() && ! address_1.val() ) {
						Flexify_Checkout.Shippings.autoFillAddress( type );
					}

					// on keyup event, try to autofill
					postcode.on('keyup', () => {
						Flexify_Checkout.Shippings.autoFillAddress( type );
					});
				});
			},

			/**
			 * Initialize CNPJ-based autofill on the billing CNPJ field
			 * 
			 * @since 1.4.5
			 * @version 5.0.0
			 * @return {void}
			 */
			initCnpjAutofill: function() {
				$('#billing_cnpj').on( 'blur', this.onCnpjBlur.bind(this) );
			},

			/**
			 * Handle blur event on CNPJ field: perform AJAX lookup and fill fields
			 * 
			 * @since 1.4.5
			 * @version 5.0.0
			 * @param {Event} e | Blur event
			 * @return {void}
			 */
			onCnpjBlur: function(e) {
				const cnpj = $(e.target).val().replace(/\D/g, '');

				if ( cnpj.length !== 14 ) {
					return;
				}

				// send AJAX request
				Flexify_Checkout.Helpers.ajaxRequest(
					{
						action: 'cnpj_autofill_query',
						cnpj: cnpj
					},

					// beforeSend: block the checkout form
					() => {
						// add loading placeholders
						Flexify_Checkout.UI.togglePlaceholder( 'fields', true );
					},
					response => {
						if ( response.success && response.data ) {
							// auto‐fill postcode if returned
							if ( response.data.cep ) {
								const raw = response.data.cep.replace(/\D/g, '');
								const fmt = raw.replace(/^(\d{5})(\d{3})/, '$1-$2');

								$('#billing_postcode').val(fmt);
							}

							this.fillCnpjFields(response.data);
						}

						// remove placeholders
						Flexify_Checkout.UI.togglePlaceholder( 'fields', false );
					},
					error => {
						console.error('CNPJ autofill error: ', error);

						// remove placeholders
						Flexify_Checkout.UI.togglePlaceholder( 'fields', false );
					}
				);
			},

			/**
			 * Fill checkout fields based on the CNPJ lookup response
			 * 
			 * @since 1.4.5
			 * @version 5.0.0
			 * @param {object} data | Response data from server
			 * @return {void}
			 */
			fillCnpjFields: function(data) {
				if (data.telefone) {
					$('#billing_phone').val(data.telefone);
				}

				if (data.nome) {
					$('#billing_company').val(data.nome);
					$('#billing_company_field').addClass('is-active');
				}

				if (data.logradouro) {
					$('#billing_address_1').val(data.logradouro);
				}

				if (data.numero) {
					$('#billing_number').val(data.numero);
				}

				if (data.bairro) {
					$('#billing_neighborhood').val(data.bairro);
				}

				if (data.municipio) {
					$('#billing_city').val(data.municipio);
				}

				if (data.uf) {
					$('#billing_state').val(data.uf);
				}
			},

			/**
			 * Hide Brazilian Market fields if billing country is not Brazil
			 * 
			 * @since 3.0.0
			 * @version 5.0.0
			 * @return {void}
			 */
			toggleBrazilianMarketFields: function() {
				const country = $('#billing_country').val();
				const selectors = [
					'#billing_persontype_field',
					'#billing_cpf_field',
					'#billing_rg_field',
					'#billing_cnpj_field',
					'#billing_ie_field',
					'#billing_cellphone_field',
					'#billing_birthdate_field',
					'#billing_sex_field',
					'#billing_number_field',
					'#billing_neighborhood_field'
				].join(',');

				// hide when country is not BR
				$(selectors).toggleClass('d-none', country !== 'BR');
			},

			/**
			 * Initialize module
			 * 
			 * @since 5.0.0
			 */
			init: function() {
				Flexify_Checkout.Fields.prepareFields();
				Flexify_Checkout.Fields.addRemoveFocusClass();

				// Add is-active class
				$(document).on('change focus keydown', '.form-row input, .form-row select, .form-row textarea', function() {
					$(this).closest('.form-row').addClass('is-active');
				});
				
				$(document).on('blur', '.form-row input, .form-row select, .form-row textarea', function() {
					let row = $(this).closest('.form-row');

					if ( Flexify_Checkout.Fields.hasPermanentPlaceholder( row ) || $(this).val() ) {
						return;
					}

					row.removeClass('is-active');
				});

				$(document.body).on('country_to_state_changed', function() {
					Flexify_Checkout.Fields.prepareFields();
				});

				// check if auto fill address is enabled
				if ( params.fill_address.enable_auto_fill_address === 'yes' && params.license_is_valid ) {
					Flexify_Checkout.Fields.initAutoFillAddress();
				}

				// check if auto fill company info is enabled
				if ( params.enable_autofill_company_info === 'yes' && params.license_is_valid ) {
					Flexify_Checkout.Fields.initCnpjAutofill();
				}

				// Bind country change to Brazilian Market fields toggler
				if ( params.enable_hide_brazilian_market_fields === 'yes' && params.license_is_valid ) {
					// initial check on page load
					$(document).ready( Flexify_Checkout.Fields.toggleBrazilianMarketFields.bind(this) );

					// re-toggle whenever country changes
					$('#billing_country').on( 'change', Flexify_Checkout.Fields.toggleBrazilianMarketFields.bind(this) );
				}

				// Initialize international phone input
				if ( params.enable_international_phone === 'yes' && params.license_is_valid ) {
					Flexify_Checkout.Fields.internationalPhone();
				}
			},
		},

        /**
         * Components object helper
         * 
         * @since 1.0.0
         * @version 5.0.0
         */
        Components: {

			/**
			 * Add the spinner
			 *
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return void
			 */
			addSpinner: function() {
				$('[data-step-next]').prop('disabled', true).addClass('flexify-button--processing');
				document.querySelector('.flexify-checkout__spinner').style.display = 'flex';
			},

			/**
			 * Remove the spinner
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return void
			 */
			removeSpinner: function() {
				$('[data-step-next]').prop('disabled', false).removeClass('flexify-button--processing');
				document.querySelector('.flexify-checkout__spinner').style.display = 'none';
			},

            /**
             * Add password strenght meter
             * 
             * @since 2.0.0
             * @version 5.0.0
             */
            passwordStrengthMeter: function() {
                if ( params.check_password_strenght !== 'yes' ) {
                    return;
                }

				// on change password field
                $('#account_password').on('keyup', function() {
                    let password = $(this).val();
                    let password_strenght_element = $('.woocommerce-password-strength');
                    let meter_bar = $('.create-account').find('.password-strength-meter');
                    let next_step_button = $('.flexify-button');

                    // reset classes
                    meter_bar.removeClass('short bad good strong');

                    if ( password !== '') {
                        $('.password-meter').addClass('active');

                        // Check if the class is present before accessing its properties
                        if ( password_strenght_element.length > 0 ) {
                            let password_strenght = password_strenght_element.attr('class');

                            if ( password_strenght.includes('short') ) {
                                meter_bar.addClass('short');
                                next_step_button.prop('disabled', true);
                            } else if ( password_strenght.includes('bad') ) {
                                meter_bar.addClass('bad');
                                next_step_button.prop('disabled', true);
                            } else if ( password_strenght.includes('good') ) {
                                meter_bar.addClass('good');
                                next_step_button.prop('disabled', false);
                            } else if ( password_strenght.includes('strong') ) {
                                meter_bar.addClass('strong');
                                next_step_button.prop('disabled', false);
                            }
                        }
                    } else {
                        $('.password-meter').removeClass('active');
                    }
                });
            },
             
            /**
             * Init Select2 for new select fields
             * 
             * @since 3.2.0
             * @version 5.0.0
             */
            initSelect2Fields: function() {
                const get_selects = params.get_new_select_fields || [];

                $(get_selects).each( function() {
                    $('#' + this).select2();
                });
            },

            /**
			 * Get fragments on updated checkout and replace HTML
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {string} fragments | Fragment HTML
			 * @return void
			 */
			updateFragments: function( fragments ) {
				for ( var selector in fragments ) {
					if ( $(selector).length ) {
						$(selector).replaceWith( fragments[selector] );
					}
				}
			},

			/**
			 * Enable the account toggle
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return void
			 */
			accountToggle: function() {
				$('.woocommerce-account-fields input#createaccount').unbind();

				Array.from(document.querySelectorAll('.woocommerce-account-fields input#createaccount')).forEach( function(checkbox) {
					checkbox.addEventListener('change', function(e) {
						e.preventDefault();
						e.stopPropagation();

						let account_fields = e.target.closest('.woocommerce-account-fields').querySelector('div.create-account');

						if ( ! account_fields ) {
							return false;
						}

						if ( e.target.checked ) {
							Flexify_Checkout.UI.slideDown(account_fields);
							account_fields.setAttribute('aria-hidden', 'false');
						} else {
							Flexify_Checkout.UI.slideUp(account_fields);
							account_fields.setAttribute('aria-hidden', 'true');
						}

						// Remove errors
						setTimeout( function() {
							Array.from(account_fields.querySelectorAll('input, select, textarea')).forEach( function(field) {
								field.closest('.form-row').classList.remove('woocommerce-invalid');
							});
						}, 1);

						$(document.body).trigger('country_to_state_changed');

						return false;
					});
				});
			},

			/**
			 * Enable the shipping toggle
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return void
			 */
			shippingToggle: function() {
				Array.from(document.querySelectorAll('#ship-to-different-address input')).forEach( function(checkbox) {
					checkbox.addEventListener('change', function(e) {
						e.preventDefault();

						var shipping_address_fields = e.target.closest('.woocommerce-shipping-fields__wrapper').querySelector('.shipping_address');

						if ( e.target.checked ) {
							Flexify_Checkout.UI.slideDown( shipping_address_fields );
							shipping_address_fields.setAttribute('aria-hidden', 'false');
						} else {
							Flexify_Checkout.UI.slideUp( shipping_address_fields );
							shipping_address_fields.setAttribute('aria-hidden', 'true');
						}

						$(document.body).trigger('country_to_state_changed');

						return false;
					});
				});
			},

			/**
			 * Enable the order notes toggle
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return void
			 */
			orderNotesToggle: function() {
				Array.from(document.querySelectorAll('#show-additional-fields input')).forEach( function(checkbox) {
					checkbox.addEventListener('change', function(e) {
						e.preventDefault();

						var additional_fields = e.target.closest('.woocommerce-additional-fields__wrapper').querySelector('.woocommerce-additional-fields');
						
						if (e.target.checked) {
							Flexify_Checkout.UI.slideDown(additional_fields);
							additional_fields.setAttribute('aria-hidden', 'false');
						} else {
							Flexify_Checkout.UI.slideUp(additional_fields);
							additional_fields.setAttribute('aria-hidden', 'true');
						}

						$(document.body).trigger('country_to_state_changed');

						return false;
					});
				});
			},

			/**
			 * Change data-type value for the country/state field when country is changed
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return void
			 */
			onCountryChange: function() {
				window.setTimeout( function() {
					if ( $("#billing_state").length ) {
						let tag_name = $("#billing_state").prop('tag_name');

						if ( 'SELECT' === tag_name ) {
							$("#billing_state").closest('.form-row').attr('data-type', 'select');
						} else {
							$("#billing_state").closest('.form-row').attr('data-type', 'text');
							$("#billing_state").attr('placeholder', '');
						}
					}

					if ( $("#shipping_state").length ) {
						let tag_name = $("#shipping_state").prop('tag_name');

						if ( 'SELECT' === tag_name ) {
							$("#shipping_state").closest('.form-row').attr('data-type', 'select');
						} else {
							$("#shipping_state").closest('.form-row').attr('data-type', 'text');
							$("#shipping_state").attr('placeholder', '');
						}
					}
				});
			},

			/**
			 * Watch country change
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return void
			 */
			watchCountryChange: function() {
				if ( ! $('#billing_country').length ) {
					Flexify_Checkout.Components.onCountryChange();

					return;
				}

				$('#billing_country, #shipping_country').change( function() {
					Flexify_Checkout.Components.onCountryChange();
				});

				Flexify_Checkout.Components.onCountryChange();
			},

			/**
			 * Change customer review data on update
			 * 
			 * @since 3.6.5
			 * @version 5.0.0
			 * @return void
			 */
			updateReviewData: function() {
				const fields = document.querySelectorAll('input, select, textarea');
  
				fields.forEach( function(field) {
					$(field).on('change input keyup', function() {
						let update_element_text = field.id.replace('billing_', '');
						let get_element_to_update = $('.flexify-review-customer__content').find('.customer-details-info.' + update_element_text);
						
						get_element_to_update.html( $(field).val() );
					});
				});
			},

			/**
			 * Show emails suggestions list
			 * 
			 * @since 3.5.0
			 * @version 5.0.0
			 * @param {jQuery} inputField | The email input field
			 * @param {jQuery} autoList | The <ul> container for suggestions
			 * @param {string[]} suggestions | Array of matching providers
			 * @return {void}
			 */
			showSuggestions: function( inputField, autoList, suggestions ) {
				autoList.empty();

				suggestions.forEach( provider => {
					const li = $('<li>').text( inputField.val().split('@')[0] + '@' + provider ).on('click', function() {
						inputField.val( $(this).text() ).trigger('change');
						autoList.removeClass('show');
						$("button[type='submit']").prop("disabled", false);
					});

					autoList.append( li );
				});

				autoList.addClass('show');
			},

			/**
			 * Bind suggestion behavior to a single email field
			 * 
			 * @since 3.5.0
			 * @version 5.0.0
			 * @param {jQuery} inputField | The email input field to bind suggestions to
			 * @return {void}
			 */
			bindEmailSuggestionField: function( inputField ) {
				const field_id = inputField.attr('id');
				let providers = params.get_email_providers || {};
				let container_id = '#flexify_checkout_email_suggest_' + field_id;

				// create container if missing
				if ( $(container_id).length === 0 ) {
					inputField.after(`<div id="flexify_checkout_email_suggest_${field_id}"></div>`);
				}

				const suggestionContainer = $(container_id);
				const autoList = $('<ul>').addClass('auto-list').appendTo(suggestionContainer);

				// key events
				inputField.on('keyup', () => {
					const val = inputField.val();

					if ( val.includes('@') ) {
						const parts = val.split('@');
						const domainPart = parts[1] || '';
						const matches = providers.filter( p => p.startsWith(domainPart) );

						if ( matches.length ) {
							this.showSuggestions( inputField, autoList, matches );
						} else {
							autoList.removeClass('show');
						}
					} else {
						autoList.removeClass('show');
					}
				});

				// hide on blur
				inputField.on('blur', () => {
					setTimeout(() => autoList.removeClass('show'), 200);
				});
			},

			/**
			 * Move all express checkout buttons to $wrap so they appear altogether within the checkout page
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @returns {void}
			 */
			relocateExpressButtons: function() {
				const wrapper = $('.flexify-express-checkout-wrap');
				
				// Stripe
				$("#wc-stripe-payment-request-wrapper>div").each( function() {
					$(this).appendTo(wrapper).wrap('<div class="flexify-express-checkout__btn flexify-expresss-checkout__btn--stripe flexify-skeleton"></div>');
				});

				// Paypal
				$('.eh_paypal_express_link').appendTo(wrapper).wrap('<div class="flexify-express-checkout__btn"></div>');
			},

			/**
			 * Hide express checkout wrap if stripe div is the only element and its empty
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return {void}
			 */
			stripeHideEmptyElement: function() {
				const wrapper = $('.flexify-express-checkout-wrap');
				
				if ( ! $('#wc-stripe-payment-request-button').length ) {
					return;
				}

				// If stripe elements is the only element then hide the express checkout wrap
				// and wait for Google/Apple pay buttons to mount.
				$('.flexify-expresss-checkout__btn--stripe').addClass('flexify-skeleton');

				// Wait for stripe payment elements to mount.
				setTimeout( function() {
					if ( ! $('#wc-stripe-payment-request-button div').length ) {
						$('#wc-stripe-payment-request-button').hide();
					}
					
					$('.flexify-expresss-checkout__btn--stripe').removeClass('flexify-skeleton');

					// if stripe is the only element in the express checkout and its empty then hide wrap.
					if ( wrapper.find(">div,>span,>a").length < 2 && 0 == $('#wc-stripe-payment-request-button>div').length ) {
						wrapper.hide();
					}
				}, 3000);
			},

            /**
             * Initialize module
             * 
             * @since 5.0.0
             */
            init: function() {
                this.initSelect2Fields();
                this.passwordStrengthMeter();
				this.accountToggle();
				this.shippingToggle();
				this.orderNotesToggle();
				this.watchCountryChange();
				this.updateReviewData();
				this.relocateExpressButtons();
				this.stripeHideEmptyElement();

				// Remove no-js class from HTML element
				$('html').removeClass('no-js');

				// check if email suggestions are enabled
				if ( params.enable_emails_suggestions === 'yes' ) {
					$('p.form-row[data-type="email"] input').each( (_, el) => {
						this.bindEmailSuggestionField( $(el) );
					});
				}
            },
        },

		/**
		 * User interface object helper
		 * 
		 * @since 1.0.0
		 * @version 5.0.0
		 */
		UI: {

			/**
			 * Slide down with JQuery and JS
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {object} element | Element object
			 * @return void
			 */
			slideDown: function( element ) {
				if ( 'block' === element.style.display ) {
					return;
				}

				element.style.height = 0;
				element.classList.add('slide-down');
				element.style.display = 'block';
				element.style.height = `${element.scrollHeight}px`;

				setTimeout( function() {
					element.classList.remove('slide-down');
					element.style.height = '';
				}, 500);
			},

			/**
			 * Slide up with JQuery and JS
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {object} element | Element object
			 * @return void
			 */
			slideUp: function( element ) {
				if ( 'none' === element.style.display ) {
					return;
				}

				element.style.height = `${element.scrollHeight}px`;
				element.classList.add('slide-up');

				setTimeout( function() {
					element.style.height = 0;
				}, 10);

				setTimeout( function() {
					element.style.display = 'none';
					element.classList.remove('slide-up');
					element.style.height = '';
				}, 500);
			},

			/**
			 * Add or remove the loading class on elements
			 * 
			 * @since 5.0.0
			 * @param {string|Element|jQuery|Array} elements  Seletor, elemento DOM, jQuery ou array deles
			 * @param {boolean} add true to add the class, false to remove it
			 * @return {void}
			 */
			togglePlaceholder: function( elements, add ) {
				$(elements).each( function() {
					$(this).toggleClass( 'flexify-checkout-loading-placeholder', add );
				});
			},
		},

		/**
		 * Handle with shipping functions
         * 
         * @since 1.0.0
         * @version 5.0.0
		 */
		Shippings: {

			/**
			 * Because of address autocomplete, the validation error on fields do not appear. 
			 * Display shipping form (manual) if 'Ship to a different address' is checked and address autocomplete
			 *
			 * @since 1.0.0
			 * @version 5.0.0
			 * @param {array} error_fields | Array of error fields
			 * @return void
			 */
			maybeShowShippingForm: function( error_fields ) {
				if ( ! $("#ship-to-different-address-checkbox").is(":checked") || ! error_fields ) {
					return;
				}

				var showManualAddressFields = false;

				// If at least one of the fields is a shipping field.
				error_fields.forEach(field => {
					if (field.includes("shipping_")) {
						showManualAddressFields = true;
					}
				});

				if (showManualAddressFields) {
					$(".shipping-address-search").slideUp();
					$(".woocommerce-shipping-fields").slideDown();
				}
			},

            /**
             * Add shipping cost row to the order review table for mobile view
             * 
             * @since 1.0.0
             * @version 5.0.0
             * @param {object} data | Data received from the fragment
             * @return void
             */
            addShippingRowOnSummary: function( data ) {
                // Add row if it doesn't exits
                if ( $('#flexify-checkout-summary-shipping-row').length === 0 ) {
                    $('#order_review > table > tfoot tr.cart-subtotal').after('<tr id="flexify-checkout-summary-shipping-row"></tr>');
                }

                let shipping_method = data?.fragments?.flexify?.shipping_row;

                // Add shipping cost data received from the fragment
                if ( shipping_method && shipping_method.length > 0 ) {
                    $('#flexify-checkout-summary-shipping-row').html(shipping_method);
                }
            },

			/**
			 * Watch country select2 events
			 * 
			 * @since 1.0.0
			 * @version 5.0.0
			 * @return void
			 */
			countryChanges: function() {
				$('select.country_select').on('select2:open', function(e) {
					let select2_above = $('.select2-dropdown--above');

					if ( select2_above.length <= 0 ) {
						return;
					}

					let field_row = $(this).closest('.form-row'), $label = field_row.find('label');
					$label.hide();

				}).on('select2:close', function(e) {
					let field_row = $(this).closest('.form-row'), $label = field_row.find('label');
					$label.show();
				});
			},

			/**
			 * Select shipping method
			 * 
			 * @since 3.5.0
			 * @version 5.0.0
			 * @return void
			 */
			selectShippingMethod: function() {
				// Remove the class from all shipping method items
				$('.shipping-method-item').removeClass('selected-method');

				// Find the selected shipping method input and add the class to its parent li
				$('input.shipping_method:checked').closest('.shipping-method-item').addClass('selected-method');
			},

			/**
			 * Initialize module
			 * 
			 * @since 5.0.0
			 */
			init: function() {
				this.countryChanges();
			
				// Trigger country to state change event on page load
				$(document.body).trigger('country_to_state_changed');

				// Event listener for clicks on .shipping-method-item
				$(document).on('click', '.shipping-method-item', function(event) {
					// Prevent default action if the click target is the input or label to avoid double triggering
					if ( $(event.target).is('input') || $(event.target).is('label') ) {
						return;
					}

					// Find the input radio inside the clicked .shipping-method-item and click it
					$(this).find('input[type="radio"]').prop('checked', true).trigger('change');
				});

				// Check again when the shipping method input changes
				$(document).on('change', 'input.shipping_method', function() {
					Flexify_Checkout.Shippings.selectShippingMethod();
				});

				// on change shipping method
				$(document.body).on('change', 'input.shipping_method', function() {
					Flexify_Checkout.Shippings.selectShippingMethod();
					Flexify_Checkout.Sidebar.updateSidebarTotal();
				});
			},
		},

		/**
		 * Handle with payment functions
		 * 
		 * @since 5.0.0
		 */
		Payments: {

			/**
			 * Update checkout on change payment method
			 * 
			 * @since 3.3.0
			 * @version 5.0.0
			 * @return void
			 */
			changePaymentMethod: function() {
				$(document.body).on('change', 'input[name="payment_method"]', function() {
					$(document.body).trigger('update_checkout');
				});
			},

			/**
			 * Initialize module
			 * 
			 * @since 5.0.0
			 */
			init: function() {
				this.changePaymentMethod();
			},
		},

		/**
		 * Handle with session functions
		 * 
		 * @since 1.8.5
		 * @version 5.0.0
		 */
		Session: {
			/**
			 * Debounce function to limit the rate of calls
			 *
			 * @since 3.6.0
			 * @version 3.9.9
			 * @param {Function} func  The function to debounce
			 * @param {number}   wait  Milliseconds to wait
			 * @return {Function}
			 */
			debounce: function(func, wait) {
				let timeout;

				return function() {
					const context = this, args = arguments;
					clearTimeout(timeout);
					timeout = setTimeout(() => func.apply(context, args), wait);
				};
			},

			/**
			 * Gather all checkout field values and send to session via AJAX
			 *
			 * @since 1.8.5
			 * @version 3.9.9
			 * @return {void}
			 */
			update: function() {
				const groups = params.get_all_checkout_fields || {};
				const fieldsData = [];

				// percorre cada grupo (billing, shipping, etc)
				$.each(groups, (idx, group) => {
					if (!group) {
						return true; // continue
					}

					// cada group é { field_id: props, … }
					$.each(group, (fieldId) => {
						const val = jQuery('#' + fieldId).val();
						if (val !== undefined) {
							fieldsData.push({ field_id: fieldId, value: val });
						}
					});
				});

				// send request
				Flexify_Checkout.Helpers.ajaxRequest(
					{
						action: 'get_checkout_session_data',
						fields_data: JSON.stringify(fieldsData),
						ship_to_different_address: $('#ship-to-different-address-checkbox').is(':checked') ? 'yes' : 'no'
					},
					null,
					null,
					error => console.error('Session update error:', error)
				);
			},

			/**
			 * Bind events and perform initial session update
			 *
			 * @since 1.8.5
			 * @version 3.9.9
			 * @return {void}
			 */
			init: function() {
				const groups = params.get_all_checkout_fields || {};
				const debounced = this.debounce(this.update.bind(this), 500);

				// primeira sincronização
				this.update();

				// quando qualquer campo billing mudar
				$.each(groups, (idx, group) => {
					if (group.billing) {
						jQuery.each(group.billing, (fieldId) => {
							jQuery('#' + fieldId).on('change input', debounced);
						});
					}
				});

				// quando clicar no botão de próximo passo
				$('.flexify-button[data-step-next]').on('click', function(e) {
					e.preventDefault();
					this.update();
				});
			}
		},

		/**
		 * Conditions object helper
		 * 
		 * @since 3.5.0
		 * @version 5.0.0
		 */
		Conditions: {

			/**
			 * Check condition
			 * 
			 * @since 3.5.0
			 * @version 5.0.0
			 * @param {string} condition - Check condition
			 * @param {string} value - Get condition value
			 * @param {string} value_compare - Optional value for compare with value
			 * @return {boolean}
			 */
			checkCondition: function( condition, value, value_compare = '' ) {
				switch ( condition ) {
					case 'is':           return value === value_compare;
					case 'is_not':       return value !== value_compare;
					case 'empty':        return value === '';
					case 'not_empty':    return value !== '';
					case 'contains':     return value.indexOf( value_compare ) !== -1;
					case 'not_contain':  return value.indexOf( value_compare ) === -1;
					case 'start_with':   return value.startsWith( value_compare );
					case 'finish_with':  return value.endsWith( value_compare );
					case 'bigger_then':  return parseFloat( value ) > parseFloat( value_compare );
					case 'less_than':    return parseFloat( value ) < parseFloat( value_compare );
					default:             return false;
				}
			},

			/**
			 * Show or hide fields based on conditions
			 * 
			 * @since 3.5.0
			 * @version 5.0.0
			 * @return {void}
			 */
			checkFieldVisibility: function() {
				const field_conditions = params.field_condition || [];

				field_conditions.forEach( cond => {
					const $comp = $( '#' + cond.component_field );
					const row = $comp.closest('.form-row');
					const val = $( '#' + cond.verification_condition_field ).val();
					const passed = this.checkCondition( cond.condition, val, cond.condition_value );

					if ( cond.type_rule === 'show' && cond.verification_condition === 'field' ) {
						if ( passed ) {
							$comp.prop('required', true);
							row.removeClass('temp-hidden').addClass('validate-required required-field').show();
						} else {
							$comp.prop('required', false);
							row.removeClass('required-field woocommerce-invalid validate-required').addClass('temp-hidden').hide();
						}
					} else if ( cond.type_rule === 'hide' ) {
						if ( passed ) {
							$comp.prop('required', false);
							row.removeClass('required required-field woocommerce-invalid validate-required').addClass('temp-hidden').hide();
						} else {
							$comp.prop('required', true);
							row.removeClass('temp-hidden').addClass('validate-required required-field').show();
						}
					}
				});

				// adiciona o asterisco nos labels que forem required
				$('label.has-condition.required-field > span.optional').remove();

				$('label.has-condition.required-field').each( function() {
					if ( ! $(this).find('abbr.required').length ) {
						$(this).append('<abbr class="required" title="' + params.i18n.required_field + '">*</abbr>');
					}
				});
			},

			/**
			 * Initialize module
			 * 
			 * @since 5.0.0
			 */
			init: function() {
				const field_conditions = params.field_condition || [];

				field_conditions.forEach( cond => {
					const selector = '#' + cond.verification_condition_field;

					$( document ).on('change input keyup', selector, () => {
						this.checkFieldVisibility();

						$('form.checkout').trigger('update_checkout').trigger('wc_fragment_refresh');
					});
				});

				this.checkFieldVisibility();
			},
		},

        /**
         * Compatibility functions
         * 
         * @since 1.0.0
         * @version 5.0.0
         */
        Compatibility: {

            /**
             * Add compatibility with Sales Booster
             * 
             * @since 1.0.0
             * @version 5.0.0
             * @return void
             */
            compatSalesBooster: function() {
                Array.from(document.querySelectorAll('[data-flexify-wsb-checkout-bump-trigger]')).forEach( function(checkbox) {
                    checkbox.addEventListener('change', function() {
                        $('[data-flexify-wsb-checkout-bump-trigger]').trigger('change');
                    });
                });
            },

            /**
             * Compatibility with Delivery Slots plugin
             * 
             * @since 1.0.0
             * @version 5.0.0
             * @return void
             */
            compatDeliverySlots: function() {
                // setTimeOut because we want our event listener to run after wc_checkout_form::validate_field().
                window.setTimeout( function() {
                    $('#jckwds-delivery-date, #jckwds-delivery-time').on('validate', function(e) {
                        if ('1' === $('[name=flexify-wds-fields-hidden]').val()) {
                            $(e.target).closest('.form-row').removeClass('woocommerce-invalid');
                            e.stopPropagation();
                        }
                    });
                });
            },

			/**
			 * Show/hide Brazilian-market checkout fields based on person type
			 *
			 * @since 3.9.6
			 * @version 5.0.0
			 * @return {void}
			 */
			updatePersonTypeFields: function() {
				const persontype = $('#billing_persontype');

				// check if has person type selector
				if ( ! persontype.length ) {
					return;
				}

				const type = parseInt( persontype.val(), 10 );

				// individual (1): show CPF, hide CNPJ, IE, Company
				Flexify_Checkout.Fields.toggleField( '#billing_cpf', type === 1, true,  true );
				Flexify_Checkout.Fields.toggleField( '#billing_cnpj', type === 1 ? false : true, false, true ); // hide for 1, show for 2
				Flexify_Checkout.Fields.toggleField( '#billing_ie', type === 1 ? false : true, false, true );
				Flexify_Checkout.Fields.toggleField( '#billing_company', type === 1 ? false : true, type === 2, false );
			},

			/**
			 * Bind change event and perform initial toggle on page load
			 *
			 * @since 5.0.0
			 * @return {void}
			 */
			initPersonTypeFields: function() {
				const fn = this.updatePersonTypeFields.bind(this);

				$(document).on('change', '#billing_persontype', fn);

				// initial state
				fn();
			},

            /**
             * Initialize compatibility functions
             * 
             * @since 5.0.0
             */
            init: function() {
                this.compatSalesBooster();
                this.compatDeliverySlots();
				this.initPersonTypeFields();

				// Handle the condition where back button is pressed and document.ready event is not triggered.
				$(window).on('pageshow', function() {
					Flexify_Checkout.Fields.prepareFields();
				});

				// When auto-saved address is pasted from the keyboard in iOS, it doesnt trigger update_checkout.
				$('.address-field input.input-text').on('input propertychange paste', function() {
					$(this).trigger('keydown');
				});
            },
        },

		/**
		 * Initialize main object
		 * 
		 * @since 5.0.0
		 */
		init: function() {
			// initialize local storage
			this.localStorage.init();

			// initialize compatibility functions
            this.Compatibility.init();

			// initialize listen trigger functions
			this.Triggers.init();

			// initialize login functions
			this.loginForm.init();
		
			// initialize steps functions
			this.Steps.init();

			// initialize fields functions
			this.Fields.init();

			// initialize payment functions
			this.Payments.init();

            // initialize sidebar functions
            this.Sidebar.init();

            // initialize components functions
            this.Components.init();

			// initialize conditions functions
			this.Conditions.init();

			// initialize session functions
			this.Session.init();
		},
	};

	// Initialize main object on document ready
	$(document).ready( function() {
		Flexify_Checkout.init();
	});
})(jQuery);