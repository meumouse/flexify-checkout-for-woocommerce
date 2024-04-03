/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./source/frontend/js/addressSearch.js":
/*!*********************************************!*\
  !*** ./source/frontend/js/addressSearch.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _validation__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./validation */ "./source/frontend/js/validation.js");
/* harmony import */ var _form__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./form */ "./source/frontend/js/form.js");
/* harmony import */ var _ui__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ui */ "./source/frontend/js/ui.js");



var flexifyAddressSearch = {},
  flexifyAddressAutocomplete,
  flexifyAddressAutocompleteShipping;

/**
 * Run.
 */
flexifyAddressSearch.init = function() {
  this.initSearch();
  this.watchSelect2();
  jQuery(document.body).trigger('country_to_state_changed');
};

/**
 * Watch select2 events.
 */
flexifyAddressSearch.watchSelect2 = function() {
  jQuery('select.country_select').on('select2:open', function(e) {
    let $select2_above = jQuery('.select2-dropdown--above');

    if ($select2_above.length <= 0) {
      return;
    }

    let $field_row = jQuery(this).closest('.form-row'), $label = $field_row.find('label');
    $label.hide();

  }).on('select2:close', function (e) {
    let $field_row = jQuery(this).closest('.form-row'), $label = $field_row.find('label');
    $label.show();
  });
};

/**
 * Initialise the Address Search.
 */
flexifyAddressSearch.initSearch = function() {
  flexifyAddressSearch.handleManualButtonClick();

  if ('undefined' === typeof google) {
    flexifyAddressSearch.hideLookup();
    return;
  }

  var billingAddressSearch = document.getElementById('billing_address_search');
  var shippingAddressSearch = document.getElementById('shipping_address_search');

  if (!billingAddressSearch && !shippingAddressSearch) {
    return;
  }

  // Create the autocomplete object, restricting the search to geographical
  // location types.
  var options = {
    types: ['geocode']
  };

  // Only limits to the first 5 countries, so if there's more than 5, it's
  // best to just ignore this setting.
  if (flexify_checkout_vars.allowed_countries.length <= 5) {
    options.componentRestrictions = {
      country: flexify_checkout_vars.allowed_countries
    };
  }

  if (billingAddressSearch) {
    flexifyAddressAutocomplete = new google.maps.places.Autocomplete(billingAddressSearch, options);

    // When the user selects an address from the dropdown, populate the address
    // fields in the form.
    flexifyAddressAutocomplete.addListener('place_changed', function() {
      var place = flexifyAddressAutocomplete.getPlace();
      flexifyAddressSearch.fillInAddress(place, 'billing');
    });
    billingAddressSearch.addEventListener('focus', flexifyAddressSearch.preventAutocomplete);
  }

  if (shippingAddressSearch) {
    flexifyAddressAutocompleteShipping = new google.maps.places.Autocomplete(shippingAddressSearch, options);

    // When the user selects an address from the dropdown, populate the address
    // fields in the form.
    flexifyAddressAutocompleteShipping.addListener('place_changed', function() {
      flexifyAddressSearch.fillInAddress(flexifyAddressAutocompleteShipping.getPlace(), 'shipping');
    });
    shippingAddressSearch.addEventListener('focus', flexifyAddressSearch.preventAutocomplete);
  }
};

/**
 * Prevent Autocomplete on the Search Field.
 *
 * @param {object} e Event.
 */
flexifyAddressSearch.preventAutocomplete = function (e) {
  // e.target.setAttribute( 'autocomplete', 'new-password' );
};

/**
 * Fill in the Billing Address Fields.
 */
flexifyAddressSearch.fillInAddress = function (place, type) {
  var formFieldsValue = {},
    component_form = {
      // types : [ field html id, short or long name ]
      'street_number': [`${type}_street_number`, 'short_name'],
      'route': [`${type}_address_1`, 'long_name'],
      'locality': [`${type}_city`, 'long_name'],
      'sublocality_level_1': [`${type}_neighborhood`, 'long_name'],
      'administrative_area_level_1': [`${type}_state`, 'short_name'],
      'administrative_area_level_2': [`administrative_area_level_2`, 'long_name'],
      'postal_town': [`${type}_city`, 'long_name'],
      'country': [`${type}_country`, 'long_name'],
      'postal_code': [`${type}_postcode`, 'short_name']
    },
    streetNumber = '',
    country_code = '';
  console.log(place);
  var isModern = document.querySelectorAll('.flexify-checkout--modern').length;

  for (var field in place.address_components) {
    for (var address_type in place.address_components[field].types) {
      for (var component_key in component_form) {
        var types = place.address_components[field].types;
        var prop;

        if (component_key === types[address_type]) {
          if (component_key === 'street_number') {
            streetNumber = place.address_components[field]['short_name'];
          } else if (component_key === 'country') {
            prop = component_form[component_key][1];

            if (place.address_components[field].hasOwnProperty(prop)) {
              country_code = place.address_components[field]["short_name"];
            }
          } else {
            if (document.getElementById(`${type}_country`).value === "KR") {
              streetNumber = place.address_components[0]['short_name'];
              streetNumber += "," + place.address_components[1]['long_name'];
            }
          }
          prop = component_form[component_key][1];

          if (place.address_components[field].hasOwnProperty(prop)) {
            formFieldsValue[component_form[component_key][0]] = place.address_components[field][prop];
          }
        }
      }
    }
  }

  // Support for Brazilian Market on WooCommerce.
  formFieldsValue[`${type}_number`] = formFieldsValue[`${type}_street_number`];
  if (!formFieldsValue[`${type}_city`] && formFieldsValue.administrative_area_level_2 && formFieldsValue.administrative_area_level_2 !== formFieldsValue[`${type}_country`]) {
    formFieldsValue[`${type}_city`] = formFieldsValue.administrative_area_level_2;
  }

  if (place.address_components[0].types.includes('subpremise')) {
    formFieldsValue[`${type}_street_number`] = place.address_components[0].long_name + '/' + formFieldsValue[`${type}_street_number`];
  }

  // For Italy states we want to use administrative_area_level_2 instead of administrative_area_level_1.
  if ("Italy" === formFieldsValue[`${type}_country`]) {
    var state = flexifyAddressSearch.getAddressComponent(place.address_components, 'administrative_area_level_2', 'short_name');
    if (state) {
      formFieldsValue[`${type}_state`] = state;
    }
  }

  if (0 === jQuery(`#${type}_street_number`).length) {
    if (formFieldsValue[`${type}_street_number`]) {
      formFieldsValue[`${type}_address_1`] = formFieldsValue[`${type}_street_number`] + ' ' + formFieldsValue[`${type}_address_1`];
    }

    /*
    Example adr_address: '300/<span class="street-address">121 Day St</span>..' 
    We want to capture "300/" and "121 Day St".
    */
    var match = place.adr_address.match(/(.*)<span.*class="street-address">(.*?)<\/span>/m);

    // [0] is original string, [1] is subpremise, [2] is the street address.
    if (match && (match[1] || match[2])) {
      var address_1 = match[1] + match[2];
      formFieldsValue[`${type}_address_1`] = address_1;
    }
  }

  if (jQuery(`#${type}_state`).is("input")) {
    var level_1 = flexifyAddressSearch.getAddressComponent(place.address_components, 'administrative_area_level_1');
    var level_2 = flexifyAddressSearch.getAddressComponent(place.address_components, 'administrative_area_level_2');
    var locality = flexifyAddressSearch.getAddressComponent(place.address_components, 'locality');
    var state = level_2 ? level_2 : level_1 === locality ? '' : level_1;
    formFieldsValue[`${type}_state`] = state;
  }

  for (var f in formFieldsValue) {
    if (f === `${type}_country`) {
      if (document.getElementById(f) === null) {
        return false;
      }

      var el = document.getElementById(f), eltype = el.nodeName;
      
      if (eltype === 'SELECT') {
        for (var i = 0; i < el.options.length; i++) {
          if (el.options[i].text === formFieldsValue[f] || el.options[i].value === country_code) {
            el.selectedIndex = i;
            var event = new CustomEvent('change');
            document.getElementById(f).dispatchEvent(event);
            document.getElementById(f).closest('.form-row').classList.add('is-dirty');
            break;
          }
        }
      }
    } else {
      if (document.getElementById(f) === null) {
        continue;
      } else {
        document.getElementById(f).value = formFieldsValue[f];
        var val = formFieldsValue[f];

        if (f === `${type}_state`) {
          jQuery(document).one('country_to_state_changed', {
            field: f,
            value: formFieldsValue[f]
          }, function (event) {
            window.setTimeout(() => {
              jQuery('#' + event.data.field).val(event.data.value);
              jQuery('#' + event.data.field).trigger('change');
            }, 200);
          });
        }
        jQuery('#' + f).trigger('change').trigger('keydown');
        document.getElementById(f).closest('.form-row').classList.add('is-dirty');
      }
    }
  }

  if (place.vicinity && place.vicinity !== formFieldsValue[`${type}_city`]) {
    jQuery(`#${type}_address_2`).val(place.vicinity);
  }

  var billingFields = document.querySelectorAll(`.woocommerce-${type}-fields`);
  var showManualBillingFieldsButtons = document.querySelectorAll(`.flexify-address-button-wrapper--${type}-manual`);
  
  if (!billingFields.length) {
    return;
  }

  Array.from(billingFields).forEach( function (field) {
    field.style.display = 'block';
    Array.from(field.querySelectorAll('input, select')).forEach( function (input) {
      if (input.value) {
        input.parentElement.classList.add('is-dirty');
      } else {
        input.parentElement.classList.remove('is-dirty');
      }
      if (input.attributes['placeholder']) {
        input.parentElement.classList.add('has-placeholder');
      } else {
        input.parentElement.classList.remove('has-placeholder');
      }
      if (input.parentElement.classList.contains('.validate-required')) {
        input.parentElement.classList.remove('is-invalid');
      }
    });

    if (isModern) {
      let field_wrapper = field.closest(`.woocommerce-${type}-fields__wrapper`);
      if (field_wrapper) {
        field_wrapper.querySelector(`.${type}-address-search`).style.display = 'none';
      }
    }
  });

  if (isModern) {
    Array.from(showManualBillingFieldsButtons).forEach( function (button) {
      button.closest(`.${type}-address-search`).style.display = 'none';
    });
  }

  // Woo trigger select2 reload.
  jQuery(document.body).trigger('country_to_state_changed');
  jQuery(".country_to_state").trigger('change');
  _validation__WEBPACK_IMPORTED_MODULE_0__["default"].clearErrorMessages();
};

/**
 * Handle Manual Address clicks.
 */
flexifyAddressSearch.handleManualButtonClick = function() {
  var showManualBillingFieldsButtons = document.querySelectorAll('.flexify-address-button--billing-manual');
  var showManualShippingFieldsButtons = document.querySelectorAll('.flexify-address-button--shipping-manual');
  var openBillingSearchFieldButtons = document.querySelectorAll('.flexify-address-button--billing-lookup');
  var openShippingSearchFieldButtons = document.querySelectorAll('.flexify-address-button--shipping-lookup');
  var isModern = document.querySelectorAll('.flexify-checkout--modern').length;

  Array.from(showManualBillingFieldsButtons).forEach( function (button) {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      var panel = e.target.closest('.billing-address-search').parentElement.querySelector('.billing-address-search + .woocommerce-billing-fields');
      
      if ('block' !== panel.style.display) {
        _ui__WEBPACK_IMPORTED_MODULE_2__["default"].slideDown(panel);
        if (isModern) {
          _ui__WEBPACK_IMPORTED_MODULE_2__["default"].slideUp(button.closest('.billing-address-search'));
        }
      } else {
        _ui__WEBPACK_IMPORTED_MODULE_2__["default"].slideUp(panel);
      }

      // Woo trigger select2 reload.
      jQuery(document.body).trigger('country_to_state_changed');
      return false;
    });
  });

  Array.from(openBillingSearchFieldButtons).forEach( function (button) {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      _ui__WEBPACK_IMPORTED_MODULE_2__["default"].slideUp(button.closest('.woocommerce-billing-fields'));
      _ui__WEBPACK_IMPORTED_MODULE_2__["default"].slideDown(button.closest('.woocommerce-billing-fields__wrapper').querySelector('.billing-address-search'));

      // Woo trigger select2 reload.
      jQuery(document.body).trigger('country_to_state_changed');
      return false;
    });
  });

  Array.from(showManualShippingFieldsButtons).forEach( function (button) {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      var panel = e.target.closest('.shipping-address-search').parentElement.querySelector('.shipping-address-search + .woocommerce-shipping-fields');
      
      if ('block' !== panel.style.display) {
        _ui__WEBPACK_IMPORTED_MODULE_2__["default"].slideDown(panel);
        if (isModern) {
          _ui__WEBPACK_IMPORTED_MODULE_2__["default"].slideUp(button.closest('.shipping-address-search'));
        }
      } else {
        _ui__WEBPACK_IMPORTED_MODULE_2__["default"].slideUp(panel);
      }

      // Woo trigger select2 reload.
      jQuery(document.body).trigger('country_to_state_changed');
      return false;
    });
  });

  Array.from(openShippingSearchFieldButtons).forEach( function (button) {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      _ui__WEBPACK_IMPORTED_MODULE_2__["default"].slideUp(button.closest('.woocommerce-shipping-fields'));
      _ui__WEBPACK_IMPORTED_MODULE_2__["default"].slideDown(button.closest('.woocommerce-shipping-fields__wrapper').querySelector('.shipping-address-search'));

      // Woo trigger select2 reload.
      jQuery(document.body).trigger('country_to_state_changed');
      return false;
    });
  });
};

flexifyAddressSearch.getAddressComponent = function (components, component, name) {
  if (!name) {
    name = 'long_name';
  }

  for (var loop_component of components) {
    if (loop_component.types.includes(component)) {
      return loop_component[name];
    }
  }

  return '';
};

/**
 * Hide Address lookup and show manual fields.
 */
flexifyAddressSearch.hideLookup = function() {
  jQuery(".billing-address-search").hide();
  jQuery(".shipping-address-search").hide();
  jQuery(".woocommerce-billing-fields").show();
  jQuery(".woocommerce-shipping-fields").show();
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (flexifyAddressSearch);

/***/ }),

/***/ "./source/frontend/js/cart.js":
/*!************************************!*\
  !*** ./source/frontend/js/cart.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _helper__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./helper */ "./source/frontend/js/helper.js");
/* harmony import */ var _ui__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ui */ "./source/frontend/js/ui.js");
/* harmony import */ var _stepper__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./stepper */ "./source/frontend/js/stepper.js");
/* harmony import */ var _validation__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./validation */ "./source/frontend/js/validation.js");

var flexifyCart = {};
flexifyCart.init = function() {
  flexifyCart.removeControls();
  flexifyCart.quantityControls();
  flexifyCart.moveShippingRow();
};

flexifyCart.runOnce = function() {
  flexifyCart.orderSummaryToggle(true);
  var header = document.querySelector('.flexify-checkout__sidebar-header');

  if (header) {
    header.addEventListener('click', flexifyCart.orderSummaryToggle);
  }

  jQuery(window).on('resize', flexifyCart.orderSummaryResize);
};

/**
 * Remove button.
 */
flexifyCart.removeControls = function() {
  jQuery(document).on('click', '.flexify-checkout__remove-link a.remove', function (e) {
    e.preventDefault();
    jQuery(this).closest('.cart_item').find('input').val(0);
    jQuery('body').trigger('update_checkout');
  });
};

/**
 * Add quantity control.
 */
flexifyCart.quantityControls = function() {
  var quantityControls = document.querySelectorAll('.quantity input[type="number"]');
  Array.from(quantityControls).forEach( function (control) {
    var controlWrapper = control.closest('.quantity');
    if (0 < jQuery(controlWrapper).find('.quantity__button').length) {
      return;
    }
    var buttonMinus = document.createElement('button');
    buttonMinus.setAttribute('type', 'button');
    buttonMinus.classList.add('quantity__button');
    buttonMinus.classList.add('quantity__button--minus');
    buttonMinus.innerHTML = '-';
    controlWrapper.prepend(buttonMinus);
    buttonMinus.addEventListener('click', function() {
      control.value = parseInt(control.value) - 1;
      control.dispatchEvent(new Event('change'));
    });
    var buttonPlus = document.createElement('button');
    buttonPlus.setAttribute('type', 'button');
    buttonPlus.classList.add('quantity__button');
    buttonPlus.classList.add('quantity__button--plus');
    buttonPlus.innerHTML = '+';
    controlWrapper.appendChild(buttonPlus);
    buttonPlus.addEventListener('click', function() {
      control.value = parseInt(control.value) + 1;
      control.dispatchEvent(new Event('change'));
    });
    control.addEventListener('change', async function (e) {
      e.preventDefault();

      // PHP side will be able to handle the quantity update.
      jQuery('body').trigger('update_checkout');
      return false;
    });
  });
  jQuery('.quantity input[type="number"]').on('focusin', function() {
    jQuery(this).closest('.quantity').addClass('quantity--on-focus');
  });
  jQuery('.quantity input[type="number"]').on('focusout', function() {
    jQuery(this).closest('.quantity').removeClass('quantity--on-focus');
  });
};

/**
 * Updates the Cart Count.
 * 
 * Updates the cart count that is shown on the modern theme.
 */
flexifyCart.updateCartCount = function() {
  var total = 0;
  jQuery('.quantity input.qty').each( function() {
    total += parseInt(jQuery(this).val(), 10);
  });
  var cart_count = jQuery('.order_review_heading__count');
  if (cart_count.length) {
    cart_count.html(total);
  }
};

/**
 * Update Total.
 * 
 * Update the total when the cart changes.
 */
flexifyCart.update_total = function() {
  var total = jQuery('.order-total td:last-of-type').html();
  jQuery('.flexify-checkout__sidebar-header-total').html(total);
};

/**
 * Move Shipping Row.
 * 
 * Move the shipping row to the top of the order table or
 * to address tab on mobile.
 * 
 * @returns void
 */
flexifyCart.moveShippingRow = function() {
  var is_modern = document.querySelectorAll('.flexify-checkout--modern').length;

  // No need to run this code for classic theme
  if (!is_modern) {
    return;
  }

  if (jQuery('.woocommerce-checkout-review-order-table .woocommerce-shipping-totals').length) {
    jQuery('.flexify-checkout__shipping-table tbody').html('');
  }
  
  const is_mobile = _helper__WEBPACK_IMPORTED_MODULE_0__["default"].isMobile();
  const has_sidebar = jQuery('.flexify-checkout--has-sidebar').length;

  // Pick the shipping row from content-right/sidebar.
  var $shipping_row = jQuery('.flexify-checkout__content-right tr.woocommerce-shipping-totals.shipping');

  if ( !$shipping_row.length ) {
    return;
  }

  if ( !is_mobile && has_sidebar ) {
    // Site admin can place a table.flexify-shipping-container--desktop anywhere on the page to change the location of shipping method for *desktop*.
    if (jQuery('.flexify-shipping-container--desktop').length) {
      jQuery('.flexify-shipping-container--desktop').empty().prepend($shipping_row);
    } else {
      // Else Place shipping row on the sidebar.
      jQuery('.flexify-checkout__content-left .flexify-checkout__shipping-table>tbody').html($shipping_row);
    }
  } else {
    // Site admin can place a table.flexify-shipping-container--mobile anywhere on the page to change the location of shipping method for *mobile*.
    if (jQuery('.flexify-shipping-container--mobile').length) {
      jQuery('.flexify-shipping-container--mobile').empty().prepend($shipping_row);
    } else {
      // Place Shipping row on step 2.
      jQuery('.flexify-step--address .flexify-checkout__shipping-table>tbody').html($shipping_row);
    }
  }
};

jQuery(document.body).on('update_checkout', function() {
  jQuery('.flexify-checkout__shipping-table').block({
    message: null,
    overlayCSS: {
      background: '#fff',
      opacity: 0.6
    }
  });
});

jQuery(document.body).on('updated_checkout', function(e, data) {
  jQuery('.flexify-checkout__shipping-table').unblock();

  if (_helper__WEBPACK_IMPORTED_MODULE_0__["default"].isModernCheckout()) {
    flexifyCart.addShippingRowToOrderSummary(data);
  }

  if (data?.fragments?.flexify?.global_error) {
    _validation__WEBPACK_IMPORTED_MODULE_3__["default"].displayGlobalNotice(data.fragments.flexify.global_error);
  }

  if (data?.fragments?.flexify?.empty_cart) {
    // jshint ignore:line
    jQuery('.flexify-checkout').append(data.fragments.flexify.empty_cart);

    setTimeout(() => {
      window.location = flexify_checkout_vars.shop_page;
    }, 3000);
  }
});


