jQuery(function ($) {
    var autocomplete = new google.maps.places.Autocomplete(
        document.getElementById('wpinv_address'),
        {types: ['geocode']}
    );
    autocomplete.addListener('place_changed', fillInAddress);
    function fillInAddress() {
        var place = autocomplete.getPlace();
        var temp_state = '';

        for (var i = 0; i < place.address_components.length; i++) {

            var address_type = place.address_components[i].types[0];

            switch (address_type) {
                case 'route':
                    var val = place.address_components[i]['short_name'];
                    document.getElementById('wpinv_address').value = val;
                    break;
                case 'locality':
                    var val = place.address_components[i]['long_name'];
                    document.getElementById('wpinv_city').value = val;
                    break;
                case 'administrative_area_level_1':
                    var val = place.address_components[i]['short_name'];
                    temp_state = val;
                    break;
                case 'country':
                    var val = place.address_components[i]['short_name'];

                    $('#wpinv_country').val(val);
                    var $this = $('#wpinv_country');
                    data = {
                        action: 'wpinv_get_states_field',
                        country: val,
                        field_name: 'wpinv_state',
                    };
                    $this.closest('.gdmbx-row').find('.wpi-loader').show();
                    $('#wpinv_state').css({
                        'opacity': '.5'
                    });
                    $.post(ajaxurl, data, function(response) {
                        if ('nostates' === response || '' == temp_state) {
                            var text_field = '<input type="text" value="' + temp_state + '" id="wpinv_state" name="wpinv_state" />';
                            $('#wpinv_state').replaceWith(text_field);
                        } else {
                            $('#wpinv_state').replaceWith(response);
                            $('#wpinv_state').find('option[value="' + temp_state + '"]').attr('selected', 'selected');
                            $('#wpinv_state').change();
                            $('#wpinv_state').addClass('form-control wpi-input required');
                        }

                        $this.closest('.gdmbx-row').find('.wpi-loader').hide();
                        $('#wpinv_state').css({
                            'opacity': '1'
                        });
                    });
                    break;
                case 'postal_code':
                    var val = place.address_components[i]['short_name'];
                    document.getElementById('wpinv_zip').value = val;
                    break;
                default:
                    break;
            }
        }
    }
});