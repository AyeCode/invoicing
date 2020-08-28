<?php
/**
 * Template that prints the geolocation page.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/geolocation.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;


?><!DOCTYPE html>

<html <?php language_attributes(); ?>>

    <head>

		<meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" >

        <meta name="robots" content="noindex,nofollow">

		<link rel="profile" href="https://gmpg.org/xfn/11">

        <title><?php _e( 'GetPaid GeoLocation', 'invoicing' ); ?></title>

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.6.0/dist/leaflet.js"></script>

        <style>

            html,
            body {
                height:100%;
                margin:0;
                padding:0;
                width:100%
            }
            
            body {
                text-align:center;
                background:#fff;
                color:#222;
                font-size:small;
            }
            
            body,
            p {
                font-family: arial,sans-serif
            }
            
            #map{
                margin:auto;
                width:100%;
                height:calc(100% - 120px);
                min-height:240px
            }

        </style>
    </head>
    

    <body class="getpaid-geolocation">

        <div id="map"></div>

        <script type="text/javascript">
            var osmUrl = 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                osmAttrib = '&copy; <a href="http://openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                osm = L.tileLayer( osmUrl, { maxZoom: 18, attribution: osmAttrib } ),
                latlng = new L.LatLng( <?php echo sanitize_text_field( $latitude );?>, <?php echo sanitize_text_field( $longitude );?> );

            var map = new L.Map( 'map', {center: latlng, zoom: 18, layers: [osm]});

            var marker = new L.Marker(latlng);
            map.addLayer(marker);

            marker
                .bindPopup("<p><?php echo esc_attr( $address );?></p>")
                .openPopup();
        </script>

        <div style="height:100px"><?php echo wp_kses_post( $content ); ?></div>

    </body>


</html>