/**
 * Make only the field selected in the person type mandatory
 * 
 * @since 1.0.0
 */
jQuery(document).ready( function($) {
  var cpf_valid = true;
  var cnpj_valid = true;

  function check_persontype() {
    var selected_persontype = $('#billing_persontype').val();
    
    if (selected_persontype === '1') {
      $('#billing_company, #billing_cnpj, #billing_ie').removeAttr('required');
      $('#billing_cpf_field, #billing_rg_field').addClass('validate-required');
      $('#billing_cnpj_field, #billing_ie_field').removeClass('validate-required');
    } else if (selected_persontype === '2') {
      $('#billing_cpf, #billing_rg').removeAttr('required');
      $('#billing_cnpj_field, #billing_ie_field').addClass('validate-required');
      $('#billing_cpf_field, #billing_rg_field').removeClass('validate-required');
    }
  }

  $(document).ready(check_persontype);
  $('#billing_persontype').change(check_persontype);
  
  // Validar CPF
  jQuery('#billing_cpf').on('blur', function() {
    var cpfValue = jQuery(this).val().trim();
    var $parent = jQuery(this).closest('.form-row');

    if (cpfValue && !isCPFValid(cpfValue)) {
      $parent.removeClass('woocommerce-validated').addClass('woocommerce-invalid woocommerce-invalid-cpf woocommerce-invalid-required-field');
      $parent.find('.error').text(flexify_checkout_vars.i18n.cpf.invalid);
      cpf_valid = false;
    } else {
      $parent.removeClass('woocommerce-invalid woocommerce-invalid-cpf woocommerce-invalid-required-field');
      cpf_valid = true;
    }
    
    // Verificar se CPF ou CNPJ são válidos e atualizar o estado do botão
    updateNextButtonState();
  });

  // Validar CNPJ
  jQuery('#billing_cnpj').on('blur', function() {
    var cnpjValue = jQuery(this).val().trim();
    var $parent = jQuery(this).closest('.form-row');

    if (cnpjValue && !isCNPJValid(cnpjValue)) {
      $parent.removeClass('woocommerce-validated').addClass('woocommerce-invalid woocommerce-invalid-cnpj woocommerce-invalid-required-field');
      $parent.find('.error').text(flexify_checkout_vars.i18n.cnpj.invalid);
      cnpj_valid = false;
    } else {
      $parent.removeClass('woocommerce-invalid woocommerce-invalid-cnpj woocommerce-invalid-required-field');
      cnpj_valid = true;
    }
    
    // Verificar se CPF ou CNPJ são válidos e atualizar o estado do botão
    updateNextButtonState();
  });

  // validate CPF field
  function isCPFValid(cpf) {
    cpf = cpf.replace(/[^0-9]/g, '');
  
    if (cpf.length !== 11 || /^([0-9])\1+$/.test(cpf)) {
      return false;
    }
  
    let digit = cpf.substring(0, 9);
  
    for (let j = 10; j <= 11; j++) {
      let sum = 0;
  
      for (let i = 0; i < j - 1; i++) {
        sum += (j - i) * parseInt(digit[i]);
      }
  
      let summod11 = sum % 11;
      digit = digit.substring(0, j - 1) + (summod11 < 2 ? '0' : (11 - summod11).toString());
    }
  
    return parseInt(digit[9]) === parseInt(cpf[9]) && parseInt(digit[10]) === parseInt(cpf[10]);
  }

  // validade CNPJ field
  function isCNPJValid(cnpj) {
    cnpj = cnpj.replace(/\D/g, '');

    if ( cnpj.length !== 14 || parseInt(cnpj.substr(-4)) === 0 ) {
      return false;
    }

    for ( let t = 11; t < 13; t++ ) {
      let d = 0;

      for (let p = 2, c = t; c >= 0; c--, (p < 9) ? p++ : p = 2) {
        d += parseInt(cnpj[c]) * p;
      }

      d = ((10 * d) % 11) % 10;

      if ( parseInt( cnpj[t + 1] ) !== d ) {
        return false;
      }
    }

    return true;
  }

  function updateNextButtonState() {
    if (cpf_valid && cnpj_valid) {
      $('button[data-step-next]').removeAttr('disabled');
    } else {
      $('button[data-step-next]').attr('disabled', 'disabled');
    }
  }
});


/**
 * Init Select2 for new select fields
 * 
 * @since 3.2.0
 */
jQuery(document).ready( function($) {
  var get_selects = flexify_checkout_vars.get_new_select_fields;

  $(get_selects).each( function() {
    $('#' + this).select2();
  });
});


/**
 * Hide Show Order Summary.
 * Toggle for the checkout summary on mobile view.
 * 
 * @since 1.0.0
 */
flexifyCart.orderSummaryToggle = function(first) {
  if (!_helper__WEBPACK_IMPORTED_MODULE_0__["default"].isMobile()) {
    return;
  }
  
  var isModern = document.querySelectorAll('.flexify-checkout--modern').length;
  var linkHide = document.querySelector('.flexify-checkout__sidebar-header-link--hide');
  var linkShow = document.querySelector('.flexify-checkout__sidebar-header-link--show');
  var sideBar = document.querySelector('.flexify-checkout__order-review');

  if (isModern) {
    sideBar = document.querySelector('.flexify-checkout__content-right');
  }

  if (!linkHide || !sideBar) {
    return;
  }

  var style = window.getComputedStyle(linkHide);

  if (style.display === 'none') {
    linkHide.style.display = 'block';
    linkShow.style.display = 'none';

    if (true === first) {
      sideBar.style.display = 'block';
    } else {
      _ui__WEBPACK_IMPORTED_MODULE_1__["default"].slideDown(sideBar);
    }
    _ui__WEBPACK_IMPORTED_MODULE_1__["default"].slideDown(sideBar);
  } else {
    linkHide.style.display = 'none';
    linkShow.style.display = 'block';

    if (true === first) {
      sideBar.style.display = 'none';
    } else {
      _ui__WEBPACK_IMPORTED_MODULE_1__["default"].slideUp(sideBar);
    }
  }
};

flexifyCart.orderSummaryResize = function() {
  var $linkHide = jQuery('.flexify-checkout__sidebar-header-link--hide');
  var $linkShow = jQuery('.flexify-checkout__sidebar-header-link--show');
  var $sideBar = jQuery('.flexify-checkout__order-review');

  if (_helper__WEBPACK_IMPORTED_MODULE_0__["default"].isModernCheckout()) {
    $sideBar = jQuery('.flexify-checkout__content-right');
  }

  // We never want to hide sidebar for desktop.
  if (!_helper__WEBPACK_IMPORTED_MODULE_0__["default"].isMobile() && $sideBar.is(":hidden")) {
    $sideBar.show();
    return;
  }
};

flexifyCart.block = function() {
  jQuery('.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table').block({
    message: null,
    overlayCSS: {
      background: '#fff',
      opacity: 0.6
    }
  });
};

flexifyCart.unblock = function() {
  jQuery('.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table').unblock();
};

/**
 * Update international phone number when country is change
 * 
 * @since 3.0.0
 */
jQuery( function($) {
  $('#billing_country').change( function() {
    let country_code_lc = $(this).val().toLowerCase();
    let country_item = $('.iti__country-list').find('[data-country-code="' + country_code_lc + '"]');
    let country_code = country_item.attr('data-country-code');

    $('.flexify-intl-phone input[type=tel], .flexify-intl-phone input[type=text]').each( function() {
        $(this).intlTelInput('setCountry', country_code);
    });
  });
});

/**
 * Add password strenght meter
 * 
 * @since 2.0.0
 */
jQuery(document).ready( function($) {
  $('#account_password').on('keyup', function() {
    let password = $(this).val();
    let passwordStrengthElement = $('.woocommerce-password-strength');
    let meterBar = $('.create-account').find('.password-strength-meter');
    let next_step_button = $('.flexify-button');

    // reset classes
    meterBar.removeClass('short bad good strong');

    if (password !== '') {
      $('.password-meter').addClass('active');

      // Check if the class is present before accessing its properties
      if (passwordStrengthElement.length > 0) {
        let passwordStrength = passwordStrengthElement.attr('class');

        if (passwordStrength.includes('short')) {
          meterBar.addClass('short');
          next_step_button.prop('disabled', true);
        } else if (passwordStrength.includes('bad')) {
          meterBar.addClass('bad');
          next_step_button.prop('disabled', true);
        } else if (passwordStrength.includes('good')) {
          meterBar.addClass('good');
          next_step_button.prop('disabled', false);
        } else if (passwordStrength.includes('strong')) {
          meterBar.addClass('strong');
          next_step_button.prop('disabled', false);
        }
      }
    } else {
      $('.password-meter').removeClass('active');
    }
  });
});

/**
 * Add shipping cost row to the order review table for mobile view.
 */
flexifyCart.addShippingRowToOrderSummary = function(data) {
  // Add row if it doesn't exits.
  if (!jQuery('.flexify-shop-table-shipping-price').length) {
    jQuery('.shop_table tfoot .cart-subtotal').first().after('<tr class="flexify-shop-table-shipping-price"></tr>');
  }

  // Add shipping cost data received from the fragment.
  if (data?.fragments?.flexify?.shipping_row) {
    // jshint ignore:line
    jQuery('.flexify-shop-table-shipping-price').html(data.fragments.flexify.shipping_row);
  }
};

let on_resize;

jQuery(window).on('resize', function() {
  clearTimeout(on_resize);
  on_resize = setTimeout(flexifyCart.moveShippingRow, 250);
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (flexifyCart);

/***/ }),

/***/ "./source/frontend/js/checkoutButton.js":
/*!**********************************************!*\
  !*** ./source/frontend/js/checkoutButton.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _helper__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./helper */ "./source/frontend/js/helper.js");

var CheckoutButton = {
  cache: {
    button_html: ''
  },
  /**
   * Init.
   */
  init: function() {
    if (!_helper__WEBPACK_IMPORTED_MODULE_0__["default"].isModernCheckout() || jQuery('#payment_method_stripe').length) {
      return;
    }

    if (wp.hooks.applyFilters('flexify_checkout_checkout_button_animation', true)) {
      CheckoutButton.prepare_button_dom();
      jQuery(document).on('click', '#place_order', CheckoutButton.on_button_click);

      if ( jQuery('#place_order').hasClass('flexify-checkout-btn-loading') ) {
        jQuery('#place_order').trigger('click');
      }
    }

    jQuery(document.body).on('checkout_error', CheckoutButton.on_error);
    jQuery(document.body).on('updated_checkout', CheckoutButton.on_updated_checkout);
    jQuery(document.body).on('payment_method_selected', CheckoutButton.on_payment_method_selected);
  },
  /**
   * Prepare button DOM for animation.
   * @returns 
   */
  prepare_button_dom: function() {
    if (jQuery("#place_order").find('.flexify-submit-dots').length) {
      return;
    }

    jQuery("#place_order").html(jQuery("#place_order").html() + `<span class="flexify-submit-dots">
			<i class="flexify-submit-dot flexify-submit-dot__1"></i>
			<i class="flexify-submit-dot flexify-submit-dot__1"></i>
			<i class="flexify-submit-dot flexify-submit-dot__1"></i>
		</span>`);
  },
  /**
   * On button click.
   */
  on_button_click: function() {
    CheckoutButton.prepare_button_dom();
    jQuery('#place_order').addClass('flexify-checkout-btn-loading');
  },
  /**
   * On WooCommerce error.
   */
  on_error: function() {
    jQuery('#place_order').removeClass('flexify-checkout-btn-loading');
  },
  /**
   * On updated_checkout event. Modify the button html.
   *
   * @param {event} e
   * @param {data} data
   */
  on_updated_checkout: function (e, data) {
    if (!data || !data.fragments || !data.fragments.flexify) {
      return;
    }

    if (data.fragments.flexify.total) {
      CheckoutButton.cache.button_html = `${flexify_checkout_vars.i18n.pay} ${data.fragments.flexify.total}`;
      jQuery('#place_order').html(CheckoutButton.cache.button_html);
    }
  },
  /**
   * The HTML of checkout button would reset to default when payment method is selected.
   * This function would change it back.
   *
   * @returns 
   */
  on_payment_method_selected: function() {
    if (!CheckoutButton.cache.button_html || 'paypal' === jQuery("[name='payment_method']:checked").val()) {
      return;
    }
    
    jQuery('#place_order').html(CheckoutButton.cache.button_html);
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (CheckoutButton);

/***/ }),

/***/ "./source/frontend/js/compatibility.js":
/*!*********************************************!*\
  !*** ./source/frontend/js/compatibility.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
var flexifyCompatibility = {};

flexifyCompatibility.init = function() {
  this.compatSalesBooster();
  this.ie11PasswordStrength();
  this.compatDeliverySlots();
};

/**
 * Add compatibility with Sales Booster.
 */
flexifyCompatibility.compatSalesBooster = function() {
  Array.from(document.querySelectorAll('[data-flexify-wsb-checkout-bump-trigger]')).forEach( function (checkbox) {
    checkbox.addEventListener('change', function() {
      jQuery('[data-flexify-wsb-checkout-bump-trigger]').trigger('change');
    });
  });
};

flexifyCompatibility.compatDeliverySlots = function() {
  // setTimeOut because we want our event listener to run after wc_checkout_form::validate_field().
  window.setTimeout( function() {
    jQuery('#jckwds-delivery-date, #jckwds-delivery-time').on('validate', function (e) {
      if ('1' === jQuery('[name=flexify-wds-fields-hidden]').val()) {
        jQuery(e.target).closest('.form-row').removeClass('woocommerce-invalid');
        e.stopPropagation();
      }
    });
  });
};

/**
 *  IE11 Compatibility.
 */
if (!('remove' in Element.prototype)) {
  Element.prototype.remove = function() {
    if (this.parentNode) {
      this.parentNode.removeChild(this);
    }
  };
}
if (!Array.from) {
  Array.from = function() {
    var toStr = Object.prototype.toString;
    var isCallable = function (fn) {
      return typeof fn === 'function' || toStr.call(fn) === '[object Function]';
    };

    var toInteger = function (value) {
      var number = Number(value);

      if (isNaN(number)) {
        return 0;
      }

      if (number === 0 || !isFinite(number)) {
        return number;
      }

      return (number > 0 ? 1 : -1) * Math.floor(Math.abs(number));
    };

    var maxSafeInteger = Math.pow(2, 53) - 1;
    var toLength = function (value) {
      var len = toInteger(value);
      return Math.min(Math.max(len, 0), maxSafeInteger);
    };

    // The length property of the from method is 1.
    return function from(arrayLike /*, mapFn, thisArg */) {
      // 1. Let C be the this value.
      var C = this;

      // 2. Let items be ToObject(arrayLike).
      var items = Object(arrayLike);

      // 3. ReturnIfAbrupt(items).
      if (arrayLike == null) {
        throw new TypeError("Array.from requires an array-like object - not null or undefined");
      }

      // 4. If mapfn is undefined, then let mapping be false.
      var mapFn = arguments.length > 1 ? arguments[1] : void undefined;
      var T;

      if (typeof mapFn !== 'undefined') {
        // 5. else
        // 5. a If IsCallable(mapfn) is false, throw a TypeError exception.
        if (!isCallable(mapFn)) {
          throw new TypeError('Array.from: when provided, the second argument must be a function');
        }

        // 5. b. If thisArg was supplied, let T be thisArg; else let T be undefined.
        if (arguments.length > 2) {
          T = arguments[2];
        }
      }

      // 10. Let lenValue be Get(items, "length").
      // 11. Let len be ToLength(lenValue).
      var len = toLength(items.length);

      // 13. If IsConstructor(C) is true, then
      // 13. a. Let A be the result of calling the [[Construct]] internal method of C with an argument list containing the single item len.
      // 14. a. Else, Let A be ArrayCreate(len).
      var A = isCallable(C) ? Object(new C(len)) : new Array(len);

      // 16. Let k be 0.
      var k = 0;
      // 17. Repeat, while k < len… (also steps a - h)
      var kValue;
      while (k < len) {
        kValue = items[k];
        if (mapFn) {
          A[k] = typeof T === 'undefined' ? mapFn(kValue, k) : mapFn.call(T, kValue, k);
        } else {
          A[k] = kValue;
        }
        k += 1;
      }
      // 18. Let putStatus be Put(A, "length", len, true).
      A.length = len;
      // 20. Return A.
      return A;
    };
  }();
}
if (!Element.prototype.matches) {
  Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
}
if (!Element.prototype.closest) {
  Element.prototype.closest = function (s) {
    var el = this;
    do {
      if (Element.prototype.matches.call(el, s)) {
        return el;
      }
      el = el.parentElement || el.parentNode;
    } while (el !== null && el.nodeType === 1);
    return null;
  };
}
if (!Object.entries) {
  Object.entries = function (obj) {
    var ownProps = Object.keys(obj),
      i = ownProps.length,
      resArray = new Array(i); // preallocate the Array
    while (i--) {
      resArray[i] = [ownProps[i], obj[ownProps[i]]];
    }
    return resArray;
  };
}
if (!String.prototype.includes) {
  String.prototype.includes = function (search, start) {
    'use strict';

    if (typeof start !== 'number') {
      start = 0;
    }
    if (start + search.length > this.length) {
      return false;
    } else {
      return this.indexOf(search, start) !== -1;
    }
  };
}
if (!Array.prototype.includes) {
  Object.defineProperty(Array.prototype, "includes", {
    enumerable: false,
    value: function (obj) {
      var newArr = this.filter( function (el) {
        return el === obj;
      });
      return newArr.length > 0;
    }
  });
}
( function (arr) {
  arr.forEach( function (item) {
    if (item.hasOwnProperty('prepend')) {
      return;
    }
    Object.defineProperty(item, 'prepend', {
      configurable: true,
      enumerable: true,
      writable: true,
      value: function prepend() {
        var argArr = Array.prototype.slice.call(arguments),
          docFrag = document.createDocumentFragment();
        argArr.forEach( function (argItem) {
          var isNode = argItem instanceof Node;
          docFrag.appendChild(isNode ? argItem : document.createTextNode(String(argItem)));
        });
        this.insertBefore(docFrag, this.firstChild);
      }
    });
  });
})([Element.prototype, Document.prototype, DocumentFragment.prototype]);
( function() {
  if (typeof window.CustomEvent === "function") {
    return false;
  }
  function CustomEvent(event, params) {
    params = params || {
      bubbles: false,
      cancelable: false,
      detail: null
    };
    var evt = document.createEvent('CustomEvent');
    evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
    return evt;
  }
  window.CustomEvent = CustomEvent;
})();

/**
 * Add support for IE11 password strength.
 */
