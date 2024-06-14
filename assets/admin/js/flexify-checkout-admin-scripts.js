( function($) {
    "use strict";

	/**
	 * Activate fields
	 * 
	 * @since 1.0.0
	 * @version 3.5.0
	 */
	jQuery( function($) {
		// Reads the index stored in localStorage, if it exists
		let active_tab_index = localStorage.getItem('flexify_checkout_get_admin_tab_index');

		if (active_tab_index === null) {
			// If it is null, activate the general tab
			$('.flexify-checkout-wrapper a.nav-tab[href="#general"]').click();
		} else {
			$('.flexify-checkout-wrapper a.nav-tab').eq(active_tab_index).click();
		}
	});
	  
	$(document).on('click', '.flexify-checkout-wrapper a.nav-tab', function() {
		// Stores the index of the active tab in localStorage
		let tab_index = $(this).index();
		localStorage.setItem('flexify_checkout_get_admin_tab_index', tab_index);
		
		let attr_href = $(this).attr('href');
		
		$('.flexify-checkout-wrapper a.nav-tab').removeClass('nav-tab-active');
		$('.flexify-checkout-form .nav-content').removeClass('active');
		$(this).addClass('nav-tab-active');
		$('.flexify-checkout-form').find(attr_href).addClass('active');
		
		return false;
	});


	/**
	 * Hide toast on click button or after 3 seconds
	 * 
	 * @since 1.0.0
	 */
	jQuery( function($) {
		$('.hide-toast').click( function() {
			$('.updated-option-success, .update-notice-flexify-checkout').fadeOut('fast');
		});

		setTimeout( function() {
			$('.update-notice-flexify-checkout').fadeOut('fast');
		}, 3000);
	});


	/**
	 * Display loader and hide span on click
	 * 
	 * @since 1.0.0
	 */
	jQuery( function($) {
		$('.button-loading').on('click', function() {
			let btn = $(this);
			let btn_text = btn.text();
			let btn_width = btn.width();
			let btn_height = btn.height();

			// keep original width and height
			btn.width(btn_width);
			btn.height(btn_height);

			// Add spinner inside button
			btn.html('<span class="spinner-border spinner-border-sm"></span>');
		});

		// Prevent keypress enter
		$('.form-control').keypress( function(event) {
			if (event.keyCode === 13) {
				event.preventDefault();
			}
		});
	});


	/**
	 * Save options in AJAX
	 * 
	 * @since 1.0.0
	 */
	jQuery(document).ready( function($) {
		let settings_form = $('form[name="flexify-checkout"]');
		let originalValues = settings_form.serialize();
		var notification_delay;

		// send options to backend on change html
		settings_form.on('change', function() {
			// hide notice if already visible
			$('.updated-option-success').fadeOut('fast', function() {
				$(this).removeClass('active').css('display', '');
			});

			if (settings_form.serialize() !== originalValues) {
				ajax_save_options(); // send option serialized on change
			}
		});
	
		function ajax_save_options() {
			$.ajax({
				url: flexify_checkout_params.ajax_url,
				type: 'POST',
				data: {
					action: 'flexify_checkout_ajax_save_options',
					form_data: settings_form.serialize(),
				},
				success: function(response) {
					try {
					//	console.log(response.options);

						if (response.status === 'success') {
							originalValues = settings_form.serialize();

							// display notice
							$('.updated-option-success').addClass('active');
							
							if (notification_delay) {
								clearTimeout(notification_delay);
							}
				
							//hide notice on timeout
							notification_delay = setTimeout( function() {
								$('.updated-option-success').fadeOut('fast', function() {
									$(this).removeClass('active').css('display', '');
								});
							}, 3000);
						}
					} catch (error) {
						console.log(error);
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					console.error("AJAX request failed:", textStatus, errorThrown);
				}
			});
		}
	});


	/**
	 * Control visibility to elements on change toggle switch
	 * 
	 * @since 1.0.0
	 * @version 3.5.0
	 */
	jQuery( function($) {
		visibility_controller('#enable_auto_apply_coupon_code', '.show-coupon-code-enabled');
		visibility_controller('#enable_inter_bank_pix_api', '.inter-bank-pix');
		visibility_controller('#enable_inter_bank_ticket_api', '.inter-bank-slip');
		visibility_controller('#enable_fill_address', '.require-auto-fill-address');
		visibility_controller('#enable_manage_fields', '.step-checkout-fields-container');
		visibility_controller('#enable_field_masks', '.require-input-mask');
		visibility_controller('#email_providers_suggestion', '.require-email-suggestions-enabled');
	});


	/**
	 * Allow only number bigger or equal 1 in inputs
	 * 
	 * @since 1.0.0
	 */
	jQuery( function($) {
		let inputField = $('.allow-numbers-be-1');
		
		inputField.on('input', function() {
			let inputValue = $(this).val();
		
			if (inputValue > 1) {
				$(this).val(inputValue);
			} else {
				$(this).val(1);
			}
		});
	});


	/**
	 * Allow insert only numbers, dot and dash in design tab
	 * 
	 * @since 1.0.0
	 */
	jQuery( function($) {
		$('.design-parameters').keydown(function(e) {
			let key = e.charCode || e.keyCode || 0;

			return (
				(key >= 96 && key <= 105) || // numbers (numeric keyboard)
				(key >= 48 && key <= 57) || // numbers (top keyboard)
				key == 190 || // dot
				key == 189 || key == 109 || // dash
				key == 8 // backspace
			);
		});
	});


	/**
	 * Change visibility for elements logo or text
	 * 
	 * @since 1.0.0
	 */
	jQuery( function($) {
		let selectElement = $('select[name="checkout_header_type"]');
		let previousOption = selectElement.val();
	  
		selectElement.change( function() {
		  let selectedOption = $(this).val();
	  
		  if (selectedOption == 'logo') {
			$('.header-styles-option-logo').removeClass('d-none');
			$('.header-styles-option-text').addClass('d-none');
		  } else {
			$('.header-styles-option-text').removeClass('d-none');
			$('.header-styles-option-logo').addClass('d-none');
		  }
	  
		  previousOption = selectedOption;
		});
	  
		if (previousOption == 'logo') {
		  $('.header-styles-option-logo').removeClass('d-none');
		  $('.header-styles-option-text').addClass('d-none');
		} else {
		  $('.header-styles-option-text').removeClass('d-none');
		  $('.header-styles-option-logo').addClass('d-none');
		}
	});


	/**
	 * Open WordPress midia library popup on click
	 * 
	 * @since 1.0.0
	 */
	jQuery(document).ready( function($) {
		var file_frame;
	
		$('#flexify-checkout-search-header-logo').on('click', function(e) {
			e.preventDefault();
	
			// If the media frame already exists, reopen it
			if (file_frame) {
				file_frame.open();
				return;
			}
	
			// create midia frame
			file_frame = wp.media.frames.file_frame = wp.media({
				title: flexify_checkout_params.set_logo_modal_title,
				button: {
					text: flexify_checkout_params.use_this_image_title,
				},
				multiple: false
			});
	
			// When an image is selected, execute the callback function
			file_frame.on('select', function() {
				var attachment = file_frame.state().get('selection').first().toJSON();
				var imageUrl = attachment.url;
			
				// Update the input value with the URL of the selected image
				$('input[name="search_image_header_checkout"]').val(imageUrl).trigger('change'); // Force change
			});

			file_frame.open();
		});
	});


	/**
	 * Display popups
	 * 
	 * @since 2.3.0
	 * @version 3.5.0
	 */
	jQuery( function($) {
		display_popup( $('#inter_bank_credencials_settings'), $('#inter_bank_credendials_container'), $('#inter_bank_credendials_close') );
		display_popup( $('#inter_bank_pix_settings'), $('#inter_bank_pix_container'), $('#inter_bank_pix_close') );
		display_popup( $('#inter_bank_slip_settings'), $('#inter_bank_slip_container'), $('#inter_bank_slip_close') );
		display_popup( $('#require_inter_bank_module_trigger'), $('#require_inter_bank_module_container'), $('#require_inter_bank_module_close') );
		display_popup( $('#require_inter_bank_module_trigger_2'), $('#require_inter_bank_module_container'), $('#require_inter_bank_module_close') );
		display_popup( $('.require-pro'), $('.require-pro-container'), $('.require-pro-close') );
		display_popup( $('#set_ip_api_service_trigger'), $('.set-api-service-container'), $('.set-api-service-close') );
		display_popup( $('#add_new_checkout_fields_trigger'), $('.add-new-checkout-fields-container'), $('.add-new-checkout-fields-close') );
		display_popup( $('#auto_fill_address_api_trigger'), $('.auto-fill-address-api-container'), $('.auto-fill-address-api-close') );
		display_popup( $('#set_new_font_family_trigger'), $('#set_new_font_family_container'), $('#close_new_font_family') );
		display_popup( $('#fcw_reset_settings_trigger'), $('#fcw_reset_settings_container'), $('#fcw_close_reset') );
		display_popup( $('#add_new_checkout_condition_trigger'), $('#add_new_checkout_condition_container'), $('#close_add_new_checkout_condition') );
		display_popup( $('#set_email_providers_trigger'), $('#set_email_providers_container'), $('#close_set_email_providers') );
	});

	
	/**
	 * Process upload key and crt files
	 * 
	 * @since 2.3.0
	 */
	$(document).ready( function() {
		// Add event handlers for dragover and dragleave
		$('.dropzone').on('dragover dragleave', function(e) {
			e.preventDefault();
			$(this).toggleClass('drag-over', e.type === 'dragover');
		});
	
		// Add event handlers for drop
		$('.dropzone').on('drop', function(e) {
			e.preventDefault();
	
			var file = e.originalEvent.dataTransfer.files[0];

			if ( ! $(this).hasClass('file-uploaded') ) {
				handleFile(file, $(this));
			}
		});
	
		// Adds a change event handler to the input file
		$('#upload-file-crt, #upload-file-key').on('change', function(e) {
			e.preventDefault();
	
			var file = e.target.files[0];

			handleFile(file, $(this).parents('.dropzone'));
		});
	
		function handleFile(file, dropzone) {
			if (file) {
				var filename = file.name;

				dropzone.children('.file-list').removeClass('d-none').text(filename);
				dropzone.addClass('file-processing');
				dropzone.append('<div class="spinner-border"></div>');
				dropzone.children('.drag-text').addClass('d-none');
				dropzone.children('.drag-and-drop-file').addClass('d-none');
				dropzone.children('.form-inter-bank-files').addClass('d-none');
	
				// Create a FormData object to send the file via AJAX
				var form_data = new FormData();
				form_data.append('action', 'upload_file');
				form_data.append('file', file);
				form_data.append('type', dropzone.attr('id'));
	
				$.ajax({
					url: flexify_checkout_params.ajax_url,
					type: 'POST',
					data: form_data,
					processData: false,
					contentType: false,
					success: function(response) {
						try {
							if (response.status === 'success') {
								dropzone.addClass('file-uploaded').removeClass('file-processing');
								dropzone.children('.spinner-border').remove();
								dropzone.append('<div class="upload-notice d-flex flex-collumn align-items-center"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="#22c55e" d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path fill="#22c55e" d="M9.999 13.587 7.7 11.292l-1.412 1.416 3.713 3.705 6.706-6.706-1.414-1.414z"></path></svg><span>'+ flexify_checkout_params.upload_success +'</span></div>');
								dropzone.children('.file-list').addClass('d-none');
							} else if (response.status === 'invalid_file') {
								$('.drop-file-inter-bank').after('<div class="text-danger mt-2"><p>'+ flexify_checkout_params.invalid_file +'</p></div>');
								dropzone.addClass('invalid-file').removeClass('file-processing');
								dropzone.children('.spinner-border').remove();
								dropzone.children('.drag-text').removeClass('d-none');
								dropzone.children('.drag-and-drop-file').removeClass('d-none');
								dropzone.children('.form-inter-bank-files').removeClass('d-none');
								dropzone.children('.file-list').addClass('d-none');
							}
						} catch (error) {
							console.log(error);
						}
					},
					error: function(xhr, status, error) {
						dropzone.addClass('fail-upload').removeClass('file-processing');
						console.log('Erro ao enviar o arquivo');
						console.log(xhr.responseText);
					}
				});
			}
		}
	});


	/**
	 * Install required module
	 * 
	 * @since 2.3.0
	 */
	jQuery(document).ready( function($) {
		$('#install_inter_bank_module').on('click', function(e) {
			e.preventDefault();

			let btn = $(this);
			let get_text = btn.text();
			let btn_width = btn.width();
			let btn_height = btn.height();

			// keep original width and height
			btn.width(btn_width);
			btn.height(btn_height);
			btn.html('<span class="spinner-border spinner-border-sm"></span>');
			btn.prop('disabled', true);
	
			var plugin_url = 'https://github.com/meumouse/module-inter-bank-for-flexify-checkout/raw/main/dist/module-inter-bank-for-flexify-checkout.zip';
	
			$.ajax({
				type: 'POST',
				url: flexify_checkout_params.ajax_url,
				data: {
					action: 'install_inter_bank_module',
					plugin_url: plugin_url,
				},
				success: function(response) {
					btn.removeClass('btn-primary').addClass('btn-success');
					btn.html('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="#fff" d="m10 15.586-3.293-3.293-1.414 1.414L10 18.414l9.707-9.707-1.414-1.414z"></path></svg>');
					btn.prop('disabled', false);

					setTimeout(function() {
						location.reload();
					}, 500);
				},
				error: function(response) {
					console.log(response);
					btn.html(get_text);
					btn.prop('disabled', false);
					alert('Erro ao instalar o plugin: ' + response.responseText);
				}
			});
		});
	});


	/**
	 * Helper color selector
	 * 
	 * @since 2.4.0
	 * @version 2.6.0
	 */
	jQuery(document).ready( function($) {
		$('.get-color-selected').on('input', function() {
			var color_value = $(this).val();
	
			$(this).closest('.color-container').find('.form-control-color').val(color_value);
		});
	
		$('.form-control-color').on('input', function() {
			var color_value = $(this).val();
	
			$(this).closest('.color-container').find('.get-color-selected').val(color_value);
		});

		$('.reset-color').on('click', function(e) {
			e.preventDefault();
			var color_value = $(this).data('color');

			$(this).closest('.color-container').find('.form-control-color').val(color_value);
			$(this).closest('.color-container').find('.get-color-selected').val(color_value).change();
		});
	});


	/**
	 * Reorder and add new checkout fields
	 * 
	 * @since 3.0.0
	 * @version 3.2.0
	 */
	jQuery(document).ready( function($) {
		var step_1 = $('#contact_step').sortable({
			connectWith: '#shipping_step',
			update: function(event, ui) {
				updateFieldProperties(event, ui, '1');
			},
		});
	
		var step_2 = $('#shipping_step').sortable({
			connectWith: '#contact_step',
			update: function(event, ui) {
				updateFieldProperties(event, ui, '2');
			},
		});

		// Ordenar os campos do container #contact_step
		sort_fields_by_priority('contact_step');

		// Ordenar os campos do container #shipping_step
		sort_fields_by_priority('shipping_step');
	
		// Função para atualizar propriedades do campo
		function updateFieldProperties(event, ui, step) {
			var container = ui.item.closest('.step-container');
	
			// Atualiza a prioridade do campo
			$(container).find('.field-item').each( function(index) {
				$(this).find('.change-priority').val(index + 1).change();
			});
	
			// Atualiza o passo (step) do campo se movido para outra etapa
			$(ui.item).parent().siblings('.step-title').each( function() {
				$(this).closest('.step-container').find('.field-item').find('.change-step').val(step).change();
			});
		}

		// Função para ordenar os campos por prioridade
		function sort_fields_by_priority(container) {
			var container = $('#' + container);
			var fieldItems = container.find('.field-item');
	
			fieldItems.sort(function(a, b) {
				var priorityA = $(a).find('.change-priority').val();
				var priorityB = $(b).find('.change-priority').val();
				return priorityA - priorityB;
			});
	
			// Remover os elementos ordenados e reanexá-los ao container
			fieldItems.detach().appendTo(container);
		}

		// open popup
		$(document).on('click', '.flexify-checkout-step-trigger', function(e) {
			e.preventDefault();
			
			let get_field = $(e.target).closest('.field-item').addClass('active');

			get_field.children('.flexify-checkout-step-container').addClass('show');
			$(step_1).sortable( 'option', 'disabled', true );
			$(step_2).sortable( 'option', 'disabled', true );
		});

		// close popup on click close button
		$(document).on('click', '.flexify-checkout-step-close-popup', function(e) {
			e.preventDefault();

			$(this).closest('.flexify-checkout-step-container').removeClass('show');
			$('.flexify-checkout-step-trigger').closest('.field-item').removeClass('active');
			$(step_1).sortable( 'option', 'disabled', false );
			$(step_2).sortable( 'option', 'disabled', false );
		});

		// close popup if click outside the container
		$(document).on('click', '.flexify-checkout-step-container', function(e) {
			if (e.target === this) {
				$(this).removeClass('show');
				$('.flexify-checkout-step-trigger').closest('.field-item').removeClass('active');
				$(step_1).sortable( 'option', 'disabled', false );
				$(step_2).sortable( 'option', 'disabled', false );
			}
		});

		// deactive field on click toggle switch
		$(document).on('click', '.toggle-active-field', function(e) {
			let checked = $(e.target).prop('checked');
			let target = $('.field-item.active');

			$(target).toggleClass('active', checked).removeClass('inactive');
			$(target).toggleClass('inactive', !checked).removeClass('active');
		});

		if ( $('.field-item').hasClass('require-pro') ) {
			$(step_1).sortable( 'option', 'disabled', true );
			$(step_2).sortable( 'option', 'disabled', true );
		}

		// exclude field action
		$(document).on('click', '.exclude-field', function(e) {
			e.preventDefault();

			var index = $(this).data('exclude');
			var btn = $(this);
			var btn_width = btn.width();
			var btn_height = btn.height();
			var notification_delay;

			// keep original width and height
			btn.width(btn_width);
			btn.height(btn_height);

			// Add spinner inside button
			btn.html('<span class="spinner-border spinner-border-sm"></span>');

			$.ajax({
				url: flexify_checkout_params.ajax_url,
				type: 'POST',
				data: {
					action: 'remove_checkout_fields',
					field_to_remove: index,
				},
				success: function(response) {
					try {
						if (response && response.status === 'success') {
							// Fade out the field with animation
							$('#' + response.field).fadeOut( 500, function() {
								$(this).remove();
							});

							$('.updated-option-success').addClass('active');
	
							if (notification_delay) {
								clearTimeout(notification_delay);
							}
					
							notification_delay = setTimeout( function() {
								$('.updated-option-success').fadeOut('fast', function() {
									$(this).removeClass('active').css('display', '');
								});
							}, 3000);
						} else {
							console.error('Invalid JSON response or missing "status" field:', response);
						}
					} catch (error) {
						console.error('Error parsing JSON:', error);
					}
				},
				error: function(xhr, textStatus, errorThrown) {
					console.error('AJAX request failed:', textStatus, errorThrown);
				}
			});
		});

		// on digit id name for new option select
		$(document).on('keyup', '#checkout_field_name', function() {
			var concact_field = "billing_".concat( $(this).val() );

			if ( check_availability_field(concact_field) === false ) {
				$('#check_field_availability').removeClass('d-none');
				$('#set_field_id').addClass('invalid-option');
				$('#checkout_field_name_concat').val('');
			} else {
				$('#check_field_availability').addClass('d-none');
				$('#set_field_id').removeClass('invalid-option');
				$('#checkout_field_name_concat').val(concact_field);
			}
		});

		/**
		 * Check availability for target field
		 * 
		 * @since 3.2.0
		 * @param {string} field | ID and name field
		 * @return bool
		 */
		function check_availability_field(field) {
			var get_current_fields = flexify_checkout_params.get_array_checkout_fields;

			// check if field is equal index of current fields
			if ( get_current_fields.indexOf(field) !== -1 ) {
				return false;
			} else {
				return true;
			}
		}

		// display visibility for container options
		$(document).on('change', '#checkout_field_type', function() {
			var selected_option = $(this).val();

			if ( selected_option === 'select' ) {
				$('.require-add-new-field-select').removeClass('d-none');
			} else if ( selected_option === 'multicheckbox' ) {
				$('.require-add-new-field-multicheckbox').removeClass('d-none');
			} else {
				$('.require-add-new-field-select').addClass('d-none');
				$('.require-add-new-field-multicheckbox').addClass('d-none');
			}
		});

		// add new select option
		$(document).on('click', '#add_new_options_to_select', function(e) {
			e.preventDefault();

			let value = $('#add_new_field_select_option_value');
			let title = $('#add_new_field_select_option_title');
			let new_option = `<div class="d-flex align-items-center mb-2 option-container" data-option="${value.val()}">
				<div class="input-group me-2">
					<span class="input-group-text">${value.val()}</span>
					<span class="input-group-text input-control-wd-7.7">${title.val()}</span>
				</div>
				<button class="btn btn-outline-danger btn-icon rounded-3 exclude-option-select" data-exclude="${value.val()}">
					<svg class="icon icon-danger" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
				</button>
			</div>`;

			$('#preview_options_container').append(new_option);
			$('#preview_select_new_field').append(new Option(title.val(), value.val()));
			$(value).val('');
			$(title).val('');
		});

		// exclude select option action
		$(document).on('click', '.exclude-option-select', function(e) {
			e.preventDefault();

			let exclude_option = $(this).data('exclude');

			$(this).closest('.option-container').remove();
			$('#preview_select_new_field > option[value="'+ exclude_option +'"]').remove();
		});

		// add new multicheckbox
		$(document).on('click', '#add_new_options_to_multicheckbox', function(e) {
			e.preventDefault();

			let id = $('#add_new_field_multicheckbox_option_id');
			let title = $('#add_new_field_multicheckbox_option_title');
			let new_option = `<div class="form-check mb-2 multicheckbox-container">
				<input class="form-check-input" type="checkbox" id="${id.val()}">
				<label class="form-check-label" for="${id.val()}">${title.val()}</label>
				<button class="btn btn-outline-danger btn-icon rounded-3 ms-3 exclude-option-multicheckbox" data-exclude="${id.val()}">
					<svg class="icon icon-danger" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
				</button>
			</div>`;

			$('#preview_multicheckbox_container').append(new_option);
			$(id).val('');
			$(title).val('');
		});

		// exclude checkbox option
		$(document).on('click', '.exclude-option-multicheckbox', function(e) {
			e.preventDefault();

			$(this).closest('.multicheckbox-container').remove();
		});

		// change switch value
		$(document).on('change', '#required_field', function() {
			if ( $(this).is(':checked') ) {
				$(this).val('yes');
			} else {
				$(this).val('no');
			}
		});

		// change field name
		$(document).on('keyup input', '.get-name-field', function() {
			var input_value = $(this).val();
			var get_modal = $(this).closest('.flexify-checkout-step-container.show');

			get_modal.find('h5.popup-title').children('.field-name').text(input_value);
			get_modal.parent('.field-item').children('.field-name').text(input_value);
		});

		// processing form new field settings
		$(document).on('click', '#fcw_add_new_field', function(e) {
			e.preventDefault();

			var btn = $(this);
			var btn_width = btn.width();
			var btn_text = btn.text();
			var btn_height = btn.height();
			var notification_delay;
			var priority = flexify_checkout_params.get_array_checkout_fields.length + 1;
			// keep original width and height
			btn.width(btn_width);
			btn.height(btn_height);

			// Add spinner inside button
			btn.html('<span class="spinner-border spinner-border-sm"></span>');

			var id = $('#checkout_field_name_concat').val();
			var type = $('#checkout_field_type option:selected').val();
			var label = $('#checkout_field_title').val();
			var required = $('#required_field').val();
			var position = $('#field_position option:selected').val();
			var classes = $('#field_classes').val();
			var label_classes = $('#field_label_classes').val();
			var step = $('#field_step option:selected').val();
			var source = $('#field_source').val();
			var select_options = [];
			let selected_option = $('#checkout_field_type option:selected').val();
			var get_input_mask = $('#field_input_mask').val();

			if ( selected_option === 'select' ) {
				$('#preview_select_new_field option').each( function() {
					let option_value = $(this).val();
					let option_text = $(this).text();
	
					select_options.push({
						value: option_value,
						text: option_text,
					});
				});
			}
		
			var new_field_container = `<div id="${id}" class="field-item d-flex align-items-center justify-content-between">
				<input type="hidden" class="change-priority" name="checkout_step[${id}][priority]" value="${priority}">
				<input type="hidden" class="change-step" name="checkout_step[${id}][step]" value="${step}">

				<span class="field-name">${label}</span>

				<div class="d-flex justify-content-end">
					<button class="flexify-checkout-step-trigger btn btn-sm btn-outline-primary ms-auto rounded-3" data-trigger="${id}">${flexify_checkout_params.edit_popup_trigger_btn}</button>
					<button class="btn btn-outline-danger btn-icon ms-3 rounded-3 exclude-field" data-exclude="${id}">
						<svg class="icon icon-danger" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
					</button>
				</div>

				<div class="flexify-checkout-step-container">
					<div class="popup-content">
						<div class="popup-header">
							<h5 class="popup-title">${flexify_checkout_params.edit_popup_title}${` ` + label}</h5>
							<button class="flexify-checkout-step-close-popup btn-close fs-lg"></button>
						</div>
						<div class="popup-body">
							<table class="form-table">
								<tr>
									<th class="w-50">
										${flexify_checkout_params.edit_popup_active_field_title}
										<span class="flexify-checkout-description">${flexify_checkout_params.edit_popup_active_field_description}</span>
									</th>
									<td class="w-50">
										<div class="form-check form-switch">
											<input type="checkbox" class="toggle-switch toggle-active-field" name="checkout_step[${id}][enabled]" value="${required}" ${required} === 'yes' ? checked="checked" : ''/>
										</div>
									</td>
								</tr>
								<tr>
									<th class="w-50">
										${flexify_checkout_params.edit_popup_required_title}
										<span class="flexify-checkout-description">${flexify_checkout_params.edit_popup_required_description}</span>
									</th>
									<td class="w-50">
										<div class="form-check form-switch">
											<input type="checkbox" class="toggle-switch toggle-active-field" name="checkout_step[${id}][required]" value="${required}" ${required} === 'yes' ? checked="checked": '' />
										</div>
									</td>
								</tr>
								<tr>
									<th class="w-50">
										${flexify_checkout_params.edit_popup_label_title}
										<span class="flexify-checkout-description">${flexify_checkout_params.edit_popup_label_description}</span>
									</th>
									<td class="w-50">
										<input type="text" class="get-name-field form-control" name="checkout_step[${id}][label]" value="${label}"/>
									</td>
								</tr>
								<tr>
									<th class="w-50">
										${flexify_checkout_params.edit_popup_position_title}
										<span class="flexify-checkout-description">${flexify_checkout_params.edit_popup_position_description}</span>
									</th>
									<td class="w-50">
										<select class="form-select" name="checkout_step[${id}][position]">
											<option value="left" ${position === 'left' ? 'selected=selected': ''}>${flexify_checkout_params.edit_popup_position_left_option}</option>
											<option value="right" ${position === 'right' ? 'selected=selected': ''}>${flexify_checkout_params.edit_popup_position_right_option}</option>
											<option value="full" ${position === 'full' ? 'selected=selected': ''}>${flexify_checkout_params.edit_popup_position_full_option}</option>
										</select>
									</td>
								</tr>
								<tr>
									<th class="w-50">
										${flexify_checkout_params.edit_popup_classes_title}
										<span class="flexify-checkout-description">${flexify_checkout_params.edit_popup_classes_description}</span>
									</th>
									<td class="w-50">
										<input type="text" class="form-control" name="checkout_step[${id}][classes]" value="${classes}"/>
									</td>
								</tr>
								<tr>
									<th class="w-50">
										${flexify_checkout_params.edit_popup_label_classes_title}
										<span class="flexify-checkout-description">${flexify_checkout_params.edit_popup_label_classes_description}</span>
									</th>
									<td class="w-50">
										<input type="text" class="form-control" name="checkout_step[${id}][label_classes]" value="${label_classes}"/>
									</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
			</div>`;

			$.ajax({
				url: flexify_checkout_params.ajax_url,
				type: 'POST',
				data: {
					action: 'add_new_field_to_checkout',
					get_field_id: id,
					get_field_type: type,
					get_field_label: label,
					get_field_required: required,
					get_field_position: position,
					get_field_classes: classes,
					get_field_label_classes: label_classes,
					get_field_step: step,
					get_field_source: source,
					get_field_priority: priority,
					get_field_options_for_select: select_options,
					input_mask: get_input_mask,
				},
				success: function(response) {
					try {
						if (response.status === 'success') {
							$('#fcw_add_new_field').text(btn_text);
							$('.updated-option-success').addClass('active');
	
							if (notification_delay) {
								clearTimeout(notification_delay);
							}
					
							// hide notice on timeout
							notification_delay = setTimeout( function() {
								$('.updated-option-success').fadeOut('fast', function() {
									$(this).removeClass('active').css('display', '');
								});
							}, 3000);

							// add new field to container
							if ( step === '1' ) {
								$('#contact_step').append(new_field_container);
							} else if ( step === '2' ) {
								$('#shipping_step').append(new_field_container);
							}

							// close popup
							$('.add-new-checkout-fields-container').removeClass('show');

							// set default values
							$('#checkout_field_name').val('');
							$('#checkout_field_name_concat').val('');
							$('#checkout_field_type').val('text').attr('selected','selected');
							$('#checkout_field_title').val('');
							$('#required_field').val('no');
							$('#field_position').val('left').attr('selected','selected');
							$('#field_classes').val('');
							$('#field_label_classes').val('');
							$('#field_step').val('1').attr('selected','selected');
							$('#field_source').val('');
							$('#field_input_mask').val('');
						} else {
							console.error('Invalid JSON response or missing "status" field:', response);
						}
					} catch (error) {
						console.error('Error parsing JSON:', error);
					}
				},
				error: function(xhr, textStatus, errorThrown) {
					console.error('AJAX request failed:', textStatus, errorThrown);
				}
			});
		});
	});


	/**
	 * Before active license actions
	 * 
	 * @since 3.0.0
	 */
	jQuery( function($) {
		$('.pro-version').prop('disabled', true);

		$('#active_license_form').on('click', function() {
			$('.require-pro-container').removeClass('show');
			$('.flexify-checkout-wrapper a.nav-tab[href="#about"]').click();
		});
	});


	/**
	 * Include Bootstrap date picker
	 * 
	 * @since 3.2.0
	 */
	jQuery(document).ready( function($) {
		/**
		 * Initialize Bootstrap datepicker
		 */
		$('.dateselect').datepicker({
			format: 'dd/mm/yyyy',
			todayHighlight: true,
			language: 'pt-BR',
		});
		
		$(document).on('focus', '.dateselect', function() {
			if ( ! $(this).data('datepicker') ) {
				$(this).datepicker({
					format: 'dd/mm/yyyy',
					todayHighlight: true,
					language: 'pt-BR',
				});
			}
		});
	});


	/**
	 * Process upload alternative license
	 * 
	 * @since 3.3.0
	 */
	jQuery(document).ready( function() {
		// Add event handlers for dragover and dragleave
		$('#license_key_zone').on('dragover dragleave', function(e) {
			e.preventDefault();
			$(this).toggleClass('drag-over', e.type === 'dragover');
		});
	
		// Add event handlers for drop
		$('#license_key_zone').on('drop', function(e) {
			e.preventDefault();
	
			var file = e.originalEvent.dataTransfer.files[0];

			if ( ! $(this).hasClass('file-uploaded') ) {
				handle_file(file, $(this));
			}
		});
	
		// Adds a change event handler to the input file
		$('#upload_license_key').on('change', function(e) {
			e.preventDefault();
	
			var file = e.target.files[0];

			handle_file(file, $(this).parents('.dropzone'));
		});
	
		/**
		 * Handle sent file
		 * 
		 * @since 3.3.0
		 * @param {string} file | File
		 * @param {string} dropzone | Dropzone div
		 * @returns void
		 */
		function handle_file(file, dropzone) {
			if (file) {
				var filename = file.name;

				dropzone.children('.file-list').removeClass('d-none').text(filename);
				dropzone.addClass('file-processing');
				dropzone.append('<div class="spinner-border"></div>');
				dropzone.children('.drag-text').addClass('d-none');
				dropzone.children('.drag-and-drop-file').addClass('d-none');
				dropzone.children('.form-inter-bank-files').addClass('d-none');
	
				// Create a FormData object to send the file via AJAX
				var form_data = new FormData();
				form_data.append('action', 'alternative_activation_license');
				form_data.append('file', file);
	
				$.ajax({
					url: flexify_checkout_params.ajax_url,
					type: 'POST',
					data: form_data,
					processData: false,
					contentType: false,
					success: function(response) {
						try {
							if (response.status === 'success') {
								dropzone.addClass('file-uploaded').removeClass('file-processing');
								dropzone.children('.spinner-border').remove();
								dropzone.append('<div class="upload-notice d-flex flex-collumn align-items-center"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="#22c55e" d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path fill="#22c55e" d="M9.999 13.587 7.7 11.292l-1.412 1.416 3.713 3.705 6.706-6.706-1.414-1.414z"></path></svg><span>'+ flexify_checkout_params.upload_success +'</span></div>');
								dropzone.children('.file-list').addClass('d-none');

								setTimeout( function() {
									location.reload();
								}, 1000);
							} else if (response.status === 'invalid_file') {
								$('.drop-file-license-key').after('<div class="text-danger mt-2"><p>'+ flexify_checkout_params.invalid_file +'</p></div>');
								dropzone.addClass('invalid-file').removeClass('file-processing');
								dropzone.children('.spinner-border').remove();
								dropzone.children('.drag-text').removeClass('d-none');
								dropzone.children('.drag-and-drop-file').removeClass('d-none');
								dropzone.children('.form-inter-bank-files').removeClass('d-none');
								dropzone.children('.file-list').addClass('d-none');
							}
						} catch (error) {
							console.log(error);
						}
					},
					error: function(xhr, status, error) {
						dropzone.addClass('fail-upload').removeClass('file-processing');
						console.log('Erro ao enviar o arquivo');
						console.log(xhr.responseText);
					}
				});
			}
		}
	});


	/**
	 * Add new font action
	 * 
	 * @since 3.5.0
	 */
	jQuery(document).ready( function($) {
		var get_font_name = false;
		var get_font_url = false;

		// on change font name
		$(document).on('change', '#set_new_font_family_name', function() {
            get_font_name = $(this).val() !== '';
            update_add_font_state();
        });

		// on change font url
		$(document).on('change', '#set_new_font_family_url', function() {
            get_font_url = $(this).val() !== '';
            update_add_font_state();
        });

		// get button state
        function update_add_font_state() {
            if ( get_font_name && get_font_url ) {
                $('#add_new_font_to_lib').prop('disabled', false);
            } else {
                $('#add_new_font_to_lib').prop('disabled', true);
            }
        }

		// Add new font action
		$(document).on('click', '#add_new_font_to_lib', function(e) {
			e.preventDefault();

			let btn = $(this);
			let btn_html = btn.text();
			let btn_width = btn.width();
			let btn_height = btn.height();
			let get_new_font_id = $('#set_new_font_family_name').val().replace(/\s+/g, '_').replace(/[^\w\s]/gi, '');
			let get_new_font_name = $('#set_new_font_family_name').val();
	
			// keep original width and height
			btn.width(btn_width);
			btn.height(btn_height);

			// remove active alerts
			$('#set_new_font_family_container').find('.alert').remove();
	
			// Add spinner inside button
			btn.html('<span class="spinner-border spinner-border-sm"></span>');

			// send request in AJAX
			$.ajax({
				url: flexify_checkout_params.ajax_url,
				type: 'POST',
				data: {
					action: 'add_new_font_action',
					new_font_id: get_new_font_id,
					new_font_name: get_new_font_name,
					new_font_url: $('#set_new_font_family_url').val(),
				},
				success: function(response) {
					try {
						if ( response.status === 'success' && response.reload === true ) {
							btn.removeClass('btn-primary').addClass('btn-success').html('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: #ffffff"><path d="m10 15.586-3.293-3.293-1.414 1.414L10 18.414l9.707-9.707-1.414-1.414z"></path></svg>');
							$('#set_font_family').append('<option value="'+ get_new_font_id +'">'+ get_new_font_name +'</option>');

							setTimeout( function() {
								$('#set_new_font_family_container').removeClass('show');
								$('#set_new_font_family_name').val('');
								$('#set_new_font_family_name').val('');
							}, 500);
						} else if ( response.status === 'error' && response.font_exists === true ) {
							btn.html(btn_html);
							btn.before('<div class="alert alert-danger me-3"><svg class="icon-danger me-2" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path d="M11 11h2v6h-2zm0-4h2v2h-2z"></path></svg>'+ flexify_checkout_params.font_exists +'</div>');
						}
					} catch (error) {
						console.log(error);

						btn.html(btn_html);
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					console.error("AJAX request failed:", textStatus, errorThrown);
				}
			});
		});
	});


	/**
	 * Check if user really wants deactivate license
	 * 
	 * @since 3.5.0
	 */
	jQuery(document).ready( function($) {
		var btn = $('#flexify_checkout_deactive_license');
		var btn_html = btn.html();
		var btn_width = btn.width();
		var btn_height = btn.height();

		$(btn).on('click', function(e) {
			btn.width(btn_width);
			btn.height(btn_height);
			var confirm_deactivate_license = confirm(flexify_checkout_params.confirm_deactivate_license);

			if ( ! confirm_deactivate_license ) {
				e.preventDefault();
				btn.html(btn_html);
			}
		});
	});


	/**
	 * Control visibility to select conditions
	 * 
	 * @since 3.5.0
	 */
	jQuery(document).ready( function($) {
		// change visibility for component condition	
		select_visibility_controller( $('#add_new_condition_component'), 'field', '.specific-component-fields' );
		select_visibility_controller( $('#add_new_condition_component'), 'payment', '.specific-component-payment' );
		select_visibility_controller( $('#add_new_condition_component'), 'shipping', '.specific-component-shipping' );

		// change visibility for user function roles condition
		select_visibility_controller( $('#add_new_condition_user_function'), 'specific_user', '.specific-users-container' );
		select_visibility_controller( $('#add_new_condition_user_function'), 'specific_role', '.specific-roles-container' );

		// change visibility for product filter condition
		select_visibility_controller( $('#add_new_condition_product_filter'), 'specific_products', '.specific-products' );
		select_visibility_controller( $('#add_new_condition_product_filter'), 'specific_categories', '.specific-categories' );
		select_visibility_controller( $('#add_new_condition_product_filter'), 'specific_attributes', '.specific-attributes' );

		// change visibility for component verification condition
		select_visibility_controller( $('#add_new_condition_component_verification'), 'field', '.specific-checkout-fields' );
	
		// change visibility for condition value
		var visibility_condition_options = ['is', 'is_not', 'contains', 'not_contain', 'start_with', 'finish_with', 'bigger_then', 'less_than'];
		select_visibility_controller('#add_new_condition_component_type', visibility_condition_options, '.condition-value');
	});


	/**
	 * Add new condition in AJAX
	 * 
	 * @since 3.5.0
	 */
	jQuery(document).ready( function($) {
		var specific_products = [];
		var specific_categories = [];
		var specific_attributes = [];
		var specific_users = [];
		var form_data = new FormData();
		form_data.append('action', 'add_new_checkout_condition');

		var condition_array = [
			{ id: '#add_new_condition_type_rule', type: 'select', value: 'type_rule' },
			{ id: '#add_new_condition_component', type: 'select', value: 'component' },
			{ id: '#add_new_condition_specific_field_component', type: 'select', value: 'component_field' },
			{ id: '#add_new_condition_component_verification', type: 'select', value: 'verification_condition' },
			{ id: '#add_new_condition_specific_field', type: 'select', value: 'verification_condition_field' },
			{ id: '#add_new_condition_component_type', type: 'select', value: 'condition' },
			{ id: '#add_new_condition_get_condition_value', type: 'input', value: 'condition_value' },
			{ id: '#add_new_condition_specific_payment_component', type: 'select', value: 'payment_method' },
			{ id: '#add_new_condition_specific_shipping_component', type: 'select', value: 'shipping_method' },
			{ id: '#add_new_condition_specific_user_role', type: 'select', value: 'filter_user' }
		];
		var get_user_role_function;

		/**
		 * Search specific info to backend in AJAX and select specific item value
		 * 
		 * @since 3.5.0
		 * @param {string} input | Get input ID or class for search
		 * @param {string} container | Get container ID
		 * @param {string} action | Get wp_ajax action
		 * @param {string} data | Get data info
		 * @param {array} array_target | Get array for change index values
		 */
		function search_specific_info(input, container, action, data, array_target) {
			var spinner = false;

			$(input).on('keyup', function() {
				var search_info = $(this).val();
			
				if (search_info.length >= 3) {
					if ( spinner === false ) {
						spinner = true;
						$(input).after('<i class="spinner-border specific-search-spinner"></i>');
					}

					$.ajax({
						url: flexify_checkout_params.ajax_url,
						type: 'POST',
						data: {
							action: action,
							search_query: search_info,
						},
						success: function(response) {
							$(input).parent('div').find('.specific-search-spinner').remove();
							spinner = false;
							$(container).addClass('has-items').html(response);
						},
						error: function (jqXHR, textStatus, errorThrown) {
							console.error("AJAX request failed:", textStatus, errorThrown);
						}
					});
				} else {
					$(input).parent('div').find('.specific-search-spinner').remove();
					spinner = false;
					$(container).html('');
				}
			});

			// Use event delegation to handle clicks on dynamically added items
			container.on('click', 'li.list-group-item', function() {
				var get_data_item = $(this).data(data);
				
				// Toggle the "selected" class when clicking on an item
				$(this).toggleClass('selected');
				
				// Add item to array
				if ( $(this).hasClass('selected') ) {
					if (get_data_item !== 0) {
						array_target.push(get_data_item);
					}
				} else {
					// Remove item from array
					var index = array_target.indexOf(get_data_item);

					if (index !== -1) {
						array_target.splice(index, 1);
					}
				}

				// set updated data to form data
				form_data.set( data, JSON.stringify(array_target) );
			});
		}

		/**
		 * Iterate for each condition item
		 */
		condition_array.forEach( function(condition) {
			if (condition.type === 'select') {
				$(condition.id).on('change', function(e) {
					var select_value = $(e.target).val();
					
					if (select_value !== 'none') {
						form_data.append(condition.value, select_value);
					}
				});
			} else if (condition.type === 'input') {
				$(document).on('keyup', function(e) {
					var input_value = $(e.target).val();
					
					if (input_value !== '') {
						form_data.append(condition.value, input_value);
					}
				});
			}
		});

		/**
		 * Active submit new condition if all selects has values
		 * 
		 * @since 3.5.0
		 */
		function check_button_state() {
			let type_rule = $('#add_new_condition_type_rule').val();
			let component = $('#add_new_condition_component').val();
			let component_specific_field = $('#add_new_condition_specific_field_component').val();
			let component_specific_payment = $('#add_new_condition_specific_payment_component').val();
			let component_specific_shipping = $('#add_new_condition_specific_shipping_component').val();
			let verification_condition = $('#add_new_condition_component_verification').val();
			let verification_specific_field = $('#add_new_condition_specific_field').val();
			let component_type = $('#add_new_condition_component_type').val();
			let condition = $('#add_new_condition_component_type').val();
			let visibility_condition_options = ['is', 'is_not', 'contains', 'not_contain', 'start_with', 'finish_with', 'bigger_then', 'less_than'];
			var all_valid = true;

			if (type_rule === 'none' || component === 'none' || component_type === 'none') {
				all_valid = false;
			}

			if (component === 'field' && component_specific_field === 'none') {
				all_valid = false;
			}

			if (component === 'payment' && component_specific_payment === 'none') {
				all_valid = false;
			}

			if (component === 'shipping' && component_specific_shipping === 'none') {
				all_valid = false;
			}

			if (verification_condition === 'field' && verification_specific_field === 'none') {
				all_valid = false;
			}

			// check each value from condition
			$(visibility_condition_options).each( function(index, value) {
				if (condition === value && $('#add_new_condition_get_condition_value').val() === '') {
					all_valid = false;
				}
			});

			$('#add_new_condition_submit').prop('disabled', ! all_valid);
		}

		/**
		 * Set default values for conditions
		 * 
		 * @since 3.5.0
		 * @param {string} container | Container target
		 */
		function clear_condition_options(container) {
			// find all selects elements
			jQuery(container).find('select').each( function() {
				// Select the first option
				var first_option = jQuery(this).find('option:first').val();
        		jQuery(this).val(first_option).change();
			});

			// find all inputs elements
			jQuery(container).find('input').each( function() {
				jQuery(this).val('');
			});
		}

		// Attach change event listeners to relevant selects
		$('#add_new_condition_type_rule, #add_new_condition_component, #add_new_condition_specific_field_component, #add_new_condition_specific_payment_component, #add_new_condition_specific_shipping_component, #add_new_condition_component_verification, #add_new_condition_specific_field, #add_new_condition_component_type, #add_new_condition_condition_type, #add_new_condition_get_condition_value').on('change keyup', check_button_state);
		
		// add form data for specific users
		$('#add_new_condition_user_function').on('change', function() {
			if ($(this).val() === 'specific_user') {
				form_data.append('specific_user', specific_users);
			}
		});

		// add form data for specific user role
		$('#add_new_condition_specific_user_role').on('change', function() {
			if ( get_user_role_function === 'specific_role' ) {
				form_data.append('specific_role', $(this).val());
			}
		});

		// add form data for product filter
		$('#add_new_condition_product_filter').on('change', function() {
			if ( $(this).val() === 'specific_products' ) {
				form_data.append('specific_products', specific_products);
			} else if ( $(this).val() === 'specific_categories' ) {
				form_data.append('specific_categories', specific_categories);
			} else if ( $(this).val() === 'specific_attributes' ) {
				form_data.append('specific_attributes', specific_attributes);
			} else {
				form_data.append('product_filter', $(this).val());
			}
		});

		// Get WooCommerce products in AJAX
		search_specific_info( $('.product-search'), $('#get_specific_products'), 'get_woo_products_ajax', 'product-id', specific_products );

		// Get WooCommerce products categories in AJAX
		search_specific_info( $('.category-search'), $('#get_specific_categories'), 'get_woo_categories_ajax', 'category-id', specific_categories );

		// Get WooCommerce products attributes in AJAX
		search_specific_info( $('.attribute-search'), $('#get_specific_attribute'), 'get_woo_attributes_ajax', 'attribute-id', specific_attributes );

		// Get users in AJAX
		search_specific_info( $('.user-search'), $('#get_specific_users'), 'search_users_ajax', 'user-id', specific_users );

		// set updated values for specific products
		$(document).on('click', '#get_specific_products li.list-group-item', function() {
			form_data.set('specific_products', JSON.stringify(specific_products));
		});

		// add condition action
		$(document).on('click', '#add_new_condition_submit', function(e) {
			e.preventDefault();

			let btn = $(this);
			let btn_html = btn.html();
			let btn_width = btn.width();
			let btn_height = btn.height();
	
			// keep original width and height
			btn.width(btn_width);
			btn.height(btn_height);
	
			// Add spinner inside button
			btn.html('<span class="spinner-border spinner-border-sm"></span>');

			// send request in AJAX
			$.ajax({
				url: flexify_checkout_params.ajax_url,
				type: 'POST',
				processData: false,
                contentType: false,
				data: form_data,
				success: function(response) {
					try {
						if ( response.status === 'success' ) {
							btn.removeClass('btn-primary').addClass('btn-success').html('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: #ffffff"><path d="m10 15.586-3.293-3.293-1.414 1.414L10 18.414l9.707-9.707-1.414-1.414z"></path></svg>');
							
							var get_last_item_list = $('#display_conditions').children('ul.list-group').children('li').last();
							var condition_index = get_last_item_list.data('condition') + 1;

							// display success notice
							$('.flexify-checkout-wrapper').before(`<div class="toast toast-success exclude-condition-toast show">
								<div class="toast-header bg-success text-white">
									<svg class="flexify-toast-check-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g stroke-width="0"/><g stroke-linecap="round" stroke-linejoin="round"/><g><path d="M10.5 15.25C10.307 15.2353 10.1276 15.1455 9.99998 15L6.99998 12C6.93314 11.8601 6.91133 11.7029 6.93756 11.55C6.96379 11.3971 7.03676 11.2562 7.14643 11.1465C7.2561 11.0368 7.39707 10.9638 7.54993 10.9376C7.70279 10.9114 7.86003 10.9332 7.99998 11L10.47 13.47L19 5.00004C19.1399 4.9332 19.2972 4.91139 19.45 4.93762C19.6029 4.96385 19.7439 5.03682 19.8535 5.14649C19.9632 5.25616 20.0362 5.39713 20.0624 5.54999C20.0886 5.70286 20.0668 5.86009 20 6.00004L11 15C10.8724 15.1455 10.6929 15.2353 10.5 15.25Z" fill="#ffffff"/> <path d="M12 21C10.3915 20.9974 8.813 20.5638 7.42891 19.7443C6.04481 18.9247 4.90566 17.7492 4.12999 16.34C3.54037 15.29 3.17596 14.1287 3.05999 12.93C2.87697 11.1721 3.2156 9.39921 4.03363 7.83249C4.85167 6.26578 6.1129 4.9746 7.65999 4.12003C8.71001 3.53041 9.87134 3.166 11.07 3.05003C12.2641 2.92157 13.4719 3.03725 14.62 3.39003C14.7224 3.4105 14.8195 3.45215 14.9049 3.51232C14.9903 3.57248 15.0622 3.64983 15.116 3.73941C15.1698 3.82898 15.2043 3.92881 15.2173 4.03249C15.2302 4.13616 15.2214 4.2414 15.1913 4.34146C15.1612 4.44152 15.1105 4.53419 15.0425 4.61352C14.9745 4.69286 14.8907 4.75712 14.7965 4.80217C14.7022 4.84723 14.5995 4.87209 14.4951 4.87516C14.3907 4.87824 14.2867 4.85946 14.19 4.82003C13.2186 4.52795 12.1987 4.43275 11.19 4.54003C10.193 4.64212 9.22694 4.94485 8.34999 5.43003C7.50512 5.89613 6.75813 6.52088 6.14999 7.27003C5.52385 8.03319 5.05628 8.91361 4.77467 9.85974C4.49307 10.8059 4.40308 11.7987 4.50999 12.78C4.61208 13.777 4.91482 14.7431 5.39999 15.62C5.86609 16.4649 6.49084 17.2119 7.23999 17.82C8.00315 18.4462 8.88357 18.9137 9.8297 19.1953C10.7758 19.4769 11.7686 19.5669 12.75 19.46C13.747 19.3579 14.713 19.0552 15.59 18.57C16.4349 18.1039 17.1818 17.4792 17.79 16.73C18.4161 15.9669 18.8837 15.0864 19.1653 14.1403C19.4469 13.1942 19.5369 12.2014 19.43 11.22C19.4201 11.1169 19.4307 11.0129 19.461 10.9139C19.4914 10.8149 19.5409 10.7228 19.6069 10.643C19.6728 10.5631 19.7538 10.497 19.8453 10.4485C19.9368 10.3999 20.0369 10.3699 20.14 10.36C20.2431 10.3502 20.3471 10.3607 20.4461 10.3911C20.5451 10.4214 20.6372 10.471 20.717 10.5369C20.7969 10.6028 20.863 10.6839 20.9115 10.7753C20.9601 10.8668 20.9901 10.9669 21 11.07C21.1821 12.829 20.842 14.6026 20.0221 16.1695C19.2022 17.7363 17.9389 19.0269 16.39 19.88C15.3288 20.4938 14.1495 20.8755 12.93 21C12.62 21 12.3 21 12 21Z" fill="#ffffff"/></g></svg>
									<span class="me-auto">${response.toast_header_success}</span>
									<button class="btn-close btn-close-white ms-2 hide-toast" type="button" aria-label="Fechar"></button>
								</div>
								<div class="toast-body">${response.toast_body_success}</div>
							</div>`);

							// hide notice with fadeout
							setTimeout( function() {
								$('.exclude-condition-toast').fadeOut('fast');
							}, 3000);

							// remove notice from HTML after 3.5 seconds
							setTimeout( function() {
								$('.exclude-condition-toast').remove();
							}, 3500);

							// check if has empty condition info
							if ( response[0] !== undefined && response[0].empty_conditions === 'yes' ) {
								let conditions_wrap = $('#empty_conditions').parent('td');
								$('#empty_conditions').remove();
								
								conditions_wrap.append(`<div id="display_conditions" class="mb-3">
									<ul class="list-group">
										<li class="list-group-item d-flex align-items-center justify-content-between" data-condition="1">
											<div class="d-grid">
												<div class="mb-2">${response.condition_line_1}</div>
												<div>${response.condition_line_2}</div>
											</div>
											<button class="exclude-condition btn btn-icon btn-sm btn-outline-danger rounded-3 ms-3">
												<svg class="icon icon-sm icon-danger" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
											</button>
										</li>
									</ul>
								</div>`);
							} else {
								$('#display_conditions ul.list-group').append(`<li class="list-group-item d-flex align-items-center justify-content-between" data-condition="${condition_index}">
									<div class="d-grid">
                                        <div class="mb-2">${response.condition_line_1}</div>
                                        <div>${response.condition_line_2}</div>
                                    </div>
                                    <button class="exclude-condition btn btn-icon btn-sm btn-outline-danger rounded-3 ms-3">
                                        <svg class="icon icon-sm icon-danger" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
                                    </button>
								</li>`);
							}

							setTimeout( function() {
								// close fullscreen modal conditions
								$('#close_add_new_checkout_condition').click();

								// set default values
								clear_condition_options('#add_new_condition_container_master');

								btn.html(btn_html);
								btn.removeClass('btn-success').addClass('btn-primary');
							}, 500);
						} else {
							btn.html(btn_html);
							btn.after('<div class="alert alert-danger me-3"><svg class="icon-danger me-2" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path d="M11 11h2v6h-2zm0-4h2v2h-2z"></path></svg>'+ response.error_message +'</div>');
						}
					} catch (error) {
						console.log(error);

						btn.html(btn_html);
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					console.error("AJAX request failed:", textStatus, errorThrown);
				}
			});
		});

		// exclude condition item
		$(document).on('click', '.exclude-condition', function(e) {
			e.preventDefault();

			let btn = $(this);
			let btn_html = btn.html();
			let btn_width = btn.width();
			let btn_height = btn.height();
			let get_condition_index = btn.parent('.list-group-item').data('condition');
	
			// keep original width and height
			btn.width(btn_width);
			btn.height(btn_height);
	
			// Add spinner inside button
			btn.html('<span class="spinner-border spinner-border-sm"></span>');

			// send request in AJAX
			$.ajax({
				url: flexify_checkout_params.ajax_url,
				type: 'POST',
				data: {
					'action': 'exclude_condition_item',
					'condition_index': get_condition_index,
				},
				success: function(response) {
					try {
						if ( response.status === 'success' ) {
							// add success toast
							$('.flexify-checkout-wrapper').before(`<div class="toast toast-success exclude-condition-toast show">
								<div class="toast-header bg-success text-white">
									<svg class="flexify-toast-check-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g stroke-width="0"/><g stroke-linecap="round" stroke-linejoin="round"/><g><path d="M10.5 15.25C10.307 15.2353 10.1276 15.1455 9.99998 15L6.99998 12C6.93314 11.8601 6.91133 11.7029 6.93756 11.55C6.96379 11.3971 7.03676 11.2562 7.14643 11.1465C7.2561 11.0368 7.39707 10.9638 7.54993 10.9376C7.70279 10.9114 7.86003 10.9332 7.99998 11L10.47 13.47L19 5.00004C19.1399 4.9332 19.2972 4.91139 19.45 4.93762C19.6029 4.96385 19.7439 5.03682 19.8535 5.14649C19.9632 5.25616 20.0362 5.39713 20.0624 5.54999C20.0886 5.70286 20.0668 5.86009 20 6.00004L11 15C10.8724 15.1455 10.6929 15.2353 10.5 15.25Z" fill="#ffffff"/> <path d="M12 21C10.3915 20.9974 8.813 20.5638 7.42891 19.7443C6.04481 18.9247 4.90566 17.7492 4.12999 16.34C3.54037 15.29 3.17596 14.1287 3.05999 12.93C2.87697 11.1721 3.2156 9.39921 4.03363 7.83249C4.85167 6.26578 6.1129 4.9746 7.65999 4.12003C8.71001 3.53041 9.87134 3.166 11.07 3.05003C12.2641 2.92157 13.4719 3.03725 14.62 3.39003C14.7224 3.4105 14.8195 3.45215 14.9049 3.51232C14.9903 3.57248 15.0622 3.64983 15.116 3.73941C15.1698 3.82898 15.2043 3.92881 15.2173 4.03249C15.2302 4.13616 15.2214 4.2414 15.1913 4.34146C15.1612 4.44152 15.1105 4.53419 15.0425 4.61352C14.9745 4.69286 14.8907 4.75712 14.7965 4.80217C14.7022 4.84723 14.5995 4.87209 14.4951 4.87516C14.3907 4.87824 14.2867 4.85946 14.19 4.82003C13.2186 4.52795 12.1987 4.43275 11.19 4.54003C10.193 4.64212 9.22694 4.94485 8.34999 5.43003C7.50512 5.89613 6.75813 6.52088 6.14999 7.27003C5.52385 8.03319 5.05628 8.91361 4.77467 9.85974C4.49307 10.8059 4.40308 11.7987 4.50999 12.78C4.61208 13.777 4.91482 14.7431 5.39999 15.62C5.86609 16.4649 6.49084 17.2119 7.23999 17.82C8.00315 18.4462 8.88357 18.9137 9.8297 19.1953C10.7758 19.4769 11.7686 19.5669 12.75 19.46C13.747 19.3579 14.713 19.0552 15.59 18.57C16.4349 18.1039 17.1818 17.4792 17.79 16.73C18.4161 15.9669 18.8837 15.0864 19.1653 14.1403C19.4469 13.1942 19.5369 12.2014 19.43 11.22C19.4201 11.1169 19.4307 11.0129 19.461 10.9139C19.4914 10.8149 19.5409 10.7228 19.6069 10.643C19.6728 10.5631 19.7538 10.497 19.8453 10.4485C19.9368 10.3999 20.0369 10.3699 20.14 10.36C20.2431 10.3502 20.3471 10.3607 20.4461 10.3911C20.5451 10.4214 20.6372 10.471 20.717 10.5369C20.7969 10.6028 20.863 10.6839 20.9115 10.7753C20.9601 10.8668 20.9901 10.9669 21 11.07C21.1821 12.829 20.842 14.6026 20.0221 16.1695C19.2022 17.7363 17.9389 19.0269 16.39 19.88C15.3288 20.4938 14.1495 20.8755 12.93 21C12.62 21 12.3 21 12 21Z" fill="#ffffff"/></g></svg>
									<span class="me-auto">${response.toast_header_success}</span>
									<button class="btn-close btn-close-white ms-2 hide-toast" type="button" aria-label="Fechar"></button>
								</div>
								<div class="toast-body">${response.toast_body_success}</div>
							</div>`);

							// hide notice with fadeout
							setTimeout( function() {
								$('.exclude-condition-toast').fadeOut('fast');
							}, 3000);

							// remove notice from HTML after 3.5 seconds
							setTimeout( function() {
								$('.exclude-condition-toast').remove();
							}, 3500);

							// remove condition item with fade
							setTimeout( function() {
								btn.parent('.list-group-item').fadeOut('fast');
							}, 300);

							setTimeout( function() {
								// remove condition item from HTML
								btn.parent('.list-group-item').remove();

								// check is has empty conditions info
								if ( response[0] !== undefined  && response[0].empty_conditions === 'yes' ) {
									let conditions_wrap = $('#display_conditions').parent('td');
									$('#display_conditions').remove();

									// add empty conditions info
									conditions_wrap.append(`<div id="empty_conditions" class="alert alert-info d-flex align-items-center">
										<svg class="icon icon-info me-2" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path d="M11 11h2v6h-2zm0-4h2v2h-2z"></path></svg>
										<span>${response[0].empty_conditions_message}</span>
									</div>`);
								}
							}, 500);
						} else {
							// display error notice
							$('.flexify-checkout-wrapper').before(`<div class="toast toast-danger exclude-condition-toast show">
								<div class="toast-header bg-danger text-white">
									<svg class="flexify-toast-check-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g stroke-width="0"/><g stroke-linecap="round" stroke-linejoin="round"/><g><path d="M10.5 15.25C10.307 15.2353 10.1276 15.1455 9.99998 15L6.99998 12C6.93314 11.8601 6.91133 11.7029 6.93756 11.55C6.96379 11.3971 7.03676 11.2562 7.14643 11.1465C7.2561 11.0368 7.39707 10.9638 7.54993 10.9376C7.70279 10.9114 7.86003 10.9332 7.99998 11L10.47 13.47L19 5.00004C19.1399 4.9332 19.2972 4.91139 19.45 4.93762C19.6029 4.96385 19.7439 5.03682 19.8535 5.14649C19.9632 5.25616 20.0362 5.39713 20.0624 5.54999C20.0886 5.70286 20.0668 5.86009 20 6.00004L11 15C10.8724 15.1455 10.6929 15.2353 10.5 15.25Z" fill="#ffffff"/> <path d="M12 21C10.3915 20.9974 8.813 20.5638 7.42891 19.7443C6.04481 18.9247 4.90566 17.7492 4.12999 16.34C3.54037 15.29 3.17596 14.1287 3.05999 12.93C2.87697 11.1721 3.2156 9.39921 4.03363 7.83249C4.85167 6.26578 6.1129 4.9746 7.65999 4.12003C8.71001 3.53041 9.87134 3.166 11.07 3.05003C12.2641 2.92157 13.4719 3.03725 14.62 3.39003C14.7224 3.4105 14.8195 3.45215 14.9049 3.51232C14.9903 3.57248 15.0622 3.64983 15.116 3.73941C15.1698 3.82898 15.2043 3.92881 15.2173 4.03249C15.2302 4.13616 15.2214 4.2414 15.1913 4.34146C15.1612 4.44152 15.1105 4.53419 15.0425 4.61352C14.9745 4.69286 14.8907 4.75712 14.7965 4.80217C14.7022 4.84723 14.5995 4.87209 14.4951 4.87516C14.3907 4.87824 14.2867 4.85946 14.19 4.82003C13.2186 4.52795 12.1987 4.43275 11.19 4.54003C10.193 4.64212 9.22694 4.94485 8.34999 5.43003C7.50512 5.89613 6.75813 6.52088 6.14999 7.27003C5.52385 8.03319 5.05628 8.91361 4.77467 9.85974C4.49307 10.8059 4.40308 11.7987 4.50999 12.78C4.61208 13.777 4.91482 14.7431 5.39999 15.62C5.86609 16.4649 6.49084 17.2119 7.23999 17.82C8.00315 18.4462 8.88357 18.9137 9.8297 19.1953C10.7758 19.4769 11.7686 19.5669 12.75 19.46C13.747 19.3579 14.713 19.0552 15.59 18.57C16.4349 18.1039 17.1818 17.4792 17.79 16.73C18.4161 15.9669 18.8837 15.0864 19.1653 14.1403C19.4469 13.1942 19.5369 12.2014 19.43 11.22C19.4201 11.1169 19.4307 11.0129 19.461 10.9139C19.4914 10.8149 19.5409 10.7228 19.6069 10.643C19.6728 10.5631 19.7538 10.497 19.8453 10.4485C19.9368 10.3999 20.0369 10.3699 20.14 10.36C20.2431 10.3502 20.3471 10.3607 20.4461 10.3911C20.5451 10.4214 20.6372 10.471 20.717 10.5369C20.7969 10.6028 20.863 10.6839 20.9115 10.7753C20.9601 10.8668 20.9901 10.9669 21 11.07C21.1821 12.829 20.842 14.6026 20.0221 16.1695C19.2022 17.7363 17.9389 19.0269 16.39 19.88C15.3288 20.4938 14.1495 20.8755 12.93 21C12.62 21 12.3 21 12 21Z" fill="#ffffff"/></g></svg>
									<span class="me-auto">${response.toast_header_error}</span>
									<button class="btn-close btn-close-white ms-2 hide-toast" type="button" aria-label="Fechar"></button>
								</div>
								<div class="toast-body">${response.toast_body_error}</div>
							</div>`);

							// hide notice with fade after 3 seconds
							setTimeout( function() {
								$('.exclude-condition-toast').fadeOut('fast');
							}, 3000);

							// remove error notice from HTML after 3.5 seconds
							setTimeout( function() {
								$('.exclude-condition-toast').remove();
							}, 3500);
						}
					} catch (error) {
						console.log(error);

						btn.html(btn_html);
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					console.error("AJAX request failed:", textStatus, errorThrown);
				}
			});
		});
	});


	/**
	 * Add new email suggestion provider
	 * 
	 * @since 3.5.0
	 */
	jQuery(document).ready( function($) {
		var submit_new_provider = $('#add_new_email_provider');
		var get_new_provider = $('#get_new_email_provider');
		var new_provider;

		// get new provider value
		$(get_new_provider).on('keyup input', function() {
			new_provider = $(this).val();

			if ( $(this).val() !== '' ) {
				submit_new_provider.prop('disabled', false);
			} else {
				submit_new_provider.prop('disabled', true);
			}
		});

		// send new provider
		$(submit_new_provider).on('click', function(e) {
			e.preventDefault();

			let btn = $(this);
			let btn_html = btn.html();
			let btn_width = btn.width();
			let btn_height = btn.height();
	
			// keep original width and height
			btn.width(btn_width);
			btn.height(btn_height);
	
			// Add spinner inside button
			btn.html('<span class="spinner-border spinner-border-sm"></span>');

			$.ajax({
				url: flexify_checkout_params.ajax_url,
				type: 'POST',
				data: {
					'action': 'add_new_email_provider',
					'new_provider': new_provider,
				},
				success: function(response) {
					try {
						if ( response.status === 'success' ) {
							new_provider = '';
							get_new_provider.val('');
							submit_new_provider.prop('disabled', true);
							btn.html(btn_html);

							// add new item to list group
							$('#flexify_checkout_email_providers').append(`<li class="list-group-item d-flex align-items-center justify-content-between" data-provider="${response.new_provider}">
								<span>${response.new_provider}</span>
								<button class="exclude-provider btn btn-icon btn-sm btn-outline-danger rounded-3 ms-3">
									<svg class="icon icon-sm icon-danger" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
								</button>
							</li>`);

							// add success toast
							$('.flexify-checkout-wrapper').before(`<div class="toast toast-success new-email-provider-toast show">
								<div class="toast-header bg-success text-white">
									<svg class="flexify-toast-check-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g stroke-width="0"/><g stroke-linecap="round" stroke-linejoin="round"/><g><path d="M10.5 15.25C10.307 15.2353 10.1276 15.1455 9.99998 15L6.99998 12C6.93314 11.8601 6.91133 11.7029 6.93756 11.55C6.96379 11.3971 7.03676 11.2562 7.14643 11.1465C7.2561 11.0368 7.39707 10.9638 7.54993 10.9376C7.70279 10.9114 7.86003 10.9332 7.99998 11L10.47 13.47L19 5.00004C19.1399 4.9332 19.2972 4.91139 19.45 4.93762C19.6029 4.96385 19.7439 5.03682 19.8535 5.14649C19.9632 5.25616 20.0362 5.39713 20.0624 5.54999C20.0886 5.70286 20.0668 5.86009 20 6.00004L11 15C10.8724 15.1455 10.6929 15.2353 10.5 15.25Z" fill="#ffffff"/> <path d="M12 21C10.3915 20.9974 8.813 20.5638 7.42891 19.7443C6.04481 18.9247 4.90566 17.7492 4.12999 16.34C3.54037 15.29 3.17596 14.1287 3.05999 12.93C2.87697 11.1721 3.2156 9.39921 4.03363 7.83249C4.85167 6.26578 6.1129 4.9746 7.65999 4.12003C8.71001 3.53041 9.87134 3.166 11.07 3.05003C12.2641 2.92157 13.4719 3.03725 14.62 3.39003C14.7224 3.4105 14.8195 3.45215 14.9049 3.51232C14.9903 3.57248 15.0622 3.64983 15.116 3.73941C15.1698 3.82898 15.2043 3.92881 15.2173 4.03249C15.2302 4.13616 15.2214 4.2414 15.1913 4.34146C15.1612 4.44152 15.1105 4.53419 15.0425 4.61352C14.9745 4.69286 14.8907 4.75712 14.7965 4.80217C14.7022 4.84723 14.5995 4.87209 14.4951 4.87516C14.3907 4.87824 14.2867 4.85946 14.19 4.82003C13.2186 4.52795 12.1987 4.43275 11.19 4.54003C10.193 4.64212 9.22694 4.94485 8.34999 5.43003C7.50512 5.89613 6.75813 6.52088 6.14999 7.27003C5.52385 8.03319 5.05628 8.91361 4.77467 9.85974C4.49307 10.8059 4.40308 11.7987 4.50999 12.78C4.61208 13.777 4.91482 14.7431 5.39999 15.62C5.86609 16.4649 6.49084 17.2119 7.23999 17.82C8.00315 18.4462 8.88357 18.9137 9.8297 19.1953C10.7758 19.4769 11.7686 19.5669 12.75 19.46C13.747 19.3579 14.713 19.0552 15.59 18.57C16.4349 18.1039 17.1818 17.4792 17.79 16.73C18.4161 15.9669 18.8837 15.0864 19.1653 14.1403C19.4469 13.1942 19.5369 12.2014 19.43 11.22C19.4201 11.1169 19.4307 11.0129 19.461 10.9139C19.4914 10.8149 19.5409 10.7228 19.6069 10.643C19.6728 10.5631 19.7538 10.497 19.8453 10.4485C19.9368 10.3999 20.0369 10.3699 20.14 10.36C20.2431 10.3502 20.3471 10.3607 20.4461 10.3911C20.5451 10.4214 20.6372 10.471 20.717 10.5369C20.7969 10.6028 20.863 10.6839 20.9115 10.7753C20.9601 10.8668 20.9901 10.9669 21 11.07C21.1821 12.829 20.842 14.6026 20.0221 16.1695C19.2022 17.7363 17.9389 19.0269 16.39 19.88C15.3288 20.4938 14.1495 20.8755 12.93 21C12.62 21 12.3 21 12 21Z" fill="#ffffff"/></g></svg>
									<span class="me-auto">${response.toast_header_success}</span>
									<button class="btn-close btn-close-white ms-2 hide-toast" type="button" aria-label="Fechar"></button>
								</div>
								<div class="toast-body">${response.toast_body_success}</div>
							</div>`);

							// hide notice with fadeout
							setTimeout( function() {
								$('.new-email-provider-toast').fadeOut('fast');
							}, 3000);

							// remove notice from HTML after 3.5 seconds
							setTimeout( function() {
								$('.new-email-provider-toast').remove();
							}, 3500);
						} else {
							$('.flexify-checkout-wrapper').before(`<div class="toast toast-danger new-email-provider-toast show">
								<div class="toast-header bg-danger text-white">
									<svg class="flexify-toast-check-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g stroke-width="0"/><g stroke-linecap="round" stroke-linejoin="round"/><g><path d="M10.5 15.25C10.307 15.2353 10.1276 15.1455 9.99998 15L6.99998 12C6.93314 11.8601 6.91133 11.7029 6.93756 11.55C6.96379 11.3971 7.03676 11.2562 7.14643 11.1465C7.2561 11.0368 7.39707 10.9638 7.54993 10.9376C7.70279 10.9114 7.86003 10.9332 7.99998 11L10.47 13.47L19 5.00004C19.1399 4.9332 19.2972 4.91139 19.45 4.93762C19.6029 4.96385 19.7439 5.03682 19.8535 5.14649C19.9632 5.25616 20.0362 5.39713 20.0624 5.54999C20.0886 5.70286 20.0668 5.86009 20 6.00004L11 15C10.8724 15.1455 10.6929 15.2353 10.5 15.25Z" fill="#ffffff"/> <path d="M12 21C10.3915 20.9974 8.813 20.5638 7.42891 19.7443C6.04481 18.9247 4.90566 17.7492 4.12999 16.34C3.54037 15.29 3.17596 14.1287 3.05999 12.93C2.87697 11.1721 3.2156 9.39921 4.03363 7.83249C4.85167 6.26578 6.1129 4.9746 7.65999 4.12003C8.71001 3.53041 9.87134 3.166 11.07 3.05003C12.2641 2.92157 13.4719 3.03725 14.62 3.39003C14.7224 3.4105 14.8195 3.45215 14.9049 3.51232C14.9903 3.57248 15.0622 3.64983 15.116 3.73941C15.1698 3.82898 15.2043 3.92881 15.2173 4.03249C15.2302 4.13616 15.2214 4.2414 15.1913 4.34146C15.1612 4.44152 15.1105 4.53419 15.0425 4.61352C14.9745 4.69286 14.8907 4.75712 14.7965 4.80217C14.7022 4.84723 14.5995 4.87209 14.4951 4.87516C14.3907 4.87824 14.2867 4.85946 14.19 4.82003C13.2186 4.52795 12.1987 4.43275 11.19 4.54003C10.193 4.64212 9.22694 4.94485 8.34999 5.43003C7.50512 5.89613 6.75813 6.52088 6.14999 7.27003C5.52385 8.03319 5.05628 8.91361 4.77467 9.85974C4.49307 10.8059 4.40308 11.7987 4.50999 12.78C4.61208 13.777 4.91482 14.7431 5.39999 15.62C5.86609 16.4649 6.49084 17.2119 7.23999 17.82C8.00315 18.4462 8.88357 18.9137 9.8297 19.1953C10.7758 19.4769 11.7686 19.5669 12.75 19.46C13.747 19.3579 14.713 19.0552 15.59 18.57C16.4349 18.1039 17.1818 17.4792 17.79 16.73C18.4161 15.9669 18.8837 15.0864 19.1653 14.1403C19.4469 13.1942 19.5369 12.2014 19.43 11.22C19.4201 11.1169 19.4307 11.0129 19.461 10.9139C19.4914 10.8149 19.5409 10.7228 19.6069 10.643C19.6728 10.5631 19.7538 10.497 19.8453 10.4485C19.9368 10.3999 20.0369 10.3699 20.14 10.36C20.2431 10.3502 20.3471 10.3607 20.4461 10.3911C20.5451 10.4214 20.6372 10.471 20.717 10.5369C20.7969 10.6028 20.863 10.6839 20.9115 10.7753C20.9601 10.8668 20.9901 10.9669 21 11.07C21.1821 12.829 20.842 14.6026 20.0221 16.1695C19.2022 17.7363 17.9389 19.0269 16.39 19.88C15.3288 20.4938 14.1495 20.8755 12.93 21C12.62 21 12.3 21 12 21Z" fill="#ffffff"/></g></svg>
									<span class="me-auto">${response.toast_header_error}</span>
									<button class="btn-close btn-close-white ms-2 hide-toast" type="button" aria-label="Fechar"></button>
								</div>
								<div class="toast-body">${response.toast_body_error}</div>
							</div>`);

							// hide notice with fade after 3 seconds
							setTimeout( function() {
								$('.new-email-provider-toast').fadeOut('fast');
							}, 3000);

							// remove error notice from HTML after 3.5 seconds
							setTimeout( function() {
								$('.new-email-provider-toast').remove();
							}, 3500);
						}
					} catch (error) {
						console.error(error);
						btn.html(btn_html);
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					console.error("AJAX request failed:", textStatus, errorThrown);
				}
			});
		});

		// exclude email provider action
		$(document).on('click', '.exclude-provider', function(e) {
			e.preventDefault();

			var get_provider = $(this).parent('.list-group-item');
			let btn = $(this);
			let btn_html = btn.html();
			let btn_width = btn.width();
			let btn_height = btn.height();
	
			// keep original width and height
			btn.width(btn_width);
			btn.height(btn_height);
	
			// Add spinner inside button
			btn.html('<span class="spinner-border spinner-border-sm"></span>');

			$.ajax({
				url: flexify_checkout_params.ajax_url,
				type: 'POST',
				data: {
					'action': 'remove_email_provider',
					'exclude_provider': get_provider.data('provider'),
				},
				success: function(response) {
					try {
						if ( response.status === 'success' ) {
							// remove email provider to list group with fade
							setTimeout( function() {
								$(get_provider).fadeOut('fast');
							}, 500);

							// remove from HTML after 1 second
							setTimeout( function() {
								$(get_provider).remove();
							}, 1000);

							// add success toast
							$('.flexify-checkout-wrapper').before(`<div class="toast toast-success exclude-email-provider-toast show">
								<div class="toast-header bg-success text-white">
									<svg class="flexify-toast-check-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g stroke-width="0"/><g stroke-linecap="round" stroke-linejoin="round"/><g><path d="M10.5 15.25C10.307 15.2353 10.1276 15.1455 9.99998 15L6.99998 12C6.93314 11.8601 6.91133 11.7029 6.93756 11.55C6.96379 11.3971 7.03676 11.2562 7.14643 11.1465C7.2561 11.0368 7.39707 10.9638 7.54993 10.9376C7.70279 10.9114 7.86003 10.9332 7.99998 11L10.47 13.47L19 5.00004C19.1399 4.9332 19.2972 4.91139 19.45 4.93762C19.6029 4.96385 19.7439 5.03682 19.8535 5.14649C19.9632 5.25616 20.0362 5.39713 20.0624 5.54999C20.0886 5.70286 20.0668 5.86009 20 6.00004L11 15C10.8724 15.1455 10.6929 15.2353 10.5 15.25Z" fill="#ffffff"/> <path d="M12 21C10.3915 20.9974 8.813 20.5638 7.42891 19.7443C6.04481 18.9247 4.90566 17.7492 4.12999 16.34C3.54037 15.29 3.17596 14.1287 3.05999 12.93C2.87697 11.1721 3.2156 9.39921 4.03363 7.83249C4.85167 6.26578 6.1129 4.9746 7.65999 4.12003C8.71001 3.53041 9.87134 3.166 11.07 3.05003C12.2641 2.92157 13.4719 3.03725 14.62 3.39003C14.7224 3.4105 14.8195 3.45215 14.9049 3.51232C14.9903 3.57248 15.0622 3.64983 15.116 3.73941C15.1698 3.82898 15.2043 3.92881 15.2173 4.03249C15.2302 4.13616 15.2214 4.2414 15.1913 4.34146C15.1612 4.44152 15.1105 4.53419 15.0425 4.61352C14.9745 4.69286 14.8907 4.75712 14.7965 4.80217C14.7022 4.84723 14.5995 4.87209 14.4951 4.87516C14.3907 4.87824 14.2867 4.85946 14.19 4.82003C13.2186 4.52795 12.1987 4.43275 11.19 4.54003C10.193 4.64212 9.22694 4.94485 8.34999 5.43003C7.50512 5.89613 6.75813 6.52088 6.14999 7.27003C5.52385 8.03319 5.05628 8.91361 4.77467 9.85974C4.49307 10.8059 4.40308 11.7987 4.50999 12.78C4.61208 13.777 4.91482 14.7431 5.39999 15.62C5.86609 16.4649 6.49084 17.2119 7.23999 17.82C8.00315 18.4462 8.88357 18.9137 9.8297 19.1953C10.7758 19.4769 11.7686 19.5669 12.75 19.46C13.747 19.3579 14.713 19.0552 15.59 18.57C16.4349 18.1039 17.1818 17.4792 17.79 16.73C18.4161 15.9669 18.8837 15.0864 19.1653 14.1403C19.4469 13.1942 19.5369 12.2014 19.43 11.22C19.4201 11.1169 19.4307 11.0129 19.461 10.9139C19.4914 10.8149 19.5409 10.7228 19.6069 10.643C19.6728 10.5631 19.7538 10.497 19.8453 10.4485C19.9368 10.3999 20.0369 10.3699 20.14 10.36C20.2431 10.3502 20.3471 10.3607 20.4461 10.3911C20.5451 10.4214 20.6372 10.471 20.717 10.5369C20.7969 10.6028 20.863 10.6839 20.9115 10.7753C20.9601 10.8668 20.9901 10.9669 21 11.07C21.1821 12.829 20.842 14.6026 20.0221 16.1695C19.2022 17.7363 17.9389 19.0269 16.39 19.88C15.3288 20.4938 14.1495 20.8755 12.93 21C12.62 21 12.3 21 12 21Z" fill="#ffffff"/></g></svg>
									<span class="me-auto">${response.toast_header_success}</span>
									<button class="btn-close btn-close-white ms-2 hide-toast" type="button" aria-label="Fechar"></button>
								</div>
								<div class="toast-body">${response.toast_body_success}</div>
							</div>`);

							// hide notice with fadeout
							setTimeout( function() {
								$('.exclude-email-provider-toast').fadeOut('fast');
							}, 3000);

							// remove notice from HTML after 3.5 seconds
							setTimeout( function() {
								$('.exclude-email-provider-toast').remove();
							}, 3500);
						} else {
							$('.flexify-checkout-wrapper').before(`<div class="toast toast-danger exclude-email-provider-toast show">
								<div class="toast-header bg-danger text-white">
									<svg class="flexify-toast-check-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g stroke-width="0"/><g stroke-linecap="round" stroke-linejoin="round"/><g><path d="M10.5 15.25C10.307 15.2353 10.1276 15.1455 9.99998 15L6.99998 12C6.93314 11.8601 6.91133 11.7029 6.93756 11.55C6.96379 11.3971 7.03676 11.2562 7.14643 11.1465C7.2561 11.0368 7.39707 10.9638 7.54993 10.9376C7.70279 10.9114 7.86003 10.9332 7.99998 11L10.47 13.47L19 5.00004C19.1399 4.9332 19.2972 4.91139 19.45 4.93762C19.6029 4.96385 19.7439 5.03682 19.8535 5.14649C19.9632 5.25616 20.0362 5.39713 20.0624 5.54999C20.0886 5.70286 20.0668 5.86009 20 6.00004L11 15C10.8724 15.1455 10.6929 15.2353 10.5 15.25Z" fill="#ffffff"/> <path d="M12 21C10.3915 20.9974 8.813 20.5638 7.42891 19.7443C6.04481 18.9247 4.90566 17.7492 4.12999 16.34C3.54037 15.29 3.17596 14.1287 3.05999 12.93C2.87697 11.1721 3.2156 9.39921 4.03363 7.83249C4.85167 6.26578 6.1129 4.9746 7.65999 4.12003C8.71001 3.53041 9.87134 3.166 11.07 3.05003C12.2641 2.92157 13.4719 3.03725 14.62 3.39003C14.7224 3.4105 14.8195 3.45215 14.9049 3.51232C14.9903 3.57248 15.0622 3.64983 15.116 3.73941C15.1698 3.82898 15.2043 3.92881 15.2173 4.03249C15.2302 4.13616 15.2214 4.2414 15.1913 4.34146C15.1612 4.44152 15.1105 4.53419 15.0425 4.61352C14.9745 4.69286 14.8907 4.75712 14.7965 4.80217C14.7022 4.84723 14.5995 4.87209 14.4951 4.87516C14.3907 4.87824 14.2867 4.85946 14.19 4.82003C13.2186 4.52795 12.1987 4.43275 11.19 4.54003C10.193 4.64212 9.22694 4.94485 8.34999 5.43003C7.50512 5.89613 6.75813 6.52088 6.14999 7.27003C5.52385 8.03319 5.05628 8.91361 4.77467 9.85974C4.49307 10.8059 4.40308 11.7987 4.50999 12.78C4.61208 13.777 4.91482 14.7431 5.39999 15.62C5.86609 16.4649 6.49084 17.2119 7.23999 17.82C8.00315 18.4462 8.88357 18.9137 9.8297 19.1953C10.7758 19.4769 11.7686 19.5669 12.75 19.46C13.747 19.3579 14.713 19.0552 15.59 18.57C16.4349 18.1039 17.1818 17.4792 17.79 16.73C18.4161 15.9669 18.8837 15.0864 19.1653 14.1403C19.4469 13.1942 19.5369 12.2014 19.43 11.22C19.4201 11.1169 19.4307 11.0129 19.461 10.9139C19.4914 10.8149 19.5409 10.7228 19.6069 10.643C19.6728 10.5631 19.7538 10.497 19.8453 10.4485C19.9368 10.3999 20.0369 10.3699 20.14 10.36C20.2431 10.3502 20.3471 10.3607 20.4461 10.3911C20.5451 10.4214 20.6372 10.471 20.717 10.5369C20.7969 10.6028 20.863 10.6839 20.9115 10.7753C20.9601 10.8668 20.9901 10.9669 21 11.07C21.1821 12.829 20.842 14.6026 20.0221 16.1695C19.2022 17.7363 17.9389 19.0269 16.39 19.88C15.3288 20.4938 14.1495 20.8755 12.93 21C12.62 21 12.3 21 12 21Z" fill="#ffffff"/></g></svg>
									<span class="me-auto">${response.toast_header_error}</span>
									<button class="btn-close btn-close-white ms-2 hide-toast" type="button" aria-label="Fechar"></button>
								</div>
								<div class="toast-body">${response.toast_body_error}</div>
							</div>`);

							// hide notice with fade after 3 seconds
							setTimeout( function() {
								$('.exclude-email-provider-toast').fadeOut('fast');
							}, 3000);

							// remove error notice from HTML after 3.5 seconds
							setTimeout( function() {
								$('.exclude-email-provider-toast').remove();
							}, 3500);
						}
					} catch (error) {
						console.error(error);
						btn.html(btn_html);
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					console.error("AJAX request failed:", textStatus, errorThrown);
				}
			});
		});
	});

})(jQuery);