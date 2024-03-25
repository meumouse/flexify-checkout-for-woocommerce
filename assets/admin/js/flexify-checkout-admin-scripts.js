(function ($) {
    "use strict";

	/**
	 * Activate fields
	 * 
	 * @since 1.0.0
	 */
	jQuery( function($) {
		// Reads the index stored in localStorage, if it exists
		let activeTabIndex = localStorage.getItem('activeTabIndex');

		if (activeTabIndex === null) {
			// If it is null, activate the general tab
			$('.flexify-checkout-wrapper a.nav-tab[href="#general"]').click();
		} else {
			$('.flexify-checkout-wrapper a.nav-tab').eq(activeTabIndex).click();
		}
	});
	  
	$(document).on('click', '.flexify-checkout-wrapper a.nav-tab', function() {
		// Stores the index of the active tab in localStorage
		let tabIndex = $(this).index();
		localStorage.setItem('activeTabIndex', tabIndex);
		
		let attrHref = $(this).attr('href');
		
		$('.flexify-checkout-wrapper a.nav-tab').removeClass('nav-tab-active');
		$('.flexify-checkout-form .nav-content').removeClass('active');
		$(this).addClass('nav-tab-active');
		$('.flexify-checkout-form').find(attrHref).addClass('active');
		
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
			let $btn = $(this);
			let expireDate = $btn.text();
			let btnWidth = $btn.width();
			let btnHeight = $btn.height();

			// keep original width and height
			$btn.width(btnWidth);
			$btn.height(btnHeight);

			// Add spinner inside button
			$btn.html('<span class="spinner-border spinner-border-sm"></span>');
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
	jQuery( function($) {
		let settingsForm = $('form[name="flexify-checkout"]');
		let originalValues = settingsForm.serialize();
		var notificationDelay;
	
		settingsForm.on('change', function() {
			if (settingsForm.serialize() != originalValues) {
				ajax_save_options(); // send option serialized on change
			}
		});
	
		function ajax_save_options() {
			$.ajax({
				url: flexify_checkout_params.ajax_url,
				type: 'POST',
				data: {
					action: 'flexify_checkout_ajax_save_options',
					form_data: settingsForm.serialize(),
				},
				success: function(response) {
					try {
						var responseData = JSON.parse(response); // Parse the JSON response
						console.log(responseData.options);

						if (responseData.status === 'success') {
							originalValues = settingsForm.serialize();
							$('.updated-option-success').addClass('active');
							
							if (notificationDelay) {
								clearTimeout(notificationDelay);
							}
				
							notificationDelay = setTimeout( function() {
								$('.updated-option-success').fadeOut('fast', function() {
									$(this).removeClass('active').css('display', '');
								});
							}, 3000);
						}
					} catch (error) {
						console.log(error);
					}
				}
			});
		}
	});


	/**
	 * Change container visibility
	 * 
	 * @since 1.0.0
	 */
	jQuery( function($) {
		/**
		 * Function to change container visibility
		 * 
		 * @param {string} method - activation element selector
		 * @param {string} container - container selector
		 */
		function toggleContainerVisibility(method, container) {
			let checked = $(method).prop('checked');

			$(container).toggleClass('d-none', !checked);
		}
	
		/**
		 * Show or hide coupon code field
		 * 
		 * @since 1.0.0
		 */
		toggleContainerVisibility('#enable_auto_apply_coupon_code', '.show-coupon-code-enabled');
		$('#enable_auto_apply_coupon_code').click( function() {
			toggleContainerVisibility('#enable_auto_apply_coupon_code', '.show-coupon-code-enabled');
		});

		/**
		 * Show or hide Pix popup settings
		 * 
		 * @since 2.3.0
		 */
		toggleContainerVisibility('#enable_inter_bank_pix_api', '.inter-bank-pix');
		$('#enable_inter_bank_pix_api').click( function() {
			toggleContainerVisibility('#enable_inter_bank_pix_api', '.inter-bank-pix');
		});

		/**
		 * Show or hide Bank slip popup settings
		 * 
		 * 
		 * @since 2.3.0
		 */
		toggleContainerVisibility('#enable_inter_bank_ticket_api', '.inter-bank-slip');
		$('#enable_inter_bank_ticket_api').click( function() {
			toggleContainerVisibility('#enable_inter_bank_ticket_api', '.inter-bank-slip');
		});

		/**
		 * Show or hide IP API settings
		 * 
		 * 
		 * @since 3.0.0
		 */
		toggleContainerVisibility('#enable_set_country_from_ip', '.require-set-country-from-ip');
		$('#enable_set_country_from_ip').click( function() {
			toggleContainerVisibility('#enable_set_country_from_ip', '.require-set-country-from-ip');
		});
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
	  
		selectElement.change(function() {
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
	 */
	jQuery( function($) {
		/**
		 * Function for display popups based on Bootstrap
		 * 
		 * @param {string} trigger | Trigger for display popup
		 * @param {string} container | Container for display content
		 * @param {string} close | Close button popup
		 */
		function display_popup(trigger, container, close) {
			trigger.on('click', function(e) {
				e.preventDefault();
				container.addClass('show');
			});
		
			container.on('click', function(e) {
				if (e.target === this) {
					$(this).removeClass('show');
				}
			});
		
			close.on('click', function(e) {
				e.preventDefault();
				container.removeClass('show');
			});
		}

		display_popup( $('#inter_bank_credencials_settings'), $('#inter_bank_credendials_container'), $('#inter_bank_credendials_close') );
		display_popup( $('#inter_bank_pix_settings'), $('#inter_bank_pix_container'), $('#inter_bank_pix_close') );
		display_popup( $('#inter_bank_slip_settings'), $('#inter_bank_slip_container'), $('#inter_bank_slip_close') );
		display_popup( $('#require_inter_bank_module_trigger'), $('#require_inter_bank_module_container'), $('#require_inter_bank_module_close') );
		display_popup( $('#require_inter_bank_module_trigger_2'), $('#require_inter_bank_module_container'), $('#require_inter_bank_module_close') );
		display_popup( $('.require-pro'), $('.require-pro-container'), $('.require-pro-close') );
		display_popup( $('#set_ip_api_service_trigger'), $('.set-api-service-container'), $('.set-api-service-close') );
		display_popup( $('#add_new_checkout_fields_trigger'), $('.add-new-checkout-fields-container'), $('.add-new-checkout-fields-close') );
	});

	
	/**
	 * Process upload key and crt files
	 * 
	 * @since 2.3.0
	 */
	$(document).ready(function() {
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
				var formData = new FormData();
				formData.append('action', 'upload_file');
				formData.append('file', file);
				formData.append('type', dropzone.attr('id'));
	
				$.ajax({
					url: flexify_checkout_params.ajax_url,
					type: 'POST',
					data: formData,
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
					}, 1000);
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
	 * Sortable checkout fields for reorder position on steps
	 * 
	 * @since 3.0.0
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
		$(document).on('click', '.toggle-active-tab', function(e) {
			let checked = $(e.target).prop('checked');
			let target = $('.field-item.active');

			$(target).toggleClass('active', checked).removeClass('inactive');
			$(target).toggleClass('inactive', !checked).removeClass('active');
		});

		if ( $('.field-item').hasClass('require-pro') ) {
			$(step_1).sortable( 'option', 'disabled', true );
			$(step_2).sortable( 'option', 'disabled', true );
		}
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

})(jQuery);