flexifyCompatibility.ie11PasswordStrength = function() {
  if ('undefined' === typeof wp) {
    window.wp = {};
  }
  if ('undefined' === wp.i18n) {
    /* global wp, pwsL10n, wc_password_strength_meter_params */

    wp.passwordStrength = {
      /**
       * Determines the strength of a given password.
       *
       * Compares first password to the password confirmation.
       *
       * @since 3.7.0
       *
       * @param {string} password1       The subject password.
       * @param {Array}  disallowedList An array of words that will lower the entropy of
       *                                 the password.
       * @param {string} password2       The password confirmation.
       *
       * @return {number} The password strength score.
       */
      meter: function (password1, disallowedList, password2) {
        if (!Array.isArray(disallowedList)) disallowedList = [disallowedList.toString()];
        if (password1 != password2 && password2 && password2.length > 0) return 5;
        if ('undefined' === typeof window.zxcvbn) {
          // Password strength unknown.
          return -1;
        }
        var result = zxcvbn(password1, disallowedList);
        return result.score;
      },
      /**
       * Builds an array of words that should be penalized.
       *
       * Certain words need to be penalized because it would lower the entropy of a
       * password if they were used. The disallowedList is based on user input fields such
       * as username, first name, email etc.
       *
       * @since 3.7.0
       * @deprecated 5.5.0 Use {@see 'userInputDisallowedList()'} instead.
       *
       * @return {string[]} The array of words to be disallowed.
       */
      userInputBlacklist: function() {
        window.console.log(sprintf( /* translators: 1: Deprecated function name, 2: Version number, 3: Alternative function name. */
        __('%1$s is deprecated since version %2$s! Use %3$s instead. Please consider writing more inclusive code.'), 'wp.passwordStrength.userInputBlacklist()', '5.5.0', 'wp.passwordStrength.userInputDisallowedList()'));
        return wp.passwordStrength.userInputDisallowedList();
      },
      /**
       * Builds an array of words that should be penalized.
       *
       * Certain words need to be penalized because it would lower the entropy of a
       * password if they were used. The disallowed list is based on user input fields such
       * as username, first name, email etc.
       *
       * @since 5.5.0
       *
       * @return {string[]} The array of words to be disallowed.
       */
      userInputDisallowedList: function() {
        var i,
          userInputFieldsLength,
          rawValuesLength,
          currentField,
          rawValues = [],
          disallowedList = [],
          userInputFields = ['user_login', 'first_name', 'last_name', 'nickname', 'display_name', 'email', 'url', 'description', 'weblog_title', 'admin_email'];

        // Collect all the strings we want to disallow.
        rawValues.push(document.title);
        rawValues.push(document.URL);
        userInputFieldsLength = userInputFields.length;

        for (i = 0; i < userInputFieldsLength; i++) {
          currentField = jQuery('#' + userInputFields[i]);

          if (0 === currentField.length) {
            continue;
          }

          rawValues.push(currentField[0].defaultValue);
          rawValues.push(currentField.val());
        }

        /*
         * Strip out non-alphanumeric characters and convert each word to an
         * individual entry.
         */
        rawValuesLength = rawValues.length;

        for (i = 0; i < rawValuesLength; i++) {
          if (rawValues[i]) {
            disallowedList = disallowedList.concat(rawValues[i].replace(/\W/g, ' ').split(' '));
          }
        }

        /*
         * Remove empty values, short words and duplicates. Short words are likely to
         * cause many false positives.
         */
        disallowedList = jQuery.grep(disallowedList, function (value, key) {
          if ('' === value || 4 > value.length) {
            return false;
          }

          return jQuery.inArray(value, disallowedList) === key;
        });

        return disallowedList;
      }
    };

    // Backward compatibility.

    /**
     * Password strength meter function.
     *
     * @since 2.5.0
     * @deprecated 3.7.0 Use wp.passwordStrength.meter instead.
     *
     * @global
     *
     * @type {wp.passwordStrength.meter}
     */
    window.passwordStrength = wp.passwordStrength.meter;

    /**
     * Password Strength Meter class.
     */
    var wc_password_strength_meter = {
      /**
       * Initialize strength meter actions.
       */
      init: function() {
        Array.from(document.querySelectorAll('form.checkout #account_password')).forEach( function (password) {
          password.addEventListener('keyup', wc_password_strength_meter.strengthMeter);
        });
      },
      /**
       * Strength Meter.
       */
      strengthMeter: function() {
        var wrapper = jQuery('form.register, form.checkout, form.edit-account, form.lost_reset_password'),
          submit = jQuery('button[type="submit"]', wrapper),
          field = jQuery('#reg_password, #account_password, #password_1', wrapper),
          strength = 1,
          fieldValue = field.val(),
          stop_checkout = !wrapper.is('form.checkout'); // By default is disabled on checkout.

        wc_password_strength_meter.includeMeter(wrapper, field);
        strength = wc_password_strength_meter.checkPasswordStrength(wrapper, field);

        // Allow password strength meter stop checkout.
        if (wc_password_strength_meter_params.stop_checkout) {
          stop_checkout = true;
        }
        if (fieldValue.length > 0 && strength < wc_password_strength_meter_params.min_password_strength && -1 !== strength && stop_checkout) {
          submit.attr('disabled', 'disabled').addClass('disabled');
        } else {
          submit.prop('disabled', false).removeClass('disabled');
        }
      },
      /**
       * Include meter HTML.
       *
       * @param {Object} wrapper
       * @param {Object} field
       */
      includeMeter: function (wrapper, field) {
        var meter = wrapper.find('.woocommerce-password-strength');
        if ('' === field.val()) {
          meter.hide();
          jQuery(document.body).trigger('wc-password-strength-hide');
        } else if (0 === meter.length) {
          field.after('<div class="woocommerce-password-strength" aria-live="polite"></div>');
          jQuery(document.body).trigger('wc-password-strength-added');
        } else {
          meter.show();
          jQuery(document.body).trigger('wc-password-strength-show');
        }
      },
      /**
       * Check password strength.
       *
       * @param {Object} field
       *
       * @return {Int}
       */
      checkPasswordStrength: function (wrapper, field) {
        var meter = wrapper.find('.woocommerce-password-strength'),
          hint = wrapper.find('.woocommerce-password-hint'),
          hint_html = '<small class="woocommerce-password-hint">' + wc_password_strength_meter_params.i18n_password_hint + '</small>',
          strength = wp.passwordStrength.meter(field.val(), wp.passwordStrength.userInputDisallowedList()),
          error = '';

        // Reset.
        meter.removeClass('short bad good strong');
        hint.remove();

        if (meter.is(':hidden')) {
          return strength;
        }

        // Error to append
        if (strength < wc_password_strength_meter_params.min_password_strength) {
          error = ' - ' + wc_password_strength_meter_params.i18n_password_error;
        }

        switch (strength) {
          case 0:
            meter.addClass('short').html(pwsL10n['short'] + error);
            meter.after(hint_html);
            break;
          case 1:
            meter.addClass('bad').html(pwsL10n.bad + error);
            meter.after(hint_html);
            break;
          case 2:
            meter.addClass('bad').html(pwsL10n.bad + error);
            meter.after(hint_html);
            break;
          case 3:
            meter.addClass('good').html(pwsL10n.good + error);
            break;
          case 4:
            meter.addClass('strong').html(pwsL10n.strong + error);
            break;
          case 5:
            meter.addClass('short').html(pwsL10n.mismatch);
            break;
        }

        return strength;
      }
    };

    wc_password_strength_meter.init();
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (flexifyCompatibility);

/***/ }),

/***/ "./source/frontend/js/components.js":
/*!******************************************!*\
  !*** ./source/frontend/js/components.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ui__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ui */ "./source/frontend/js/ui.js");

var flexifyComponents = {};

/**
 * Run.
 */
flexifyComponents.init = function() {
  this.accountToggle();
  this.shippingToggle();
  this.orderNotesToggle();
  this.watchCountryChange();
  document.querySelector('html').classList.remove('no-js');
};

/**
 * Enable the Account Toggle.
 */
flexifyComponents.accountToggle = function() {
  jQuery(document).ready( function() {
    jQuery('.woocommerce-account-fields input#createaccount').unbind();
    Array.from(document.querySelectorAll('.woocommerce-account-fields input#createaccount')).forEach( function (checkbox) {
      checkbox.addEventListener('change', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var accountFields = e.target.closest('.woocommerce-account-fields').querySelector('div.create-account');
        if (!accountFields) {
          return false;
        }
        if (e.target.checked) {
          _ui__WEBPACK_IMPORTED_MODULE_0__["default"].slideDown(accountFields);
          accountFields.setAttribute('aria-hidden', 'false');
        } else {
          _ui__WEBPACK_IMPORTED_MODULE_0__["default"].slideUp(accountFields);
          accountFields.setAttribute('aria-hidden', 'true');
        }

        // Remove errors.
        setTimeout( function() {
          Array.from(accountFields.querySelectorAll('input, select, textarea')).forEach( function (field) {
            field.closest('.form-row').classList.remove('woocommerce-invalid');
          });
        }, 1);
        jQuery(document.body).trigger('country_to_state_changed');
        return false;
      });
    });
  });
};

/**
 * Enable the Shipping Toggle.
 */
flexifyComponents.shippingToggle = function() {
  Array.from(document.querySelectorAll('#ship-to-different-address input')).forEach( function (checkbox) {
    checkbox.addEventListener('change', function (e) {
      e.preventDefault();
      var shippingAddressFields = e.target.closest('.woocommerce-shipping-fields__wrapper').querySelector('.shipping_address');
      if (e.target.checked) {
        _ui__WEBPACK_IMPORTED_MODULE_0__["default"].slideDown(shippingAddressFields);
        shippingAddressFields.setAttribute('aria-hidden', 'false');
      } else {
        _ui__WEBPACK_IMPORTED_MODULE_0__["default"].slideUp(shippingAddressFields);
        shippingAddressFields.setAttribute('aria-hidden', 'true');
      }
      jQuery(document.body).trigger('country_to_state_changed');
      return false;
    });
  });
};

/**
 * Enable the Order Notes Toggle.
 */
flexifyComponents.orderNotesToggle = function() {
  Array.from(document.querySelectorAll('#show-additional-fields input')).forEach( function (checkbox) {
    checkbox.addEventListener('change', function (e) {
      e.preventDefault();

      var additionalFields = e.target.closest('.woocommerce-additional-fields__wrapper').querySelector('.woocommerce-additional-fields');
      
      if (e.target.checked) {
        _ui__WEBPACK_IMPORTED_MODULE_0__["default"].slideDown(additionalFields);
        additionalFields.setAttribute('aria-hidden', 'false');
      } else {
        _ui__WEBPACK_IMPORTED_MODULE_0__["default"].slideUp(additionalFields);
        additionalFields.setAttribute('aria-hidden', 'true');
      }

      jQuery(document.body).trigger('country_to_state_changed');

      return false;
    });
  });
};

/**
 * Change data-type value for the county/state field when country is changed.
 */
flexifyComponents.onCountryChange = function() {
  window.setTimeout( function() {
    if (jQuery("#billing_state").length) {
      var tagName = jQuery("#billing_state").prop('tagName');

      if ('SELECT' === tagName) {
        jQuery("#billing_state").closest('.form-row').attr('data-type', 'select');
      } else {
        jQuery("#billing_state").closest('.form-row').attr('data-type', 'text');
        jQuery("#billing_state").attr('placeholder', '');
      }
    }

    if (jQuery("#shipping_state").length) {
      var tagName = jQuery("#shipping_state").prop('tagName');

      if ('SELECT' === tagName) {
        jQuery("#shipping_state").closest('.form-row').attr('data-type', 'select');
      } else {
        jQuery("#shipping_state").closest('.form-row').attr('data-type', 'text');
        jQuery("#shipping_state").attr('placeholder', '');
      }
    }
  });
};

/**
 * Watch country change.
 */
flexifyComponents.watchCountryChange = function() {
  if ( !jQuery('#billing_country').length ) {
    flexifyComponents.onCountryChange();

    return;
  }

  jQuery('#billing_country, #shipping_country').change( function() {
    flexifyComponents.onCountryChange();
  });

  flexifyComponents.onCountryChange();
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (flexifyComponents);

/***/ }),

/***/ "./source/frontend/js/coupon.js":
/*!**************************************!*\
  !*** ./source/frontend/js/coupon.js ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _helper__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./helper */ "./source/frontend/js/helper.js");
/* harmony import */ var _stepper__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./stepper */ "./source/frontend/js/stepper.js");
/* harmony import */ var _ui__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ui */ "./source/frontend/js/ui.js");

var flexifyCoupon = {};
flexifyCoupon.init = function() {
  this.onButtonClick();
  this.onFormSubmit();
  this.onChange();
  var sideBar = document.querySelector('.flexify-checkout--has-sidebar');

  ( function ($, document) {
    $(document).ready( function() {
      if (sideBar) {
        $(document.body).on('wc_fragments_refreshed', function() {
          flexifyCoupon.onButtonClick();
        });
        $(document.body).on('updated_checkout', function() {
          flexifyCoupon.onButtonClick();
        });
        $(document).on('keydown', '#coupon_code', function (e) {
          if (e.key === 'Enter' || e.keyCode === 13) {
            jQuery("[name=apply_coupon]").trigger('click');
            e.preventDefault();
          }
        });

        // Call removeCoupon after the WooCommerce event listener has been added.
        setTimeout(flexifyCoupon.removeCoupon, 100);
      }
    });
  })(jQuery, document);
};

/**
 * Login Buttons On Click.
 * 
 * Handle the show and hide of the login form from a custom button.
 */
flexifyCoupon.onButtonClick = function() {
  var buttons = document.querySelectorAll('[data-show-coupon]');

  Array.from(buttons).forEach( function (button) {
    button.setAttribute('type', 'button');
    button.addEventListener('click', function (e) {
      e.preventDefault();
      var form = e.target.closest('.woocommerce-form-coupon__wrapper').querySelector('.woocommerce-form-coupon');

      if ('none' === form.style.display) {
        _ui__WEBPACK_IMPORTED_MODULE_2__["default"].slideDown(form);
      } else {
        _ui__WEBPACK_IMPORTED_MODULE_2__["default"].slideUp(form);
      }

      return false;
    });
  });
};

flexifyCoupon.onFormSubmit = function() {
  jQuery(document).on('click', 'button[name=apply_coupon]', async function (e) {
    e.preventDefault();
    var $form = jQuery(this).closest('.woocommerce-form-coupon__wrapper');
    var $row = $form.find('.form-row');

    jQuery('.woocommerce-form-coupon__wrapper').find('.error, .success').remove();
    var data = {
      action: 'apply_coupon',
      coupon_code: $form.find('input[name="coupon_code"]').val(),
      security: wc_checkout_params.apply_coupon_nonce
    };
    _stepper__WEBPACK_IMPORTED_MODULE_1__["default"].loadSpinner();
    let onError = function (err) {};
    await _helper__WEBPACK_IMPORTED_MODULE_0__["default"].ajaxRequestWoo(data, function (response) {
      var message = response.replace(/(<([^>]+)>)/gi, '');
      if (response.includes('woocommerce-error')) {
        $row.addClass('woocommerce-invalid');
        $row.eq(0).append(`<div class='error' aria-hidden='false' aria-live='polite'>${message}</div>`);
      } else {
        jQuery(document.body).trigger('update_checkout', {
          update_shipping_method: false
        });
        jQuery(document.body).one('updated_checkout', function() {
          jQuery('.woocommerce-form-coupon__inner .form-row-first').append(`<div class="success" aria-hidden="false" aria-live="polite">${message}</div>`);
        });
      }
    }, onError);
    _stepper__WEBPACK_IMPORTED_MODULE_1__["default"].removeSpinner();
    return false;
  });
};
flexifyCoupon.onChange = function() {
  jQuery(document).on('keyup', '#coupon_code', function() {
    var $btn = jQuery(this).closest('.checkout_coupon').find('.flexify-coupon-button');
    if (jQuery(this).val().trim()) {
      $btn.removeClass('flexify-coupon-button--disabled');
    } else {
      $btn.addClass('flexify-coupon-button--disabled');
    }
  });
};
flexifyCoupon.removeCoupon = function (e) {
  // Remove WooCommerce's event listener.
  jQuery(document.body).off('click', '.woocommerce-remove-coupon');
  jQuery(document.body).on('click', '.woocommerce-remove-coupon', function (e) {
    e.preventDefault();

    var container = jQuery(this).parents('.woocommerce-checkout-review-order'), coupon = jQuery(this).data('coupon');
    container.addClass('processing').block({
      message: null,
      overlayCSS: {
        background: '#fff',
        opacity: 0.6
      }
    });

    var data = {
      security: wc_checkout_params.remove_coupon_nonce,
      coupon: coupon
    };

    jQuery.ajax({
      type: 'POST',
      url: wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', 'remove_coupon'),
      data: data,
      success: function (code) {
        jQuery('.woocommerce-error, .woocommerce-message').remove();
        jQuery('.woocommerce-form-coupon__wrapper').find('.error, .success').remove();
        container.removeClass('processing').unblock();
        
        if (code) {
          jQuery(document.body).trigger('removed_coupon_in_checkout', [data.coupon]);
          jQuery(document.body).trigger('update_checkout', {
            update_shipping_method: false
          });
          jQuery(document.body).one('updated_checkout', function() {
            jQuery('.woocommerce-form-coupon__inner .form-row-first').append(`<div class="success" aria-hidden="false" aria-live="polite">${flexify_checkout_vars.i18n.coupon_success}</div>`);
          });

          // Remove coupon code from coupon field
          jQuery('form.checkout_coupon').find('input[name="coupon_code"]').val('');
        }
      },
      error: function (jqXHR) {
        if (wc_checkout_params.debug_mode) {
          /* jshint devel: true */
          console.log(jqXHR.responseText);
        }
      },
      dataType: 'html'
    });
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (flexifyCoupon);

/***/ }),

/***/ "./source/frontend/js/expressCheckout.js":
/*!***********************************************!*\
  !*** ./source/frontend/js/expressCheckout.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
var flexifyExpressCheckout = {
  /**
   * Elements.
   */
  els: {
    $wrap: jQuery('.flexify-express-checkout-wrap')
  },
  /**
   * Init.
   */
  init: function() {
    jQuery( function() {
      flexifyExpressCheckout.relocateExpressButtons();
      flexifyExpressCheckout.els.$wrap = jQuery('.flexify-express-checkout-wrap');
      flexifyExpressCheckout.stripeHideEmptyElement();
    });
  },
  /**
   * Move all express checkout buttons to $wrap so they appear altogether within the checkout page.
   */
  relocateExpressButtons: function() {
    // Stripe.
    jQuery("#wc-stripe-payment-request-wrapper>div").each( function() {
      jQuery(this).appendTo(flexifyExpressCheckout.els.$wrap).wrap("<div class='flexify-express-checkout__btn flexify-expresss-checkout__btn--stripe flexify-skeleton'></div>");
    });

    // Paypal.
    jQuery(".eh_paypal_express_link").appendTo(flexifyExpressCheckout.els.$wrap).wrap("<div class='flexify-express-checkout__btn'></div>");
  },
  /**
   * Hide express checkout wrap if stripe div is the only element and its empty.
   * @returns 
   */
  stripeHideEmptyElement: function() {
    if (!jQuery('#wc-stripe-payment-request-button').length) {
      return;
    }

    // If stripe elements is the only element then hide the express checkout wrap
    // and wait for Google/Apple pay buttons to mount.
    jQuery(".flexify-expresss-checkout__btn--stripe").addClass("flexify-skeleton");

    // Wait for stripe payment elements to mount.
    setTimeout( function() {
      if (!jQuery('#wc-stripe-payment-request-button div').length) {
        jQuery('#wc-stripe-payment-request-button').hide();
      }
      jQuery(".flexify-expresss-checkout__btn--stripe").removeClass("flexify-skeleton");

      // if stripe is the only element in the express checkout and its empty then hide wrap.
      if (flexifyExpressCheckout.els.$wrap.find(">div,>span,>a").length < 2 && 0 == jQuery("#wc-stripe-payment-request-button>div").length) {
        flexifyExpressCheckout.els.$wrap.hide();
      }
    }, 3000);
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (flexifyExpressCheckout);

/***/ }),

/***/ "./source/frontend/js/form.js":
/*!************************************!*\
  !*** ./source/frontend/js/form.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _helper__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./helper */ "./source/frontend/js/helper.js");

var flexifyForm = {};

flexifyForm.init = function() {
  jQuery( function() {
    if (!_helper__WEBPACK_IMPORTED_MODULE_0__["default"].isModernCheckout()) {
      return;
    }

    flexifyForm.prepareFields();
    flexifyForm.addRemoveFocusClass();

    // Add is-active class.
    jQuery(document).on('change focus keydown', '.form-row input, .form-row select, .form-row textarea', function() {
      jQuery(this).closest('.form-row').addClass('is-active');
    });
    
    jQuery(document).on('blur', '.form-row input, .form-row select, .form-row textarea', function() {
      var $row = jQuery(this).closest('.form-row');

      if (flexifyForm.hasPermanentPlaceholder($row) || jQuery(this).val()) {
        return;
      }

      $row.removeClass('is-active');
    });

    jQuery(document.body).on('country_to_state_changed', function() {
      flexifyForm.prepareFields();
    });
  });
};

/**
 * Prepare fields on the pageload i.e. add is-active class for the input which are not empty.
 */
flexifyForm.prepareFields = function() {
  jQuery('.form-row input, .form-row select').each( function() {
    flexifyForm.prepareField( jQuery(this) );
  });
};

/**
 * Prepare a field i.e. add/remove is-active based on its content.
 * @param {*} $input 
 * @returns 
 */
flexifyForm.prepareField = function($input) {
  var $row = $input.closest('.form-row');
  var $label = $row.find('label');

  if (flexifyForm.hasPermanentPlaceholder($row)) {
    $row.addClass('is-active');
    return;
  }

  if ($input.val() || $row.find('select').length > 0) {
    $row.addClass('is-active');
  } else {
    $row.removeClass('is-active');
  }
};

/**
 * Get checkout data and save to 'customer' session
 * 
 * @since 1.8.5
 */
