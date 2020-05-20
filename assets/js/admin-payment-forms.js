"use strict";

jQuery(function ($) {

	// Init our vue app
	new Vue({

		el: '#wpinv-form-builder',

		data: $.extend(true, {
			active_tab: 'new_item',
			active_form_element: null,
			last_dropped: null,
			selected_item: ''
		}, wpinvPaymentFormAdmin),

		computed: {

			totalPrice: function totalPrice() {
				var price = this.form_items.reduce(function (combined, item) {
					return combined + parseFloat(item.price);
				}, 0);
				return this.formatPrice(price);
			},

			elementString: function elementString() {
				return JSON.stringify(this.form_elements);
			},

			itemString: function itemString() {
				return JSON.stringify(this.form_items);
			},

			hasRecurring: function hasRecurring() {

				for( var i = 0; i < this.form_items.length; i++ ) {
					if ( this.form_items[i].recurring ) {
						return true;
					}
				}

				return false;
			},

			hasRequiredItem: function hasRequiredItem() {

				for( var i = 0; i < this.form_items.length; i++ ) {
					if ( this.form_items[i].required ) {
						return true;
					}
				}

				return false;
			}

		},

		methods: {

			canCheckoutSeveralSubscriptions: function canCheckoutSeveralSubscriptions( field ) {

				// Has no recurring item or only has one item.
				if ( ! this.hasRecurring || 2 > this.form_items.length ) {
					return false;
				}

				// Check if users can buy more than 1 item.
				if ( 'total' == field.items_type || 'checkbox' == field.items_type || 'multi_select' == field.items_type ) {
					return true;
				}

				// Check if we have a required item.
				return this.hasRequiredItem
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
			addSelectedItem: function addSelectedItem() {

				if ( this.selected_item && this.all_items[this.selected_item].id ) {

					var exists = false
					var selected_item = this.all_items[this.selected_item]

					$( this.form_items ).each( function( index, item ) {

						if ( item.id && item.id == selected_item.id ) {
							exists = true
						}

					} )

					if ( ! exists ) {
						this.form_items.push( selected_item );
					}

				}

				this.selected_item = ''

			},

			// Adds a currency to a price.
			addNewItem: function addNewItem() {
				var rand = Math.random() + this.form_items.length;
				var key = rand.toString(36).replace(/[^a-z]+/g, '');
				this.form_items.push({
					title: "New item",
					id: key,
					price: '0.00',
					recurring: false,
					new: true,
					type: 'custom',
					description: ''
				});
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
			toggleAddressPanel: function togglePanel( container_id, field_class) {

				var parent = $( '#' + container_id + ' .' + field_class );

				parent.find('.wpinv-form-address-field-editor-editor-body').slideToggle(400 ); // Toggle the active class
				parent.toggleClass('active'); // Toggle dashicons

				parent.find('.wpinv-available-items-editor-header > .toggle-icon .dashicons-arrow-down').toggle();
				parent.find('.wpinv-available-items-editor-header > .toggle-icon .dashicons-arrow-up').toggle();
			}

		}

	});

});
