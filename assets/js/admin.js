window.getpaid = window.getpaid || {}

// Init the select2 container.
getpaid.init_select2_item_search = function (select, parent) {

	if (!parent) {
		parent = jQuery('#getpaid-add-items-to-invoice')
	}

	jQuery(select).select2({
		minimumInputLength: 3,
		allowClear: false,
		dropdownParent: parent,
		ajax: {
			url: WPInv_Admin.ajax_url,
			delay: 250,
			data: function (params) {

				var data = {
					action: 'wpinv_get_invoicing_items',
					search: params.term,
					_ajax_nonce: WPInv_Admin.wpinv_nonce,
					post_id: WPInv_Admin.post_ID
				}

				// Query parameters will be ?search=[term]&type=public
				return data;
			},
			processResults: function (res) {

				if (res.success) {
					return {
						results: res.data
					};
				}

				return {
					results: []
				};
			}
		},
		templateResult: function (item) {

			if (item.loading) {
				return WPInv_Admin.searching;
			}

			if (!item.id) {
				return item.text;
			}

			return jQuery('<span>' + item.text + '</span>')
		}
	});

}

// Currency formatter.
getpaid.currency = new Intl.NumberFormat(undefined, {
	style: 'currency',
	currency: WPInv_Admin.currency,
})

