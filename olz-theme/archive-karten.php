<?php
/**
* The template for displaying pages
*
* This is the template that displays all pages by default.
* Please note that this is the WordPress construct of pages and that
* other "pages" on your WordPress site will use a different template.
*
* @package WordPress
* @subpackage OLZ-Theme
* @since OLZ-Theme 1.0
*/

get_header(); ?>

<?php include('menu.php'); ?>

<div class="rightcolumn">
    <h3>Filter</h3>
    <?php echo olz_posts_filter_taxonomy('karten-typ'); ?>
    <h3>Übersicht</h3>
    <script src='https://api.mapbox.com/mapbox.js/v2.2.1/mapbox.js'></script>
    <link href='https://api.mapbox.com/mapbox.js/v2.2.1/mapbox.css' rel='stylesheet' />
    <!-- <script src='https://api.tiles.mapbox.com/mapbox-gl-js/v0.11.4/mapbox-gl.js'></script>
    <link href='https://api.tiles.mapbox.com/mapbox-gl-js/v0.11.4/mapbox-gl.css' rel='stylesheet' /> -->
    <div style='height:500px;' id='karten_map'></div>
    <script>
    <?php
    $kq = new WP_Query(olz_posts_filter_taxonomy('karten-typ', array(
        'post_type'=>'karten',
        'posts_per_page'=>-1,
        'order'=>'ASC',
        'orderby'=>'meta-value',
        'meta-key'=>'karten-typ',
    )));
    $maps_min_lat = -1;
    $maps_min_lng = -1;
    $maps_max_lat = -1;
    $maps_max_lng = -1;
    $maps = array();
    while ($kq->have_posts()) {
        $kq->the_post();
        $map_min_lat = floatval(get_post_meta($post->ID, "map_min_lat", true));
        if ($maps_min_lat==-1 || $map_min_lat<$maps_min_lat) $maps_min_lat = $map_min_lat;
        $map_min_lng = floatval(get_post_meta($post->ID, "map_min_lng", true));
        if ($maps_min_lng==-1 || $map_min_lng<$maps_min_lng) $maps_min_lng = $map_min_lng;
        $map_max_lat = floatval(get_post_meta($post->ID, "map_max_lat", true));
        if ($maps_max_lat==-1 || $maps_max_lat<$map_max_lat) $maps_max_lat = $map_max_lat;
        $map_max_lng = floatval(get_post_meta($post->ID, "map_max_lng", true));
        if ($maps_max_lng==-1 || $maps_max_lng<$map_max_lng) $maps_max_lng = $map_max_lng;
        $map_url = wp_get_attachment_url(get_post_thumbnail_id($post->ID));
        $maps[] = array(
            'minLat'=>$map_min_lat,
            'minLng'=>$map_min_lng,
            'maxLat'=>$map_max_lat,
            'maxLng'=>$map_max_lng,
            'url'=>$map_url,
        );
    }
    echo "var maps = ".json_encode($maps).";";
    echo "var mapsMinLat = ".json_encode($maps_min_lat).";";
    echo "var mapsMinLng = ".json_encode($maps_min_lng).";";
    echo "var mapsMaxLat = ".json_encode($maps_max_lat).";";
    echo "var mapsMaxLng = ".json_encode($maps_max_lng).";";
    ?>
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
            maps.map(function (oMap, index) {
                if (!oMap.url) {
                    return;
                }
                var sourceIdent = 'o-map-' + index
                map.addSource(sourceIdent, {
                    type: 'image',
                    url: oMap.url,
                    coordinates: [
                       [oMap.minLng, oMap.maxLat],
                       [oMap.maxLng, oMap.maxLat],
                       [oMap.maxLng, oMap.minLat],
                       [oMap.minLng, oMap.minLat]
                    ]
                })
                map.addLayer({
                    id: sourceIdent + '-layer',
                    source: sourceIdent,
                    type: 'raster',
                    paint: {'raster-opacity': 0.5},
                })
            })
        })
        map.fitBounds([[mapsMinLng, mapsMinLat], [mapsMaxLng, mapsMaxLat]], {padding: 30, duration: 0})
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
<div class="maincolumn">
    <div class="articlelist">
        <?php
        $kq = new WP_Query(olz_posts_filter_taxonomy('karten-typ', array(
            'post_type'=>'karten',
            'posts_per_page'=>-1,
            'order'=>'ASC',
            'orderby'=>'meta-value',
            'meta-key'=>'karten-typ',
        )));
        while ($kq->have_posts()) {
                $kq->the_post();
                get_template_part('content', 'list-karten');
        }
        ?>
    </div>
</div>

<?php get_footer(); ?>