jQuery(document).ready( function($) {
  $('.flexify-button[data-step-next]').on('click', function(e) {
    e.preventDefault();

    $('#billing_country, #shipping_country').trigger('change');
    $('#billing_state_field, #shipping_state_field').removeClass('woocommerce-invalid');

    var first_name = $('#billing_first_name').val();
    var last_name = $('#billing_last_name').val();
    var phone = $('#billing_phone').val();
    var international_phone = $('#billing_phone_full_number').val();
    var email = $('#billing_email').val();
    var company = $('#billing_company').val();
    var billing_gender = $('#billing_gender').val();
    var billing_persontype = $('#billing_persontype').val();
    var billing_cpf = $('#billing_cpf').val();
    var billing_rg = $('#billing_rg').val();
    var billing_birthdate = $('#billing_birthdate').val();
    var billing_cnpj = $('#billing_cnpj').val();
    var billing_ie = $('#billing_ie').val();
    var billing_country = $('#billing_country').val();
    var billing_postcode = $('#billing_postcode').val();
    var billing_address_1 = $('#billing_address_1').val();
    var billing_number = $('#billing_number').val();
    var billing_neighborhood = $('#billing_neighborhood').val();
    var billing_address_2 = $('#billing_address_2').val();
    var billing_city = $('#billing_city').val();
    var billing_state = $('#billing_state').val();

    var data = {
        action: 'get_checkout_session_data',
        billing_first_name: first_name,
        billing_last_name: last_name,
        billing_phone: phone,
        billing_phone_full_number: international_phone,
        billing_email: email,
        billing_company: company,
        billing_gender: billing_gender,
        billing_persontype: billing_persontype,
        billing_cpf: billing_cpf,
        billing_rg: billing_rg,
        billing_birthdate: billing_birthdate,
        billing_cnpj: billing_cnpj,
        billing_ie: billing_ie,
        billing_country: billing_country,
        billing_postcode: billing_postcode,
        billing_address_1: billing_address_1,
        billing_number: billing_number,
        billing_neighborhood: billing_neighborhood,
        billing_address_2: billing_address_2,
        billing_city: billing_city,
        billing_state: billing_state,
    };

    $.ajax({
        url: wc_checkout_params.ajax_url,
        type: 'POST',
        data: data,
        success: function(response) {
            updateCustomerInfo(response.data);
            $('body').trigger('update_checkout');
        },
        error: function(xhr, status, error) {
            console.error('Erro ao inserir dados na sessão do cliente:', error);
        }
    });
  });

  function updateCustomerInfo(response) {
    $('.woocommerce-customer-details--name').text(response.data.first_name + ' ' + response.data.last_name);
    $('.woocommerce-customer-details--phone').text(response.data.phone);
    $('.woocommerce-customer-details--email').text(response.data.email);
  }
});

/**
 * Does this field have permanent placeholder?
 * 
 * @param {jQuery} $row Row object.
 * 
 * @returns bool
 */
flexifyForm.hasPermanentPlaceholder = function ($row) {
  var $label = $row.find('label');

  if (!$label.length) {
    return false;
  }
  
  var _for = $label.attr('for');

  if (['billing_address_info', 'shipping_address_info', ''].includes(_for)) {
    return true;
  }

  if ('billing_phone' === _for && 'yes' === flexify_checkout_vars.international_phone) {
    return true;
  }

  return false;
};

flexifyForm.addRemoveFocusClass = function() {
  jQuery(document).on('focus', '.form-row input', function() {
    jQuery(this).closest('.form-row').addClass('form-row--focus');
  });

  jQuery(document).on('blur', '.form-row input', function() {
    jQuery(this).closest('.form-row').removeClass('form-row--focus');
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (flexifyForm);

/***/ }),

/***/ "./source/frontend/js/geocodeMap.js":
/*!******************************************!*\
  !*** ./source/frontend/js/geocodeMap.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
var GeocodeMap = {
  $address: null,
  geocoder: null,
  map: null,
  /**
   * Init.
   */
  init: function() {
    jQuery(document).ready( function() {
      if (!jQuery('#flexify-ty-map-canvas').length || !google) {
        return;
      }
      GeocodeMap.geocode();
    });
  },
  /**
   * Geocode.
   */
  geocode: function() {
    GeocodeMap.geocoder = new google.maps.Geocoder();
    GeocodeMap.geocoder.geocode({
      'address': jQuery('#flexify-ty-map-canvas').data('address')
    }, GeocodeMap.setupMap);
  },
  /**
   * Setup Map.
   *
   * @param {*} results 
   * @param {*} status 
   * @returns 
   */
  setupMap: function (results, status) {
    if (status !== google.maps.GeocoderStatus.OK) {
      jQuery('#flexify-ty-map-canvas').hide();
    }

    var options = {
      zoom: 12,
      center: results[0].geometry.location,
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      zoomControl: false,
      mapTypeControl: false,
      scaleControl: false,
      streetViewControl: false,
      rotateControl: false,
      fullscreenControl: false
    };

    GeocodeMap.map = new google.maps.Map(document.getElementById("flexify-ty-map-canvas"), options);

    var marker = new google.maps.Marker({
      map: GeocodeMap.map,
      position: results[0].geometry.location,
      title: jQuery('#flexify-ty-map-canvas').data('address')
    });
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (GeocodeMap);

/***/ }),

/***/ "./source/frontend/js/helper.js":
/*!**************************************!*\
  !*** ./source/frontend/js/helper.js ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
var flexifyHelper = {};

/**
 * Serialize Data
 * 
 * Transform a JavaScript object into a serialized string so it can be passed
 * into PHP and decoded as an array.
 *
 * @param {object} obj    Object.
 * @param {string} prefix Key.
 * @returns 
 */
flexifyHelper.serializeData = function (obj, prefix) {
  var str = [];

  for (var p in obj) {
    if (obj.hasOwnProperty(p)) {
      var k = prefix ? prefix + '[' + p + ']' : p, v = obj[p];

      str.push(typeof v === 'object' ? flexifyHelper.serializeData(v, k) : encodeURIComponent(k) + '=' + encodeURIComponent(v));
    }
  }

  return str.join('&');
};

/**
 * Get Field Value.
 * 
 * Get the value of a field, depending on its type.
 *
 * @param {object} field Field.
 * @returns 
 */
flexifyHelper.getFieldValue = function (field) {
  var value = field.value; // @todo account for other field types here.

  return value;
};

/**
 * Do AJAX.
 * 
 * A simple native AJAX function.
 * 
 * @since 1.0.0
 * @param {object} data | Data.
 * @param {function} onSuccess | Success Function.
 * @param {function} onError | Error Function.
 */
flexifyHelper.ajaxRequest = async function (data, onSuccess, onError) {
  await new Promise((resolve, reject) => {
    var request = new XMLHttpRequest();

    request.open('POST', wc_checkout_params.ajax_url);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    request.onload = function() {
      if (this.status >= 200 && this.status < 400) {
        // Success.
        resolve(this.response);
      } else {
        // Error.
        if (typeof onError === 'function') {
          reject('error');
        }
      }
    };

    request.onerror = function() {
      // Error.
      if (typeof onError === 'function') {
        reject('error');
      }
    };

    request.send(flexifyHelper.serializeData(data));
  }).then(response => {
    onSuccess(response);
  }).catch(error => {
    if (typeof onError === 'function') {
      onError();
    } else {
      console.log(error);
    }
  });
};

flexifyHelper.ajaxRequestWoo = async function (data, onSuccess, onError) {
  await new Promise((resolve, reject) => {
    var request = new XMLHttpRequest();
    var url = wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', data.action);

    // @todo: we get deprecation warnings when passing false, 
    // we need to transpile the whole thing into ESNext and use async await.
    request.open('POST', url);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    request.onload = function() {
      if (this.status >= 200 && this.status < 400) {
        // Success.
        resolve(this.response);
      } else {
        // Error.
        if (typeof onError === 'function') {
          reject('error');
        }
      }
    };
    request.onerror = function() {
      // Error.
      if (typeof onError === 'function') {
        reject('error');
      }
    };
    request.send(flexifyHelper.serializeData(data));
  }).then(response => {
    onSuccess(response);
  }).catch(error => {
    onError(error);
  });
};

/**
 * Clean up the DOM instead of theme file override.
 */
flexifyHelper.removeDomElements = function() {
  var loginToggle = document.querySelector('.woocommerce-form-login-toggle');
  var shopKeeperLogin = document.querySelector('.shopkeeper_checkout_login');

  if (loginToggle) {
    loginToggle.remove();
  }

  if (shopKeeperLogin) {
    shopKeeperLogin.remove();
  }

  flexifyHelper.repositionNotices();
};

/**
 * Reposition Notices.
 * 
 * For some reason Woo does not always put the notices inside the wrapper, which breaks the layout. This fixes that.
 */
flexifyHelper.repositionNotices = function() {
  var isModern = document.querySelectorAll('.flexify-checkout--modern').length;
  var formNotice = document.querySelector('form.woocommerce-checkout > .woocommerce-NoticeGroup.woocommerce-NoticeGroup-checkout');
  var noticeWrapper = document.querySelector('.woocommerce-notices-wrapper');
  
  if (isModern && formNotice) {
    var error = formNotice.querySelector('.woocommerce-error');

    if (!error) {
      return;
    }

    var errorContainer = document.querySelector('.woocommerce > .woocommerce-notices-wrapper');
    errorContainer.append(error);
    formNotice.remove();
  }

  if (isModern && noticeWrapper) {
    jQuery('.woocommerce-notices-wrapper').prependTo('.flexify-checkout__steps');
  }
};

flexifyHelper.isModernCheckout = function() {
  return document.querySelectorAll('.flexify-checkout--modern').length;
};

flexifyHelper.isMobile = function() {
  const width = jQuery(window).width();
  return width < 1024;
};

flexifyHelper.debounce = function (func) {
  var _this = this;
  let timeout = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 300;
  let timer;

  return function() {
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    clearTimeout(timer);
    timer = setTimeout(() => {
      func.apply(_this, args);
    }, timeout);
  };
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (flexifyHelper);

/***/ }),

/***/ "./source/frontend/js/intlPhone.js":
/*!*****************************************!*\
  !*** ./source/frontend/js/intlPhone.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _helper__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./helper */ "./source/frontend/js/helper.js");

var flexifyIntlPhone = {
  iti_open: false,
  init: function() {
    jQuery( function() {
      if (typeof intlTelInputGlobals === 'undefined') {
        return;
      }

      flexifyIntlPhone.initialiseFields();

      /**
       * We need to handle click outside ourself because, otherwise
       * it takes 2 clicks outside to close the country dropdown.
       */
      if (document.querySelector('.iti__selected-flag')) {
        document.querySelector('.iti__selected-flag').removeEventListener('click', intlTelInputGlobals.instances[0]._handleClickSelectedFlag, {}, false);
      }

      flexifyIntlPhone.toggleDropdown();
      flexifyIntlPhone.handleClickOutside();
      jQuery(document.body).on('flexify_step_change', flexifyIntlPhone.onStepChange);
    });
  },
  initialiseFields: function() {
    jQuery('.flexify-intl-phone input[type=tel], .flexify-intl-phone input[type=text]').each( function() {
      var args = {
        'hiddenInput': jQuery(this).attr('name') + '_full_number',
        'onlyCountries': flexify_checkout_vars.allowed_countries,
        'preferredCountries': [],
        'nationalMode': true,
        'autoPlaceholder': 'polite',
        'utilsScript': flexify_checkout_vars.intl_util_path,
        'separateDialCode': true
      };

      if (flexify_checkout_vars?.base_country) {
        args.initialCountry = flexify_checkout_vars?.base_country;
        args.preferredCountries = [flexify_checkout_vars?.base_country];
      }

      /**
       * Filter to modify arguments before they are passed to intlTelInput function.
       * 
       * Full list of arguments can be found here: https://github.com/jackocnr/intl-tel-input#initialisation-options
       */
      args = wp.hooks.applyFilters('flexify_checkout_intl_phone_args', args);
      jQuery('.flexify-intl-phone input[type=tel], .flexify-intl-phone input[type=text]').intlTelInput(args);

      // Update the hidden field when field is changed.
      jQuery(this).on('countrychange', flexifyIntlPhone.updateHiddenField);
      jQuery(this).on('change', flexifyIntlPhone.updateHiddenField);

      // Validation.
      jQuery(this).on('blur', flexifyIntlPhone.mark_changed);
      jQuery(this).on('blur validate flexify_validate keyup', flexifyIntlPhone.validate);
      jQuery(this).closest('.form-row').addClass('flexify-intl-phone--init');
      // Disable wc_checkout_form.validate_field() event listener on input event.
      jQuery("form.checkout").off("input", "**");
    });
  },
  /**
   * Update hidden field.
   */
  updateHiddenField: function() {
    var hidden_name = jQuery(this).attr('name') + '_full_number';
    var $hidden_field = jQuery(`[name=${hidden_name}]`);

    if ($hidden_field.length) {
      $hidden_field.val(jQuery(this).intlTelInput("getNumber"));
    }
  },
  /**
   * Update hidden field on step change.
   */
  onStepChange: function() {
    jQuery('.flexify-intl-phone input[type=tel], .flexify-intl-phone input[type=text]').each( function() {
      flexifyIntlPhone.updateHiddenField.apply(this);
    });
  },
  /**
   * Manually toggle dropdown opening and closing since it doesn't automatically work perfectly.
   *
   * WooCommerce stops the event propogation when clicked within `.woocommerce-input-wrapper`.
   * This interfere's with the default behaviour of intlTelInput.
   * 
   * Read: https://github.com/woocommerce/woocommerce/issues/22720
   */
  toggleDropdown: function() {
    jQuery('.iti__selected-flag').click( function() {
      var instances = intlTelInputGlobals.instances;

      if (instances && instances.length > 0) {
        var instance = instances[0];

        if (instance._isOpen()) {
          instance._closeDropdown();
        } else {
          instance._openDropdown();
        }
      }
    });
  },
  /**
   * Close dropdown when clicked outside.
   */
  handleClickOutside: function() {
    document.addEventListener('click', function(e) {
      var instances = intlTelInputGlobals.instances;

      if (instances && instances.length > 0) {
        var instance = instances[0];

        if (instances && !e.target.closest('.iti__selected-flag')) {
          instance._closeDropdown();
        }
      }
    });
  },
  /**
   * Validate the phone field.
   * @returns 
   */
  validate: function() {
    var val = jQuery(this).val().trim(),
    $parent = jQuery(this).closest('.form-row');

    if (!val && $parent.hasClass('woocommerce-invalid-required-field')) {
      return;
    }

    var isValid = intlTelInputGlobals.instances[0].isValidNumber();
    
    if (!$parent.hasClass('has-changed') && val.length < 4) {
      $parent.removeClass('woocommerce-validated woocommerce-invalid');
      return;
    }

    if (!isValid) {
      $parent.removeClass('woocommerce-validated').addClass('woocommerce-invalid woocommerce-invalid-phone');
      $parent.find('.error').text(flexify_checkout_vars.i18n.phone.invalid);
    } else {
      $parent.removeClass('woocommerce-invalid woocommerce-invalid-phone');
    }
  },
  /**
   * Add class `has-changed` if the value has changed.
   */
  mark_changed: function() {
    if (jQuery(this).val()) {
      jQuery(this).closest('.form-row').addClass('has-changed');
    }
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (flexifyIntlPhone);

/***/ }),

/***/ "./source/frontend/js/localStorage.js":
/*!********************************************!*\
  !*** ./source/frontend/js/localStorage.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * Save all field's data in localStorage. Load it when page is loaded.
 */
const flexifyLocalStorage = {
  init: function() {
    flexifyLocalStorage.load_data();
    flexifyLocalStorage.watch_data_change();
  },
  /**
   * Watch data change and save in localStorage as JSON.
   */
  watch_data_change: function() {
    const inputs = document.querySelectorAll('form.checkout input, form.checkout textarea, form.checkout select');

    Array.from(inputs).forEach(input => {
      input.addEventListener('change', e => {
        const form = input.closest('form');

        if (!form) {
          return;
        }

        const form_data = flexifyLocalStorage.formSerialize(form);
        const json = JSON.stringify(form_data);

        localStorage.setItem('flexify_checkout_form_data', json);
      });
    });
  },
  /**
   * Load data from localStorage and populate fields.
   */
  load_data: function() {
    const json = localStorage.getItem('flexify_checkout_form_data');
    const form = document.querySelector('form.checkout');

    if (!json || !form) {
      return;
    }

    const single_checkbox = ['order_notes_switch', 'show_shipping'];
    const data = JSON.parse(json);

    if ('object' !== typeof data) {
      return;
    }

    data.forEach(fieldData => {
      const field = form.querySelector('[name="' + window.CSS.escape(fieldData.name) + '"]');

      if (!field) {
        return;
      }

      if (flexify_checkout_vars.localstorage_fields.includes(fieldData.name) && fieldData.value && '' == field.value) {
        field.value = fieldData.value;
        field.dispatchEvent(new Event('change'));
      }

      if (single_checkbox.includes(fieldData.name) && 'on' === fieldData.value) {
        field.setAttribute('checked', true);
        field.dispatchEvent(new Event('change'));
      }
    });
  },
  formSerialize: function (formElement) {
    const values = [];
    const inputs = formElement.elements;
    for (let i = 0; i < inputs.length; i++) {
      values.push({
        name: inputs[i].name,
        value: inputs[i].value
      });
    }
    return values;
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (flexifyLocalStorage);

/***/ }),

/***/ "./source/frontend/js/loginButtons.js":
/*!********************************************!*\
  !*** ./source/frontend/js/loginButtons.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var magnific_popup__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! magnific-popup */ "./node_modules/magnific-popup/dist/jquery.magnific-popup.js");
/* harmony import */ var magnific_popup__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(magnific_popup__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _loginForm__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./loginForm */ "./source/frontend/js/loginForm.js");

var flexifyLoginButtons = {};

/**
 * Run.
 */
flexifyLoginButtons.init = function() {
  this.onClick();

  /**
   * If auto-open class is present in the login for i.e. user has entered a wrong password,
   * Then open the login form automatically.
   */
  if (jQuery('.woocommerce-form-login').hasClass('woocommerce-form-login--auto-open')) {
    window.setTimeout( function() {
      flexifyLoginButtons.openPopup();
    }, 1000);
  }
};

/**
 * Login Buttons On Click.
 * 
 * Handle the show and hide of the login form from a custom button.
 */
flexifyLoginButtons.onClick = function() {
  // Remove the event listener added by WooCommerce, as it returns false,
  // causing our event listener to never run.
  setTimeout(() => {
    jQuery(document.body).off('click', 'a.showlogin');
  }, 100);
  jQuery(document).on('click', '[data-login], .showlogin', function(e) {
    e.preventDefault();
    flexifyLoginButtons.openPopup();
  });
};

/**
 * Open popup.
 */
flexifyLoginButtons.openPopup = function(auto_popup) {
  var billing_email = jQuery('#billing_email').val();

  if (billing_email) {
    jQuery('.woocommerce-form-login #username').val(billing_email).trigger('change');
  }

  if (auto_popup) {
    _loginForm__WEBPACK_IMPORTED_MODULE_1__["default"].showGlobalNotice(flexify_checkout_vars.i18n.account_exists, 'info');
  }

  window.setTimeout( function() {
    jQuery('.woocommerce-form-login #password').focus().trigger('focus');
  }, 300);
  
  jQuery.magnificPopup.open({
    items: {
      src: '.woocommerce-form-login',
      type: 'inline',
    }
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (flexifyLoginButtons);

/***/ }),

/***/ "./source/frontend/js/loginForm.js":
/*!*****************************************!*\
  !*** ./source/frontend/js/loginForm.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
var flexifyLoginForm = {
  /**
   * Elements.
   */
  els: {
    $form: jQuery('.woocommerce-form-login')
  },
  /**
   * Init.
   */
  init: function() {
    jQuery(document).ready( function() {
      jQuery('.woocommerce-form-login>h2:first').append('<div class="flexify-login-notice"></div>');
      jQuery('.woocommerce-form-login').on('submit', flexifyLoginForm.onSubmit);
    });
  },
  /**
   * Handle submit event.
   *
   * @param {obj} e event. 
   */
  onSubmit: function (e) {
    e.preventDefault();
    var data = {
      action: 'flexify_login',
      username: flexifyLoginForm.els.$form.find('#username').val(),
      password: flexifyLoginForm.els.$form.find('#password').val(),
      remember: flexifyLoginForm.els.$form.find('#rememberme').val(),
      _wpnonce: flexifyLoginForm.els.$form.find('#woocommerce-login-nonce').val()
    };
    
    flexifyLoginForm.block();

    jQuery.post(flexify_checkout_vars.ajax_url, data).done( function(data) {
      if (data.success) {
        flexifyLoginForm.showGlobalNotice(flexify_checkout_vars.i18n.login_successful, 'success');
        window.location.reload();
      } else {
        flexifyLoginForm.showGlobalNotice(data.data.error, 'error');
      }
    }).fail( function() {
      flexifyLoginForm.showGlobalNotice(flexify_checkout_vars.i18n.error, 'error');
    }).always( function() {
      flexifyLoginForm.unblock();
    });
  },
  /**
   * Show global notice for the login form.
   *
   * @param {string} msg  The message to display.
   * @param {string} type 'error' or 'success'.
   */
  showGlobalNotice: function (msg, type) {
    if (!type) {
      type = 'error';
    }

    var $notice = jQuery('.flexify-login-notice');
    var typeClass = `flexify-login-notice--${type}`;
    
    $notice.removeClass('flexify-login-notice--success flexify-login-notice--error flexify-login-notice--info');
    $notice.addClass(typeClass);
    $notice.html(msg);
  },
  /**
   * Block spinner.
   */
  block: function() {
    flexifyLoginForm.els.$form.block({
      message: null,
      overlayCSS: {
        background: '#fff',
        opacity: 0.6
      }
    });
  },
  /**
   * Unblock spinner.
   */
  unblock: function() {
    flexifyLoginForm.els.$form.unblock();
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (flexifyLoginForm);

/***/ }),

