<?php

$post_type = 'karten';

function olz_karten_save_meta_box_data($post_id) {
    update_meta_from_dict($post_id, 'map_min_lat', $_POST, floatval, true);
    update_meta_from_dict($post_id, 'map_min_lng', $_POST, floatval, true);
    update_meta_from_dict($post_id, 'map_max_lat', $_POST, floatval, true);
    update_meta_from_dict($post_id, 'map_max_lng', $_POST, floatval, true);
}
add_save_post_action($post_type, 'olz_karten_save_meta_box_data');

function olz_karten_location_meta_box($post) {
    wp_nonce_field('olz_save_meta_box_data', 'olz_meta_box_nonce');
    echo "<script>
    var mapMinLat = ".json_encode(floatval(get_post_meta($post->ID, "map_min_lat", true))).";
    var mapMinLng = ".json_encode(floatval(get_post_meta($post->ID, "map_min_lng", true))).";
    var mapMaxLat = ".json_encode(floatval(get_post_meta($post->ID, "map_max_lat", true))).";
    var mapMaxLng = ".json_encode(floatval(get_post_meta($post->ID, "map_max_lng", true))).";
    var mapUrl = ".json_encode(wp_get_attachment_url(get_post_thumbnail_id($post->ID))).";
    </script>";
    ?>
    <script src='https://api.tiles.mapbox.com/mapbox-gl-js/v0.41.0/mapbox-gl.js'></script>
    <link href='https://api.tiles.mapbox.com/mapbox-gl-js/v0.41.0/mapbox-gl.css' rel='stylesheet' />
    <input type='text' id='meta_map_min_lat' name='map_min_lat' value='' />
    <input type='text' id='meta_map_min_lng' name='map_min_lng' value='' />
    <input type='text' id='meta_map_max_lat' name='map_max_lat' value='' />
    <input type='text' id='meta_map_max_lng' name='map_max_lng' value='' />
    <div style='height:300px;' id='meta_location_map'></div>
    <script>

    mapboxgl.accessToken = 'pk.eyJ1Ijoib2x6aW1tZXJlYmVyZyIsImEiOiJjajl3cHNyMDI3ZGp6MnhxeW1qZXVpdnk4In0.VcKf-JDSK6ltrowMopc-pQ'
    var map = new mapboxgl.Map({
        container: 'meta_location_map',
        style: 'mapbox://styles/mapbox/outdoors-v10',
        scrollZoom: false,
    })
    map.addControl(new mapboxgl.NavigationControl())
    map.once('load', function () {
        map.addSource('o-map', {
            type: 'image',
            url: mapUrl,
            coordinates: [
               [mapMinLng, mapMaxLat],
               [mapMaxLng, mapMaxLat],
               [mapMaxLng, mapMinLat],
               [mapMinLng, mapMinLat]
            ]
        })
        map.addLayer({
            id: 'o-map-layer',
            source: 'o-map',
            type: 'raster',
            paint: {'raster-opacity': 0.5},
        })
    })
    map.fitBounds([[mapMinLng, mapMinLat], [mapMaxLng, mapMaxLat]], {padding: 30, duration: 0})
    map.animationStep = 0;
    setInterval(function (map) {
        var val = 0.5+0.5*Math.sin(map.animationStep*Math.PI/10);
        map.setPaintProperty('o-map-layer', 'raster-opacity', val);
        map.animationStep++;
    }.bind(this, map), 100);
    jQuery("#meta_map_min_lng").val(mapMinLng);
    jQuery("#meta_map_min_lat").val(mapMinLat);
    jQuery("#meta_map_max_lng").val(mapMaxLng);
    jQuery("#meta_map_max_lat").val(mapMaxLat);
    </script>
    <?php
}

function olz_register_karten_meta_boxes() {
    add_meta_box('karten-location', 'Overlay', 'olz_karten_location_meta_box', 'karten', 'normal', 'high');
}
add_action('add_meta_boxes_karten', 'olz_register_karten_meta_boxes');

?>
