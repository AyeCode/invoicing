/**
 * GetPaid Item Variations Admin
 *
 * @package GetPaid
 */

(function ($, GetPaid_Item_Variations) {
	'use strict';

	/**
	 * Current variation index counter.
	 *
	 * @type {number}
	 */
	var variationIndex = 0;

	/**
	 * Initializes the module.
	 */
	GetPaid_Item_Variations.init = function () {
		this.initIndex();
		this.initSortable();
		this.toggleEmpty();
		this.syncBodyClass();
		this.syncDownloadFiles();
		this.bindEvents();
	};

	/**
	 * Reads the highest existing card index and sets the counter.
	 */
	GetPaid_Item_Variations.initIndex = function () {
		$('#getpaid-variations-list .getpaid-variation-card').each(function () {
			var idx = parseInt($(this).data('index'), 10);

			if (!isNaN(idx) && idx >= variationIndex) {
				variationIndex = idx + 1;
			}
		});
	};

	/**
	 * Makes the variation cards sortable via drag handle.
	 */
	GetPaid_Item_Variations.initSortable = function () {
		$('#getpaid-variations-list').sortable({
			handle: '.getpaid-pkg-sort-handle',
			axis: 'y',
			placeholder: 'getpaid-variation-card-placeholder mb-2',
			tolerance: 'pointer',
		});
	};

	/**
	 * Binds all event handlers.
	 */
	GetPaid_Item_Variations.bindEvents = function () {
		var self = this;

		// Toggle.
		$(document).on('change', '#_wpinv_has_variations', function () {
			self.toggleVariationsBox.call(this);
			self.syncBodyClass();
		});

		// Card actions.
		$(document).on('click', '#getpaid-add-variation', $.proxy(this.addVariation, this));
		$(document).on('click', '.getpaid-remove-variation', $.proxy(this.removeVariation, this));
		$(document).on('blur', '.getpaid-pkg-name', this.autoSlugify);
		$(document).on('change', '.getpaid-pkg-recurring-toggle', this.toggleRecurring);
		$(document).on('change', '.getpaid-pkg-license-toggle', this.toggleLicense);

		// Download file sync.
		$(document).on('input change', '#getpaid_item_downloads .getpaid-file-name, #getpaid_item_downloads .getpaid-file-url', $.proxy(this.syncDownloadFiles, this));
		$(document).on('click', '.getpaid-add-download-file, .wpinv-remove-downloadable-file', function () {
			setTimeout($.proxy(self.syncDownloadFiles, self), 200);
		});
		$(document).on('click', '#getpaid_item_downloads .upload_file_button', function () {
			var attempts = 0;
			var poll = setInterval(function () {
				self.syncDownloadFiles();
				if (++attempts >= 10) {
					clearInterval(poll);
				}
			}, 500);
		});

		// Scroll to downloads.
		$(document).on('click', '.getpaid-scroll-to-downloads', function (e) {
			e.preventDefault();
			self.scrollToDownloads();
		});

		// Validation.
		$('#post').on('submit', $.proxy(this.validate, this));
		$(document).on('input', '.getpaid-pkg-name', this.clearNameError);
	};

	/**
	 * Shows or hides the empty state placeholder.
	 */
	GetPaid_Item_Variations.toggleEmpty = function () {
		var hasCards = $('#getpaid-variations-list .getpaid-variation-card').length > 0;
		$('#getpaid-variations-empty').toggleClass('d-none', hasCards);
	};

	/**
	 * Toggles body classes and hides/shows the item price row.
	 */
	GetPaid_Item_Variations.syncBodyClass = function () {
		var enabled = $('#_wpinv_has_variations').is(':checked');

		$('body').toggleClass('getpaid_has_variations', enabled);
		$('body').toggleClass('getpaid_has_no_variations', !enabled);
		$('#wpinv_item_price').closest('.form-group').toggle(!enabled);
		$('.wpinv_maximum_renewals, .wpinv_minimum_price').toggle(!enabled);
	};

	/**
	 * Toggles the variations box visibility. Called with checkbox element as `this`.
	 */
	GetPaid_Item_Variations.toggleVariationsBox = function () {
		var box = $('.getpaid-variations-box');

		if ($(this).is(':checked')) {
			box.removeClass('collapse');
		} else {
			box.addClass('collapse');
		}
	};

	/**
	 * Adds a new variation card from the template.
	 */
	GetPaid_Item_Variations.addVariation = function () {
		var template = $('#getpaid-variation-card-template').html();
		var card = template.replace(/__INDEX__/g, variationIndex);

		$('#getpaid-variations-list').append(card);

		if ($('#getpaid-variations-list .getpaid-variation-card').length === 1) {
			$('#getpaid-variations-list .getpaid-variation-card:first input[name="_wpinv_variation_default"]').prop('checked', true);
		}

		$('#getpaid-variations-list .getpaid-variation-card:last .getpaid-pkg-name').trigger('focus');

		variationIndex++;
		this.toggleEmpty();
		this.syncDownloadFiles();
	};

	/**
	 * Removes a variation card with confirmation.
	 *
	 * @param {Event} e Click event.
	 */
	GetPaid_Item_Variations.removeVariation = function (e) {
		var card = $(e.target).closest('.getpaid-variation-card');
		var self = this;

		if (typeof aui_confirm === 'function') {
			aui_confirm(
				GetPaid_Item_Variations.i18n.confirmRemove,
				GetPaid_Item_Variations.i18n.confirmDelete,
				GetPaid_Item_Variations.i18n.confirmCancel,
				true
			).then(function (confirmed) {
				if (confirmed) {
					self.doRemoveVariation(card);
				}
			});
		} else if (confirm(GetPaid_Item_Variations.i18n.confirmRemove)) {
			this.doRemoveVariation(card);
		}
	};

	/**
	 * Performs the actual card removal.
	 *
	 * @param {jQuery} card The card element to remove.
	 */
	GetPaid_Item_Variations.doRemoveVariation = function (card) {
		var wasDefault = card.find('input[name="_wpinv_variation_default"]').is(':checked');
		card.remove();

		if (wasDefault) {
			$('#getpaid-variations-list .getpaid-variation-card:first input[name="_wpinv_variation_default"]').prop('checked', true);
		}

		this.toggleEmpty();
	};

	/**
	 * Auto-generates the variation slug from the name on blur.
	 * Called with the name input as `this`.
	 */
	GetPaid_Item_Variations.autoSlugify = function () {
		var card = $(this).closest('.getpaid-variation-card');
		var idField = card.find('.getpaid-pkg-id');

		if ('' === idField.val()) {
			idField.val(
				$(this).val()
					.toLowerCase()
					.replace(/[^a-z0-9]+/g, '-')
					.replace(/^-+|-+$/g, '')
			);
		}
	};

	/**
	 * Toggles recurring billing fields visibility.
	 * Called with the checkbox as `this`.
	 */
	GetPaid_Item_Variations.toggleRecurring = function () {
		var $card       = $(this).closest('.getpaid-variation-card');
		var isRecurring = $(this).is(':checked');

		$card.find('.getpaid-pkg-recurring-fields').toggleClass('d-none', !isRecurring);
		$card.find('.getpaid-pkg-license-duration-input').prop('disabled', isRecurring);
	};

	/**
	 * Toggles license fields visibility.
	 * Called with the checkbox as `this`.
	 */
	GetPaid_Item_Variations.toggleLicense = function () {
		$(this).closest('.getpaid-variation-card')
			.find('.getpaid-pkg-license-fields')
			.toggleClass('d-none', !$(this).is(':checked'));
	};

	/**
	 * Collects download file data from the downloads meta box.
	 *
	 * @return {Array} Array of { id, name } objects.
	 */
	GetPaid_Item_Variations.getDownloadFiles = function () {
		var files = [];

		$('#getpaid_item_downloads tbody tr').each(function () {
			var $row = $(this);
			var name = $.trim($row.find('.getpaid-file-name').val() || '');
			var url = $.trim($row.find('.getpaid-file-url').val() || '');
			var nameAttr = $row.find('.getpaid-file-name').attr('name') || '';
			var match = nameAttr.match(/\[([^\]]+)\]/);
			var fileId = match ? match[1] : '';

			if (fileId && (name || url)) {
				files.push({ id: fileId, name: name || url });
			}
		});

		return files;
	};

	/**
	 * Syncs download file checkboxes in each variation card from the downloads table.
	 */
	GetPaid_Item_Variations.syncDownloadFiles = function () {
		var files = this.getDownloadFiles();

		$('#getpaid-variations-list .getpaid-variation-card').each(function () {
			var $card = $(this);
			var $row = $card.find('.getpaid-pkg-downloads-row');
			var $list = $row.find('.getpaid-pkg-downloads-list');
			var index = $card.data('index');

			if (!$row.length) {
				return;
			}

			var selectedIds = [];
			var dataSelected = $row.data('selected');

			if (dataSelected && Array.isArray(dataSelected) && dataSelected.length > 0) {
				selectedIds = dataSelected.map(String);
			} else {
				$list.find('input[type="checkbox"]:checked').each(function () {
					selectedIds.push(String($(this).val()));
				});
			}

			$list.empty();

			if (files.length === 0) {
				$list.html('<span class="getpaid-pkg-downloads-empty text-muted">' + GetPaid_Item_Variations.i18n.noFiles + '</span>');
				return;
			}

			var inputName = '_wpinv_item_variations[' + index + '][download_ids][]';

			$.each(files, function (_, file) {
				var isChecked = selectedIds.length === 0 || selectedIds.indexOf(String(file.id)) !== -1;

				var $label = $('<label class="d-flex align-items-center gap-1 mb-0 c-pointer"></label>');
				var $cb = $('<input type="checkbox">')
					.attr('name', inputName)
					.val(file.id)
					.prop('checked', isChecked);

				$label.append($cb).append(' ' + $('<span/>').text(file.name).html());
				$list.append($label);
			});

			$row.removeData('selected').removeAttr('data-selected');
		});
	};

	/**
	 * Scrolls to and highlights the downloads meta box.
	 */
	GetPaid_Item_Variations.scrollToDownloads = function () {
		var $target = $('#getpaid_item_downloads');

		if ($target.length) {
			$('html, body').animate({ scrollTop: $target.offset().top - 50 }, 300);
			$target.css('outline', '2px solid #2271b1');
			setTimeout(function () { $target.css('outline', ''); }, 1500);
		}
	};

	/**
	 * Validates variation names before form submission.
	 *
	 * @param {Event} e Submit event.
	 */
	GetPaid_Item_Variations.validate = function (e) {
		if (!$('#_wpinv_has_variations').is(':checked')) {
			return;
		}

		var $cards = $('#getpaid-variations-list .getpaid-variation-card');

		if (!$cards.length) {
			return;
		}

		var hasError = false;

		$cards.find('.getpaid-pkg-name').css('border-color', '');
		$('.getpaid-pkg-validation-error').remove();

		$cards.each(function () {
			var $name = $(this).find('.getpaid-pkg-name');

			if (!$.trim($name.val())) {
				hasError = true;
				$name.css('border-color', '#d63638');
			}
		});

		if (hasError) {
			e.preventDefault();

			$('.getpaid-item-variations-wrap').prepend(
				'<div class="notice notice-error getpaid-pkg-validation-error"><p>' +
				GetPaid_Item_Variations.i18n.nameRequired +
				'</p></div>'
			);

			$('html, body').animate({
				scrollTop: $('.getpaid-item-variations-wrap').offset().top - 50,
			}, 300);
		}
	};

	/**
	 * Clears the name field error highlight on input.
	 * Called with the name input as `this`.
	 */
	GetPaid_Item_Variations.clearNameError = function () {
		if ($.trim($(this).val())) {
			$(this).css('border-color', '');
		}
	};

	$(function () {
		GetPaid_Item_Variations.init();
	});

}(jQuery, getpaidItemVariations));