/***/ "./source/frontend/js/orderpay.js":
/*!****************************************!*\
  !*** ./source/frontend/js/orderpay.js ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
let flexifyOrderPay = {
  init: function() {
    jQuery(document).ready( function() {
      jQuery('body.woocommerce-order-pay #place_order').each( function() {
        var text = jQuery(this).data('text');

        if (text) {
          jQuery(this).html(text);
        }
      });
    });
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (flexifyOrderPay);

/***/ }),

/***/ "./source/frontend/js/stepper.js":
/*!***************************************!*\
  !*** ./source/frontend/js/stepper.js ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _validation__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./validation */ "./source/frontend/js/validation.js");
/* harmony import */ var _helper__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./helper */ "./source/frontend/js/helper.js");

var flexifyStepper = {
  steps_hash: flexify_checkout_vars.steps
};

/**
 * Run.
 */
flexifyStepper.init = function() {
  this.handeStepOnPageLoad();
  this.onNextClick();
  this.onStepperClick();
  window.addEventListener('hashchange', flexifyStepper.onHashChange);
};
flexifyStepper.handeStepOnPageLoad = function() {
  if (!window.location.hash) {
    window.location.hash = this.steps_hash[1];
    return;
  }
  flexifyStepper.onHashChange();
};

/**
 * Change input password visibility
 * 
 * @since 1.9.0
 */
jQuery(document).ready( function($) {
  $('.toggle-password-visibility .toggle').on('click', function() {
    var inputLoginPass = $('.flexify-login-password');
    var showPasswordIcon = $('.toggle-password-visibility .show-password');
    var hidePasswordIcon = $('.toggle-password-visibility .hide-password');

    if (inputLoginPass.attr('type') === 'password') {
      inputLoginPass.attr('type', 'text');
      showPasswordIcon.hide();
      hidePasswordIcon.show();
    } else {
      inputLoginPass.attr('type', 'password');
      showPasswordIcon.show();
      hidePasswordIcon.hide();
    }
  });
});

/**
 * Validation on Step change.
 * 
 * By default Woo does not provide inline validation messages. 
 * We use AJAX to get the correct message and then trigger Woo validation.
 */
flexifyStepper.onNextClick = function() {
  var steps = document.querySelectorAll('[data-step-next]');

  Array.from(steps).forEach( function (step) {
    step.addEventListener('click', async function (e) {
      e.preventDefault();
      flexifyStepper.loadSpinner(_helper__WEBPACK_IMPORTED_MODULE_1__["default"].isModernCheckout());
      _validation__WEBPACK_IMPORTED_MODULE_0__["default"].clearErrorMessages();

      var fields = flexifyStepper.getFields(step.closest('[data-step]'));
      var errorFields = await _validation__WEBPACK_IMPORTED_MODULE_0__["default"].checkFieldsForErrors(fields);

      if (errorFields) {
        return false;
      }

      var nextStepNumber = step.attributes['data-step-show'].value;
      var nextStep = document.querySelector('[data-step="' + nextStepNumber + '"]'); // ES5 Support.

      if (!nextStep) {
        return false;
      }

      // Only change the hash. Panels will be toggled by hashchange vent listener.
      window.location.hash = '#' + flexifyStepper.steps_hash[nextStepNumber];

      // Woo trigger select2 reload.
      jQuery(document.body).trigger('country_to_state_changed');

      return false;
    });
  });
};

flexifyStepper.onStepperClick = function() {
  var steppers = document.querySelectorAll('[data-stepper]');

  Array.from(steppers).forEach( function (stepper) {
    stepper.addEventListener('click', async function (e) {
      e.preventDefault();
      _validation__WEBPACK_IMPORTED_MODULE_0__["default"].clearErrorMessages();
      var hasErrors = false;
      var stepNumber = stepper.attributes['data-stepper'].value;
      var isActive = stepper.closest('[data-stepper-li]').classList.contains('selected');

      if (isActive) {
        return false;
      }

      // Check current step fields.
      if (stepNumber > 1) {
        // var fields = flexifyStepper.getFields( document.querySelector( `[data-step="${ stepNumber - 1}"]` ) ); 
        var fields = flexifyStepper.getFields(document.querySelector('[data-step="' + (stepNumber - 1) + '"]')); // ES5 Support.
        hasErrors = await _validation__WEBPACK_IMPORTED_MODULE_0__["default"].checkFieldsForErrors(fields);
      }

      if (hasErrors) {
        return false;
      }

      // Only change the hash. Panels will be toggled by hashchange event listener.
      window.location.hash = '#' + flexifyStepper.steps_hash[stepNumber];

      // Woo trigger select2 reload.
      jQuery(document.body).trigger('country_to_state_changed');

      return false;
    });
  });
};

/**
 * Disable Next Steppers.
 * 
 * @param {int} stepNumber The Step Number.
 */
flexifyStepper.disableNextSteppers = function (stepNumber) {
  Array.from(document.querySelectorAll('[data-stepper-li]')).forEach( function (stepper) {
    if (stepNumber === stepper.attributes['data-stepper-li'].value) {
      stepper.classList.remove('complete');
    }
    if (stepNumber >= stepper.attributes['data-stepper-li'].value) {
      return;
    }
    stepper.classList.add('disabled');
    stepper.classList.remove('complete');
    stepper.querySelector('[data-stepper]').setAttribute('disabled', 'disabled');
    stepper.querySelector('[data-stepper]').setAttribute('aria-disabled', 'true');
  });
};

/**
 * Complete Previous Steps.
 * 
 * @param {int} stepNumber The Step Number.
 */
flexifyStepper.completePreviousSteppers = function (stepNumber) {
  Array.from(document.querySelectorAll('[data-stepper-li]')).forEach( function (stepper) {
    if (stepper.attributes['data-stepper-li'].value >= stepNumber) {
      return;
    }
    stepper.classList.add('complete');
  });
};

/**
 * Disable Next Steppers.
 * 
 * @param {int} currentStepNumber The current step number.
 * @param {int} nextStepNumber The next step number.
 */
flexifyStepper.switchStepper = function (currentStepNumber, nextStepNumber) {
  // Steppers.
  // var currentStepper = document.querySelector( `[data-stepper-li="${currentStepNumber}"]` );
  var currentStepper = document.querySelector('[data-stepper-li="' + currentStepNumber + '"]'); // ES5 Support
  // var nextStepper = document.querySelector( `[data-stepper-li="${nextStepNumber}"]` );
  var nextStepper = document.querySelector('[data-stepper-li="' + nextStepNumber + '"]'); // ES5 Support.

  // Handle Steppers.
  currentStepper.classList.remove('error');
  currentStepper.classList.remove('disabled');
  currentStepper.querySelector('button').removeAttribute('disabled');
  currentStepper.querySelector('button').removeAttribute('aria-disabled');
  currentStepper.classList.remove('selected');
  nextStepper.classList.remove('error');
  nextStepper.classList.remove('disabled');
  nextStepper.querySelector('button').removeAttribute('disabled');
  nextStepper.querySelector('button').removeAttribute('aria-disabled');
  nextStepper.classList.add('selected');
  flexifyStepper.completePreviousSteppers(nextStepNumber);
};

/**
 * Switch Panels
 * 
 * @param {int} currentStepNumber The current step number.
 * @param {int} nextStepNumber The next step number.
 */
flexifyStepper.switchPanels = function (currentStepNumber, nextStepNumber) {
  var currentStep = document.querySelector('[data-step="' + currentStepNumber + '"]');
  var nextStep = document.querySelector('[data-step="' + nextStepNumber + '"]');
  currentStep.style.display = 'none';
  currentStep.setAttribute('aria-hidden', 'true');
  nextStep.style.display = '';
  nextStep.setAttribute('aria-hidden', 'false');
  window.scrollTo(0, 0);
};

/**
 * Get Fields.
 * 
 * Get all the fields that are relevant to the current step.
 * 
 * @param {element} parent Parent Element.
 */
flexifyStepper.getFields = function (parent) {
  var allFields = parent.querySelectorAll('input, select, textarea');
  var accountFields = parent.querySelectorAll('.create-account input, .create-account select, .create-account textarea');
  var shippingFields = parent.querySelectorAll('.woocommerce-shipping-fields input, .woocommerce-shipping-fields select, .woocommerce-shipping-fields textarea');
  var additionalFields = parent.querySelectorAll('.woocommerce-additional-fields input, .woocommerce-additional-fields select, .woocommerce-additional-fields textarea');
  var fields = [];

  Array.from(allFields).forEach( function (field) {
    if (!parent.querySelectorAll('input[name=createaccount]:checked').length && !parent.querySelectorAll('.create-account[style="display:block;"]').length && Array.from(accountFields).includes(field)) {
      return;
    }
    if (!parent.querySelectorAll('input[name=ship_to_different_address]:checked').length && Array.from(shippingFields).includes(field)) {
      return;
    }
    if (!parent.querySelectorAll('input[name=show_additional_fields]:checked').length && Array.from(additionalFields).includes(field)) {
      return;
    }

    // Dont validate this fields.
    if ('billing_phone_full_number' === field.name) {
      return;
    }

    fields.push(field);
  });

  return fields;
};

flexifyStepper.onHashChange = async function (e) {
  if (!window.location.hash) {
    return;
  }

  var hash, parts, step, scroll_element, goingForward;

  hash = window.location.hash.replace('#', '');
  goingForward = flexifyStepper.isHashGoingForward(e);

  if (hash.includes("|")) {
    parts = hash.split("|");
    step = parts[0];
    scroll_element = parts[1];
  } else {
    step = hash;
  }

  var nextStepper = document.querySelector('[data-hash="' + step + '"]');

  if (!nextStepper) {
    return;
  }

  var nextStepNumber = nextStepper.attributes['data-stepper'].value;
  var stepper = document.querySelector('.flexify-stepper__step.selected .flexify-stepper__button');
  var currentStepNumber = stepper.attributes['data-stepper'].value;
  var stepNumber = stepper.attributes['data-stepper'].value;
  var isActive = nextStepNumber === currentStepNumber;

  if (goingForward) {
    _validation__WEBPACK_IMPORTED_MODULE_0__["default"].clearErrorMessages();
  }

  if (isActive) {
    flexifyStepper.scrollToElement(scroll_element);

    return false;
  }

  flexifyStepper.switchPanels(stepNumber, nextStepNumber);
  flexifyStepper.switchStepper(stepNumber, nextStepNumber);
  flexifyStepper.scrollToElement(scroll_element);

  // Woo trigger select2 reload.
  jQuery(document.body).trigger('country_to_state_changed');

  // Trigger custom event.
  jQuery(document.body).trigger('flexify_step_change');

  if (document.getElementById("billing_phone")) {
    document.getElementById("billing_phone").dispatchEvent(new Event('keyup'));
  }
};

/**
 * Load the Spinner.
 *
 * @param {bool} buttonSpinner If true, then it will show a small spinner within the navgation buttons.
 */
flexifyStepper.loadSpinner = function(buttonSpinner) {
  jQuery('[data-step-next]').prop('disabled', true);

  if (buttonSpinner) {
    jQuery('[data-step-next]').addClass('flexify-button--processing');
  } else {
    document.querySelector('.flexify-checkout__spinner').style.display = 'block';
  }
};

/**
 * Remove the Spinner.
 */
flexifyStepper.removeSpinner = function(buttonSpinner) {
  jQuery('[data-step-next]').prop('disabled', false).removeClass('flexify-button--processing');
  document.querySelector('.flexify-checkout__spinner').style.display = 'none';
};

flexifyStepper.scrollToElement = function(scroll_element) {
  if (scroll_element && jQuery(`#${scroll_element}`).length) {
    jQuery('html, body').animate({
      scrollTop: jQuery(`#${scroll_element}`).offset().top - 60
    }, 'fast');
  }
};

flexifyStepper.updateCustomFragments = function(fragments) {
  for (var selector in fragments) {
    if (jQuery(selector).length) {
      jQuery(selector).replaceWith(fragments[selector]);
    }
  }
};

/**
 * Should be called on hashchange event. It tells if we are navigating to the
 * next step by returning true.
 *
 * @param Event e Event Object.
 * @returns 
 */
flexifyStepper.isHashGoingForward = function(e) {
  if (!e) {
    return false;
  }

  var newUrl = new URL(e.newURL);
  var oldUrl = new URL(e.oldURL);
  var newHashIndex = flexifyStepper.findHashIndex(newUrl.hash);
  var oldHashIndex = flexifyStepper.findHashIndex(oldUrl.hash);

  return parseInt(newHashIndex) > parseInt(oldHashIndex);
};

/**
 * Return index of the provided step slug.
 * @param {*} step_slug 
 * @returns 
 */
flexifyStepper.findHashIndex = function (step_slug) {
  step_slug = step_slug.replace('#', '');

  for (var idx in this.steps_hash) {
    if (this.steps_hash[idx] === step_slug) {
      return idx;
    }
  }

  return false;
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (flexifyStepper);

/***/ }),

/***/ "./source/frontend/js/ui.js":
/*!**********************************!*\
  !*** ./source/frontend/js/ui.js ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
var flexifyUI = {};

/**
 * Slide down with JQuery and JS.
 * @param {object} element Element.
 */
flexifyUI.slideDown = function (element) {
  if ('block' === element.style.display) {
    return;
  }

  element.style.height = 0;
  element.classList.add('slide-down');
  element.style.display = 'block';
  // element.style.height = `${element.scrollHeight}px`;
  element.style.height = element.scrollHeight + 'px'; // ES5 Support.

  setTimeout( function() {
    element.classList.remove('slide-down');
    element.style.height = '';
  }, 500);
};

/**
 * Slide up with JQuery and JS.
 * @param {object} element Element.
 */
flexifyUI.slideUp = function (element) {
  if ('none' === element.style.display) {
    return;
  }

  // element.style.height = `${element.scrollHeight}px`;
  element.style.height = element.scrollHeight + 'px'; // ES5 Support.
  element.classList.add('slide-up');
  setTimeout( function() {
    element.style.height = 0;
  }, 10);
  setTimeout( function() {
    element.style.display = 'none';
    element.classList.remove('slide-up');
    element.style.height = '';
  }, 500);
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (flexifyUI);

/***/ }),

/***/ "./source/frontend/js/validation.js":
/*!******************************************!*\
  !*** ./source/frontend/js/validation.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _helper__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./helper */ "./source/frontend/js/helper.js");
/* harmony import */ var _stepper__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./stepper */ "./source/frontend/js/stepper.js");
/* harmony import */ var _loginButtons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./loginButtons */ "./source/frontend/js/loginButtons.js");

var flexifyValidation = {};

/**
 * Run.
 */
flexifyValidation.init = function() {
  this.onChange();
  jQuery(document.body).on('checkout_error', flexifyValidation.onCheckoutError);

  jQuery( function() {
    // Offer to login if a user a user already exits with the matching email.
    if (jQuery('#billing_email').val() && flexifyValidation.isValidEmail(jQuery('#billing_email').val())) {
      flexifyValidation.checkFieldForErrors(document.getElementById('billing_email'));
    }
  });
};

/**
 * Validation on Change.
 * 
 * By default Woo does not provide inline validation messages. 
 * We use AJAX to get the correct message and then trigger Woo validation.
 */
flexifyValidation.onChange = function() {
  var fields = document.querySelectorAll('input, select, textarea');

  Array.from(fields).forEach( function(field) {
    field.addEventListener('change', async function (e) {
      e.preventDefault();
      await flexifyValidation.checkFieldForErrors(field);
      return false;
    });
  });
};

/**
 * Check Field for Errors.
 *
 * @param {array} fields Fields.
 * @returns bool
 */
flexifyValidation.checkFieldForErrors = async function (field) {
  var row = field.closest('.form-row');

  if (!row) {
    return false;
  }

  if (!row.attributes['data-label'] || !row.attributes['data-type']) {
    return false;
  }

  var value = _helper__WEBPACK_IMPORTED_MODULE_0__["default"].getFieldValue(field);
  var type = row.attributes['data-type'].value;
  var data = {
    action: 'flexify_check_for_inline_error',
    args: {
      label: row.attributes['data-label'].value,
      required: row.classList.contains('required'),
      type: type
    },
    country: document.getElementById('billing_country').value,
    key: field.attributes.name.value,
    value: value,
  };

  // Its too slow to trigger every field, so check the more advanced fields with ajax.
  if ('country' === type || 'postcode' === type || 'phone' === type || 'email' === type) {
    await _helper__WEBPACK_IMPORTED_MODULE_0__["default"].ajaxRequest(data, function (response) {
      var value = JSON.parse(response).data;
      var $row = jQuery(field).closest('.form-row');

      // Update the inline validation messages for the field.
      field.closest('.form-row').querySelector('.error').innerHTML = value.message;
      field.closest('.form-row').classList.remove('woocommerce-invalid');

      // Trigger Woo Validation.
      if (field.closest('.form-row').classList.contains('validate-required')) {
        jQuery(field).trigger('validate');
      }

      // If a custom message has been returned, mark the row as invalid.
      if (value.isCustom) {
        field.closest('.form-row').classList.add('woocommerce-invalid');
      }

      if ('dont_offer' !== flexify_checkout_vars.allow_login_existing_user) {
        if ('info' === value.messageType) {
          if (!$row.find('.info').length) {
            $row.append('<span class="info" style="display:none"></span>');
          }

          let $span = $row.find('.info');
          $span.slideDown();
          $span.html(value.message);

          if ('inline_popup' === flexify_checkout_vars.allow_login_existing_user) {
            _loginButtons__WEBPACK_IMPORTED_MODULE_2__["default"].openPopup(true);
          }
        } else {
          let $span = $row.find('.info');
          $span.slideUp();
        }
      }
    });
  } else {
    // Trigger Woo Validation.
    if (field.closest('.form-row').classList.contains('validate-required')) {
      jQuery(field).trigger('validate');
    }
  }

  var hasError = field.closest('.form-row').classList.contains('woocommerce-invalid');

  if (hasError) {
    _stepper__WEBPACK_IMPORTED_MODULE_1__["default"].disableNextSteppers(field.closest('[data-step]').attributes['data-step'].value);
  }

  flexifyValidation.accessibleErrors();

  return hasError;
};

/**
 * Check Fields for Errors.
 *
 * @param {array} fields Fields.
 * @returns bool
 */
