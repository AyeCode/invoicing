jQuery(function ($) {
    var autocomplete = new google.maps.places.Autocomplete(
        document.getElementById('wpinv_address'),
        {types: ['geocode']}
    );
    autocomplete.addListener('place_changed', fillInAddress);
    function fillInAddress() {
        var place = autocomplete.getPlace();
        console.log(place);
        var temp_state = '';
        var street_number = '';
        
        for (var i = 0; i < place.address_components.length; i++) {

            var address_type = place.address_components[i].types[0];

            switch (address_type) {
                case 'street_number':
                    street_number = place.address_components[i]['short_name'];
                    break;
                case 'route':
                    var val = place.address_components[i]['long_name'];
                    document.getElementById('wpinv_address').value = street_number ? street_number+" "+val : val;
                    break;
                case 'postal_town':
                    var val = place.address_components[i]['short_name'];
                    document.getElementById('wpinv_city').value = val;
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

                    var elB = $('#wpinv-address');
                    var elF = $('#wpinv-fields');
                    data = {
                        action: 'wpinv_get_states_field',
                        country: val,
                        field_name: 'wpinv_state',
                    };

                    if(elB.length){
                        var $this = $('#wpinv_country', elB);

                        $this.closest('.gdmbx-row').find('.wpi-loader').show();
                        $('#wpinv_state', elB).css({
                            'opacity': '.5'
                        });
                        $.post(ajaxurl, data, function(response) {
                            if ('nostates' === response || '' == temp_state) {
                                var text_field = '<input type="text" value="' + temp_state + '" id="wpinv_state" name="wpinv_state" />';
                                $('#wpinv_state', elB).replaceWith(text_field);
                            } else {
                                $('#wpinv_state', elB).replaceWith(response);
                                $('#wpinv_state', elB).find('option[value="' + temp_state + '"]').attr('selected', 'selected');
                                $('#wpinv_state', elB).find('option[value=""]').remove();
                            }

                            $('#wpinv_state', elB).addClass('gdmbx2-text-large');
                            if (typeof $this.data('change') === '1') {
                                $('#wpinv_state', elB).change();
                            } else {
                                window.wpiConfirmed = true;
                                $('#wpinv-recalc-totals').click();
                                window.wpiConfirmed = false;
                            }

                            $this.closest('.gdmbx-row').find('.wpi-loader').hide();
                            $('#wpinv_state', elB).css({
                                'opacity': '1'
                            });
                        });
                    } else if(elF.length){
                        $('.wpinv_errors').remove();
                        wpinvBlock(jQuery('#wpinv_state_box'));
                        var $this = $('#wpinv_country', elF);
                        $.post(ajaxurl, data, function(response) {
                            if ('nostates' === response) {
                                var text_field = '<input type="text" required="required" class="wpi-input required" id="wpinv_state" name="wpinv_state">';
                                $('#wpinv_state', elF).replaceWith(text_field);
                            } else {
                                $('#wpinv_state', elF).replaceWith(response);
                                $('#wpinv_state', elF).find('option[value="' + temp_state + '"]').attr('selected', 'selected');
                                var changeState = function() {
                                    wpinv_recalculate_taxes(temp_state);
                                };
                                $("#wpinv_state", elF).unbind("change", changeState);
                                $("#wpinv_state", elF).bind("change", changeState);
                            }
                            $('#wpinv_state', elF).find('option[value=""]').remove();
                            $('#wpinv_state', elF).addClass('form-control wpi-input required');
                        }).done(function(data) {
                            jQuery('#wpinv_state_box').unblock();
                            wpinv_recalculate_taxes();
                        });
                    }

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