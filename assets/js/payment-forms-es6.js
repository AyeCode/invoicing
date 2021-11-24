jQuery(function ($) {

	/**
	 * Simple throttle function
	 * @param function callback The callback function
	 * @param int limit The number of milliseconds to wait for
	 */
	function gp_throttle(callback, limit) {

		// Ensure we have a limit.
		if (!limit) {
			limit = 200
		}

		// Initially, we're not waiting
		var wait = false;

		// Ensure that the last call was handled
		var did_last = true;

		// We return a throttled function
		return function () {

			// If we're not waiting
			if (!wait) {

				// We did the last action.
				did_last = true;

				// Execute users function
				callback.bind(this).call();

				// Prevent future invocations
				wait = true;

				// For a period of time...
				setTimeout(function () {

					// then allow future invocations
					wait = false;

				}, limit);

				// If we're waiting...
			} else {

				// We did not do the last action.
				did_last = false;

				// Wait for a period of time...
				var that = this
				setTimeout(function () {

					// then ensure that we did the last call.
					if (!did_last) {
						callback.bind(that).call();
						did_last = true
					}

				}, limit);

			}

		}
	}

	// Pass in a form to attach event listeners.
	window.getpaid_form = function (form) {

		return {

			// Cache states to reduce server requests.
			fetched_initial_state: 0,

			// Cache states to reduce server requests.
			cached_states: {},

			// The current form.
			form,

			// Alerts the user whenever an error occurs.
			show_error(error, container) {

				form.find('.getpaid-payment-form-errors, .getpaid-custom-payment-form-errors').html('').addClass('d-none')

				// Display the error.
				if (container && form.find(container).length) {
					form.find(container).html(error).removeClass('d-none');
					form.find(container).closest('.getpaid-address-field-wrapper').find('form-control').addClass('is-invalid');
				} else {
					form.find('.getpaid-payment-form-errors').html(error).removeClass('d-none');

					form.find('.getpaid-custom-payment-form-errors').each(
						function () {
							var form_control = $(this).closest('.getpaid-address-field-wrapper').find('form-control');

							if (form_control.val() != '') {
								form_control.addClass('is-valid');
							}
						}
					)
				}

			},

			// Hides the current error.
			hide_error() {

				// Hide the error
				form.find('.getpaid-payment-form-errors, .getpaid-custom-payment-form-errors').html('').addClass('d-none')
				form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid')
			},

			// Caches a state.
			cache_state(key, state) {
				this.cached_states[key] = state
			},

			// Returns the current cache key.
			current_state_key() {
				return this.form.serialize()
			},

			// Checks if the current state is cached.
			is_current_state_cached() {
				return this.cached_states.hasOwnProperty(this.current_state_key())
			},

			// Switches to a given form state.
			switch_state() {

				// Hide any errors.
				this.hide_error()

				// Retrieve form state.
				var state = this.cached_states[this.current_state_key()]

				if (!state) {
					return this.fetch_state()
				}

				// Process totals.
				if (state.totals) {

					for (var total in state.totals) {
						if (state.totals.hasOwnProperty(total)) {
							this.form.find('.getpaid-form-cart-totals-total-' + total).html(state.totals[total])
						}
					}

				}

				// Hide/Display fees discount.
				if (!Array.isArray(state.fees)) {
					this.form.find('.getpaid-form-cart-totals-fees').removeClass('d-none')
				} else {
					this.form.find('.getpaid-form-cart-totals-fees').addClass('d-none')
				}

				if (!Array.isArray(state.discounts)) {
					this.form.find('.getpaid-form-cart-totals-discount').removeClass('d-none')
				} else {
					this.form.find('.getpaid-form-cart-totals-discount').addClass('d-none')
				}

				// Process item sub-totals.
				if (state.items) {

					for (var item in state.items) {
						if (state.items.hasOwnProperty(item)) {
							this.form.find('.getpaid-form-cart-item-subtotal-' + item).html(state.items[item])
						}
					}

				}

				// Process text updates.
				if (state.texts) {

					for (var selector in state.texts) {
						if (state.texts.hasOwnProperty(selector)) {
							this.form.find(selector).html(state.texts[selector])
						}
					}

				}

				// Hide/Display Gateways.
				if (state.gateways) {
					this.process_gateways(state.gateways, state)
				}

				// Misc data.
				if (state.js_data) {
					this.form.data('getpaid_js_data', state.js_data)
				}

				this.setup_saved_payment_tokens()

				this.form.trigger('getpaid_payment_form_changed_state', [state]);

			},

			// Refreshes the state either from cache or from the server.
			refresh_state() {

				// If we have the state in the cache...
				if (this.is_current_state_cached()) {
					return this.switch_state()
				}

				// ... else, fetch from the server.
				this.fetch_state()
			},

			// Fetch a state from the server, and applies it to the form.
			fetch_state() {

				// Block the form.
				wpinvBlock(this.form);

				// Return a promise.
				var key = this.current_state_key()
				return $.post(WPInv.ajax_url, key + '&action=wpinv_payment_form_refresh_prices&_ajax_nonce=' + WPInv.formNonce + '&initial_state=' + this.fetched_initial_state)

					.done((res) => {

						// If successful, cache the prices.
						if (res.success) {
							this.fetched_initial_state = 1
							this.cache_state(key, res.data)
							return this.switch_state()
						}

						if (res.success === false) {
							this.show_error(res.data.error, res.data.code);
							return;
						}

						// Else, display an error.
						this.show_error(res)
					})

					// Connection error.
					.fail(() => {
						this.show_error(WPInv.connectionError)
					})

					// Unblock the form.
					.always(() => {
						wpinvUnblock(this.form);
					})

			},

			// Updates the state field.
			update_state_field(wrapper) {

				wrapper = $(wrapper)

				// Ensure that we have a state field.
				if (wrapper.find('.wpinv_state').length) {

					var state = wrapper.find('.getpaid-address-field-wrapper__state')

					wpinvBlock(state);

					var data = {
						action: 'wpinv_get_payment_form_states_field',
						country: wrapper.find('.wpinv_country').val(),
						form: this.form.find('input[name="form_id"]').val(),
						name: state.find('.wpinv_state').attr('name'),
						_ajax_nonce: WPInv.formNonce
					};

					$.get(WPInv.ajax_url, data, (res) => {

						if ('object' == typeof res) {
							state.replaceWith(res.data)
						}

					})

						.always(() => {
							wpinvUnblock(wrapper.find('.getpaid-address-field-wrapper__state'))
						});

				}

			},

			// Attaches events to a form.
			attach_events() {

				// Cache the object.
				var that = this

				// Keeps the state in sync.
				var on_field_change = gp_throttle(
					function () { that.refresh_state() },
					500
				)

				// Refresh prices.
				this.form.on('change', '.getpaid-refresh-on-change', on_field_change);
				this.form.on('input', '.getpaid-payment-form-element-price_select :input:not(.getpaid-refresh-on-change)', on_field_change);
				this.form.on('change', '.getpaid-payment-form-element-currency_select :input:not(.getpaid-refresh-on-change)', on_field_change);
				this.form.on('change', '.getpaid-item-quantity-input', on_field_change);
				this.form.on('change', '[name="getpaid-payment-form-selected-item"]', on_field_change);

				this.form.on('change', '.getpaid-item-mobile-quantity-input', function() {
					let input = $( this );
					input
						.closest( '.getpaid-payment-form-items-cart-item' )
						.find('.getpaid-item-quantity-input')
						.val( input.val() )
						.trigger( 'change' );
				});

				this.form.on('change', '.getpaid-item-quantity-input', function() {
					let input = $( this );
					input
						.closest( '.getpaid-payment-form-items-cart-item' )
						.find('.getpaid-item-mobile-quantity-input')
						.val( input.val() );
				});

				// Refresh when price changes.
				this.form.on('change', '.getpaid-item-price-input', function () {
					if (!$(this).hasClass('is-invalid')) {
						on_field_change()
					}
				});

				// Refresh prices when hitting enter key.
				this.form.on(
					'keypress',
					'.getpaid-refresh-on-change, .getpaid-payment-form-element-price_select :input:not(.getpaid-refresh-on-change), .getpaid-item-quantity-input, .getpaid-item-price-input',
					function (e) {
						if (e.keyCode == '13') {
							e.preventDefault();
							on_field_change()
						}
					}
				);

				// Update states when country changes.
				this.form.on('change', '.getpaid-shipping-address-wrapper .wpinv_country', () => {
					this.update_state_field('.getpaid-shipping-address-wrapper')
				});

				// Refresh when country changes.
				this.form.on('change', '.getpaid-billing-address-wrapper .wpinv_country', () => {
					this.update_state_field('.getpaid-billing-address-wrapper')

					if (this.form.find('.getpaid-billing-address-wrapper .wpinv_country').val() != this.form.find('.getpaid-billing-address-wrapper .wpinv_country').data('ipCountry')) {
						this.form.find('.getpaid-address-field-wrapper__address-confirm').removeClass('d-none')
					} else {
						this.form.find('.getpaid-address-field-wrapper__address-confirm').addClass('d-none')
					}

					on_field_change()
				});

				// Refresh when state changes.
				this.form.on('change', '.getpaid-billing-address-wrapper .wpinv_state, .getpaid-billing-address-wrapper .wpinv_vat_number', () => {
					on_field_change()
				});

				// VAT.
				this.form.on('click', '.getpaid-vat-number-validate, [name="confirm-address"]', () => {
					on_field_change()
				});

				this.form.on('change', '.getpaid-billing-address-wrapper .wpinv_vat_number', function () {
					var validator = $(this).parent().find('.getpaid-vat-number-validate')
					validator.text(validator.data('validate'))
				});

				// Discounts.
				if (this.form.find('.getpaid-discount-field').length) {

					// Refresh prices when the discount button is clicked.
					this.form.find('.getpaid-discount-button').on('click', function (e) {
						e.preventDefault();
						on_field_change()
					});

					// Refresh prices when hitting enter key in the discount field.
					this.form.find('.getpaid-discount-field').on('keypress', function (e) {
						if (e.keyCode == '13') {
							e.preventDefault();
							on_field_change()
						}
					});

					// Refresh prices when the discount value changes.
					this.form.find('.getpaid-discount-field').on('change', function (e) {
						on_field_change()
					});

				}

				// Watch for gateway clicks.
				this.form.on('change', '.getpaid-gateway-radio input', () => {
					var gateway = this.form.find('.getpaid-gateway-radio input:checked').val()
					form.find('.getpaid-gateway-description').slideUp();
					form.find(`.getpaid-description-${gateway}`).slideDown();
				});

				// Drap & drop.
				this.form.find('.getpaid-file-upload-element').each(function () {

					// Prepare element containers.
					let dropfield = $(this),
						parent = dropfield.closest( '.form-group' ),
						uploaded_files_div = parent.find( '.getpaid-uploaded-files' ),
						max_files = parseInt( parent.data( 'max' ) );

					// Prepare files and readers.
					let loadedFiles = [],
						xhr;

					// Load a single file.
					let loadFile = function ( file_data ) {

						if ( ! file_data ) {
							return
						}

						// Prepare progress bar.
						let progress_bar = parent.find( '.getpaid-progress-template' ).clone().removeClass( 'd-none getpaid-progress-template' );
						uploaded_files_div.append( progress_bar );

						// Hook the close button.
						progress_bar
							.find( 'a.close' )
							.on( 'click', function( e ) {
								e.preventDefault();

								// Remove from the list of loaded files.
								let index = loadedFiles.indexOf( file_data );
								if ( index > -1 ) {
									loadedFiles = loadedFiles.splice( index, 1 );
								}

								// Remove the element.
								progress_bar.fadeOut( 300, () => { progress_bar.remove() } );

								// Try aborting the ajax request.
								try {
									if ( xhr ) {
										xhr.abort();
									}
								} catch (e) { }

							})


						// Set file name & size.
						progress_bar.find( '.getpaid-progress-file-name' ).text( file_data.name ).attr( 'title', file_data.name );
						progress_bar.find( '.progress-bar' ).attr( 'aria-valuemax', file_data.size );

						// Set correct file icon.
						let icons = {
							'application/pdf'             : '<i class="fas fa-file-pdf"></i>',
							'application/zip'             : '<i class="fas fa-file-archive"></i>',
							'application/x-gzip'          : '<i class="fas fa-file-archive"></i>',
							'application/rar'             : '<i class="fas fa-file-archive"></i>',
							'application/x-7z-compressed' : '<i class="fas fa-file-archive"></i>',
							'application/x-tar'           : '<i class="fas fa-file-archive"></i>',
							audio                         : '<i class="fas fa-file-music"></i>',
							image                         : '<i class="fas fa-file-image"></i>',
							video                         : '<i class="fas fa-file-video"></i>',
							'application/msword'          : '<i class="fas fa-file-word"></i>',
							'application/vnd.ms-excel'    : '<i class="fas fa-file-excel"></i>',
							'application/msword'          : '<i class="fas fa-file-word"></i>',
							'application/vnd.ms-word'     : '<i class="fas fa-file-word"></i>',
							'application/vnd.ms-powerpoint' : '<i class="fas fa-file-powerpoint"></i>',
						}

						if ( file_data.type ) {

							Object.keys(icons).forEach(function (prop) {
								if ( file_data.type.indexOf(prop) !== -1 ) {
									progress_bar.find( '.fa.fa-file' ).replaceWith( icons[prop] )
								}
							});

						}

						// Have we reached max files count?
						if ( ! ( loadedFiles.length < max_files ) ) {
							progress_bar.find( '.getpaid-progress' ).html( '<div class="col-12 alert alert-danger" role="alert">You have exceeded the number of files you can upload.</div>' );
							return;
						}

						// Check file data to prevent submitting unsupported file types.
						let extension = file_data.name.match(/\.([^\.]+)$/)[1],
							extensions = parent.find( '.getpaid-files-input' ).data( 'extensions' );

						if ( extensions.indexOf( extension.toString().toLowerCase() ) < 0 ) {
							progress_bar.find( '.getpaid-progress' ).html( '<div class="col-12 alert alert-danger" role="alert">Unsupported file type.</div>' );
							return;
						}

						let form_data = new FormData();
						form_data.append( 'file', file_data );
						form_data.append( 'action', 'wpinv_file_upload' );
						form_data.append( 'form_id', progress_bar.closest( 'form' ).find('input[name="form_id"]').val() );
						form_data.append( '_ajax_nonce', WPInv.formNonce );
						form_data.append( 'field_name', parent.data( 'name' ) );

						loadedFiles.push( file_data );

						xhr = $.ajax({
							url: WPInv.ajax_url,
							type: 'POST',
							contentType: false,
							processData: false,
							data: form_data,

							xhr: function() {
								let _xhr = new window.XMLHttpRequest();

								_xhr.upload.addEventListener( 'progress', function(e) {

									if (e.lengthComputable) {
										let percentLoaded = Math.round(e.loaded * 100 / e.total) + "%";
										progress_bar
											.find( '.progress-bar' )
											.attr( 'aria-valuenow', e.loaded )
											.css( 'width', percentLoaded )
											.text( percentLoaded )
									}

								}, false);

								return _xhr;
							},

							success: function (response) {

								if ( response.success ) {
									progress_bar.append( response.data );
								} else {
									progress_bar.find( '.getpaid-progress' ).html( '<div class="col-12 alert alert-danger" role="alert">' + response.data + '</div>' );
								}

							},

							error: function (request, status, message ) {
								progress_bar.find( '.getpaid-progress' ).html( '<div class="col-12 alert alert-danger" role="alert">' + message + '</div>' );
							}

						})

					};

					// Loads several files at once.
					let loadFiles = function loadFiles(flist) {
						Array.prototype.forEach.apply(flist, [loadFile]);
					};

					// Handle drag-drops.
					dropfield

						// On drag enter, highlight.
						.on( 'dragenter', () => {
							dropfield.addClass( 'getpaid-trying-to-drop' );
						})

						// When dragging over, prevent page from redirecting.
						.on( 'dragover', (e) => {
							e = e.originalEvent;
							e.stopPropagation();
							e.preventDefault();
							e.dataTransfer.dropEffect = 'copy';
						})

						// When drag leave, remove highlight.
						.on( 'dragleave', () => {
							dropfield.removeClass( 'getpaid-trying-to-drop' );
						})

						// When a file is dropped, act on it.
						.on( 'drop', (e) => {
							e = e.originalEvent;
							e.stopPropagation();
							e.preventDefault();
							let files = e.dataTransfer.files;

							if ( files.length > 0 ) {
								loadFiles(files);
							}
						});

					// Manual uploads.
					parent.find( '.getpaid-files-input' ).on( 'change', function(e) {
						let files = e.originalEvent.target.files;

						if ( files ) {
							loadFiles(files);
							parent.find( '.getpaid-files-input' ).val('')
						}

					});
				})

				// Tooltips.

				if ( jQuery.fn.popover && this.form.find( '.gp-tooltip' ).length ) {
					this.form.find( '.gp-tooltip' ).popover({
						container: this.form[0],
						html: true,
						content: function() { return $(this).closest( '.getpaid-form-cart-item-name' ).find('.getpaid-item-desc').html() }
					});
				}

				// Flatpickr
				if ( jQuery.fn.flatpickr && this.form.find( '.getpaid-init-flatpickr' ).length ) {
					this.form.find( '.getpaid-init-flatpickr' ).each( function() {

						let options = {},
						$el = jQuery( this );

						if ( $el.data('disable_alt') && $el.data('disable_alt').length > 0 ) {
							options.disable = $el.data('disable_alt');
						}

						if ( $el.data('disable_days_alt') && $el.data('disable_days_alt').length > 0 ) {
							options.disable = options.disable || [];
							let disabled_days = $el.data('disable_days_alt');

							options.disable.push( function( date ) {
								// return true to disable
								return disabled_days.indexOf( date.getDay() ) >= 0;
							})

						}

						jQuery( this )
							.removeClass( 'flatpickr-input' )
							.flatpickr( options );
					});
				}

			},

			// Processes gateways
			process_gateways(enabled_gateways, state) {

				// Prepare the submit btn.
				this.form.data('initial_amt', state.initial_amt)
				this.form.data('currency', state.currency)
				var submit_btn = this.form.find('.getpaid-payment-form-submit')
				var free_label = submit_btn.data('free').replace(/%price%/gi, state.totals.raw_total);
				var btn_label = submit_btn.data('pay').replace(/%price%/gi, state.totals.raw_total);
				submit_btn.prop('disabled', false).css('cursor', 'pointer')

				// If it's free, hide the gateways and display the free checkout text...
				if (state.is_free) {
					submit_btn.val(free_label)
					this.form.find('.getpaid-gateways').slideUp();
					this.form.data('isFree', 'yes')
					return
				}

				this.form.data('isFree', 'no')

				// ... else show, the gateways and the pay text.
				this.form.find('.getpaid-gateways').slideDown();
				submit_btn.val(btn_label);

				// Next, hide the no gateways errors and display the gateways div.
				this.form.find('.getpaid-no-recurring-gateways, .getpaid-no-subscription-group-gateways, .getpaid-no-multiple-subscription-group-gateways, .getpaid-no-active-gateways').addClass('d-none');
				this.form.find('.getpaid-select-gateway-title-div, .getpaid-available-gateways-div, .getpaid-gateway-descriptions-div').removeClass('d-none');

				// If there are no gateways?
				if (enabled_gateways.length < 1) {

					this.form.find('.getpaid-select-gateway-title-div, .getpaid-available-gateways-div, .getpaid-gateway-descriptions-div').addClass('d-none');
					submit_btn.prop('disabled', true).css('cursor', 'not-allowed');

					if (state.has_multiple_subscription_groups) {
						this.form.find('.getpaid-no-multiple-subscription-group-gateways').removeClass('d-none');
						return
					}

					if (state.has_subscription_group) {
						this.form.find('.getpaid-no-subscription-group-gateways').removeClass('d-none');
						return
					}

					if (state.has_recurring) {
						this.form.find('.getpaid-no-recurring-gateways').removeClass('d-none');
						return
					}

					this.form.find('.getpaid-no-active-gateways').removeClass('d-none');
					return

				}

				// If only one gateway available, hide the radio button.
				if (enabled_gateways.length == 1) {
					this.form.find('.getpaid-select-gateway-title-div').addClass('d-none');
					this.form.find('.getpaid-gateway-radio input').addClass('d-none');
				} else {
					this.form.find('.getpaid-gateway-radio input').removeClass('d-none');
				}

				// Hide all visible payment methods.
				this.form.find('.getpaid-gateway').addClass('d-none');

				// Display enabled gateways.
				$.each(enabled_gateways, (index, value) => {
					this.form.find(`.getpaid-gateway-${value}`).removeClass('d-none');
				})

				// If there is no gateway selected, select the first.
				if (0 === this.form.find('.getpaid-gateway:visible input:checked').length) {
					this.form.find('.getpaid-gateway:visible .getpaid-gateway-radio input').eq(0).prop('checked', true);
				}

				// Trigger change event for selected gateway.
				if (0 === this.form.find('.getpaid-gateway-description:visible').length) {
					this.form.find('.getpaid-gateway-radio input:checked').trigger('change');
				}

			},

			// Sets up payment tokens.
			setup_saved_payment_tokens() {

				// For each saved payment tokens list
				var currency = this.form.data('currency')
				this.form.find('.getpaid-saved-payment-methods').each(function () {

					var list = $(this)

					list.show()

					// When the payment method changes...
					$('input', list).on('change', function () {

						if ($(this).closest('li').hasClass('getpaid-new-payment-method')) {
							list.closest('.getpaid-gateway-description').find('.getpaid-new-payment-method-form').slideDown();
						} else {
							list.closest('.getpaid-gateway-description').find('.getpaid-new-payment-method-form').slideUp();
						}

					})

					// Hide unsupported methods.
					list.find('input').each(function () {

						if ('none' != $(this).data('currency') && currency != $(this).data('currency')) {
							$(this).closest('li').addClass('d-none')
							$(this).prop('checked', false);
						} else {
							$(this).closest('li').removeClass('d-none')
						}

					})

					// If non is selected, select first.
					if (0 === $('li:not(.d-none) input', list).filter(':checked').length) {
						$('li:not(.d-none) input', list).eq(0).prop('checked', true);
					}

					if (0 === $('li:not(.d-none) input', list).filter(':checked').length) {
						$('input', list).last().prop('checked', true);
					}

					// Hide the list if there are no saved payment methods.
					if (2 > $('li:not(.d-none) input', list).length) {
						list.hide()
					}

					// Trigger change event for selected method.
					$('input', list).filter(':checked').trigger('change');

				})

			},

			// Handles toggling shipping address on and off.
			handleAddressToggle(address_toggle) {

				var wrapper = address_toggle.closest('.getpaid-payment-form-element-address')

				// Hide titles and shipping address.
				wrapper.find('.getpaid-billing-address-title, .getpaid-shipping-address-title, .getpaid-shipping-address-wrapper').addClass('d-none')

				address_toggle.on('change', function () {

					if ($(this).is(':checked')) {

						// Hide titles and shipping address.
						wrapper.find('.getpaid-billing-address-title, .getpaid-shipping-address-title, .getpaid-shipping-address-wrapper').addClass('d-none')

						// Show general title.
						wrapper.find('.getpaid-shipping-billing-address-title').removeClass('d-none')

					} else {

						// Show titles and shipping address.
						wrapper.find('.getpaid-billing-address-title, .getpaid-shipping-address-title, .getpaid-shipping-address-wrapper').removeClass('d-none')

						// Hide general title.
						wrapper.find('.getpaid-shipping-billing-address-title').addClass('d-none')

					}

				});

			},

			// Inits a form.
			init() {

				this.setup_saved_payment_tokens()
				this.attach_events()
				this.refresh_state()

				// Hide billing email.
				this.form.find('.getpaid-payment-form-element-billing_email span.d-none').closest('.col-12').addClass('d-none')

				// Hide empty gateway descriptions.
				this.form.find('.getpaid-gateway-description:not(:has(*))').remove()

				// Handle shipping address.
				var address_toggle = this.form.find('[name ="same-shipping-address"]')

				if (address_toggle.length > 0) {
					this.handleAddressToggle(address_toggle)
				}

				// Trigger setup event.
				$('body').trigger('getpaid_setup_payment_form', [this.form]);
			},
		}

	}

	/**
	 * Set's up a payment form for use.
	 *
	 * @param {string} form
	 * @TODO Move this into the above class.
	 */
	var setup_form = function (form) {

		// Add the row class to gateway credit cards.
		form.find('.getpaid-gateway-descriptions-div .form-horizontal .form-group').addClass('row')

		// Hides items that are not in an array.
		/**
		 * @param {Array} selected_items The items to display.
		 */
		function filter_form_cart(selected_items) {

			// Abort if there is no cart.
			if (0 == form.find(".getpaid-payment-form-items-cart").length) {
				return;
			}

			// Hide all selectable items.
			form.find('.getpaid-payment-form-items-cart-item.getpaid-selectable').each(function () {
				$(this).find('.getpaid-item-price-input').attr('name', '')
				$(this).find('.getpaid-item-quantity-input').attr('name', '')
				$(this).hide()
			})

			// Display selected items.
			$(selected_items).each(function (index, item_id) {

				if (item_id) {
					var item = form.find('.getpaid-payment-form-items-cart-item.item-' + item_id)
					item.find('.getpaid-item-price-input').attr('name', 'getpaid-items[' + item_id + '][price]')
					item.find('.getpaid-item-quantity-input').attr('name', 'getpaid-items[' + item_id + '][quantity]')
					item.show()
				}

			})

		}

		// Radio select items.
		if (form.find('.getpaid-payment-form-items-radio').length) {

			// Hides displays the checked items.
			var filter_totals = function () {
				var selected_item = form.find(".getpaid-payment-form-items-radio .form-check-input:checked").val();
				filter_form_cart([selected_item])
			}

			// Do this when the value changes.
			var radio_items = form.find('.getpaid-payment-form-items-radio .form-check-input')

			radio_items.on('change', filter_totals);

			// If there are none selected, select the first.
			if (0 === radio_items.filter(':checked').length) {
				radio_items.eq(0).prop('checked', true);
			}

			// Filter on page load.
			filter_totals();
		}

		// Checkbox select items.
		if (form.find('.getpaid-payment-form-items-checkbox').length) {

			// Hides displays the checked items.
			var filter_totals = function () {
				var selected_items = form
					.find('.getpaid-payment-form-items-checkbox input:checked')
					.map(function () {
						return $(this).val();
					})
					.get()

				filter_form_cart(selected_items)
			}

			// Do this when the value changes.
			var checkbox_items = form.find('.getpaid-payment-form-items-checkbox input')

			checkbox_items.on('change', filter_totals);

			// If there are none selected, select the first.
			if (0 === checkbox_items.filter(':checked').length) {
				checkbox_items.eq(0).prop('checked', true);
			}

			// Filter on page load.
			filter_totals();
		}

		// "Select" select items.
		if (form.find('.getpaid-payment-form-items-select').length) {

			// Hides displays the selected items.
			var filter_totals = function () {
				var selected_item = form.find(".getpaid-payment-form-items-select select").val();
				filter_form_cart([selected_item])
			}

			// Do this when the value changes.
			var select_box = form.find(".getpaid-payment-form-items-select select")

			select_box.on('change', filter_totals);

			// If there are none selected, select the first.
			if (!select_box.val()) {
				select_box.find("option:first").prop('selected', 'selected');
			}

			// Filter on page load.
			filter_totals();
		}

		// Refresh prices.
		getpaid_form(form).init()

		// Submitting the payment form.
		form.on('submit', function (e) {

			// Do not submit the form.
			e.preventDefault();

			// instead, display a loading indicator.
			wpinvBlock(form);

			// Hide any errors.
			form.find('.getpaid-payment-form-errors, .getpaid-custom-payment-form-errors').html('').addClass('d-none')
			form.find('.is-invalid,.is-valid').removeClass('is-invalid is-valid')

			// Fetch the unique identifier for this form.
			var unique_key = form.data('key')

			// Save data to a global variable so that other plugins can alter it.
			var data = {
				'submit': true,
				'delay': false,
				'data': form.serialize(),
				'form': form,
				'key': unique_key,
			}

			// Trigger submit event.
			if ('no' == form.data('isFree')) {
				$('body').trigger('getpaid_payment_form_before_submit', [data]);
			}

			if (!data.submit) {
				wpinvUnblock(form);
				return;
			}

			// Handles the actual submission.
			var submit = function () {
				return $.post(WPInv.ajax_url, data.data + '&action=wpinv_payment_form&_ajax_nonce=' + WPInv.formNonce)
					.done(function (res) {

						// An error occured.
						if ('string' == typeof res) {
							form.find('.getpaid-payment-form-errors').html(res).removeClass('d-none')
							return
						}

						// Redirect to the thank you page.
						if (res.success) {

							// Asume that the action is a redirect.
							if (!res.data.action) {
								window.location.href = decodeURIComponent(res.data)
							}

							if ('auto_submit_form' == res.data.action) {
								form.parent().append('<div class="getpaid-checkout-autosubmit-form">' + res.data.form + '</div>')
								$('.getpaid-checkout-autosubmit-form form').submit()
							}

							return
						}

						form.find('.getpaid-payment-form-errors').html(res.data).removeClass('d-none')
						form.find('.getpaid-payment-form-remove-on-error').remove()

						// Maybe set invoice.
						if (res.invoice && form.find('input[name="invoice_id"]').length == 0) {
							form.append('<input type="hidden" name="invoice_id" />')
							form.find('input[name="invoice_id"]').val(res.invoice)
						}

					})

					.fail(function (res) {
						form.find('.getpaid-payment-form-errors').html(WPInv.connectionError).removeClass('d-none')
						form.find('.getpaid-payment-form-remove-on-error').remove()
					})

					.always(() => {
						wpinvUnblock(form);
					})

			}

			// Are we submitting after a delay?
			if (data.delay) {

				var local_submit = function () {

					if (!data.submit) {
						wpinvUnblock(form);
					} else {
						submit();
					}

					$('body').unbind('getpaid_payment_form_delayed_submit' + unique_key, local_submit)

				}

				$('body').bind('getpaid_payment_form_delayed_submit' + unique_key, local_submit)
				return;
			}

			// If not, submit immeadiately.
			submit()

		})

	}

	// Set up all active forms.
	$('.getpaid-payment-form').each(function () {
		setup_form($(this));
	})

	// Payment buttons.
	$(document).on('click', '.getpaid-payment-button', function (e) {

		// Do not submit the form.
		e.preventDefault();

		// Add the loader.
		$('#getpaid-payment-modal .modal-body-wrapper')
			.html('<div class="d-flex align-items-center justify-content-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>')

		// Display the modal.
		$('#getpaid-payment-modal').modal()

		// Load the form via ajax.
		var data = $(this).data()
		data.action = 'wpinv_get_payment_form'
		data._ajax_nonce = WPInv.formNonce
		data.current_url = window.location.href

		$.get(WPInv.ajax_url, data, function (res) {
			$('#getpaid-payment-modal .modal-body-wrapper').html(res)
			$('#getpaid-payment-modal').modal('handleUpdate')
			$('#getpaid-payment-modal .getpaid-payment-form').each(function () {
				setup_form($(this));
			})
		})

			.fail(function (res) {
				$('#getpaid-payment-modal .modal-body-wrapper').html(WPInv.connectionError)
				$('#getpaid-payment-modal').modal('handleUpdate')
			})

	})

	// Payment links.
	$(document).on('click', 'a[href^="#getpaid-form-"], a[href^="#getpaid-item-"]', function (e) {

		var attr = $(this).attr('href')

		if (-1 != attr.indexOf('#getpaid-form-')) {

			var data = {
				'form': attr.replace('#getpaid-form-', '')
			}

		} else if (-1 != attr.indexOf('#getpaid-item-')) {

			var data = {
				'item': attr.replace('#getpaid-item-', '')
			}

		} else {

			return;

		}

		// Do not follow the link.
		e.preventDefault();

		// Add the loader.
		$('#getpaid-payment-modal .modal-body-wrapper')
			.html('<div class="d-flex align-items-center justify-content-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>')

		// Display the modal.
		$('#getpaid-payment-modal').modal()

		// Load the form via ajax.
		data.action = 'wpinv_get_payment_form'
		data._ajax_nonce = WPInv.formNonce

		$.get(WPInv.ajax_url, data, function (res) {
			$('#getpaid-payment-modal .modal-body-wrapper').html(res)
			$('#getpaid-payment-modal').modal('handleUpdate')
			$('#getpaid-payment-modal .getpaid-payment-form').each(function () {
				setup_form($(this));
			})
		})

			.fail(function (res) {
				$('#getpaid-payment-modal .modal-body-wrapper').html(WPInv.connectionError)
				$('#getpaid-payment-modal').modal('handleUpdate')
			})

	})

	// Profile edit forms.
	$(document).on('change', '.getpaid-address-edit-form #wpinv-country', function (e) {

		var state = $(this).closest('.getpaid-address-edit-form').find('.wpinv_state')

		// Ensure that we have a state field.
		if (state.length) {

			wpinvBlock(state.parent());

			var data = {
				action: 'wpinv_get_aui_states_field',
				country: $(this).val(),
				state: state.val(),
				class: 'wpinv_state',
				name: state.attr('name'),
				_ajax_nonce: WPInv.nonce
			};

			$.get(WPInv.ajax_url, data, (res) => {

				if ('object' == typeof res) {
					state.parent().replaceWith(res.data.html)
				}

			})

				.always(() => {
					wpinvUnblock(state.parent())
				});

		}

	})

	RegExp.getpaidquote = function (str) {
		console.log(str)
		return str.replace(/([.?*+^$[\]\\(){}|-])/g, "\\$1");
	};

	// Minimum amounts.
	$(document).on('input', '.getpaid-validate-minimum-amount', function (e) {

		var thousands = new RegExp(RegExp.getpaidquote(WPInv.thousands), "g");
		var decimals = new RegExp(RegExp.getpaidquote(WPInv.decimals), "g");
		var val = $(this).val();
		val = val.replace(thousands, '')
		val = val.replace(decimals, '.')

		if (isNaN(parseFloat(val))) {
			if ($(this).data('minimum-amount')) {
				$(this).val($(this).data('minimum-amount'))
			} else {
				$(this).val(0)
			}
		}

	})

});

function wpinvBlock(el, message) {
	message = typeof message != 'undefined' && message !== '' ? message : WPInv.loading;
	var $el = jQuery(el)

	// Do not block twice.
	if (1 != $el.data('GetPaidIsBlocked')) {
		$el.data('GetPaidIsBlocked', 1);
		$el.data('GetPaidWasRelative', $el.hasClass('position-relative'));
		$el.addClass('position-relative');
		$el.append('<div class="w-100 h-100 position-absolute bg-light d-flex justify-content-center align-items-center getpaid-block-ui" style="top: 0; left: 0; opacity: 0.7; cursor: progress;"><div class="spinner-border" role="status"><span class="sr-only">' + message + '</span></div></div>');
	}

}

function wpinvUnblock(el) {
	var $el = jQuery(el);

	if (1 == $el.data('GetPaidIsBlocked')) {
		$el.data('GetPaidIsBlocked', 0);

		if (!$el.data('GetPaidWasRelative')) {
			$el.removeClass('position-relative');
		}

		$el.children('.getpaid-block-ui').remove();

	}

}
