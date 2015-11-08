<?php
/**
* The template for displaying all single posts and attachments
*
* @package WordPress
* @subpackage OLZ-Theme
* @since OLZ-Theme 1.0
*/

get_header(); ?>

<?php include('menu.php'); ?>

<div class="rightcolumn">
    <div class="singlearticle">
        <?php
        while (have_posts()) {
            the_post();
            get_template_part('content', 'karten');
        }
        olz_include_text('karten-bezug');
        ?>
    </div>
</div>
<div class="maincolumn">
    <div style='height:500px;' id='karten_map'></div>
    <script>
    var mapMinLat = <?php echo json_encode(floatval(get_post_meta($post->ID, "map_min_lat", true))); ?>;
    var mapMinLng = <?php echo json_encode(floatval(get_post_meta($post->ID, "map_min_lng", true))); ?>;
    var mapMaxLat = <?php echo json_encode(floatval(get_post_meta($post->ID, "map_max_lat", true))); ?>;
    var mapMaxLng = <?php echo json_encode(floatval(get_post_meta($post->ID, "map_max_lng", true))); ?>;
    var mapUrl = <?php $url = wp_get_attachment_url(get_post_thumbnail_id($post->ID)); echo json_encode($url); ?>;

    var mapboxglTag = document.createElement('script')
    mapboxglTag.onload = function () {
        mapboxgl.accessToken = 'pk.eyJ1Ijoib2x6aW1tZXJlYmVyZyIsImEiOiJjajl3cHNyMDI3ZGp6MnhxeW1qZXVpdnk4In0.VcKf-JDSK6ltrowMopc-pQ'
        var map = new mapboxgl.Map({
            container: 'karten_map',
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
    }
    mapboxglTag.src = 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.41.0/mapbox-gl.js'
    document.body.appendChild(mapboxglTag)
    jQuery(document.body).append('<link href="https://api.tiles.mapbox.com/mapbox-gl-js/v0.41.0/mapbox-gl.css" rel="stylesheet" />')
    </script>
    <?php
    if (comments_open() || get_comments_number()) {
        comments_template();
    }
    ?>
</div>

<?php get_footer(); ?>