jQuery(function ($) {
	//'use strict';

	// Tooltips
	$('.wpi-help-tip').tooltip({
		content: function () {
			return $(this).prop('title');
		},
		tooltipClass: 'wpi-ui-tooltip',
		position: {
			my: 'center top',
			at: 'center bottom+10',
			collision: 'flipfit'
		},
		hide: {
			duration: 200
		},
		show: {
			duration: 200
		}
	});

	// Init select 2.
	wpi_select2();
	function wpi_select2() {
		if (jQuery("select.wpi_select2").length > 0) {
			jQuery("select.wpi_select2").select2();
			jQuery("select.wpi_select2_nostd").select2({
				allow_single_deselect: 'true'
			});
		}
	}

	// Init item selector.
	$('.getpaid-ajax-item-selector').each(function () {
		var el = $(this);
		getpaid.init_select2_item_search(el, $(el).parent())
	});

	// returns a random string
	function random_string() {
		return (Date.now().toString(36) + Math.random().toString(36).substr(2))
	}

	// One time Subscription items.
	if ($('#wpinv_is_one_time_recurring').length) {

		// Toggles the 'getpaid_is_one_time_subscription_item' class on the body.
		var watch_one_time_subscription_change = function () {
			$('body').toggleClass('getpaid_is_one_time_subscription_item', $('#wpinv_is_one_time_recurring').is(':checked'));
		}   

		// Toggle the class when the document is loaded...
		watch_one_time_subscription_change();

		// ... and whenever the checkbox changes.
		$(document).on('change', '#wpinv_is_one_time_recurring', watch_one_time_subscription_change);

	}

	// Subscription items.
	if ($('#wpinv_is_recurring').length) {

		// Toggles the 'getpaid_is_subscription_item' class on the body.
		var watch_subscription_change = function () {
			$('body').toggleClass('getpaid_is_subscription_item', $('#wpinv_is_recurring').is(':checked'))
			$('body').toggleClass('getpaid_is_not_subscription_item', !$('#wpinv_is_recurring').is(':checked'))
			
			$('.getpaid-price-input').toggleClass('col-sm-4', $('#wpinv_is_recurring').is(':checked'))
			$('.getpaid-price-input').toggleClass('col-sm-12', !$('#wpinv_is_recurring').is(':checked'))

		}

		// Toggle the class when the document is loaded...
		watch_subscription_change();

		// ... and whenever the checkbox changes.
		$(document).on('change', '#wpinv_is_recurring', watch_subscription_change);

	}

	// Dynamic items.
	if ($('#wpinv_name_your_price').length) {

		// Toggles the 'getpaid_is_dynamic_item' class on the body.
		var watch_dynamic_change = function () {
			$('body').toggleClass('getpaid_is_dynamic_item', $('#wpinv_name_your_price').is(':checked'))
			$('body').toggleClass('getpaid_is_not_dynamic_item', !$('#wpinv_name_your_price').is(':checked'))
		}

		// Toggle the class when the document is loaded...
		watch_dynamic_change();

		// ... and whenever the checkbox changes.
		$(document).on('change', '#wpinv_name_your_price', watch_dynamic_change);

	}

	// Rename excerpt to 'description'
	$('body.post-type-wpi_item #postexcerpt h2.hndle').text(WPInv_Admin.item_description)
	$('body.post-type-wpi_discount #postexcerpt h2.hndle').text(WPInv_Admin.discount_description)
	$('body.getpaid-is-invoice-cpt #postexcerpt h2.hndle').text(WPInv_Admin.invoice_description)
	$('body.getpaid-is-invoice-cpt #postexcerpt p, body.post-type-wpi_item #postexcerpt p, body.post-type-wpi_discount #postexcerpt p').hide()

	// Discount types.
	$(document).on('change', '#wpinv_discount_type', function () {
		$('#wpinv_discount_amount_wrap').removeClass('flat percent')
		$('#wpinv_discount_amount_wrap').addClass($(this).val())
	});

	// Fill in user information.
	$('#getpaid-invoice-fill-user-details').on('click', function (e) {
		e.preventDefault()

		var metabox = $(this).closest('.bsui');
		var user_id = metabox.find('#post_author_override').val()

		// Ensure that we have a user id and that we are not adding a new user.
		if (!user_id || $(this).attr('disabled')) {
			return;
		}

		// Block the metabox.
		wpinvBlock(metabox)

		// Retrieve the user's billing address.
		var data = {
			action: 'wpinv_get_billing_details',
			user_id: user_id,
			_ajax_nonce: WPInv_Admin.wpinv_nonce
		}

		$.get(WPInv_Admin.ajax_url, data)

			.done(function (response) {

				if (response.success) {

					$.each(response.data, function (key, value) {

						// Retrieve the associated input.
						var el = $('#wpinv_' + key)

						// If it exists...
						if (el.length) {
							el.val(value).change()
						}

					});
				}
			})

			.always(function (response) {
				wpinvUnblock(metabox);
			})

	})

	$( '#getpaid-invoice-user-id-wrapper #post_author_override' ).on( 'change', function() {
		$('#getpaid-invoice-fill-user-details').trigger( 'click' )
	} )

	// When clicking the create a new user button...
	$('#getpaid-invoice-create-new-user-button').on('click', function (e) {
		e.preventDefault()

		// Hide the button and the customer select div.
		$('#getpaid-invoice-user-id-wrapper, #getpaid-invoice-create-new-user-button').addClass('d-none')

		// Display the email input and the cancel button.
		$('#getpaid-invoice-cancel-create-new-user, #getpaid-invoice-email-wrapper').removeClass('d-none')

		// Disable the fill user details button.
		$('#getpaid-invoice-fill-user-details').attr('disabled', true);

		// Indicate that we will be creating a new user.
		$('#getpaid-invoice-create-new-user').val(1);

		// The email field is now required.
		$('#getpaid-invoice-new-user-email').prop('required', 'required');

	});

	// When clicking the "cancel new user" button...
	$('#getpaid-invoice-cancel-create-new-user').on('click', function (e) {
		e.preventDefault();

		// Hide the button and the email input divs.
		$('#getpaid-invoice-cancel-create-new-user, #getpaid-invoice-email-wrapper').addClass('d-none')

		// Display the add new user button and select customer divs.
		$('#getpaid-invoice-user-id-wrapper, #getpaid-invoice-create-new-user-button').removeClass('d-none')

		// Enable the fill user details button.
		$('#getpaid-invoice-fill-user-details').attr('disabled', false);

		// We are no longer creating a new user.
		$('#getpaid-invoice-create-new-user').val(0);
		$('#getpaid-invoice-new-user-email').prop('required', false);

	});

	// When the new user's email changes...
	$('#getpaid-invoice-new-user-email').on('change', function (e) {
		e.preventDefault();

		// Hide any error messages.
		$(this)
			.removeClass('is-invalid')
			.parent()
			.find('.invalid-feedback')
			.remove()

		var metabox = $(this).closest('.bsui');
		var email = $(this).val()

		// Block the metabox.
		wpinvBlock(metabox)

		// Ensure the email is unique.
		var data = {
			action: 'wpinv_check_new_user_email',
			email: email,
			_ajax_nonce: WPInv_Admin.wpinv_nonce
		}

		$.get(WPInv_Admin.ajax_url, data)

			.done(function (response) {
				if (!response.success) {
					// Show error messages.
					$('#getpaid-invoice-new-user-email')
						.addClass('is-invalid')
						.parent()
						.append('<div class="invalid-feedback">' + response + '</div>')
				}
			})

			.always(function (response) {
				wpinvUnblock(metabox);
			})

	});

	// When the country changes, load the states field.
	$('.getpaid-is-invoice-cpt').on('change', '#wpinv_country', function (e) {

		// Ensure that we have the states field.
		if (!$('#wpinv_state').length) {
			return
		}

		var row = $(this).closest('.row');

		// Block the row.
		wpinvBlock(row)

		// Fetch the states field.
		var data = {
			action: 'wpinv_get_aui_states_field',
			country: $('#wpinv_country').val(),
			state: $('#wpinv_state').val(),
			_ajax_nonce: WPInv_Admin.wpinv_nonce
		}

		// Fetch new states field.
		$.get(WPInv_Admin.ajax_url, data)

			.done(function (response) {
				if (response.success) {
					$('#wpinv_state').closest('.form-group').replaceWith(response.data.html)

					if (response.data.select) {
						$('#wpinv_state').select2()
					}
				}
			})

			.always(function (response) {
				wpinvUnblock(row);
			})
	})

	// Update template when it changes.
	$('#wpinv_template').on('change', function (e) {
		$(this)
			.closest('.getpaid-invoice-items-inner')
			.removeClass('amount quantity hours')
			.addClass($(this).val())
	})

	// Adding items to an invoice.
	function getpaid_add_invoice_item_modal() {

		// Contains an array of empty selections.
		var empty_select = []

		// Save a cache of the default row.
		$('#getpaid-add-items-to-invoice tbody')
			.data(
				'row',
				$('#getpaid-add-items-to-invoice tbody').html()
			)

		getpaid.init_select2_item_search('.getpaid-item-search')

		// Add a unique id.
		$('.getpaid-item-search').data('key', random_string())

		// (Maybe) add another select box.
		$('#getpaid-add-items-to-invoice').on('change', '.getpaid-item-search', function (e) {

			var el = $(this)
			var key = el.data('key')

			// If no value is selected, add it to empty selects.
			if (!el.val()) {
				if (-1 == $.inArray(key, empty_select)) {
					empty_select.push(key)
				}
				return;
			}

			// Maybe remove it from the list of empty selects.
			var index = $.inArray(key, empty_select)
			if (-1 != index) {
				empty_select.splice(index, 1);
			}

			// If we no longer have an empty select, add one.
			if (empty_select.length) {
				return;
			}

			var key = random_string()
			var row = $('#getpaid-add-items-to-invoice tbody').data('row')
			row = $(row).appendTo('#getpaid-add-items-to-invoice tbody')
			var select = row.find('.getpaid-item-search')
			select.data('key', key)
			getpaid.init_select2_item_search(select)
			empty_select.push(key)

			$('#getpaid-add-items-to-invoice').modal('handleUpdate')

		})

		// Reverts the modal.
		var revert = function () {
			empty_select = []

			$('#getpaid-add-items-to-invoice tbody')
				.html(
					$('#getpaid-add-items-to-invoice tbody').data('row')
				)

			getpaid.init_select2_item_search('.getpaid-item-search')

			// Add a unique id.
			$('.getpaid-item-search').data('key', random_string())
		}

		// Cancel addition.
		$('#getpaid-add-items-to-invoice .getpaid-cancel').on('click', revert)

		// Save addition.
		$('#getpaid-add-items-to-invoice .getpaid-add').on('click', function () {

			// Retrieve selected items.
			var items = $('#getpaid-add-items-to-invoice tbody tr')
				.map(function () {
					if ($(this).find('select').val()) {
						return {
							id: $(this).find('select').val(),
							qty: $(this).find('input').val()
						}
					}
				})
				.get()

			// Revert the modal.
			revert()

			// If no items were selected, abort
			if (!items.length) {
				return;
			}

			// Block the metabox.
			wpinvBlock('.getpaid-invoice-items-inner')

			// Add the items to the invoice.
			var data = {
				action: 'wpinv_add_invoice_items',
				post_id: $('#post_ID').val(),
				_ajax_nonce: WPInv_Admin.wpinv_nonce,
				items: items,
			}

			$.post(WPInv_Admin.ajax_url, data)

				.done(function (response) {

					if (response.success) {
						getpaid_replace_invoice_items(response.data.items)

						if (response.data.alert) {
							alert(response.data.alert)
						}

						recalculateTotals()
					}

				})

				.always(function (response) {
					wpinvUnblock('.getpaid-invoice-items-inner');
				})
		})
	}
	getpaid_add_invoice_item_modal()

	// Refresh invoice items.
	if ($('#wpinv-items .getpaid-invoice-items-inner').hasClass('has-items')) {

		// Refresh the items.
		var data = {
			action: 'wpinv_get_invoice_items',
			post_id: $('#post_ID').val(),
			_ajax_nonce: WPInv_Admin.wpinv_nonce
		}

		// Block the metabox.
		wpinvBlock('.getpaid-invoice-items-inner')

		$.post(WPInv_Admin.ajax_url, data)

			.done(function (response) {

				if (response.success) {
					getpaid_replace_invoice_items(response.data.items)
				}

			})

			.always(function (response) {
				wpinvUnblock('.getpaid-invoice-items-inner');
			})
	}

	/**
	 * Replaces all items with the provided items.
	 *
	 * @param {Array} items New invoice items.
	 */
	function getpaid_replace_invoice_items(items) {

		// Remove all existing items.
		$('tr.getpaid-invoice-item').remove()
		var _class = "no-items"

		$.each(items, function (item_id, item) {

			_class = 'has-items'
			var row = $('tr.getpaid-invoice-item-template').clone()
			row
				.removeClass('getpaid-invoice-item-template d-none')
				.addClass('getpaid-invoice-item item-' + item_id)

			$.each(item.texts, function (key, value) {
				row.find('.' + key).html(value)
			})

			row
				.data('inputs', item.inputs)
				.appendTo('#wpinv-items .getpaid_invoice_line_items')

		})

		$('.getpaid-invoice-items-inner')
			.removeClass('no-items has-items')
			.addClass(_class)
	}

	// Delete invoice items.
	$('.getpaid-is-invoice-cpt').on('click', '.getpaid-item-actions .dashicons-trash', function (e) {
		e.preventDefault();

		// Block the metabox.
		wpinvBlock('.getpaid-invoice-items-inner')

		// Item details.
		var inputs = $(this).closest('.getpaid-invoice-item').data('inputs')
		var that = this

		// Remove the item from the invoice.
		var data = {
			action: 'wpinv_remove_invoice_item',
			post_id: $('#post_ID').val(),
			_ajax_nonce: WPInv_Admin.wpinv_nonce,
			item_id: inputs['item-id'],
		}

		$.post(WPInv_Admin.ajax_url, data)

			.done(function (response) {

				if (response.success) {

					$(that).closest('.getpaid-invoice-item').remove()

					$('.getpaid-invoice-items-inner').removeClass('no-items has-items')

					if ($('tr.getpaid-invoice-item').length) {
						$('.getpaid-invoice-items-inner').addClass('has-items')
					} else {
						$('.getpaid-invoice-items-inner').addClass('no-items')
					}

					recalculateTotals()
				}

			})

			.always(function (response) {
				wpinvUnblock('.getpaid-invoice-items-inner');
			})

	})

	// Edit invoice items.
	$('.getpaid-is-invoice-cpt').on('click', '.getpaid-item-actions .dashicons-edit', function (e) {
		e.preventDefault();

		var inputs = $(this).closest('.getpaid-invoice-item').data('inputs')

		// Enter value getpaid-edit-item-div
		$.each(inputs, function (key, value) {
			$('#getpaid-edit-invoice-item .getpaid-edit-item-div .' + key).val(value)
		})

		// Display the modal.
		$('#getpaid-edit-invoice-item').modal()

	})

	// Cancel item edit.
	$('#getpaid-edit-invoice-item .getpaid-cancel').on('click', function () {
		$('#getpaid-edit-invoice-item .getpaid-edit-item-div :input').val('')
	})

	// Save edited invoice item.
	$('#getpaid-edit-invoice-item .getpaid-save').on('click', function () {

		// Retrieve item data.
		var data = $('#getpaid-edit-invoice-item .getpaid-edit-item-div :input')
			.map(function () {
				return {
					'field': $(this).attr('name'),
					'value': $(this).val(),
				}
			})
			.get()

		$('#getpaid-edit-invoice-item .getpaid-edit-item-div :input').val('')

		// Block the metabox.
		wpinvBlock('.getpaid-invoice-items-inner')

		// Save the edit.
		var post_data = {
			action: 'wpinv_edit_invoice_item',
			post_id: $('#post_ID').val(),
			_ajax_nonce: WPInv_Admin.wpinv_nonce,
			data: data,
		}

		$.post(WPInv_Admin.ajax_url, post_data)

			.done(function (response) {

				if (response.success) {
					getpaid_replace_invoice_items(response.data.items)

					if (response.data.alert) {
						alert(response.data.alert)
					}

					recalculateTotals()
				}

			})

			.always(function (response) {
				wpinvUnblock('.getpaid-invoice-items-inner');
			})
	})

	// Recalculate invoice totals.
	function recalculateTotals() {

		// Prepare arguments.
		var data = {
			country: $('#wpinv_country').val(),
			state: $('#wpinv_state').val(),
			currency: $('#wpinv_currency').val(),
			taxes: $('#wpinv_taxable:checked').length,
			action: 'wpinv_recalculate_invoice_totals',
			post_id: $('#post_ID').val(),
			_ajax_nonce: WPInv_Admin.wpinv_nonce,
		}

		// Block the metabox.
		wpinvBlock('.getpaid-invoice-items-inner')

		$.post(WPInv_Admin.ajax_url, data)

			.done(function (response) {

				if (response.success) {

					var totals = response.data.totals

					$.each(totals, function (key, value) {
						$('tr.getpaid-totals-' + key).find('.value').html(value)
					})

					if (response.data.alert) {
						alert(response.data.alert)
					}
				}

			})

			.always(function (response) {
				wpinvUnblock('.getpaid-invoice-items-inner');
			})

	}
	$('#wpinv-items .recalculate-totals-button').on('click', function (e) {
		e.preventDefault()
		recalculateTotals()
	})

	$('.getpaid-is-invoice-cpt #wpinv_vat_number, .getpaid-is-invoice-cpt #wpinv_taxable').on('change', function (e) {
		e.preventDefault()
		recalculateTotals()
	})

	var $postForm = $('.getpaid-is-invoice-cpt form#post');

	if ($('[name="wpinv_status"]', $postForm).length) {
		var origStatus = $('[name="wpinv_status"]', $postForm).val();
		$('[name="original_post_status"]', $postForm).val(origStatus);
		$('[name="hidden_post_status"]', $postForm).val(origStatus);
		$('[name="post_status"]', $postForm).replaceWith('<input type="hidden" value="' + origStatus + '" id="post_status" name="post_status">');
	}

	/**
	 * Invoice Notes Panel
	 */
	var wpinv_meta_boxes_notes = {
		init: function () {
			$('#wpinv-notes')
				.on('click', 'a.add_note', this.add_invoice_note)
				.on('click', 'a.delete_note', this.delete_invoice_note);
			if ($('ul.invoice_notes')[0]) {
				$('ul.invoice_notes')[0].scrollTop = $('ul.invoice_notes')[0].scrollHeight;
			}
		},
		add_invoice_note: function () {
			if (!$('textarea#add_invoice_note').val()) {
				return;
			}
			$('#wpinv-notes').block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
			var data = {
				action: 'wpinv_add_note',
				post_id: WPInv_Admin.post_ID,
				note: $('textarea#add_invoice_note').val(),
				note_type: $('select#invoice_note_type').val(),
				_nonce: WPInv_Admin.add_invoice_note_nonce
			};
			$.post(WPInv_Admin.ajax_url, data, function (response) {
				$('ul.invoice_notes').append(response);
				$('ul.invoice_notes')[0].scrollTop = $('ul.invoice_notes')[0].scrollHeight;
				wpinvUnblock( '#wpinv-notes' );
				$('#add_invoice_note').val('');
			});
			return false;
		},
		delete_invoice_note: function () {
			var note = $(this).closest('li.note');
			$(note).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
			var data = {
				action: 'wpinv_delete_note',
				note_id: $(note).attr('rel'),
				_nonce: WPInv_Admin.delete_invoice_note_nonce
			};
			$.post(WPInv_Admin.ajax_url, data, function () {
				$(note).remove();
			});
			return false;
		}
	};
	wpinv_meta_boxes_notes.init();
	var invDetails = jQuery('#wpinv-details .inside').html();

	if (invDetails) {
		jQuery('#submitpost', jQuery('.wpinv')).detach().appendTo(jQuery('#wpinv-details'));
		jQuery('#submitdiv', jQuery('.wpinv')).remove();
		jQuery('#publishing-action', '#wpinv-details').find('input[type=submit]').attr('name', 'save_invoice').val(WPInv_Admin.save_invoice);
	}
	var invBilling = jQuery('#wpinv-address.postbox').html();
	if (invBilling) {
		jQuery('#post_author_override', '#authordiv').remove();
		jQuery('#authordiv', jQuery('.wpinv')).hide();
	}
	var wpinvNumber;
	if (!jQuery('#post input[name="post_title"]').val() && (wpinvNumber = jQuery('#wpinv-details input[name="wpinv_number"]').val())) {
		jQuery('#post input[name="post_title"]').val(wpinvNumber);
	}
	var wpi_stat_links = jQuery('.getpaid-is-invoice-cpt .subsubsub');
	if (wpi_stat_links.is(':visible')) {
		var publish_count = jQuery('.publish', wpi_stat_links).find('.count').text();
		jQuery('.publish', wpi_stat_links).find('a').html(WPInv_Admin.status_publish + ' <span class="count">' + publish_count + '</span>');
		var pending_count = jQuery('.wpi-pending', wpi_stat_links).find('.count').text();
		jQuery('.pending', wpi_stat_links).find('a').html(WPInv_Admin.status_pending + ' <span class="count">' + pending_count + '</span>');
	}

	// Update state field based on selected country
	var getpaid_user_edit_sync_state_and_country = function () {

		// Ensure that we have both fields.
		if (!$('.getpaid_js_field-country').length || !$('.getpaid_js_field-state').length) {
			return
		}

		// fade the state field.
		$('.getpaid_js_field-state').fadeTo(1000, 0.4);

		// Prepare data.
		data = {
			action: 'wpinv_get_states_field',
			country: $('.getpaid_js_field-country').val(),
			field_name: $('.getpaid_js_field-country').attr('name').replace('country', 'state')
		};

		// Fetch new states field.
		$.post(WPInv_Admin.ajax_url, data)

			.done(function (response) {

				var value = $('.getpaid_js_field-state').val()

				if ('nostates' == response) {
					var text_field = '<input type="text" name="' + data.field_name + '" value="" class="getpaid_js_field-state regular-text"/>';
					$('.getpaid_js_field-state').replaceWith(text_field);
				} else {
					var response = $(response)
					response.addClass('getpaid_js_field-state regular-text')
					response.attr('id', data.field_name)
					$('.getpaid_js_field-state').replaceWith(response)
				}

				$('.getpaid_js_field-state').val(value)

			})

			.fail(function () {
				var text_field = '<input type="text" name="' + data.field_name + '" value="" class="getpaid_js_field-state regular-text"/>';
				$('.getpaid_js_field-state').replaceWith(text_field);
			})

			.always(function () {
				// unfade the state field.
				$('.getpaid_js_field-state').fadeTo(1000, 1);
			})


	}

	// Sync on load.
	getpaid_user_edit_sync_state_and_country();

	// Sync on changes.
	$(document.body).on('change', '.getpaid_js_field-country', getpaid_user_edit_sync_state_and_country);

	/**
	 * Reindexes the tax table.
	 */
	function wpinv_reindex_tax_table() {

		$('#wpinv_tax_rates tbody tr').each(function (rowIndex) {

			$(this).find(":input[name^='tax_rates']").each(function () {
				var name = $(this).attr('name');
				name = name.replace(/\[(\d+)\]/, '[' + (rowIndex) + ']');
				$(this).attr('name', name).attr('id', name);
			});

		});

	}

	// Inserts a new tax rate row
	$('.wpinv_add_tax_rate').on('click', function (e) {

		e.preventDefault()
		var html = $('#tmpl-wpinv-tax-rate-row').html()
		$('#wpinv_tax_rates tbody').append(html)
		wpinv_reindex_tax_table();

	});

	// Remove tax row.
	$(document).on('click', '#wpinv_tax_rates .wpinv_remove_tax_rate', function (e) {

		e.preventDefault()
		$(this).closest('tr').remove();
		wpinv_reindex_tax_table();

	});

	var elB = $('#wpinv-address');

	$('#wpinv_state', elB).on('change', function (e) {
		window.wpiConfirmed = true;
		$('#wpinv-recalc-totals').click();
		window.wpiConfirmed = false;
	});
	$('#wpinv_taxable').on('change', function (e) {
		window.wpiConfirmed = true;
		$('#wpinv-recalc-totals').click();
		window.wpiConfirmed = false;
	});

	var WPInv = {
		init: function () {
			this.preSetup();
		},
		preSetup: function () {

			var wpinvColorPicker = $('.wpinv-color-picker');
			if (wpinvColorPicker.length) {
				wpinvColorPicker.wpColorPicker();
			}
			var no_states = $('select.wpinv-no-states');
			if (no_states.length) {
				no_states.closest('tr').hide();
			}
			// Update base state field based on selected base country
			$('select[name="wpinv_settings[default_country]"]').change(function () {
				var $this = $(this),
					$tr = $this.closest('tr');
				data = {
					action: 'wpinv_get_states_field',
					country: $(this).val(),
					field_name: 'wpinv_settings[default_state]'
				};
				$.post(WPInv_Admin.ajax_url, data, function (response) {
					if ('nostates' == response) {
						$tr.next().hide();
					} else {
						$tr.next().show();
						$tr.next().find('select').replaceWith(response);
					}
				});
				return false;
			});

		},

	};
	$('.getpaid-is-invoice-cpt form#post #titlediv [name="post_title"]').attr('readonly', true);

	WPInv.init();

	/**
	 * Retrieves a report.
	 * @param {string} report
	 * @param {object} args
	 */
	function getStats(report, args) {

		// Reports.
		return $.ajax(
			{
				url: WPInv_Admin.rest_root + 'getpaid/v1/reports/' + report,
				method: 'GET',
				data: args,
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', WPInv_Admin.rest_nonce);
				}
			}
		);

	}

	/**
	 * Feeds a stat onto the page.
	 * @param {string} stat
	 * @param {string} current
	 * @param {string} previous
	 */
	function feedStat(stat, current, previous) {

		// Abort if it is not supported.
		var $el = $('.getpaid-report-cards .card.' + stat);
		if ($el.length == 0) {
			return
		}

		// Fill in card revenue.
		if (!window.Intl || ['total_invoices', 'total_items', 'refunded_items'].indexOf(stat) > -1) {
			$el.find('.getpaid-report-card-value').text(current)
			$el.find('.getpaid-report-card-previous-value').text(previous)
		} else {

			$el.find('.getpaid-report-card-value').text(getpaid.currency.format(current))
			$el.find('.getpaid-report-card-previous-value').text(getpaid.currency.format(previous))

		}

		// Fill in growth.
		var percentage = (current == 0 || previous == 0) ? '' : '%';
		if (current > previous) {
			var growth = (current - previous) * 100 / previous;
			$el.find('.getpaid-report-card-growth')
				.addClass('text-success')
				.html('<i class="fas fa-arrow-up fa-sm pr-1"></i>' + parseFloat(growth).toFixed(2) + percentage)

		} else if (current < previous) {
			var loss = (current - previous) * 100 / previous;
			$el.find('.getpaid-report-card-growth')
				.addClass('text-danger')
				.html('<i class="fas fa-arrow-down fa-sm pr-1"></i>' + parseFloat(loss).toFixed(2) + percentage)

		}
	}

	/**
	 * Draws a graph.
	 * @param {string} stat
	 * @param {object} current
	 * @param {object} previous
	 */
	function drawGraph(stat, current, previous) {

		// Abort if it is not supported.
		if ($('#getpaid-chartjs-' + stat).length == 0) {
			return
		}

		var labels = []
		var previous_dataset = []
		var current_dataset = []
		var ctx = document.getElementById('getpaid-chartjs-' + stat).getContext('2d');

		for (var date in current[0]['totals']) {
			if (current[0]['totals'].hasOwnProperty(date)) {
				labels.push(date)
				current_dataset.push(current[0]['totals'][date][stat])
			}
		}

		for (var date in previous[0]['totals']) {
			if (previous[0]['totals'].hasOwnProperty(date)) {
				previous_dataset.push(previous[0]['totals'][date][stat])
			}
		}

		var _radius = current[0].interval > 30 ? 0 : 3
		_radius = current[0].interval < 5 ? 10 : _radius

		new Chart(
			ctx,
			{
				type: 'line',
				data: {
					'labels': labels,
					'datasets': [
						{
							label: $('#getpaid-chartjs-' + stat).closest('.card').find('.card-header strong').text(),
							data: current_dataset,
							backgroundColor: current[0].interval > 30 ? 'rgba(255, 255, 255, 0)' : 'rgba(54, 162, 235, 0.1)',
							borderColor: 'rgb(54, 162, 235)',
							pointBackgroundColor: 'rgb(54, 162, 235)',
							pointHoverBackgroundColor: 'rgba(54, 162, 235, 0.4 )',
							pointRadius: _radius,
							pointHoverRadius: 15,
							lineTension: current[0].interval > 30 ? 0.1 : 0.4,
							borderWidth: current[0].interval > 30 ? 2 : 4,
						},
						{
							label: 'Previous Period',
							data: previous_dataset,
							backgroundColor: 'rgba(255, 255, 255, 0)',
							borderColor: 'rgb(77, 201, 246 )',
							pointBackgroundColor: 'rgb(77, 201, 246 )',
							pointHoverBackgroundColor: 'rgba(77, 201, 246, 0.4 )',
							pointRadius: _radius,
							pointHoverRadius: 15,
							lineTension: current[0].interval > 30 ? 0.1 : 0.4,
							borderWidth: current[0].interval > 30 ? 2 : 4,
						}
					]
				},
				options: {
					tooltips: {
						mode: 'index',
						intersect: true,
						callbacks: {
							label: function (tooltipItem, data) {
								var label = data.datasets[tooltipItem.datasetIndex].label || '';
								var value = tooltipItem.yLabel

								if (label) {
									label += ': ';
								}

								if (window.Intl && ['invoices', 'items'].indexOf(stat) == -1) {
									value = getpaid.currency.format(value);
								}

								label += value;
								return label;
							}
						}
					},
					scales: {
						yAxes: [{
							ticks: {
								beginAtZero: true,
								callback: function (value, index, values) {

									if (!window.Intl || ['invoices', 'items'].indexOf(stat) > -1) {
										return value
									} else {
										return getpaid.currency.format(value)
									}

								}
							}
						}],
						xAxes: [{
							ticks: {
								maxTicksLimit: 12,

							}
						}]
					},
					legend: {
						display: true,
						position: 'bottom',
						labels: {
							generateLabels: function (chart) {

								var datasets = chart.data.datasets;
								var labels = chart.legend.options.labels;
								var usePointStyle = labels.usePointStyle;

								return chart._getSortedDatasetMetas().map(function (meta) {

									var style = meta.controller.getStyle(usePointStyle ? 0 : undefined);
									var total = datasets[meta.index].data.reduce(function (total, num) { return total + num }, 0);

									if (window.Intl && ['invoices', 'items'].indexOf(stat) == -1) {
										total = getpaid.currency.format(total)
									}

									return {
										text: datasets[meta.index].label + " : " + total,
										fillStyle: style.backgroundColor,
										hidden: false,
										lineCap: style.borderCapStyle,
										lineDash: style.borderDash,
										lineDashOffset: style.borderDashOffset,
										lineJoin: style.borderJoinStyle,
										lineWidth: style.borderWidth,
										strokeStyle: style.borderColor,
										pointStyle: style.pointStyle,
										rotation: style.rotation,
										datasetIndex: meta.index
									};

								}, this);
							},

						},
					}
				}

			}
		);
	}

	// Handle reports.
	if ($('.row.getpaid-report-cards').length) {

		// Period selects.
		$('.getpaid-filter-earnings select').on(
			'change', function () {

				if ('custom' == $(this).val()) {
					$('.getpaid-date-range-picker').removeClass('d-none')
					$('.getpaid-date-range-viewer').addClass('d-none')
				} else {
					$('.getpaid-date-range-picker').addClass('d-none')
					$('.getpaid-date-range-viewer').removeClass('d-none')
				}

			}
		);

		$('.getpaid-filter-earnings select').trigger('change');

		wpinvBlock('.single-report-card');

		getStats('sales', WPInv_Admin.date_range)
			.done(function (response) {

				// Fill in date ranges.
				$('.getpaid-date-range-picker .getpaid-from').val(response[0].start_date)
				$('.getpaid-date-range-picker .getpaid-to').val(response[0].end_date)

				getStats('sales', response[0].previous_range)
					.done(function (second_response) {

						wpinvUnblock('.single-report-card');

						// Fill in report cards.
						for (var stat in response[0]) {
							if (response[0].hasOwnProperty(stat)) {
								feedStat(stat, response[0][stat], second_response[0][stat])
							}
						}

						// Draw graphs.
						var graphs = ['discount', 'refunds', 'sales', 'tax', 'fees', 'invoices', 'items', 'refunded_fees', 'refunded_items', 'refunded_subtotal', 'refunded_tax']
						for (var i = 0; i < graphs.length; i++) {
							drawGraph(graphs[i], response, second_response)
						}

					});
			});

	}

});

function wpinvBlock(el, message) {
    message = typeof message != 'undefined' && message !== '' ? message : WPInv_Admin.loading;
    var $el = jQuery( el )

    // Do not block twice.
    if ( 1 != $el.data( 'GetPaidIsBlocked' ) ) {
        $el.data( 'GetPaidIsBlocked', 1 )
        $el.data( 'GetPaidWasRelative', $el.hasClass( 'position-relative' ) )
        $el.addClass( 'position-relative' )
        $el.append( '<div class="w-100 h-100 position-absolute bg-light d-flex justify-content-center align-items-center getpaid-block-ui" style="top: 0; left: 0; opacity: 0.7; cursor: progress;"><div class="spinner-border" role="status"><span class="sr-only">' + message +'</span></div></div>' )
    }

}

function wpinvUnblock(el) {
    var $el = jQuery( el )

    if ( 1 == $el.data( 'GetPaidIsBlocked' ) ) {
        $el.data( 'GetPaidIsBlocked', 0 )

        if ( ! $el.data( 'GetPaidWasRelative') ) {
            $el.removeClass( 'position-relative' )
        }

        $el.children( '.getpaid-block-ui' ).remove()

    }

}