flexifyValidation.checkFieldsForErrors = async function(fields) {
  var inputs = {};
  var errorFields = [];

  // Return true is google address auto-complete field is present and empty.
  for (var field of fields) {
    if ('billing_address_search' === field.id) {
      if ('' === field.value.trim() && 'none' === jQuery('.woocommerce-billing-fields').css('display')) {
        field.closest('.form-row').classList.add('woocommerce-invalid');
        jQuery(field).trigger('validate');
        field.closest('.form-row').classList.add('woocommerce-invalid');
        _stepper__WEBPACK_IMPORTED_MODULE_1__["default"].removeSpinner();
        return true;
      }
    }
  }
  _stepper__WEBPACK_IMPORTED_MODULE_1__["default"].loadSpinner(true);
  // Get all the data so we can do an inline validation.
  Array.from(fields).forEach( function (field) {
    var row = field.closest('.form-row');

    if (!row) {
      return;
    }

    if (!row.attributes['data-label'] || !row.attributes['data-type']) {
      return;
    }

    var value = _helper__WEBPACK_IMPORTED_MODULE_0__["default"].getFieldValue(field);

    inputs[field.attributes.name.value] = {
      args: {
        label: row.attributes['data-label'].value,
        required: row.classList.contains('required'),
        type: row.attributes['data-type'].value
      },
      country: document.getElementById('billing_country').value,
      key: field.attributes.name.value,
      value: value
    };
  });
  var data = {
    action: 'flexify_check_for_inline_errors',
    fields: inputs,
    'email': jQuery('#billing_email').val()
  };
  await _helper__WEBPACK_IMPORTED_MODULE_0__["default"].ajaxRequest(data, function (response) {
    var messages = JSON.parse(response).data;

    // Update the inline validation messages for each field.
    Object.entries(messages).forEach( function (object) {
      var key = object[0];
      var value = object[1];
      var field = document.querySelector('[name="' + key + '"]');
      
      if (!field) {
        return;
      }

      // If this field is hidden by Conditinal Field of Checkout Fields Manager plugin
      // then skip validation for this field.
      if (flexifyValidation.isHiddenConditionalField(field)) {
        return;
      }
      field.closest('.form-row').querySelector('.error').innerHTML = value.message;
      field.closest('.form-row').classList.remove('woocommerce-invalid');

      // Trigger Woo Validation.
      if (field.closest('.form-row').classList.contains('validate-required')) {
        jQuery(field).trigger('validate');
        jQuery(field).trigger('flexify_validate');
      }

      // If a custom message has been returned, mark the row as invalid.
      if (value.isCustom) {
        field.closest('.form-row').classList.add('woocommerce-invalid');
      }
      if (field.closest('.form-row').classList.contains('woocommerce-invalid')) {
        errorFields.push(jQuery(field).attr('id'));
      }
    });
    _stepper__WEBPACK_IMPORTED_MODULE_1__["default"].updateCustomFragments(messages.fragments);
  });
  flexifyValidation.clearErrorMessages('data-flexify-error');

  // Check password strength if set.
  var passwords = fields[0].closest('[data-step]').querySelectorAll('#account_password');
  Array.from(passwords).forEach( function (password) {
    if (password.closest('.woocommerce-account-fields').querySelector('#createaccount') && !password.closest('.woocommerce-account-fields').querySelector('#createaccount').checked) {
      return;
    }

    if (!password.value) {
      return;
    }

    if (!password.closest('.form-row').querySelectorAll('.woocommerce-password-strength.good, .woocommerce-password-strength.strong').length) {
      hasErrors = true;
    }
  });

  if (errorFields.length) {
    var step = fields[0].closest('[data-step]').attributes['data-step'].value;
    // document.querySelector( `[data-stepper-li="${step}"]`).classList.add( 'error' );
    document.querySelector('[data-stepper-li="' + step + '"]').classList.add('error'); // ES5 Support.
    _stepper__WEBPACK_IMPORTED_MODULE_1__["default"].disableNextSteppers(step);
    flexifyValidation.scrollToError();
    flexifyValidation.validateSearchForm();
    flexifyValidation.maybeShowShippingForm(errorFields);
  }

  flexifyValidation.accessibleErrors();
  _stepper__WEBPACK_IMPORTED_MODULE_1__["default"].removeSpinner();
  return errorFields.length ? errorFields : false;
};

/**
 * Scroll to first error on page.
 */
flexifyValidation.scrollToError = function() {
  var error = document.querySelectorAll('.woocommerce-invalid')[0];
  if (!error) {
    return;
  }
  error.scrollIntoView({
    behavior: 'smooth'
  });
};

/**
 * Accessible Errors.
 * 
 * Add some accessibility classes to our errors to help those using accessibility tools.
 */
flexifyValidation.accessibleErrors = function() {
  var fields = document.querySelectorAll('input, select, textarea');

  Array.from(fields).forEach( function (field) {
    var row = field.closest('.form-row');

    if (!row) {
      return;
    }

    var error = row.querySelector('.error');

    if (error) {
      error.setAttribute('aria-hidden', 'true');
      error.setAttribute('aria-live', 'off');
    }

    if (row.classList.contains('woocommerce-invalid')) {
      field.setAttribute('aria-invalid', 'true');
      
      if (error) {
        error.setAttribute('aria-hidden', 'false');
        error.setAttribute('aria-live', 'polite');
      }
    }
  });
};

/**
 * Display Global Notice.
 * 
 * Render a global validation notice. Useful when an inline message is not possible.
 * 
 * @param {string} message Message.
 * @param {string} type Type.
 * @param {string} format Format.
 */
