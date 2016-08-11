/**
 * Custom jQuery for Custom Metaboxes and Fields
 */
window.gdmbx2 = (function(window, document, $, undefined){
	'use strict';

	// localization strings
	var l10n = window.gdmbx2_l10;
	var setTimeout = window.setTimeout;

	// gdmbx2 functionality object
	var gdmbx = {
		idNumber        : false,
		repeatEls       : 'input:not([type="button"],[id^=filelist]),select,textarea,.gdmbx2-media-status',
		noEmpty         : 'input:not([type="button"]):not([type="radio"]):not([type="checkbox"]),textarea',
		repeatUpdate    : 'input:not([type="button"]),select,textarea,label',
		styleBreakPoint : 450,
		mediaHandlers   : {},
		neweditor_id    : [],
		defaults : {
			time_picker  : l10n.defaults.time_picker,
			date_picker  : l10n.defaults.date_picker,
			color_picker : l10n.defaults.color_picker || {},
		},
		media : {
			frames : {},
		},
	};

	// Because it's a more efficient way of getting an element by id.
	var $id = function( selector ) {
		return $( document.getElementById( selector ) );
	};

	gdmbx.metabox = function() {
		if ( gdmbx.$metabox ) {
			return gdmbx.$metabox;
		}
		gdmbx.$metabox = $('.gdmbx2-wrap > .gdmbx2-metabox');
		return gdmbx.$metabox;
	};

	gdmbx.init = function() {

		gdmbx.log( 'gdmbx2 localized data', l10n );
		var $metabox     = gdmbx.metabox();
		var $repeatGroup = $metabox.find('.gdmbx-repeatable-group');

		/**
		 * Initialize time/date/color pickers
		 */
		gdmbx.initPickers( $metabox.find('input[type="text"].gdmbx2-timepicker'), $metabox.find('input[type="text"].gdmbx2-datepicker'), $metabox.find('input[type="text"].gdmbx2-colorpicker') );

		// Wrap date picker in class to narrow the scope of jQuery UI CSS and prevent conflicts
		$id( 'ui-datepicker-div' ).wrap('<div class="gdmbx2-element" />');

		// Insert toggle button into DOM wherever there is multicheck. credit: Genesis Framework
		$( '<p><span class="button gdmbx-multicheck-toggle">' + l10n.strings.check_toggle + '</span></p>' ).insertBefore( '.gdmbx2-checkbox-list:not(.no-select-all)' );

		// Make File List drag/drop sortable:
		gdmbx.makeListSortable();

		$metabox
			.on( 'change', '.gdmbx2_upload_file', function() {
				gdmbx.media.field = $(this).attr('id');
				$id( gdmbx.media.field + '_id' ).val('');
			})
			// Media/file management
			.on( 'click', '.gdmbx-multicheck-toggle', gdmbx.toggleCheckBoxes )
			.on( 'click', '.gdmbx2-upload-button', gdmbx.handleMedia )
			.on( 'click', '.gdmbx-attach-list li, .gdmbx2-media-status .img-status img, .gdmbx2-media-status .file-status > span', gdmbx.handleFileClick )
			.on( 'click', '.gdmbx2-remove-file-button', gdmbx.handleRemoveMedia )
			// Repeatable content
			.on( 'click', '.gdmbx-add-group-row', gdmbx.addGroupRow )
			.on( 'click', '.gdmbx-add-row-button', gdmbx.addAjaxRow )
			.on( 'click', '.gdmbx-remove-group-row', gdmbx.removeGroupRow )
			.on( 'click', '.gdmbx-remove-row-button', gdmbx.removeAjaxRow )
			// Ajax oEmbed display
			.on( 'keyup paste focusout', '.gdmbx2-oembed', gdmbx.maybeOembed )
			// Reset titles when removing a row
			.on( 'gdmbx2_remove_row', '.gdmbx-repeatable-group', gdmbx.resetTitlesAndIterator )
			.on( 'click', '.gdmbxhandle, .gdmbxhandle + .gdmbxhandle-title', gdmbx.toggleHandle );

		if ( $repeatGroup.length ) {
			$repeatGroup
				.filter('.sortable').each( function() {
					// Add sorting arrows
					$(this).find( '.button.gdmbx-remove-group-row' ).before( '<a class="button gdmbx-shift-rows move-up alignleft" href="#"><span class="'+ l10n.up_arrow_class +'"></span></a> <a class="button gdmbx-shift-rows move-down alignleft" href="#"><span class="'+ l10n.down_arrow_class +'"></span></a>' );
				})
				.on( 'click', '.gdmbx-shift-rows', gdmbx.shiftRows )
				.on( 'gdmbx2_add_row', gdmbx.emptyValue );
		}

		// on pageload
		setTimeout( gdmbx.resizeoEmbeds, 500);
		// and on window resize
		$(window).on( 'resize', gdmbx.resizeoEmbeds );

	};

	gdmbx.resetTitlesAndIterator = function() {
		// Loop repeatable group tables
		$( '.gdmbx-repeatable-group' ).each( function() {
			var $table = $(this);
			// Loop repeatable group table rows
			$table.find( '.gdmbx-repeatable-grouping' ).each( function( rowindex ) {
				var $row = $(this);
				// Reset rows iterator
				$row.data( 'iterator', rowindex );
				// Reset rows title
				$row.find( '.gdmbx-group-title h4' ).text( $table.find( '.gdmbx-add-group-row' ).data( 'grouptitle' ).replace( '{#}', ( rowindex + 1 ) ) );
			});
		});
	};

	gdmbx.toggleHandle = function( evt ) {
		evt.preventDefault();
		$(document).trigger( 'postbox-toggled', $(this).parent('.postbox').toggleClass('closed') );
	};

	gdmbx.toggleCheckBoxes = function( evt ) {
		evt.preventDefault();
		var $self = $(this);
		var $multicheck = $self.closest( '.gdmbx-td' ).find( 'input[type=checkbox]:not([disabled])' );

		// If the button has already been clicked once...
		if ( $self.data( 'checked' ) ) {
			// clear the checkboxes and remove the flag
			$multicheck.prop( 'checked', false );
			$self.data( 'checked', false );
		}
		// Otherwise mark the checkboxes and add a flag
		else {
			$multicheck.prop( 'checked', true );
			$self.data( 'checked', true );
		}
	};

	gdmbx.handleMedia = function( evt ) {
		evt.preventDefault();

		var $el = $( this );
		gdmbx.attach_id = ! $el.hasClass( 'gdmbx2-upload-list' ) ? $el.closest( '.gdmbx-td' ).find( '.gdmbx2-upload-file-id' ).val() : false;
		// Clean up default 0 value
		gdmbx.attach_id = '0' !== gdmbx.attach_id ? gdmbx.attach_id : false;

		gdmbx._handleMedia( $el.prev('input.gdmbx2-upload-file').attr('id'), $el.hasClass( 'gdmbx2-upload-list' ) );
	};

	gdmbx.handleFileClick = function( evt ) {
		evt.preventDefault();

		var $el    = $( this );
		var $td    = $el.closest( '.gdmbx-td' );
		var isList = $td.find( '.gdmbx2-upload-button' ).hasClass( 'gdmbx2-upload-list' );
		gdmbx.attach_id = isList ? $el.find( 'input[type="hidden"]' ).data( 'id' ) : $td.find( '.gdmbx2-upload-file-id' ).val();

		if ( gdmbx.attach_id ) {
			gdmbx._handleMedia( $td.find( 'input.gdmbx2-upload-file' ).attr('id'), isList, gdmbx.attach_id );
		}
	};

	gdmbx._handleMedia = function( formfield, isList ) {
		if ( ! wp ) {
			return;
		}

		var media         = gdmbx.media;
		media.field       = formfield;
		media.$field      = $id( media.field );
		media.fieldData   = media.$field.data();
		media.previewSize = media.fieldData.previewsize;
		media.fieldName   = media.$field.attr('name');

		var uploadStatus, attachment;

		// If this field's media frame already exists, reopen it.
		if ( media.field in media.frames ) {
			media.frames[ media.field ].open();
			return;
		}

		// Create the media frame.
		media.frames[ media.field ] = wp.media({
			title: gdmbx.metabox().find('label[for=' + media.field + ']').text(),
			library : media.fieldData.queryargs || {},
			button: {
				text: l10n.strings[ isList ? 'upload_files' : 'upload_file' ]
			},
			multiple: isList ? 'add' : false
		});

		gdmbx.mediaHandlers.list = function( selection, returnIt ) {
			// Get all of our selected files
			attachment = selection.toJSON();

			media.$field.val(attachment.url);
			$id( media.field +'_id' ).val(attachment.id);

			// Setup our fileGroup array
			var fileGroup = [];

			// Loop through each attachment
			$( attachment ).each( function() {
				if ( this.type && this.type === 'image' ) {
					var width = media.previewSize[0] ? media.previewSize[0] : 50;
					var height = media.previewSize[1] ? media.previewSize[1] : 50;

					// image preview
					uploadStatus = '<li class="img-status">'+
						'<img width="'+ width +'" height="'+ height +'" src="' + this.url + '" class="attachment-'+ width +'px'+ height +'px" alt="'+ this.filename +'">'+
						'<p><a href="#" class="gdmbx2-remove-file-button" rel="'+ media.field +'['+ this.id +']">'+ l10n.strings.remove_image +'</a></p>'+
						'<input type="hidden" id="filelist-'+ this.id +'" data-id="'+ this.id +'" name="'+ media.fieldName +'['+ this.id +']" value="' + this.url + '">'+
					'</li>';

				} else {
					// Standard generic output if it's not an image.
					uploadStatus = '<li class="file-status"><span>'+ l10n.strings.file +' <strong>'+ this.filename +'</strong></span>&nbsp;&nbsp; (<a href="' + this.url + '" target="_blank" rel="external">'+ l10n.strings.download +'</a> / <a href="#" class="gdmbx2-remove-file-button" rel="'+ media.field +'['+ this.id +']">'+ l10n.strings.remove_file +'</a>)'+
						'<input type="hidden" id="filelist-'+ this.id +'" data-id="'+ this.id +'" name="'+ media.fieldName +'['+ this.id +']" value="' + this.url + '">'+
					'</li>';

				}

				// Add our file to our fileGroup array
				fileGroup.push( uploadStatus );
			});

			if ( ! returnIt ) {
				// Append each item from our fileGroup array to .gdmbx2-media-status
				$( fileGroup ).each( function() {
					media.$field.siblings('.gdmbx2-media-status').slideDown().append(this);
				});
			} else {
				return fileGroup;
			}

		};
		gdmbx.mediaHandlers.single = function( selection ) {
			// Only get one file from the uploader
			attachment = selection.first().toJSON();

			media.$field.val(attachment.url);
			$id( media.field +'_id' ).val(attachment.id);

			if ( attachment.type && attachment.type === 'image' ) {
				// image preview
				var width = media.previewSize[0] ? media.previewSize[0] : 350;
				uploadStatus = '<div class="img-status"><img width="'+ width +'px" style="max-width: '+ width +'px; width: 100%; height: auto;" src="' + attachment.url + '" alt="'+ attachment.filename +'" title="'+ attachment.filename +'" /><p><a href="#" class="gdmbx2-remove-file-button" rel="' + media.field + '">'+ l10n.strings.remove_image +'</a></p></div>';
			} else {
				// Standard generic output if it's not an image.
				uploadStatus = '<div class="file-status"><span>'+ l10n.strings.file +' <strong>'+ attachment.filename +'</strong></span>&nbsp;&nbsp; (<a href="'+ attachment.url +'" target="_blank" rel="external">'+ l10n.strings.download +'</a> / <a href="#" class="gdmbx2-remove-file-button" rel="'+ media.field +'">'+ l10n.strings.remove_file +'</a>)</div>';
			}

			// add/display our output
			media.$field.siblings('.gdmbx2-media-status').slideDown().html(uploadStatus);
		};

		gdmbx.mediaHandlers.selectFile = function() {
			var selection = media.frames[ media.field ].state().get('selection');
			var type = isList ? 'list' : 'single';

			if ( gdmbx.attach_id && isList ) {
				$( '[data-id="'+ gdmbx.attach_id +'"]' ).parents( 'li' ).replaceWith( gdmbx.mediaHandlers.list( selection, true ) );
				return;
			}

			gdmbx.mediaHandlers[type]( selection );
		};

		gdmbx.mediaHandlers.openModal = function() {
			var selection = media.frames[ media.field ].state().get('selection');

			if ( ! gdmbx.attach_id ) {
				return selection.reset();
			}

			var attach = wp.media.attachment( gdmbx.attach_id );
			attach.fetch();
			selection.set( attach ? [ attach ] : [] );
		};

		// When a file is selected, run a callback.
		media.frames[ media.field ]
			.on( 'select', gdmbx.mediaHandlers.selectFile )
			.on( 'open', gdmbx.mediaHandlers.openModal );

		// Finally, open the modal
		media.frames[ media.field ].open();
	};

	gdmbx.handleRemoveMedia = function( evt ) {
		evt.preventDefault();
		var $self = $(this);
		if ( $self.is( '.gdmbx-attach-list .gdmbx2-remove-file-button' ) ){
			$self.parents('li').remove();
			return false;
		}

		gdmbx.media.field = $self.attr('rel');

		gdmbx.metabox().find( 'input#' + gdmbx.media.field ).val('');
		gdmbx.metabox().find( 'input#' + gdmbx.media.field + '_id' ).val('');
		$self.parents('.gdmbx2-media-status').html('');

		return false;
	};

	gdmbx.cleanRow = function( $row, prevNum, group ) {
		var $inputs = $row.find( gdmbx.repeatUpdate );
		if ( group ) {

			var $other  = $row.find( '[id]' ).not( gdmbx.repeatUpdate );

			// Remove extra ajaxed rows
			$row.find('.gdmbx-repeat-table .gdmbx-repeat-row:not(:first-child)').remove();

			// Update all elements w/ an ID
			if ( $other.length ) {
				$other.each( function() {
					var $_this = $( this );
					var oldID = $_this.attr( 'id' );
					var newID = oldID.replace( '_'+ prevNum, '_'+ gdmbx.idNumber );
					var $buttons = $row.find('[data-selector="'+ oldID +'"]');
					$_this.attr( 'id', newID );

					// Replace data-selector vars
					if ( $buttons.length ) {
						$buttons.attr( 'data-selector', newID ).data( 'selector', newID );
					}
				});
			}
		}
		gdmbx.neweditor_id = [];

		$inputs.filter(':checked').prop( 'checked', false );
		$inputs.filter(':selected').prop( 'selected', false );

		if ( $row.find('h3.gdmbx-group-title').length ) {
			$row.find( 'h3.gdmbx-group-title' ).text( $row.data( 'title' ).replace( '{#}', ( gdmbx.idNumber + 1 ) ) );
		}

		$inputs.each( function(){
			var $newInput = $(this);
			var isEditor  = $newInput.hasClass( 'wp-editor-area' );
			var oldFor    = $newInput.attr( 'for' );
			var oldVal    = $newInput.attr( 'value' );
			var type      = $newInput.prop( 'type' );
			var checkable = 'radio' === type || 'checkbox' === type ? oldVal : false;
			// var $next  = $newInput.next();
			var attrs     = {};
			var newID, oldID;
			if ( oldFor ) {
				attrs = { 'for' : oldFor.replace( '_'+ prevNum, '_'+ gdmbx.idNumber ) };
			} else {
				var oldName = $newInput.attr( 'name' );
				// Replace 'name' attribute key
				var newName = oldName ? oldName.replace( '['+ prevNum +']', '['+ gdmbx.idNumber +']' ) : '';
				oldID       = $newInput.attr( 'id' );
				newID       = oldID ? oldID.replace( '_'+ prevNum, '_'+ gdmbx.idNumber ) : '';
				attrs       = {
					id: newID,
					name: newName,
					// value: '',
					'data-iterator': gdmbx.idNumber,
				};

			}

			// Clear out old values
			if ( undefined !== typeof( oldVal ) && oldVal || checkable ) {
				attrs.value = checkable ? checkable : '';
			}

			// Clear out textarea values
			if ( 'TEXTAREA' === $newInput.prop('tagName') ) {
				$newInput.html( '' );
			}

			if ( checkable ) {
				$newInput.removeAttr( 'checked' );
			}

			$newInput
				.removeClass( 'hasDatepicker' )
				.attr( attrs ).val( checkable ? checkable : '' );

			// wysiwyg field
			if ( isEditor ) {
				// Get new wysiwyg ID
				newID = newID ? oldID.replace( 'zx'+ prevNum, 'zx'+ gdmbx.idNumber ) : '';
				// Empty the contents
				$newInput.html('');
				// Get wysiwyg field
				var $wysiwyg = $newInput.parents( '.gdmbx-type-wysiwyg' );
				// Remove extra mce divs
				$wysiwyg.find('.mce-tinymce:not(:first-child)').remove();
				// Replace id instances
				var html = $wysiwyg.html().replace( new RegExp( oldID, 'g' ), newID );
				// Update field html
				$wysiwyg.html( html );
				// Save ids for later to re-init tinymce
				gdmbx.neweditor_id.push( { 'id': newID, 'old': oldID } );
			}
		});

		return gdmbx;
	};

	gdmbx.newRowHousekeeping = function( $row ) {

		var $colorPicker = $row.find( '.wp-picker-container' );
		var $list        = $row.find( '.gdmbx2-media-status' );

		if ( $colorPicker.length ) {
			// Need to clean-up colorpicker before appending
			$colorPicker.each( function() {
				var $td = $(this).parent();
				$td.html( $td.find( 'input[type="text"].gdmbx2-colorpicker' ).attr('style', '') );
			});
		}

		// Need to clean-up colorpicker before appending
		if ( $list.length ) {
			$list.empty();
		}

		return gdmbx;
	};

	gdmbx.afterRowInsert = function( $row ) {
		var _prop;

		// Need to re-init wp_editor instances
		if ( gdmbx.neweditor_id.length ) {
			var i;
			for ( i = gdmbx.neweditor_id.length - 1; i >= 0; i-- ) {
				var id = gdmbx.neweditor_id[i].id;
				var old = gdmbx.neweditor_id[i].old;

				if ( typeof( tinyMCEPreInit.mceInit[ id ] ) === 'undefined' ) {
					var newSettings = jQuery.extend( {}, tinyMCEPreInit.mceInit[ old ] );

					for ( _prop in newSettings ) {
						if ( 'string' === typeof( newSettings[_prop] ) ) {
							newSettings[_prop] = newSettings[_prop].replace( new RegExp( old, 'g' ), id );
						}
					}
					tinyMCEPreInit.mceInit[ id ] = newSettings;
				}
				if ( typeof( tinyMCEPreInit.qtInit[ id ] ) === 'undefined' ) {
					var newQTS = jQuery.extend( {}, tinyMCEPreInit.qtInit[ old ] );
					for ( _prop in newQTS ) {
						if ( 'string' === typeof( newQTS[_prop] ) ) {
							newQTS[_prop] = newQTS[_prop].replace( new RegExp( old, 'g' ), id );
						}
					}
					tinyMCEPreInit.qtInit[ id ] = newQTS;
				}
				tinyMCE.init({
					id : tinyMCEPreInit.mceInit[ id ],
				});

			}
		}

		// Init pickers from new row
		gdmbx.initPickers( $row.find('input[type="text"].gdmbx2-timepicker'), $row.find('input[type="text"].gdmbx2-datepicker'), $row.find('input[type="text"].gdmbx2-colorpicker') );
	};

	gdmbx.updateNameAttr = function () {

		var $this = $(this);
		var name  = $this.attr( 'name' ); // get current name

		// If name is defined
		if ( typeof name !== 'undefined' ) {
			var prevNum = parseInt( $this.parents( '.gdmbx-repeatable-grouping' ).data( 'iterator' ) );
			var newNum  = prevNum - 1; // Subtract 1 to get new iterator number

			// Update field name attributes so data is not orphaned when a row is removed and post is saved
			var $newName = name.replace( '[' + prevNum + ']', '[' + newNum + ']' );

			// New name with replaced iterator
			$this.attr( 'name', $newName );
		}

	};

	gdmbx.emptyValue = function( evt, row ) {
		$( gdmbx.noEmpty, row ).val( '' );
	};

	gdmbx.addGroupRow = function( evt ) {
		evt.preventDefault();

		var $self    = $(this);

		// before anything significant happens
		$self.trigger( 'gdmbx2_add_group_row_start', $self );

		var $table   = $id( $self.data('selector') );
		var $oldRow  = $table.find('.gdmbx-repeatable-grouping').last();
		var prevNum  = parseInt( $oldRow.data('iterator') );
		gdmbx.idNumber = prevNum + 1;
		var $row     = $oldRow.clone();

		gdmbx.newRowHousekeeping( $row.data( 'title', $self.data( 'grouptitle' ) ) ).cleanRow( $row, prevNum, true );
		$row.find( '.gdmbx-add-row-button' ).prop( 'disabled', false );

		var $newRow = $( '<div class="postbox gdmbx-row gdmbx-repeatable-grouping" data-iterator="'+ gdmbx.idNumber +'">'+ $row.html() +'</div>' );
		$oldRow.after( $newRow );

		gdmbx.afterRowInsert( $newRow );

		if ( $table.find('.gdmbx-repeatable-grouping').length <= 1 ) {
			$table.find('.gdmbx-remove-group-row').prop( 'disabled', true );
		} else {
			$table.find('.gdmbx-remove-group-row').prop( 'disabled', false );
		}

		$table.trigger( 'gdmbx2_add_row', $newRow );
	};

	gdmbx.addAjaxRow = function( evt ) {
		evt.preventDefault();

		var $self         = $(this);
		var $table        = $id( $self.data('selector') );
		var $emptyrow     = $table.find('.empty-row');
		var prevNum       = parseInt( $emptyrow.find('[data-iterator]').data('iterator') );
		gdmbx.idNumber      = prevNum + 1;
		var $row          = $emptyrow.clone();

		gdmbx.newRowHousekeeping( $row ).cleanRow( $row, prevNum );

		$emptyrow.removeClass('empty-row hidden').addClass('gdmbx-repeat-row');
		$emptyrow.after( $row );

		gdmbx.afterRowInsert( $row );

		$table.trigger( 'gdmbx2_add_row', $row );

		$table.find( '.gdmbx-remove-row-button' ).removeClass( 'button-disabled' );

	};

	gdmbx.removeGroupRow = function( evt ) {
		evt.preventDefault();

		var $self   = $(this);
		var $table  = $id( $self.data('selector') );
		var $parent = $self.parents('.gdmbx-repeatable-grouping');
		var number  = $table.find('.gdmbx-repeatable-grouping').length;

		if ( number > 1 ) {

			$table.trigger( 'gdmbx2_remove_group_row_start', $self );

			// when a group is removed loop through all next groups and update fields names
			$parent.nextAll( '.gdmbx-repeatable-grouping' ).find( gdmbx.repeatEls ).each( gdmbx.updateNameAttr );

			$parent.remove();

			if ( number <= 2 ) {
				$table.find('.gdmbx-remove-group-row').prop( 'disabled', true );
			} else {
				$table.find('.gdmbx-remove-group-row').prop( 'disabled', false );
			}

			$table.trigger( 'gdmbx2_remove_row' );
		}

	};

	gdmbx.removeAjaxRow = function( evt ) {
		evt.preventDefault();

		var $self = $(this);

		// Check if disabled
		if ( $self.hasClass( 'button-disabled' ) ) {
			return;
		}

		var $parent = $self.parents('.gdmbx-row');
		var $table  = $self.parents('.gdmbx-repeat-table');
		var number  = $table.find('.gdmbx-row').length;

		if ( number > 2 ) {
			if ( $parent.hasClass('empty-row') ) {
				$parent.prev().addClass( 'empty-row' ).removeClass('gdmbx-repeat-row');
			}
			$self.parents('.gdmbx-repeat-table .gdmbx-row').remove();
			if ( number === 3 ) {
				$table.find( '.gdmbx-remove-row-button' ).addClass( 'button-disabled' );
			}
			$table.trigger( 'gdmbx2_remove_row' );
		} else {
			$self.addClass( 'button-disabled' );
		}
	};

	gdmbx.shiftRows = function( evt ) {

		evt.preventDefault();

		var $self     = $(this);
		// before anything signif happens
		$self.trigger( 'gdmbx2_shift_rows_enter', $self );

		var $parent   = $self.parents( '.gdmbx-repeatable-grouping' );
		var $goto     = $self.hasClass( 'move-up' ) ? $parent.prev( '.gdmbx-repeatable-grouping' ) : $parent.next( '.gdmbx-repeatable-grouping' );

		if ( ! $goto.length ) {
			return;
		}

		// we're gonna shift
		$self.trigger( 'gdmbx2_shift_rows_start', $self );

		var inputVals = [];
		// Loop this items fields
		$parent.find( gdmbx.repeatEls ).each( function() {
			var $element = $(this);
			var elType = $element.attr( 'type' );
			var val;

			if ( $element.hasClass('gdmbx2-media-status') ) {
				// special case for image previews
				val = $element.html();
			} else if ( 'checkbox' === elType || 'radio' === elType ) {
				val = $element.is(':checked');
			} else if ( 'select' === $element.prop('tagName') ) {
				val = $element.is(':selected');
			} else {
				val = $element.val();
			}
			// Get all the current values per element
			inputVals.push( { val: val, $: $element } );
		});
		// And swap them all
		$goto.find( gdmbx.repeatEls ).each( function( index ) {
			var $element = $(this);
			var elType = $element.attr( 'type' );
			var val;

			if ( $element.hasClass('gdmbx2-media-status') ) {
				var toRowId = $element.closest('.gdmbx-repeatable-grouping').attr('data-iterator');
				var fromRowId = inputVals[ index ]['$'].closest('.gdmbx-repeatable-grouping').attr('data-iterator');

				// special case for image previews
				val = $element.html();
				$element.html( inputVals[ index ].val );
				inputVals[ index ].$.html( val );

				inputVals[ index ].$.find('input').each(function() {
					var name = $(this).attr('name');
					name = name.replace('['+toRowId+']', '['+fromRowId+']');
					$(this).attr('name', name);
				});
				$element.find('input').each(function() {
					var name = $(this).attr('name');
					name = name.replace('['+fromRowId+']', '['+toRowId+']');
					$(this).attr('name', name);
				});

			}
			// handle checkbox swapping
			else if ( 'checkbox' === elType  ) {
				inputVals[ index ].$.prop( 'checked', $element.is(':checked') );
				$element.prop( 'checked', inputVals[ index ].val );
			}
			// handle radio swapping
			else if ( 'radio' === elType  ) {
				if ( $element.is( ':checked' ) ) {
					inputVals[ index ].$.attr( 'data-checked', 'true' );
				}
				if ( inputVals[ index ].$.is( ':checked' ) ) {
					$element.attr( 'data-checked', 'true' );
				}
			}
			// handle select swapping
			else if ( 'select' === $element.prop('tagName') ) {
				inputVals[ index ].$.prop( 'selected', $element.is(':selected') );
				$element.prop( 'selected', inputVals[ index ].val );
			}
			// handle normal input swapping
			else {
				inputVals[ index ].$.val( $element.val() );
				$element.val( inputVals[ index ].val );
			}
		});

		$parent.find( 'input[data-checked=true]' ).prop( 'checked', true ).removeAttr( 'data-checked' );
		$goto.find( 'input[data-checked=true]' ).prop( 'checked', true ).removeAttr( 'data-checked' );

		// shift done
		$self.trigger( 'gdmbx2_shift_rows_complete', $self );
	};

	gdmbx.initPickers = function( $timePickers, $datePickers, $colorPickers ) {
		// Initialize timepicker
		gdmbx.initTimePickers( $timePickers );

		// Initialize jQuery UI datepicker
		gdmbx.initDatePickers( $datePickers );

		// Initialize color picker
		gdmbx.initColorPickers( $colorPickers );
	};

	gdmbx.initTimePickers = function( $selector ) {
		if ( ! $selector.length ) {
			return;
		}

		$selector.timepicker( 'destroy' );
		$selector.timepicker( gdmbx.defaults.time_picker );
	};

	gdmbx.initDatePickers = function( $selector ) {
		if ( ! $selector.length ) {
			return;
		}

		$selector.datepicker( 'destroy' );
		$selector.datepicker( gdmbx.defaults.date_picker );
	};

	gdmbx.initColorPickers = function( $selector ) {
		if ( ! $selector.length ) {
			return;
		}
		if (typeof jQuery.wp === 'object' && typeof jQuery.wp.wpColorPicker === 'function') {

			$selector.wpColorPicker( gdmbx.defaults.color_picker );

		} else {
			$selector.each( function(i) {
				$(this).after('<div id="picker-' + i + '" style="z-index: 1000; background: #EEE; border: 1px solid #CCC; position: absolute; display: block;"></div>');
				$id( 'picker-' + i ).hide().farbtastic($(this));
			})
			.focus( function() {
				$(this).next().show();
			})
			.blur( function() {
				$(this).next().hide();
			});
		}
	};

	gdmbx.makeListSortable = function() {
		var $filelist = gdmbx.metabox().find( '.gdmbx2-media-status.gdmbx-attach-list' );
		if ( $filelist.length ) {
			$filelist.sortable({ cursor: 'move' }).disableSelection();
		}
	};

	gdmbx.maybeOembed = function( evt ) {
		var $self = $(this);
		var type = evt.type;

		var m = {
			focusout : function() {
				setTimeout( function() {
					// if it's been 2 seconds, hide our spinner
					gdmbx.spinner( '.postbox .gdmbx2-metabox', true );
				}, 2000);
			},
			keyup : function() {
				var betw = function( min, max ) {
					return ( evt.which <= max && evt.which >= min );
				};
				// Only Ajax on normal keystrokes
				if ( betw( 48, 90 ) || betw( 96, 111 ) || betw( 8, 9 ) || evt.which === 187 || evt.which === 190 ) {
					// fire our ajax function
					gdmbx.doAjax( $self, evt );
				}
			},
			paste : function() {
				// paste event is fired before the value is filled, so wait a bit
				setTimeout( function() { gdmbx.doAjax( $self ); }, 100);
			}
		};
		m[type]();

	};

	/**
	 * Resize oEmbed videos to fit in their respective metaboxes
	 */
	gdmbx.resizeoEmbeds = function() {
		gdmbx.metabox().each( function() {
			var $self      = $(this);
			var $tableWrap = $self.parents('.inside');
			var isSide     = $self.parents('.inner-sidebar').length || $self.parents( '#side-sortables' ).length;
			var isSmall    = isSide;
			var isSmallest = false;
			if ( ! $tableWrap.length )  {
				return true; // continue
			}

			// Calculate new width
			var tableW = $tableWrap.width();

			if ( gdmbx.styleBreakPoint > tableW ) {
				isSmall    = true;
				isSmallest = ( gdmbx.styleBreakPoint - 62 ) > tableW;
			}

			tableW = isSmall ? tableW : Math.round(($tableWrap.width() * 0.82)*0.97);
			var newWidth = tableW - 30;
			if ( isSmall && ! isSide && ! isSmallest ) {
				newWidth = newWidth - 75;
			}
			if ( newWidth > 639 ) {
				return true; // continue
			}

			var $embeds   = $self.find('.gdmbx-type-oembed .embed-status');
			var $children = $embeds.children().not('.gdmbx2-remove-wrapper');
			if ( ! $children.length ) {
				return true; // continue
			}

			$children.each( function() {
				var $self     = $(this);
				var iwidth    = $self.width();
				var iheight   = $self.height();
				var _newWidth = newWidth;
				if ( $self.parents( '.gdmbx-repeat-row' ).length && ! isSmall ) {
					// Make room for our repeatable "remove" button column
					_newWidth = newWidth - 91;
					_newWidth = 785 > tableW ? _newWidth - 15 : _newWidth;
				}
				// Calc new height
				var newHeight = Math.round((_newWidth * iheight)/iwidth);
				$self.width(_newWidth).height(newHeight);
			});

		});
	};

	/**
	 * Safely log things if query var is set
	 * @since  1.0.0
	 */
	gdmbx.log = function() {
		if ( l10n.script_debug && console && typeof console.log === 'function' ) {
			console.log.apply(console, arguments);
		}
	};

	gdmbx.spinner = function( $context, hide ) {
		if ( hide ) {
			$('.gdmbx-spinner', $context ).hide();
		}
		else {
			$('.gdmbx-spinner', $context ).show();
		}
	};

	// function for running our ajax
	gdmbx.doAjax = function( $obj ) {
		// get typed value
		var oembed_url = $obj.val();
		// only proceed if the field contains more than 6 characters
		if ( oembed_url.length < 6 ) {
			return;
		}

		// get field id
		var field_id         = $obj.attr('id');
		var $context         = $obj.closest( '.gdmbx-td' );
		var $embed_container = $context.find( '.embed-status' );
		var $embed_wrap      = $context.find( '.embed_wrap' );
		var $child_el        = $embed_container.find( ':first-child' );
		var oembed_width     = $embed_container.length && $child_el.length ? $child_el.width() : $obj.width();

		gdmbx.log( 'oembed_url', oembed_url, field_id );

		// show our spinner
		gdmbx.spinner( $context );
		// clear out previous results
		$embed_wrap.html('');
		// and run our ajax function
		setTimeout( function() {
			// if they haven't typed in 500 ms
			if ( $( '.gdmbx2-oembed:focus' ).val() !== oembed_url ) {
				return;
			}
			$.ajax({
				type : 'post',
				dataType : 'json',
				url : l10n.ajaxurl,
				data : {
					'action'          : 'gdmbx2_oembed_handler',
					'oembed_url'      : oembed_url,
					'oembed_width'    : oembed_width > 300 ? oembed_width : 300,
					'field_id'        : field_id,
					'object_id'       : $obj.data( 'objectid' ),
					'object_type'     : $obj.data( 'objecttype' ),
					'gdmbx2_ajax_nonce' : l10n.ajax_nonce
				},
				success: function(response) {
					gdmbx.log( response );
					// hide our spinner
					gdmbx.spinner( $context, true );
					// and populate our results from ajax response
					$embed_wrap.html( response.data );
				}
			});

		}, 500);
	};

	$(document).ready(gdmbx.init);

	return gdmbx;

})(window, document, jQuery);
