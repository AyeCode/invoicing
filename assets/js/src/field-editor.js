(function ($) {

    let draggable = require('vuedraggable')

    // remove adjacent th
    $('.wpinv-field-editor-main-wrapper').closest('tr').find('th').hide()

    // Init our vue app
    var vm = new Vue({

        components: {
            draggable
        },

        el: '.wpinv-field-editor-main-wrapper',

        data: $.extend(
            true,
            {
                showingAdvanced: false,
            },
            wpinvFieldEditor
        ),

        computed: {
            fieldString() {
                return JSON.stringify(this.fields)
            }
        },

        methods: {

            // Hide/Show the field types panel (useful for mobile)
            toggleFieldTypes() {
                $('.wpinv-field-types-editor-inside').slideToggle()

                // Toggle dashicons
                $('.wpinv-field-types-editor-header > .toggle-icon .dashicons-arrow-down').toggle()
                $('.wpinv-field-types-editor-header > .toggle-icon .dashicons-arrow-up').toggle()
            },

            // Given a panel id( field key ), it toggles the panel.
            togglePanel(id) {

                let parent = $(`#wpinv-field-editor-field-${id}`)

                // Toggle the body.
                parent.find('.wpinv-field-editor-field-body').slideToggle( 400, function(){

                    // Scroll to the first field.
                    $('html, body').animate({
                        scrollTop: parent.offset().top
                    }, 1000);
                })

                // Toggle the active class
                parent.toggleClass('active')

                // Toggle dashicons
                parent.find('.wpinv-field-editor-field-header > .toggle-icon .dashicons-arrow-down').toggle()
                parent.find('.wpinv-field-editor-field-header > .toggle-icon .dashicons-arrow-up').toggle()

            },

            // Given a field type, it returns a new field with defaults.
            getFieldData(field_type) {

                // Let's generate a unique string to use as the field key.
                let total = this.fields.length
                let rand = Math.random() + total
                let key = `${field_type}_` + rand.toString(36).replace(/[^a-z]+/g, '')

                // Clone the default_field to generate a new field.
                let newField = $.extend(true, {}, this.default_field)

                // Fetch the field type object.
                let fieldType = this.fieldTypes[field_type]

                // For predefined fields, the field type is usually the key. (e.g billing_email)
                if (fieldType && fieldType.predefined) {
                    key = field_type
                }

                // Set defaults.
                newField.name = `wpinv_${key}`
                newField.key = key
                newField.id = `wpinv_${key}`
                newField.field_type = field_type

                // Some field types also have their own defaults.
                if (fieldType && fieldType.defaults) {

                    for (let _key of Object.keys(fieldType.defaults)) {

                        if ( Array.isArray( fieldType.defaults[_key] ) ) {
                            newField[_key] = [...fieldType.defaults[_key]]
                        } else {
                            newField[_key] = fieldType.defaults[_key]
                        }
                        
                    }

                }

                // Return the new field.
                return newField
            },

            // Pushes a field to the list of fields.
            addField(field_type) {
                this.fields.push(this.getFieldData(field_type))
            },

            // Adds a field that has been dragged to the list of fields.
            addDraggedField(field_type) {
                return this.getFieldData(field_type)
            },

            // Resets checkout fields to the default checkout fields.
            resetFields() {
                this.fields = [...wpinvFieldEditor.defaultFields]
            },

            // Checks if a field has a duplicate key.
            isDuplicateKey(key) {
                return this.fields.filter(field => field.key === key).length > 1
            },

            // Synces the field key and field name.
            syncKey(field) {
                field.name = `wpinv_${field.key}`
            },

            // Checkout fields only have one predefined field of each type.
            fieldTypeDragClass(fieldType, key) {

                if (fieldType && fieldType.predefined) {

                    // Ids are not editable so let's check for that.
                    let id = `wpinv_${key}`
                    if (this.fields.filter(field => field.id === id).length) {
                        return 'wpinv-undraggable'
                    }

                }

                return 'wpinv-draggable'
            },

            // Returns the field type label.
            fieldTypeLabel(field_type) {
                return this.fieldTypes[field_type] ? this.fieldTypes[field_type].name : field_type
            },

            // Deletes a field.
            deleteField(field) {

                Swal.fire({
                    title: wpinvFieldEditor.deleteTitle,
                    text: wpinvFieldEditor.deleteText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: wpinvFieldEditor.deleteButton
                }).then((result) => {
                    if (result.value) {
                        let index = this.fields.indexOf(field);

                        if (index > -1) {
                            this.fields.splice(index, 1);
                        }
                    }
                })


            },

            // Checks if a field is predefined.
            isPredefined(field) {
                let fieldType = this.fieldTypes[field.field_type]
                return (fieldType && fieldType.predefined)
            }
        },

        watch: {
            showingAdvanced( val ) {

                if ( val ) {
                    $('.wpinv-advanced').slideDown()
                } else {
                    $('.wpinv-advanced').slideUp()
                }
            }
        },
    })

})(jQuery);