flexifyValidation.displayGlobalNotice = function (message, type, format, data) {
  // ES5 Support.
  if (!type) {
    type = 'error';
  }
  if (!format) {
    format = 'list';
  }
  var noticeArea = document.querySelectorAll('.woocommerce-notices-wrapper');
  if (!noticeArea) {
    return;
  }
  noticeArea = noticeArea[noticeArea.length - 1];
  flexifyValidation.clearErrorMessages('data-flexify-error');
  var noticeContainer = document.createElement('div');
  var noticeType = 'woocommerce-error';

  if ('error' !== type) {
    noticeType = 'woocommerce-message';
  }

  if ('info' === type) {
    noticeType = 'woocommerce-info';
  }

  if (typeof data === 'object' && !Array.isArray(data) && data !== null) {
    Object.entries(data).forEach( function (object) {
      var key = object[0];
      var value = object[1];
      noticeContainer.setAttribute(key, value);
    });
  }

  if ('list' === format) {
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
  noticeArea.append(noticeContainer);
};

/**
 * Clear error messages.
 * 
 * @param {string} Group Clear error messages only for this group. Group is the name of the data-* attribute. Example data-flexify-error.
 */
flexifyValidation.clearErrorMessages = function (group) {
  jQuery('.woocommerce-notices-wrapper > div, .woocommerce-notices-wrapper ul').each( function() {
    // if group is provided, only remove the notices beloging to this group.
    if (group) {
      if (jQuery(this).attr(group)) {
        jQuery(this).remove();
      }
    } else {
      jQuery(this).remove();
    }
  });
  jQuery('.woocommerce-NoticeGroup').remove();
};

/**
 * Validate Search Form.
 * 
 * If there is a search form error, display a message.
 */
flexifyValidation.validateSearchForm = function() {
  var addressSearches = document.querySelectorAll('#billing_address_search');
  Array.from(addressSearches).forEach( function (addressSearch) {
    var addressSection = addressSearch.closest('.woocommerce-billing-fields__wrapper').querySelector('.woocommerce-billing-fields');
    var style = window.getComputedStyle(addressSection);
    if (style.display === 'none') {
      // flexifyValidation.displayGlobalNotice( flexify_checkout_vars.i18n.errorAddressSearch ); // Do global notice.

      // Remove previous notices.
      Array.from(addressSearch.closest('.form-row').querySelectorAll('.error')).forEach( function (error) {
        error.remove();
      });

      // Do inline notice.
      var row = addressSearch.closest('.form-row');
      row.classList.add('woocommerce-invalid');
      var error = document.createElement('span');
      error.setAttribute('aria-hidden', 'false');
      error.setAttribute('aria-live', 'polite');
      error.classList.add('error');
      error.innerHTML = flexify_checkout_vars.i18n.errorAddressSearch;
      row.append(error);
    }
  });
};

/**
 * Display global errors on 'checkout_error' event.
 * 
 * @param {object} e Event.
 * @param {string} data Error message in HTML format.
 */
flexifyValidation.onCheckoutError = function (e, data) {
  /**
   * In modern checkout layout, we use CSS to hide the default error messages because that breaks the layout.
   * So we need to display our own.
   */
  if (_helper__WEBPACK_IMPORTED_MODULE_0__["default"].isModernCheckout() && data) {
    flexifyValidation.displayGlobalNotice(jQuery(data).html(), 'error');
  }
};

/**
 * Is valid email?
 * 
 * @param {string} email Email.
 * @returns bool
 */
flexifyValidation.isValidEmail = function (email) {
  var pattern = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
  return email.match(pattern);
};

/**
 * Is this field purposfully marked as hidden by Checkout fields manager plugin.
 *
 * @param {*} field 
 * @returns 
 */
flexifyValidation.isHiddenConditionalField = function (field) {
  var $row = jQuery(field).closest('.form-row');
  return $row.is(":hidden") && $row.hasClass("wooccm-conditional-child");
};

/**
 * Because of address autocomplete, the validation error on fields do not appear. 
 * Display shipping form (manual) if 'Ship to a different address' is checked and address autocomplete
 *
 * @param {*} errorFields 
 * @returns 
 */
flexifyValidation.maybeShowShippingForm = function (errorFields) {
  if (!jQuery("#ship-to-different-address-checkbox").is(":checked") || !errorFields) {
    return;
  }
  var showManualAddressFields = false;

  // If at least one of the fields is a shipping field.
  errorFields.forEach(field => {
    if (field.includes("shipping_")) {
      showManualAddressFields = true;
    }
  });
  if (showManualAddressFields) {
    jQuery(".shipping-address-search").slideUp();
    jQuery(".woocommerce-shipping-fields").slideDown();
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (flexifyValidation);

/***/ }),

/***/ "./node_modules/magnific-popup/dist/jquery.magnific-popup.js":
/*!*******************************************************************!*\
  !*** ./node_modules/magnific-popup/dist/jquery.magnific-popup.js ***!
  \*******************************************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_FACTORY__, __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;/*! Magnific Popup - v1.1.0 - 2016-02-20
* http://dimsemenov.com/plugins/magnific-popup/
* Copyright (c) 2016 Dmitry Semenov; */
;( function (factory) { 
if (true) { 
 // AMD. Register as an anonymous module. 
 !(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! jquery */ "jquery")], __WEBPACK_AMD_DEFINE_FACTORY__ = (factory),
		__WEBPACK_AMD_DEFINE_RESULT__ = (typeof __WEBPACK_AMD_DEFINE_FACTORY__ === 'function' ?
		(__WEBPACK_AMD_DEFINE_FACTORY__.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__)) : __WEBPACK_AMD_DEFINE_FACTORY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__)); 
 } else {} 
 }( function($) { 

/*>>core*/
/**
 * 
 * Magnific Popup Core JS file
 * 
 */


/**
 * Private static constants
 */
var CLOSE_EVENT = 'Close',
	BEFORE_CLOSE_EVENT = 'BeforeClose',
	AFTER_CLOSE_EVENT = 'AfterClose',
	BEFORE_APPEND_EVENT = 'BeforeAppend',
	MARKUP_PARSE_EVENT = 'MarkupParse',
	OPEN_EVENT = 'Open',
	CHANGE_EVENT = 'Change',
	NS = 'mfp',
	EVENT_NS = '.' + NS,
	READY_CLASS = 'mfp-ready',
	REMOVING_CLASS = 'mfp-removing',
	PREVENT_CLOSE_CLASS = 'mfp-prevent-close';


/**
 * Private vars 
 */
/*jshint -W079 */
var mfp, // As we have only one instance of MagnificPopup object, we define it locally to not to use 'this'
	MagnificPopup = function(){},
	_isJQ = !!(window.jQuery),
	_prevStatus,
	_window = $(window),
	_document,
	_prevContentType,
	_wrapClasses,
	_currPopupType;


/**
 * Private functions
 */
var _mfpOn = function(name, f) {
		mfp.ev.on(NS + name + EVENT_NS, f);
	},
	_getEl = function(className, appendTo, html, raw) {
		var el = document.createElement('div');
		el.className = 'mfp-'+className;
		if(html) {
			el.innerHTML = html;
		}
		if(!raw) {
			el = $(el);
			if(appendTo) {
				el.appendTo(appendTo);
			}
		} else if(appendTo) {
			appendTo.appendChild(el);
		}
		return el;
	},
	_mfpTrigger = function(e, data) {
		mfp.ev.triggerHandler(NS + e, data);

		if(mfp.st.callbacks) {
			// converts "mfpEventName" to "eventName" callback and triggers it if it's present
			e = e.charAt(0).toLowerCase() + e.slice(1);
			if(mfp.st.callbacks[e]) {
				mfp.st.callbacks[e].apply(mfp, $.isArray(data) ? data : [data]);
			}
		}
	},
	_getCloseBtn = function(type) {
		if(type !== _currPopupType || !mfp.currTemplate.closeBtn) {
			mfp.currTemplate.closeBtn = $( mfp.st.closeMarkup.replace('%title%', mfp.st.tClose ) );
			_currPopupType = type;
		}
		return mfp.currTemplate.closeBtn;
	},
	// Initialize Magnific Popup only when called at least once
	_checkInstance = function() {
		if(!$.magnificPopup.instance) {
			/*jshint -W020 */
			mfp = new MagnificPopup();
			mfp.init();
			$.magnificPopup.instance = mfp;
		}
	},
	// CSS transition detection, http://stackoverflow.com/questions/7264899/detect-css-transitions-using-javascript-and-without-modernizr
	supportsTransitions = function() {
		var s = document.createElement('p').style, // 's' for style. better to create an element if body yet to exist
			v = ['ms','O','Moz','Webkit']; // 'v' for vendor

		if( s['transition'] !== undefined ) {
			return true; 
		}
			
		while( v.length ) {
			if( v.pop() + 'Transition' in s ) {
				return true;
			}
		}
				
		return false;
	};



/**
 * Public functions
 */
MagnificPopup.prototype = {

	constructor: MagnificPopup,

	/**
	 * Initializes Magnific Popup plugin. 
	 * This function is triggered only once when $.fn.magnificPopup or $.magnificPopup is executed
	 */
	init: function() {
		var appVersion = navigator.appVersion;
		mfp.isLowIE = mfp.isIE8 = document.all && !document.addEventListener;
		mfp.isAndroid = (/android/gi).test(appVersion);
		mfp.isIOS = (/iphone|ipad|ipod/gi).test(appVersion);
		mfp.supportsTransition = supportsTransitions();

		// We disable fixed positioned lightbox on devices that don't handle it nicely.
		// If you know a better way of detecting this - let me know.
		mfp.probablyMobile = (mfp.isAndroid || mfp.isIOS || /(Opera Mini)|Kindle|webOS|BlackBerry|(Opera Mobi)|(Windows Phone)|IEMobile/i.test(navigator.userAgent) );
		_document = $(document);

		mfp.popupsCache = {};
	},

	/**
	 * Opens popup
	 * @param  data [description]
	 */
	open: function(data) {

		var i;

		if(data.isObj === false) { 
			// convert jQuery collection to array to avoid conflicts later
			mfp.items = data.items.toArray();

			mfp.index = 0;
			var items = data.items,
				item;
			for(i = 0; i < items.length; i++) {
				item = items[i];
				if(item.parsed) {
					item = item.el[0];
				}
				if(item === data.el[0]) {
					mfp.index = i;
					break;
				}
			}
		} else {
			mfp.items = $.isArray(data.items) ? data.items : [data.items];
			mfp.index = data.index || 0;
		}

		// if popup is already opened - we just update the content
		if(mfp.isOpen) {
			mfp.updateItemHTML();
			return;
		}
		
		mfp.types = []; 
		_wrapClasses = '';

		if(data.mainEl && data.mainEl.length) {
			mfp.ev = data.mainEl.eq(0);
		} else {
			mfp.ev = _document;
		}

		if(data.key) {
			if(!mfp.popupsCache[data.key]) {
				mfp.popupsCache[data.key] = {};
			}
      
			mfp.currTemplate = mfp.popupsCache[data.key];
		} else {
			mfp.currTemplate = {};
		}

		mfp.st = $.extend(true, {}, $.magnificPopup.defaults, data ); 
		mfp.fixedContentPos = mfp.st.fixedContentPos === 'auto' ? !mfp.probablyMobile : mfp.st.fixedContentPos;

		if(mfp.st.modal) {
			mfp.st.closeOnContentClick = false;
			mfp.st.closeOnBgClick = false;
			mfp.st.showCloseBtn = false;
			mfp.st.enableEscapeKey = false;
		}

		// Building markup
		// main containers are created only once
		if(!mfp.bgOverlay) {

			// Dark overlay
			mfp.bgOverlay = _getEl('bg').on('click'+EVENT_NS, function() {
				mfp.close();
			});

			mfp.wrap = _getEl('wrap').attr('tabindex', -1).on('click'+EVENT_NS, function(e) {
				if(mfp._checkIfClose(e.target)) {
					mfp.close();
				}
			});

			mfp.container = _getEl('container', mfp.wrap);
		}

		mfp.contentContainer = _getEl('content');

		if(mfp.st.preloader) {
			mfp.preloader = _getEl('preloader', mfp.container, mfp.st.tLoading);
		}

		// Initializing modules
		var modules = $.magnificPopup.modules;

		for(i = 0; i < modules.length; i++) {
			var n = modules[i];
			n = n.charAt(0).toUpperCase() + n.slice(1);
			mfp['init'+n].call(mfp);
		}

		_mfpTrigger('BeforeOpen');

		if(mfp.st.showCloseBtn) {
			// Close button
			if(!mfp.st.closeBtnInside) {
				mfp.wrap.append( _getCloseBtn() );
			} else {
				_mfpOn(MARKUP_PARSE_EVENT, function(e, template, values, item) {
					values.close_replaceWith = _getCloseBtn(item.type);
				});
				_wrapClasses += ' mfp-close-btn-in';
			}
		}

		if(mfp.st.alignTop) {
			_wrapClasses += ' mfp-align-top';
		}

	

		if(mfp.fixedContentPos) {
			mfp.wrap.css({
				overflow: mfp.st.overflowY,
				overflowX: 'hidden',
				overflowY: mfp.st.overflowY
			});
		} else {
			mfp.wrap.css({ 
				top: _window.scrollTop(),
				position: 'absolute'
			});
		}

		if( mfp.st.fixedBgPos === false || (mfp.st.fixedBgPos === 'auto' && !mfp.fixedContentPos) ) {
			mfp.bgOverlay.css({
				height: _document.height(),
				position: 'absolute'
			});
		}

		if(mfp.st.enableEscapeKey) {
			// Close on ESC key
			_document.on('keyup' + EVENT_NS, function(e) {
				if(e.keyCode === 27) {
					mfp.close();
				}
			});
		}

		_window.on('resize' + EVENT_NS, function() {
			mfp.updateSize();
		});


		if(!mfp.st.closeOnContentClick) {
			_wrapClasses += ' mfp-auto-cursor';
		}
		
		if(_wrapClasses)
			mfp.wrap.addClass(_wrapClasses);


		// this triggers recalculation of layout, so we get it once to not to trigger twice
		var windowHeight = mfp.wH = _window.height();

		
		var windowStyles = {};

		if( mfp.fixedContentPos ) {
        if(mfp._hasScrollBar(windowHeight)){
            var s = mfp._getScrollbarSize();
            if(s) {
                windowStyles.marginRight = s;
            }
        }
    }

		if(mfp.fixedContentPos) {
			if(!mfp.isIE7) {
				windowStyles.overflow = 'hidden';
			} else {
				// ie7 double-scroll bug
				$('body, html').css('overflow', 'hidden');
			}
		}
		
		var classesToadd = mfp.st.mainClass;

		if(mfp.isIE7) {
			classesToadd += ' mfp-ie7';
		}

		if(classesToadd) {
			mfp._addClassToMFP( classesToadd );
		}

		// add content
		mfp.updateItemHTML();

		_mfpTrigger('BuildControls');

		// remove scrollbar, add margin e.t.c
		$('html').css(windowStyles);
		
		// add everything to DOM
		mfp.bgOverlay.add(mfp.wrap).prependTo( mfp.st.prependTo || $(document.body) );

		// Save last focused element
		mfp._lastFocusedEl = document.activeElement;
		
		// Wait for next cycle to allow CSS transition
		setTimeout( function() {
			
			if(mfp.content) {
				mfp._addClassToMFP(READY_CLASS);
				mfp._setFocus();
			} else {
				// if content is not defined (not loaded e.t.c) we add class only for BG
				mfp.bgOverlay.addClass(READY_CLASS);
			}
			
			// Trap the focus in popup
			_document.on('focusin' + EVENT_NS, mfp._onFocusIn);

		}, 16);

		mfp.isOpen = true;
		mfp.updateSize(windowHeight);
		_mfpTrigger(OPEN_EVENT);

		return data;
	},

	/**
	 * Closes the popup
	 */
	close: function() {
		if(!mfp.isOpen) return;
		_mfpTrigger(BEFORE_CLOSE_EVENT);

		mfp.isOpen = false;
		// for CSS3 animation
		if(mfp.st.removalDelay && !mfp.isLowIE && mfp.supportsTransition )  {
			mfp._addClassToMFP(REMOVING_CLASS);
			setTimeout( function() {
				mfp._close();
			}, mfp.st.removalDelay);
		} else {
			mfp._close();
		}
	},

	/**
	 * Helper for close() function
	 */
	_close: function() {
		_mfpTrigger(CLOSE_EVENT);

		var classesToRemove = REMOVING_CLASS + ' ' + READY_CLASS + ' ';

		mfp.bgOverlay.detach();
		mfp.wrap.detach();
		mfp.container.empty();

		if(mfp.st.mainClass) {
			classesToRemove += mfp.st.mainClass + ' ';
		}

		mfp._removeClassFromMFP(classesToRemove);

		if(mfp.fixedContentPos) {
			var windowStyles = {marginRight: ''};
			if(mfp.isIE7) {
				$('body, html').css('overflow', '');
			} else {
				windowStyles.overflow = '';
			}
			$('html').css(windowStyles);
		}
		
		_document.off('keyup' + EVENT_NS + ' focusin' + EVENT_NS);
		mfp.ev.off(EVENT_NS);

		// clean up DOM elements that aren't removed
		mfp.wrap.attr('class', 'mfp-wrap').removeAttr('style');
		mfp.bgOverlay.attr('class', 'mfp-bg');
		mfp.container.attr('class', 'mfp-container');

		// remove close button from target element
		if(mfp.st.showCloseBtn &&
		(!mfp.st.closeBtnInside || mfp.currTemplate[mfp.currItem.type] === true)) {
			if(mfp.currTemplate.closeBtn)
				mfp.currTemplate.closeBtn.detach();
		}


		if(mfp.st.autoFocusLast && mfp._lastFocusedEl) {
			$(mfp._lastFocusedEl).focus(); // put tab focus back
		}
		mfp.currItem = null;	
		mfp.content = null;
		mfp.currTemplate = null;
		mfp.prevHeight = 0;

		_mfpTrigger(AFTER_CLOSE_EVENT);
	},
	
	updateSize: function(winHeight) {

		if(mfp.isIOS) {
			// fixes iOS nav bars https://github.com/dimsemenov/Magnific-Popup/issues/2
			var zoomLevel = document.documentElement.clientWidth / window.innerWidth;
			var height = window.innerHeight * zoomLevel;
			mfp.wrap.css('height', height);
			mfp.wH = height;
		} else {
			mfp.wH = winHeight || _window.height();
		}
		// Fixes #84: popup incorrectly positioned with position:relative on body
		if(!mfp.fixedContentPos) {
			mfp.wrap.css('height', mfp.wH);
		}

		_mfpTrigger('Resize');

	},

	/**
	 * Set content of popup based on current index
	 */
	updateItemHTML: function() {
		var item = mfp.items[mfp.index];

		// Detach and perform modifications
		mfp.contentContainer.detach();

		if(mfp.content)
			mfp.content.detach();

		if(!item.parsed) {
			item = mfp.parseEl( mfp.index );
		}

		var type = item.type;

		_mfpTrigger('BeforeChange', [mfp.currItem ? mfp.currItem.type : '', type]);
		// BeforeChange event works like so:
		// _mfpOn('BeforeChange', function(e, prevType, newType) { });

		mfp.currItem = item;

		if(!mfp.currTemplate[type]) {
			var markup = mfp.st[type] ? mfp.st[type].markup : false;

			// allows to modify markup
			_mfpTrigger('FirstMarkupParse', markup);

			if(markup) {
				mfp.currTemplate[type] = $(markup);
			} else {
				// if there is no markup found we just define that template is parsed
				mfp.currTemplate[type] = true;
			}
		}

		if(_prevContentType && _prevContentType !== item.type) {
			mfp.container.removeClass('mfp-'+_prevContentType+'-holder');
		}

		var newContent = mfp['get' + type.charAt(0).toUpperCase() + type.slice(1)](item, mfp.currTemplate[type]);
		mfp.appendContent(newContent, type);

		item.preloaded = true;

		_mfpTrigger(CHANGE_EVENT, item);
		_prevContentType = item.type;

		// Append container back after its content changed
		mfp.container.prepend(mfp.contentContainer);

		_mfpTrigger('AfterChange');
	},


	/**
	 * Set HTML content of popup
	 */
	appendContent: function(newContent, type) {
		mfp.content = newContent;

		if(newContent) {
			if(mfp.st.showCloseBtn && mfp.st.closeBtnInside &&
				mfp.currTemplate[type] === true) {
				// if there is no markup, we just append close button element inside
				if(!mfp.content.find('.mfp-close').length) {
					mfp.content.append(_getCloseBtn());
				}
			} else {
				mfp.content = newContent;
			}
		} else {
			mfp.content = '';
		}

		_mfpTrigger(BEFORE_APPEND_EVENT);
		mfp.container.addClass('mfp-'+type+'-holder');

		mfp.contentContainer.append(mfp.content);
	},


	/**
	 * Creates Magnific Popup data object based on given data
	 * @param  {int} index Index of item to parse
	 */
	parseEl: function(index) {
		var item = mfp.items[index],
			type;

		if(item.tagName) {
			item = { el: $(item) };
		} else {
			type = item.type;
			item = { data: item, src: item.src };
		}

		if(item.el) {
			var types = mfp.types;

			// check for 'mfp-TYPE' class
			for(var i = 0; i < types.length; i++) {
				if( item.el.hasClass('mfp-'+types[i]) ) {
					type = types[i];
					break;
				}
			}

			item.src = item.el.attr('data-mfp-src');
			if(!item.src) {
				item.src = item.el.attr('href');
			}
		}

		item.type = type || mfp.st.type || 'inline';
		item.index = index;
		item.parsed = true;
		mfp.items[index] = item;
		_mfpTrigger('ElementParse', item);

		return mfp.items[index];
	},


	/**
	 * Initializes single popup or a group of popups
	 */
	addGroup: function(el, options) {
		var eHandler = function(e) {
			e.mfpEl = this;
			mfp._openClick(e, el, options);
		};

		if(!options) {
			options = {};
		}

		var eName = 'click.magnificPopup';
		options.mainEl = el;

		if(options.items) {
			options.isObj = true;
			el.off(eName).on(eName, eHandler);
		} else {
			options.isObj = false;
			if(options.delegate) {
				el.off(eName).on(eName, options.delegate , eHandler);
			} else {
				options.items = el;
				el.off(eName).on(eName, eHandler);
			}
		}
	},
	_openClick: function(e, el, options) {
		var midClick = options.midClick !== undefined ? options.midClick : $.magnificPopup.defaults.midClick;


		if(!midClick && ( e.which === 2 || e.ctrlKey || e.metaKey || e.altKey || e.shiftKey ) ) {
			return;
		}

		var disableOn = options.disableOn !== undefined ? options.disableOn : $.magnificPopup.defaults.disableOn;

		if(disableOn) {
			if($.isFunction(disableOn)) {
				if( !disableOn.call(mfp) ) {
					return true;
				}
			} else { // else it's number
				if( _window.width() < disableOn ) {
					return true;
				}
			}
		}

		if(e.type) {
			e.preventDefault();

			// This will prevent popup from closing if element is inside and popup is already opened
			if(mfp.isOpen) {
				e.stopPropagation();
			}
		}

		options.el = $(e.mfpEl);
		if(options.delegate) {
			options.items = el.find(options.delegate);
		}
		mfp.open(options);
	},


	/**
	 * Updates text on preloader
	 */
	updateStatus: function(status, text) {

		if(mfp.preloader) {
			if(_prevStatus !== status) {
				mfp.container.removeClass('mfp-s-'+_prevStatus);
			}

			if(!text && status === 'loading') {
				text = mfp.st.tLoading;
			}

			var data = {
				status: status,
				text: text
			};
			// allows to modify status
			_mfpTrigger('UpdateStatus', data);

			status = data.status;
			text = data.text;

			mfp.preloader.html(text);

			mfp.preloader.find('a').on('click', function(e) {
				e.stopImmediatePropagation();
			});

			mfp.container.addClass('mfp-s-'+status);
			_prevStatus = status;
		}
	},


	/*
		"Private" helpers that aren't private at all
	 */
	// Check to close popup or not
	// "target" is an element that was clicked
	_checkIfClose: function(target) {

		if($(target).hasClass(PREVENT_CLOSE_CLASS)) {
			return;
		}

		var closeOnContent = mfp.st.closeOnContentClick;
		var closeOnBg = mfp.st.closeOnBgClick;

		if(closeOnContent && closeOnBg) {
			return true;
		} else {

			// We close the popup if click is on close button or on preloader. Or if there is no content.
			if(!mfp.content || $(target).hasClass('mfp-close') || (mfp.preloader && target === mfp.preloader[0]) ) {
				return true;
			}

			// if click is outside the content
			if(  (target !== mfp.content[0] && !$.contains(mfp.content[0], target))  ) {
				if(closeOnBg) {
					// last check, if the clicked element is in DOM, (in case it's removed onclick)
					if( $.contains(document, target) ) {
						return true;
					}
				}
			} else if(closeOnContent) {
				return true;
			}

		}
		return false;
	},
	_addClassToMFP: function(cName) {
		mfp.bgOverlay.addClass(cName);
		mfp.wrap.addClass(cName);
	},
	_removeClassFromMFP: function(cName) {
		this.bgOverlay.removeClass(cName);
		mfp.wrap.removeClass(cName);
	},
	_hasScrollBar: function(winHeight) {
		return (  (mfp.isIE7 ? _document.height() : document.body.scrollHeight) > (winHeight || _window.height()) );
	},
	_setFocus: function() {
		(mfp.st.focus ? mfp.content.find(mfp.st.focus).eq(0) : mfp.wrap).focus();
	},
	_onFocusIn: function(e) {
		if( e.target !== mfp.wrap[0] && !$.contains(mfp.wrap[0], e.target) ) {
			mfp._setFocus();
			return false;
		}
	},
	_parseMarkup: function(template, values, item) {
		var arr;
		if(item.data) {
			values = $.extend(item.data, values);
		}
		_mfpTrigger(MARKUP_PARSE_EVENT, [template, values, item] );

		$.each(values, function(key, value) {
			if(value === undefined || value === false) {
				return true;
			}
			arr = key.split('_');
			if(arr.length > 1) {
				var el = template.find(EVENT_NS + '-'+arr[0]);

				if(el.length > 0) {
					var attr = arr[1];
					if(attr === 'replaceWith') {
						if(el[0] !== value[0]) {
							el.replaceWith(value);
						}
					} else if(attr === 'img') {
						if(el.is('img')) {
							el.attr('src', value);
						} else {
							el.replaceWith( $('<img>').attr('src', value).attr('class', el.attr('class')) );
						}
					} else {
						el.attr(arr[1], value);
					}
				}

			} else {
				template.find(EVENT_NS + '-'+key).html(value);
			}
		});
	},

	_getScrollbarSize: function() {
		// thx David
		if(mfp.scrollbarSize === undefined) {
			var scrollDiv = document.createElement("div");
			scrollDiv.style.cssText = 'width: 99px; height: 99px; overflow: scroll; position: absolute; top: -9999px;';
			document.body.appendChild(scrollDiv);
			mfp.scrollbarSize = scrollDiv.offsetWidth - scrollDiv.clientWidth;
			document.body.removeChild(scrollDiv);
		}
		return mfp.scrollbarSize;
	}

}; /* MagnificPopup core prototype end */




/**
 * Public static functions
 */
$.magnificPopup = {
	instance: null,
	proto: MagnificPopup.prototype,
	modules: [],

	open: function(options, index) {
		_checkInstance();

		if(!options) {
			options = {};
		} else {
			options = $.extend(true, {}, options);
		}

		options.isObj = true;
		options.index = index || 0;
		return this.instance.open(options);
	},

	close: function() {
		return $.magnificPopup.instance && $.magnificPopup.instance.close();
	},

	registerModule: function(name, module) {
		if(module.options) {
			$.magnificPopup.defaults[name] = module.options;
		}
		$.extend(this.proto, module.proto);
		this.modules.push(name);
	},

	defaults: {

		// Info about options is in docs:
		// http://dimsemenov.com/plugins/magnific-popup/documentation.html#options

		disableOn: 0,

		key: null,

		midClick: false,

		mainClass: '',

		preloader: true,

		focus: '', // CSS selector of input to focus after popup is opened

		closeOnContentClick: false,

		closeOnBgClick: true,

		closeBtnInside: true,

		showCloseBtn: true,

		enableEscapeKey: true,

		modal: false,

		alignTop: false,

		removalDelay: 0,

		prependTo: null,

		fixedContentPos: 'auto',

		fixedBgPos: 'auto',

		overflowY: 'auto',

		closeMarkup: '<button title="%title%" type="button" class="mfp-close">&#215;</button>',

		tClose: 'Close (Esc)',

		tLoading: 'Loading...',

		autoFocusLast: true

	}
};



$.fn.magnificPopup = function(options) {
	_checkInstance();

	var jqEl = $(this);

	// We call some API method of first param is a string
	if (typeof options === "string" ) {

		if(options === 'open') {
			var items,
				itemOpts = _isJQ ? jqEl.data('magnificPopup') : jqEl[0].magnificPopup,
				index = parseInt(arguments[1], 10) || 0;

			if(itemOpts.items) {
				items = itemOpts.items[index];
			} else {
				items = jqEl;
				if(itemOpts.delegate) {
					items = items.find(itemOpts.delegate);
				}
				items = items.eq( index );
			}
			mfp._openClick({mfpEl:items}, jqEl, itemOpts);
		} else {
			if(mfp.isOpen)
				mfp[options].apply(mfp, Array.prototype.slice.call(arguments, 1));
		}

	} else {
		// clone options obj
		options = $.extend(true, {}, options);

		/*
		 * As Zepto doesn't support .data() method for objects
		 * and it works only in normal browsers
		 * we assign "options" object directly to the DOM element. FTW!
		 */
		if(_isJQ) {
			jqEl.data('magnificPopup', options);
		} else {
			jqEl[0].magnificPopup = options;
		}

		mfp.addGroup(jqEl, options);

	}
	return jqEl;
};

/*>>core*/

/*>>inline*/

var INLINE_NS = 'inline',
	_hiddenClass,
	_inlinePlaceholder,
	_lastInlineElement,
	_putInlineElementsBack = function() {
		if(_lastInlineElement) {
			_inlinePlaceholder.after( _lastInlineElement.addClass(_hiddenClass) ).detach();
			_lastInlineElement = null;
		}
	};

$.magnificPopup.registerModule(INLINE_NS, {
	options: {
		hiddenClass: 'hide', // will be appended with `mfp-` prefix
		markup: '',
		tNotFound: 'Content not found'
	},
	proto: {

		initInline: function() {
			mfp.types.push(INLINE_NS);

			_mfpOn(CLOSE_EVENT+'.'+INLINE_NS, function() {
				_putInlineElementsBack();
			});
		},

		getInline: function(item, template) {

			_putInlineElementsBack();

			if(item.src) {
				var inlineSt = mfp.st.inline,
					el = $(item.src);

				if(el.length) {

					// If target element has parent - we replace it with placeholder and put it back after popup is closed
					var parent = el[0].parentNode;
					if(parent && parent.tagName) {
						if(!_inlinePlaceholder) {
							_hiddenClass = inlineSt.hiddenClass;
							_inlinePlaceholder = _getEl(_hiddenClass);
							_hiddenClass = 'mfp-'+_hiddenClass;
						}
						// replace target inline element with placeholder
						_lastInlineElement = el.after(_inlinePlaceholder).detach().removeClass(_hiddenClass);
					}

					mfp.updateStatus('ready');
				} else {
					mfp.updateStatus('error', inlineSt.tNotFound);
					el = $('<div>');
				}

				item.inlineElement = el;
				return el;
			}

			mfp.updateStatus('ready');
			mfp._parseMarkup(template, {}, item);
			return template;
		}
	}
});

/*>>inline*/

/*>>ajax*/
var AJAX_NS = 'ajax',
	_ajaxCur,
	_removeAjaxCursor = function() {
		if(_ajaxCur) {
			$(document.body).removeClass(_ajaxCur);
		}
	},
	_destroyAjaxRequest = function() {
		_removeAjaxCursor();
		if(mfp.req) {
			mfp.req.abort();
		}
	};

$.magnificPopup.registerModule(AJAX_NS, {

	options: {
		settings: null,
		cursor: 'mfp-ajax-cur',
		tError: '<a href="%url%">The content</a> could not be loaded.'
	},

	proto: {
		initAjax: function() {
			mfp.types.push(AJAX_NS);
			_ajaxCur = mfp.st.ajax.cursor;

			_mfpOn(CLOSE_EVENT+'.'+AJAX_NS, _destroyAjaxRequest);
			_mfpOn('BeforeChange.' + AJAX_NS, _destroyAjaxRequest);
		},
		getAjax: function(item) {

			if(_ajaxCur) {
				$(document.body).addClass(_ajaxCur);
			}

			mfp.updateStatus('loading');

			var opts = $.extend({
				url: item.src,
				success: function(data, textStatus, jqXHR) {
					var temp = {
						data:data,
						xhr:jqXHR
					};

					_mfpTrigger('ParseAjax', temp);

					mfp.appendContent( $(temp.data), AJAX_NS );

					item.finished = true;

					_removeAjaxCursor();

					mfp._setFocus();

					setTimeout( function() {
						mfp.wrap.addClass(READY_CLASS);
					}, 16);

					mfp.updateStatus('ready');

					_mfpTrigger('AjaxContentAdded');
				},
				error: function() {
					_removeAjaxCursor();
					item.finished = item.loadError = true;
					mfp.updateStatus('error', mfp.st.ajax.tError.replace('%url%', item.src));
				}
			}, mfp.st.ajax.settings);

			mfp.req = $.ajax(opts);

			return '';
		}
	}
});

/*>>ajax*/

/*>>image*/
var _imgInterval,
	_getTitle = function(item) {
		if(item.data && item.data.title !== undefined)
			return item.data.title;

		var src = mfp.st.image.titleSrc;

		if(src) {
			if($.isFunction(src)) {
				return src.call(mfp, item);
			} else if(item.el) {
				return item.el.attr(src) || '';
			}
		}
		return '';
	};

$.magnificPopup.registerModule('image', {

	options: {
		markup: '<div class="mfp-figure">'+
					'<div class="mfp-close"></div>'+
					'<figure>'+
						'<div class="mfp-img"></div>'+
						'<figcaption>'+
							'<div class="mfp-bottom-bar">'+
								'<div class="mfp-title"></div>'+
								'<div class="mfp-counter"></div>'+
							'</div>'+
						'</figcaption>'+
					'</figure>'+
				'</div>',
		cursor: 'mfp-zoom-out-cur',
		titleSrc: 'title',
		verticalFit: true,
		tError: '<a href="%url%">The image</a> could not be loaded.'
	},

	proto: {
		initImage: function() {
			var imgSt = mfp.st.image,
				ns = '.image';

			mfp.types.push('image');

			_mfpOn(OPEN_EVENT+ns, function() {
				if(mfp.currItem.type === 'image' && imgSt.cursor) {
					$(document.body).addClass(imgSt.cursor);
				}
			});

			_mfpOn(CLOSE_EVENT+ns, function() {
				if(imgSt.cursor) {
					$(document.body).removeClass(imgSt.cursor);
				}
				_window.off('resize' + EVENT_NS);
			});

			_mfpOn('Resize'+ns, mfp.resizeImage);
			if(mfp.isLowIE) {
				_mfpOn('AfterChange', mfp.resizeImage);
			}
		},
		resizeImage: function() {
			var item = mfp.currItem;
			if(!item || !item.img) return;

			if(mfp.st.image.verticalFit) {
				var decr = 0;
				// fix box-sizing in ie7/8
				if(mfp.isLowIE) {
					decr = parseInt(item.img.css('padding-top'), 10) + parseInt(item.img.css('padding-bottom'),10);
				}
				item.img.css('max-height', mfp.wH-decr);
			}
		},
		_onImageHasSize: function(item) {
			if(item.img) {

				item.hasSize = true;

				if(_imgInterval) {
					clearInterval(_imgInterval);
				}

				item.isCheckingImgSize = false;

				_mfpTrigger('ImageHasSize', item);

				if(item.imgHidden) {
					if(mfp.content)
						mfp.content.removeClass('mfp-loading');

					item.imgHidden = false;
				}

			}
		},

		/**
		 * Function that loops until the image has size to display elements that rely on it asap
		 */
		findImageSize: function(item) {

			var counter = 0,
				img = item.img[0],
				mfpSetInterval = function(delay) {

					if(_imgInterval) {
						clearInterval(_imgInterval);
					}
					// decelerating interval that checks for size of an image
					_imgInterval = setInterval( function() {
						if(img.naturalWidth > 0) {
							mfp._onImageHasSize(item);
							return;
						}

						if(counter > 200) {
							clearInterval(_imgInterval);
						}

						counter++;
						if(counter === 3) {
							mfpSetInterval(10);
						} else if(counter === 40) {
							mfpSetInterval(50);
						} else if(counter === 100) {
							mfpSetInterval(500);
						}
					}, delay);
				};

			mfpSetInterval(1);
		},

		getImage: function(item, template) {

			var guard = 0,

				// image load complete handler
				onLoadComplete = function() {
					if(item) {
						if (item.img[0].complete) {
							item.img.off('.mfploader');

							if(item === mfp.currItem){
								mfp._onImageHasSize(item);

								mfp.updateStatus('ready');
							}

							item.hasSize = true;
							item.loaded = true;

							_mfpTrigger('ImageLoadComplete');

						}
						else {
							// if image complete check fails 200 times (20 sec), we assume that there was an error.
							guard++;
							if(guard < 200) {
								setTimeout(onLoadComplete,100);
							} else {
								onLoadError();
							}
						}
					}
				},

				// image error handler
				onLoadError = function() {
					if(item) {
						item.img.off('.mfploader');
						if(item === mfp.currItem){
							mfp._onImageHasSize(item);
							mfp.updateStatus('error', imgSt.tError.replace('%url%', item.src) );
						}

						item.hasSize = true;
						item.loaded = true;
						item.loadError = true;
					}
				},
				imgSt = mfp.st.image;


			var el = template.find('.mfp-img');
			if(el.length) {
				var img = document.createElement('img');
				img.className = 'mfp-img';
				if(item.el && item.el.find('img').length) {
					img.alt = item.el.find('img').attr('alt');
				}
				item.img = $(img).on('load.mfploader', onLoadComplete).on('error.mfploader', onLoadError);
				img.src = item.src;

				// without clone() "error" event is not firing when IMG is replaced by new IMG
				// TODO: find a way to avoid such cloning
				if(el.is('img')) {
					item.img = item.img.clone();
				}

				img = item.img[0];
				if(img.naturalWidth > 0) {
					item.hasSize = true;
				} else if(!img.width) {
					item.hasSize = false;
				}
			}

			mfp._parseMarkup(template, {
				title: _getTitle(item),
				img_replaceWith: item.img
			}, item);

			mfp.resizeImage();

			if(item.hasSize) {
				if(_imgInterval) clearInterval(_imgInterval);

				if(item.loadError) {
					template.addClass('mfp-loading');
					mfp.updateStatus('error', imgSt.tError.replace('%url%', item.src) );
				} else {
					template.removeClass('mfp-loading');
					mfp.updateStatus('ready');
				}
				return template;
			}

			mfp.updateStatus('loading');
			item.loading = true;

			if(!item.hasSize) {
				item.imgHidden = true;
				template.addClass('mfp-loading');
				mfp.findImageSize(item);
			}

			return template;
		}
	}
});

/*>>image*/

/*>>zoom*/
var hasMozTransform,
	getHasMozTransform = function() {
		if(hasMozTransform === undefined) {
			hasMozTransform = document.createElement('p').style.MozTransform !== undefined;
		}
		return hasMozTransform;
	};

$.magnificPopup.registerModule('zoom', {

	options: {
		enabled: false,
		easing: 'ease-in-out',
		duration: 300,
		opener: function(element) {
			return element.is('img') ? element : element.find('img');
		}
	},

	proto: {

		initZoom: function() {
			var zoomSt = mfp.st.zoom,
				ns = '.zoom',
				image;

			if(!zoomSt.enabled || !mfp.supportsTransition) {
				return;
			}

			var duration = zoomSt.duration,
				getElToAnimate = function(image) {
					var newImg = image.clone().removeAttr('style').removeAttr('class').addClass('mfp-animated-image'),
						transition = 'all '+(zoomSt.duration/1000)+'s ' + zoomSt.easing,
						cssObj = {
							position: 'fixed',
							zIndex: 9999,
							left: 0,
							top: 0,
							'-webkit-backface-visibility': 'hidden'
						},
						t = 'transition';

					cssObj['-webkit-'+t] = cssObj['-moz-'+t] = cssObj['-o-'+t] = cssObj[t] = transition;

					newImg.css(cssObj);
					return newImg;
				},
				showMainContent = function() {
					mfp.content.css('visibility', 'visible');
				},
				openTimeout,
				animatedImg;

			_mfpOn('BuildControls'+ns, function() {
				if(mfp._allowZoom()) {

					clearTimeout(openTimeout);
					mfp.content.css('visibility', 'hidden');

					// Basically, all code below does is clones existing image, puts in on top of the current one and animated it

					image = mfp._getItemToZoom();

					if(!image) {
						showMainContent();
						return;
					}

					animatedImg = getElToAnimate(image);

					animatedImg.css( mfp._getOffset() );

					mfp.wrap.append(animatedImg);

					openTimeout = setTimeout( function() {
						animatedImg.css( mfp._getOffset( true ) );
						openTimeout = setTimeout( function() {

							showMainContent();

							setTimeout( function() {
								animatedImg.remove();
								image = animatedImg = null;
								_mfpTrigger('ZoomAnimationEnded');
							}, 16); // avoid blink when switching images

						}, duration); // this timeout equals animation duration

					}, 16); // by adding this timeout we avoid short glitch at the beginning of animation


					// Lots of timeouts...
				}
			});
			_mfpOn(BEFORE_CLOSE_EVENT+ns, function() {
				if(mfp._allowZoom()) {

					clearTimeout(openTimeout);

					mfp.st.removalDelay = duration;

					if(!image) {
						image = mfp._getItemToZoom();
						if(!image) {
							return;
						}
						animatedImg = getElToAnimate(image);
					}

					animatedImg.css( mfp._getOffset(true) );
					mfp.wrap.append(animatedImg);
					mfp.content.css('visibility', 'hidden');

					setTimeout( function() {
						animatedImg.css( mfp._getOffset() );
					}, 16);
				}

			});

			_mfpOn(CLOSE_EVENT+ns, function() {
				if(mfp._allowZoom()) {
					showMainContent();
					if(animatedImg) {
						animatedImg.remove();
					}
					image = null;
				}
			});
		},

		_allowZoom: function() {
			return mfp.currItem.type === 'image';
		},

		_getItemToZoom: function() {
			if(mfp.currItem.hasSize) {
				return mfp.currItem.img;
			} else {
				return false;
			}
		},

		// Get element postion relative to viewport
		_getOffset: function(isLarge) {
			var el;
			if(isLarge) {
				el = mfp.currItem.img;
			} else {
				el = mfp.st.zoom.opener(mfp.currItem.el || mfp.currItem);
			}

			var offset = el.offset();
			var paddingTop = parseInt(el.css('padding-top'),10);
			var paddingBottom = parseInt(el.css('padding-bottom'),10);
			offset.top -= ( $(window).scrollTop() - paddingTop );


			/*

			Animating left + top + width/height looks glitchy in Firefox, but perfect in Chrome. And vice-versa.

			 */
			var obj = {
				width: el.width(),
				// fix Zepto height+padding issue
				height: (_isJQ ? el.innerHeight() : el[0].offsetHeight) - paddingBottom - paddingTop
			};

			// I hate to do this, but there is no another option
			if( getHasMozTransform() ) {
				obj['-moz-transform'] = obj['transform'] = 'translate(' + offset.left + 'px,' + offset.top + 'px)';
			} else {
				obj.left = offset.left;
				obj.top = offset.top;
			}
			return obj;
		}

	}
});



/*>>zoom*/

/*>>iframe*/

var IFRAME_NS = 'iframe',
	_emptyPage = '//about:blank',

	_fixIframeBugs = function(isShowing) {
		if(mfp.currTemplate[IFRAME_NS]) {
			var el = mfp.currTemplate[IFRAME_NS].find('iframe');
			if(el.length) {
				// reset src after the popup is closed to avoid "video keeps playing after popup is closed" bug
				if(!isShowing) {
					el[0].src = _emptyPage;
				}

				// IE8 black screen bug fix
				if(mfp.isIE8) {
					el.css('display', isShowing ? 'block' : 'none');
				}
			}
		}
	};

$.magnificPopup.registerModule(IFRAME_NS, {

	options: {
		markup: '<div class="mfp-iframe-scaler">'+
					'<div class="mfp-close"></div>'+
					'<iframe class="mfp-iframe" src="//about:blank" frameborder="0" allowfullscreen></iframe>'+
				'</div>',

		srcAction: 'iframe_src',

		// we don't care and support only one default type of URL by default
		patterns: {
			youtube: {
				index: 'youtube.com',
				id: 'v=',
				src: '//www.youtube.com/embed/%id%?autoplay=1'
			},
			vimeo: {
				index: 'vimeo.com/',
				id: '/',
				src: '//player.vimeo.com/video/%id%?autoplay=1'
			},
			gmaps: {
				index: '//maps.google.',
				src: '%id%&output=embed'
			}
		}
	},

	proto: {
		initIframe: function() {
			mfp.types.push(IFRAME_NS);

			_mfpOn('BeforeChange', function(e, prevType, newType) {
				if(prevType !== newType) {
					if(prevType === IFRAME_NS) {
						_fixIframeBugs(); // iframe if removed
					} else if(newType === IFRAME_NS) {
						_fixIframeBugs(true); // iframe is showing
					}
				}// else {
					// iframe source is switched, don't do anything
				//}
			});

			_mfpOn(CLOSE_EVENT + '.' + IFRAME_NS, function() {
				_fixIframeBugs();
			});
		},

		getIframe: function(item, template) {
			var embedSrc = item.src;
			var iframeSt = mfp.st.iframe;

			$.each(iframeSt.patterns, function() {
				if(embedSrc.indexOf( this.index ) > -1) {
					if(this.id) {
						if(typeof this.id === 'string') {
							embedSrc = embedSrc.substr(embedSrc.lastIndexOf(this.id)+this.id.length, embedSrc.length);
						} else {
							embedSrc = this.id.call( this, embedSrc );
						}
					}
					embedSrc = this.src.replace('%id%', embedSrc );
					return false; // break;
				}
			});

			var dataObj = {};
			if(iframeSt.srcAction) {
				dataObj[iframeSt.srcAction] = embedSrc;
			}
			mfp._parseMarkup(template, dataObj, item);

			mfp.updateStatus('ready');

			return template;
		}
	}
});



/*>>iframe*/

/*>>gallery*/
/**
 * Get looped index depending on number of slides
 */
var _getLoopedId = function(index) {
		var numSlides = mfp.items.length;
		if(index > numSlides - 1) {
			return index - numSlides;
		} else  if(index < 0) {
			return numSlides + index;
		}
		return index;
	},
	_replaceCurrTotal = function(text, curr, total) {
		return text.replace(/%curr%/gi, curr + 1).replace(/%total%/gi, total);
	};

$.magnificPopup.registerModule('gallery', {

	options: {
		enabled: false,
		arrowMarkup: '<button title="%title%" type="button" class="mfp-arrow mfp-arrow-%dir%"></button>',
		preload: [0,2],
		navigateByImgClick: true,
		arrows: true,

		tPrev: 'Previous (Left arrow key)',
		tNext: 'Next (Right arrow key)',
		tCounter: '%curr% of %total%'
	},

	proto: {
		initGallery: function() {

			var gSt = mfp.st.gallery,
				ns = '.mfp-gallery';

			mfp.direction = true; // true - next, false - prev

			if(!gSt || !gSt.enabled ) return false;

			_wrapClasses += ' mfp-gallery';

			_mfpOn(OPEN_EVENT+ns, function() {

				if(gSt.navigateByImgClick) {
					mfp.wrap.on('click'+ns, '.mfp-img', function() {
						if(mfp.items.length > 1) {
							mfp.next();
							return false;
						}
					});
				}

				_document.on('keydown'+ns, function(e) {
					if (e.keyCode === 37) {
						mfp.prev();
					} else if (e.keyCode === 39) {
						mfp.next();
					}
				});
			});

			_mfpOn('UpdateStatus'+ns, function(e, data) {
				if(data.text) {
					data.text = _replaceCurrTotal(data.text, mfp.currItem.index, mfp.items.length);
				}
			});

			_mfpOn(MARKUP_PARSE_EVENT+ns, function(e, element, values, item) {
				var l = mfp.items.length;
				values.counter = l > 1 ? _replaceCurrTotal(gSt.tCounter, item.index, l) : '';
			});

			_mfpOn('BuildControls' + ns, function() {
				if(mfp.items.length > 1 && gSt.arrows && !mfp.arrowLeft) {
					var markup = gSt.arrowMarkup,
						arrowLeft = mfp.arrowLeft = $( markup.replace(/%title%/gi, gSt.tPrev).replace(/%dir%/gi, 'left') ).addClass(PREVENT_CLOSE_CLASS),
						arrowRight = mfp.arrowRight = $( markup.replace(/%title%/gi, gSt.tNext).replace(/%dir%/gi, 'right') ).addClass(PREVENT_CLOSE_CLASS);

					arrowLeft.click( function() {
						mfp.prev();
					});
					arrowRight.click( function() {
						mfp.next();
					});

					mfp.container.append(arrowLeft.add(arrowRight));
				}
			});

			_mfpOn(CHANGE_EVENT+ns, function() {
				if(mfp._preloadTimeout) clearTimeout(mfp._preloadTimeout);

				mfp._preloadTimeout = setTimeout( function() {
					mfp.preloadNearbyImages();
					mfp._preloadTimeout = null;
				}, 16);
			});


			_mfpOn(CLOSE_EVENT+ns, function() {
				_document.off(ns);
				mfp.wrap.off('click'+ns);
				mfp.arrowRight = mfp.arrowLeft = null;
			});

		},
		next: function() {
			mfp.direction = true;
			mfp.index = _getLoopedId(mfp.index + 1);
			mfp.updateItemHTML();
		},
		prev: function() {
			mfp.direction = false;
			mfp.index = _getLoopedId(mfp.index - 1);
			mfp.updateItemHTML();
		},
		goTo: function(newIndex) {
			mfp.direction = (newIndex >= mfp.index);
			mfp.index = newIndex;
			mfp.updateItemHTML();
		},
		preloadNearbyImages: function() {
			var p = mfp.st.gallery.preload,
				preloadBefore = Math.min(p[0], mfp.items.length),
				preloadAfter = Math.min(p[1], mfp.items.length),
				i;

			for(i = 1; i <= (mfp.direction ? preloadAfter : preloadBefore); i++) {
				mfp._preloadItem(mfp.index+i);
			}
			for(i = 1; i <= (mfp.direction ? preloadBefore : preloadAfter); i++) {
				mfp._preloadItem(mfp.index-i);
			}
		},
		_preloadItem: function(index) {
			index = _getLoopedId(index);

			if(mfp.items[index].preloaded) {
				return;
			}

			var item = mfp.items[index];
			if(!item.parsed) {
				item = mfp.parseEl( index );
			}

			_mfpTrigger('LazyLoad', item);

			if(item.type === 'image') {
				item.img = $('<img class="mfp-img" />').on('load.mfploader', function() {
					item.hasSize = true;
				}).on('error.mfploader', function() {
					item.hasSize = true;
					item.loadError = true;
					_mfpTrigger('LazyLoadError', item);
				}).attr('src', item.src);
			}


			item.preloaded = true;
		}
	}
});

/*>>gallery*/

/*>>retina*/

var RETINA_NS = 'retina';

$.magnificPopup.registerModule(RETINA_NS, {
	options: {
		replaceSrc: function(item) {
			return item.src.replace(/\.\w+$/, function(m) { return '@2x' + m; });
		},
		ratio: 1 // Function or number.  Set to 1 to disable.
	},
	proto: {
		initRetina: function() {
			if(window.devicePixelRatio > 1) {

				var st = mfp.st.retina,
					ratio = st.ratio;

				ratio = !isNaN(ratio) ? ratio : ratio();

				if(ratio > 1) {
					_mfpOn('ImageHasSize' + '.' + RETINA_NS, function(e, item) {
						item.img.css({
							'max-width': item.img[0].naturalWidth / ratio,
							'width': '100%'
						});
					});
					_mfpOn('ElementParse' + '.' + RETINA_NS, function(e, item) {
						item.src = st.replaceSrc(item, ratio);
					});
				}
			}

		}
	}
});

/*>>retina*/
 _checkInstance(); }));

