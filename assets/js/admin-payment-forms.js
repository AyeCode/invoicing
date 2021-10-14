"use strict";

jQuery(function ($) {

	// Select 2.
	Vue.component('gpselect2', {
		props: ['value'],
		template: '#gpselect2-template',
		mounted: function () {
			var vm = this
			$(this.$el)
				// init select2
				.select2()
				.val(this.value)
				.trigger('change.select2')
				// emit event on change.
				.on('change', function ( e ) {
					vm.$emit('input',jQuery(e.currentTarget).val() )
				})
		},

		watch: {
			value : function (value)  {
				// update value
				jQuery(this.$el).val(value).trigger('change.select2')
			},
		},
	
		destroyed: function () {
			$(this.$el).off().select2('destroy')
		}
	})

	// Init our vue app
	window.getpaid_form_builder = new Vue({

		el: '#wpinv-form-builder',

		data: wp.hooks.applyFilters(
			'getpaid_form_builder_data',
			$.extend(
				true,
				{
					active_tab: 'new_item',
					active_form_element: null,
					last_dropped: null,
				},
				wpinvPaymentFormAdmin
			)
		),

		computed: wp.hooks.applyFilters(
			'getpaid_form_builder_computed',
			{

				elementString: function elementString() {
					return JSON.stringify(this.form_elements);
				},

				itemString: function itemString() {
					return JSON.stringify(this.form_items);
				},

				gridWidth: {

					get: function get() {

						if (this.active_form_element.grid_width) {
							return this.active_form_element.grid_width;
						}

						return 'full';
					},

					set: function set(grid_width) {
						this.$set(this.active_form_element, 'grid_width', grid_width)
					}

				},

			}
		),

		methods: wp.hooks.applyFilters(
			'getpaid_form_builder_methods',
			{

				// Returns the grid width class
				grid_class: function grid_class(field) {

					var grid_class = 'col-12'

					if ('half' == field.grid_width) {
						grid_class = 'col-12 col-md-6';
					}

					if ('third' == field.grid_width) {
						grid_class = 'col-12 col-md-4';
					}

					return grid_class
				},

				// Returns an array of visible fields.
				visible_fields: function visible_fields(fields) {
					return fields.filter(function (field) {
						return field.visible
					});
				},

				// Highlights a field for editing.
				highlightField: function highlightField(field) {
					this.active_tab = 'edit_item';
					this.active_form_element = field;
					return field;
				},

				// Returns the data for a new field.
				getNewFieldData: function getNewFieldData(field) {
					// Let's generate a unique string to use as the field key.
					var rand = Math.random() + this.form_elements.length;
					var key = rand.toString(36).replace(/[^a-z]+/g, '');
					var new_field = $.extend(true, {}, field.defaults);
					new_field.id = key;
					new_field.name = key;
					new_field.type = field.type;
					return new_field;
				},

				// Adds a field that has been dragged to the list of fields.
				addDraggedField: function addDraggedField(field) {
					this.last_dropped = this.getNewFieldData(field);
					return this.last_dropped;
				},

				// Pushes a field to the list of fields.
				addField: function addField(field) {
					this.form_elements.push(this.highlightField(this.getNewFieldData(field)));
				},

				// Highlights the last dropped field.
				highlightLastDroppedField: function highlightLastDroppedField() {
					this.highlightField(this.last_dropped);
				},

				// Deletes a field.
				removeField: function removeField(field) {
					var index = this.form_elements.indexOf(field);

					if (index > -1) {
						this.form_elements.splice(index, 1);
						this.active_tab = 'new_item';
						this.active_form_element = null;
					}
				},

				// Deletes an item.
				removeItem: function removeItem(item) {
					var index = this.form_items.indexOf(item);
					var items = this.form_items;

					if (index > -1) {
						$('.wpinv-available-items-editor.item_' + item.id).find('.wpinv-available-items-editor-body').slideToggle(400);
						$('.wpinv-available-items-editor.item_' + item.id).fadeOut(420, function () {
							items.splice(index, 1);
						});
					}
				},

				// Formats a price.
				formatPrice: function formatPrice(price) {
					var formated = parseFloat(price);

					if (isNaN(formated)) {
						formated = 0;
					}

					return this.addCurrency(this.addCommas(this.addDecimals(formated)));
				},

				// Adds decimals to a price.
				addDecimals: function addDecimals(price) {
					var decimals = price.toFixed(this.decimals) + '';
					return decimals.replace('.', this.decimals_sep);
				},

				// Adds commas to a price.
				addCommas: function addCommas(price) {
					var parts = price.toString().split(this.decimals_sep);
					parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousands_sep);
					return parts.join(this.decimals_sep);
				},

				// Adds a currency to a price.
				addCurrency: function addCurrency(price) {
					if ('left' == this.position) {
						return this.currency + '' + price;
					}

					return price + '' + this.currency;
				},

				// Adds a currency to a price.
				addSelectedItem: function addSelectedItem(event) {

					var select = $(event.target).parent().find('select')
					var selected_item = $(select).select2('data')[0]

					// Abort if no item was selected.
					if (!selected_item.form_data) {
						return
					}

					// Only add the item if it was not previously added.
					var exists = false
					selected_item = selected_item.form_data

					$(this.form_items).each(function (index, item) {

						if (item.id && item.id == selected_item.id) {
							exists = true
						}

					})

					if (!exists) {
						this.form_items.push(selected_item);
					}

					$(select)
						.val('')
						.trigger("change");

				},

				// Given a panel id( field key ), it toggles the panel.
				togglePanel: function togglePanel(id) {
					var parent = $('.wpinv-available-items-editor.item_' + id); // Toggle the body.

					parent.find('.wpinv-available-items-editor-body').slideToggle(400, function () {
						// Scroll to the first field.
						$('html, body').animate({//scrollTop: parent.offset().top
						}, 1000);
					}); // Toggle the active class

					parent.toggleClass('active'); // Toggle dashicons

					parent.find('.wpinv-available-items-editor-header > .toggle-icon .dashicons-arrow-down').toggle();
					parent.find('.wpinv-available-items-editor-header > .toggle-icon .dashicons-arrow-up').toggle();
				},

				// Toggles an address panel.
				toggleAddressPanel: function togglePanel(event) {

					var parent = $(event.target).closest('.wpinv-form-address-field-editor')

					parent.find('.wpinv-form-address-field-editor-editor-body').slideToggle(400); // Toggle the active class
					parent.toggleClass('active'); // Toggle dashicons

					parent.find('.wpinv-available-items-editor-header > .toggle-icon .dashicons-arrow-down').toggle();
					parent.find('.wpinv-available-items-editor-header > .toggle-icon .dashicons-arrow-up').toggle();
				}

			}
		),

		filters: wp.hooks.applyFilters(
			'getpaid_form_builder_filters',
			{
				optionize: function (value) {
					if (!value) return ''

					value = value.toString().split('|').splice(0, 1).join('')
					return value.toString().trim()
				},
				formatMergeTag: function (value) {
					if (!value) return ''

					return '{' + value.toString().trim().toLowerCase().replace(/[^a-z0-9]+/g, '_') + '}'
				}
			}
		),

		directives: wp.hooks.applyFilters(
			'getpaid_form_builder_directives',
			{
				initItemSearch: {

					// directive definition
					inserted: function (el) {
						getpaid.init_select2_item_search(el, $(el).parent())

						// emit event on change.
						$(el).on('change', function () {
							$(el).trigger('itemselected')
						});
					}

				}
			}
		),

	});

	// Remove the delete button on default forms.
	$(document).ready(function () {

		if (wpinvPaymentFormAdmin && wpinvPaymentFormAdmin.is_default) {
			$('#minor-publishing').hide()
			$('#delete-action').hide()
			$('#wpinv-payment-form-shortcode').hide()
		}

		$('.post-type-wpi_payment_form #visibility').hide()
		$('.post-type-wpi_payment_form .misc-pub-curtime').hide()
	})

});