/***/ }),

/***/ "jquery":
/*!*************************!*\
  !*** external "jQuery" ***!
  \*************************/
/***/ ((module) => {

"use strict";
module.exports = window["jQuery"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
(() => {
"use strict";
/*!************************************!*\
  !*** ./source/frontend/js/main.js ***!
  \************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _helper__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./helper */ "./source/frontend/js/helper.js");
/* harmony import */ var _validation__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./validation */ "./source/frontend/js/validation.js");
/* harmony import */ var _stepper__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./stepper */ "./source/frontend/js/stepper.js");
/* harmony import */ var _loginButtons__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./loginButtons */ "./source/frontend/js/loginButtons.js");
/* harmony import */ var _addressSearch__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./addressSearch */ "./source/frontend/js/addressSearch.js");
/* harmony import */ var _components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./components */ "./source/frontend/js/components.js");
/* harmony import */ var _coupon__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./coupon */ "./source/frontend/js/coupon.js");
/* harmony import */ var _compatibility__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./compatibility */ "./source/frontend/js/compatibility.js");
/* harmony import */ var _cart__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./cart */ "./source/frontend/js/cart.js");
/* harmony import */ var _form__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./form */ "./source/frontend/js/form.js");
/* harmony import */ var _localStorage__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./localStorage */ "./source/frontend/js/localStorage.js");
/* harmony import */ var _checkoutButton__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./checkoutButton */ "./source/frontend/js/checkoutButton.js");
/* harmony import */ var _geocodeMap__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./geocodeMap */ "./source/frontend/js/geocodeMap.js");
/* harmony import */ var _intlPhone__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./intlPhone */ "./source/frontend/js/intlPhone.js");
/* harmony import */ var _orderpay__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./orderpay */ "./source/frontend/js/orderpay.js");
/* harmony import */ var _loginForm__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./loginForm */ "./source/frontend/js/loginForm.js");
/* harmony import */ var _expressCheckout__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./expressCheckout */ "./source/frontend/js/expressCheckout.js");

document.addEventListener('DOMContentLoaded', function (event) {
  ( function() {
    if (typeof Event !== 'function') {
      window.Event = CustomEvent;
    }
  })();
  _helper__WEBPACK_IMPORTED_MODULE_0__["default"].removeDomElements();
  _validation__WEBPACK_IMPORTED_MODULE_1__["default"].init();
  _stepper__WEBPACK_IMPORTED_MODULE_2__["default"].init();
  _loginButtons__WEBPACK_IMPORTED_MODULE_3__["default"].init();
  _addressSearch__WEBPACK_IMPORTED_MODULE_4__["default"].init();
  _components__WEBPACK_IMPORTED_MODULE_5__["default"].init();
  _coupon__WEBPACK_IMPORTED_MODULE_6__["default"].init();
  _compatibility__WEBPACK_IMPORTED_MODULE_7__["default"].init();
  _cart__WEBPACK_IMPORTED_MODULE_8__["default"].init();
  _cart__WEBPACK_IMPORTED_MODULE_8__["default"].runOnce();
  _form__WEBPACK_IMPORTED_MODULE_9__["default"].init();
  _localStorage__WEBPACK_IMPORTED_MODULE_10__["default"].init();
  _checkoutButton__WEBPACK_IMPORTED_MODULE_11__["default"].init();
  _geocodeMap__WEBPACK_IMPORTED_MODULE_12__["default"].init();
  _intlPhone__WEBPACK_IMPORTED_MODULE_13__["default"].init();
  _orderpay__WEBPACK_IMPORTED_MODULE_14__["default"].init();
  _loginForm__WEBPACK_IMPORTED_MODULE_15__["default"].init();
  _expressCheckout__WEBPACK_IMPORTED_MODULE_16__["default"].init();
});
( function ($, document) {
  $(document).ready( function() {
    $(document.body).on('wc_fragments_refreshed', function() {
      _helper__WEBPACK_IMPORTED_MODULE_0__["default"].removeDomElements();
      _cart__WEBPACK_IMPORTED_MODULE_8__["default"].init();
      _cart__WEBPACK_IMPORTED_MODULE_8__["default"].update_total();
    });
    $(document.body).on('updated_checkout', function() {
      _helper__WEBPACK_IMPORTED_MODULE_0__["default"].removeDomElements();
      _cart__WEBPACK_IMPORTED_MODULE_8__["default"].init();
      _cart__WEBPACK_IMPORTED_MODULE_8__["default"].update_total();
    });
    $(document.body).on('change', 'input.shipping_method', function() {
      _cart__WEBPACK_IMPORTED_MODULE_8__["default"].update_total();
    });

    // Handle the condition where back button is pressed and document.ready event is not triggered.
    $(window).on('pageshow', function() {
      _form__WEBPACK_IMPORTED_MODULE_9__["default"].prepareFields();
    });

    // When auto-saved address is pasted from the keyboard in iOS, it doesnt trigger update_checkout.
    jQuery(".address-field input.input-text").on('input propertychange paste', function() {
      jQuery(this).trigger("keydown");
    });
  });
})(jQuery, document);
})();

/******/ })()
;
//# sourceMappingURL=main.js.map