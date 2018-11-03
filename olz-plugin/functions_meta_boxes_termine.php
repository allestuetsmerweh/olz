<?php

require_once(dirname(__FILE__).'/utils/general.php');

$post_type = 'termine';

function olz_ajax_get_solv_links() {
    global $wpdb;
    $search_terms = explode(' ', $_POST['search']);
    $sql_ph = array();
    $sql_val = array();
    foreach ($search_terms as $index => $search_term) {
        $sql_ph[] = "solv_uid LIKE CONCAT(%s, '%') OR name LIKE CONCAT('%', %s, '%')";
        $sql_val[] = $search_term;
        $sql_val[] = $search_term;
    }
    $sql_stmt = "SELECT solv_uid, event_date, name FROM solv_events WHERE (".implode(') AND (', $sql_ph).") ORDER BY DATEDIFF(CURRENT_TIME, event_date) ASC LIMIT 10";
    $results = $wpdb->get_results($wpdb->prepare($sql_stmt, $sql_val));
    return $results;
}
add_action('wp_ajax_get_solv_links', olz_ajaxify('olz_ajax_get_solv_links'));

function olz_termine_save_meta_box_data($post_id) {
    $_POST['timerange_start'] = dateandtimeval($_POST['timerange_start_date'], $_POST['timerange_start_time']);
    $_POST['timerange_end'] = dateandtimeval($_POST['timerange_end_date'], $_POST['timerange_end_time']);
    update_meta_from_dict($post_id, 'timerange_start', $_POST);
    update_meta_from_dict($post_id, 'timerange_end', $_POST);
    update_meta_from_dict($post_id, 'location_lat', $_POST, floatval, true);
    update_meta_from_dict($post_id, 'location_lng', $_POST, floatval, true);
    update_meta_from_dict($post_id, 'meta', $_POST, intval);
    update_meta_from_dict($post_id, 'solv', $_POST, intval);
    update_meta_from_dict($post_id, 'go2ol', $_POST, intval);
}
add_save_post_action($post_type, 'olz_termine_save_meta_box_data');

function olz_termine_meta_box($post) {
    wp_nonce_field('olz_save_meta_box_data', 'olz_meta_box_nonce');
    $timerange_start = get_post_meta($post->ID, 'timerange_start', true);
    $timerange_end = get_post_meta($post->ID, 'timerange_end', true);
    if (!$timerange_start) $timerange_start = date("Y-m-d H:00:00");
    if (!$timerange_end) $timerange_end = date("Y-m-d H:00:00");
    echo "<script>
    var postId = ".intval($post->ID).";
    var timerangeStart = ".json_encode($timerange_start).";
    var timerangeEnd = ".json_encode($timerange_end).";
    var locationLat = ".json_encode(floatval(get_post_meta($post->ID, "location_lat", true))).";
    var locationLng = ".json_encode(floatval(get_post_meta($post->ID, "location_lng", true))).";
    var meta = ".json_encode(intval(get_post_meta($post->ID, 'meta', true))).";
    var solv = ".json_encode(intval(get_post_meta($post->ID, 'solv', true))).";
    var go2ol = ".json_encode(intval(get_post_meta($post->ID, 'go2ol', true))).";
    </script>";
    ?>
    <script src='https://api.tiles.mapbox.com/mapbox-gl-js/v0.41.0/mapbox-gl.js'></script>
    <link href='https://api.tiles.mapbox.com/mapbox-gl-js/v0.41.0/mapbox-gl.css' rel='stylesheet' />
    <div style='float: right;'>ID: <span id='termine-post-id'>?</span></div>
    <h3>Zeit</h3>
    <div style='float: left; min-width: 50%; white-space: nowrap;'>Von <input type='text' id='termine-timerange-start-date' name='timerange_start_date' value='' style='width:120px;' /><input type='text' id='termine-timerange-start-time' name='timerange_start_time' value='' style='width:120px;' /></div>
    <div style='float: right; min-width: 50%; white-space: nowrap;'>Bis <input type='text' id='termine-timerange-end-date' name='timerange_end_date' value='' style='width:120px;' /><input type='text' id='termine-timerange-end-time' name='timerange_end_time' value='' style='width:120px;' /></div>
    <div style='clear: both;'></div>
    <h3>Ort</h3>
    <input type='hidden' id='meta-location-lat' name='location_lat' value='' />
    <input type='hidden' id='meta-location-lng' name='location_lng' value='' />
    <div style='height:300px;' id='meta_location_map'></div>
    <h3>Verlinkungen</h3>
    <div style='float: left; width:33%;'>
        Meta-Event: <input type='text' id='meta_meta' name='meta' value='' style='width: 100px; text-align: right;' />
    </div>
    <div style='float: left; width:33%;'>
        SOLV: <input type='text' id='meta_solv' name='solv' value='' style='width: 100px; text-align: right;' /><div id="solv-completion" class="completion-wrapper"></div>
    </div>
    <div style='float: left; width:33%;'>
        GO2OL: <input type='text' id='meta_go2ol' name='go2ol' value='' style='width: 100px; text-align: right;' />
    </div>
    <div style='clear: both;'></div>
    <script>
    jQuery("#termine-post-id").html(postId);
    jQuery("#termine-timerange-start-date").val(timerangeStart.substr(0, 10));
    jQuery("#termine-timerange-start-time").val(timerangeStart.substr(11, 8));
    jQuery("#termine-timerange-end-date").val(timerangeEnd.substr(0, 10));
    jQuery("#termine-timerange-end-time").val(timerangeEnd.substr(11, 8));
    var validateDateAndTime = function (dateStr, timeStr) {
        var dateTime = Date.parse(dateStr+' '+timeStr+'Z');
        if (dateTime && /[0-9]{2}:[0-9]{2}(:[0-9]{2})?/.exec(timeStr)) {
            var dateTimeStr = new Date(dateTime).toJSON();
            return [dateTimeStr.substr(0, 10), dateTimeStr.substr(11, 8)];
        }
        dateTime = Date.parse(dateStr+' 00:00:00Z');
        if (dateTime) {
            return [new Date(dateTime).toJSON().substr(0, 10), ''];
        }
        return null;
    }
    var validate_timeranges = function (e) {
        var startDate = jQuery("#termine-timerange-start-date").val();
        var startTime = jQuery("#termine-timerange-start-time").val();
        var endDate = jQuery("#termine-timerange-end-date").val();
        var endTime = jQuery("#termine-timerange-end-time").val();
        var startDateAndTime = validateDateAndTime(startDate, startTime) || [timerangeStart.substr(0, 10), ''];
        var endDateAndTime = validateDateAndTime(endDate, endTime) || [timerangeEnd.substr(0, 10), ''];
        var endBeforeStart = endDateAndTime[0]<startDateAndTime[0] ||
            (endDateAndTime[0]==startDateAndTime[0] && endDateAndTime[1]<startDateAndTime[1]);
        jQuery("#termine-timerange-start-date").val(startDateAndTime[0]);
        jQuery("#termine-timerange-start-time").val(startDateAndTime[1]);
        jQuery("#termine-timerange-end-date").val(endBeforeStart ? startDateAndTime[0] : endDateAndTime[0]);
        jQuery("#termine-timerange-end-time").val(endBeforeStart ? startDateAndTime[1] : endDateAndTime[1]);
    };
    jQuery("#termine-timerange-start-date").on('blur', validate_timeranges);
    jQuery("#termine-timerange-start-time").on('blur', validate_timeranges);
    jQuery("#termine-timerange-end-date").on('blur', validate_timeranges);
    jQuery("#termine-timerange-end-time").on('blur', validate_timeranges);

    jQuery("#meta-location-lng").val(locationLng);
    jQuery("#meta-location-lat").val(locationLat);
    var hasLocation = function () {
        return locationLng || locationLat;
    };
    mapboxgl.accessToken = 'pk.eyJ1Ijoib2x6aW1tZXJlYmVyZyIsImEiOiJjajl3cHNyMDI3ZGp6MnhxeW1qZXVpdnk4In0.VcKf-JDSK6ltrowMopc-pQ';
    var map = new mapboxgl.Map({
        center: [
            (hasLocation() ? locationLng : 8.5786004),
            (hasLocation() ? locationLat : 47.2690051),
        ],
        container: 'meta_location_map',
        style: 'mapbox://styles/mapbox/outdoors-v10',
        scrollZoom: false,
        zoom: 12,
    });
    map.addControl(new mapboxgl.NavigationControl());
    var marker = new mapboxgl.Marker().setLngLat([locationLng, locationLat]);
    if (hasLocation()) {
        marker.addTo(map);
    } else {
        marker.remove();
    }
    var lastMouseDown = 0;
    map.on('mousedown', function (map, marker, e) {
        lastMouseDown = new Date().getTime();
        setTimeout(function (lastMouseDownExpectation) {
            if (lastMouseDown==lastMouseDownExpectation) {
                marker.setLngLat(e.lngLat);
                marker.addTo(map);
                jQuery("#meta-location-lng").val(e.lngLat.lng);
                jQuery("#meta-location-lat").val(e.lngLat.lat);
            }
        }.bind(this, lastMouseDown), 1000);
    }.bind(this, map, marker));
    map.on('mouseup', function (e) {
        lastMouseDown = 0;
    });

    if (solv > 0) {
        jQuery("#termine-timerange-start-date").prop('disabled', true);
        jQuery("#termine-timerange-start-time").prop('disabled', true);
        jQuery("#termine-timerange-end-date").prop('disabled', true);
        jQuery("#termine-timerange-end-time").prop('disabled', true);
        jQuery('#meta_location_map').css({'opacity': 0.5, 'pointer-events': 'none'});
    }

    jQuery('#meta_meta').val(meta || '');
    jQuery('#meta_solv').val(solv || '');
    jQuery('body').on('click', function (e) {
        setTimeout(function () {
            if (jQuery('#meta_solv').is(':focus')) {
                jQuery('#solv-completion').show();
            } else {
                jQuery('#solv-completion').hide();
            }
        }, 1)
    });
    jQuery('#meta_solv').on('keyup', function () {
        if (window.solvSearchTimeout) {
            return;
        }
        window.solvSearchTimeout = setTimeout(function () {
            window.solvSearchTimeout = null;
            jQuery.post(ajaxurl, {
                'action': 'get_solv_links',
                'search': jQuery('#meta_solv').val(),
            }, function (response) {
                var tmp = JSON.parse(response);
                var options = tmp.map(function (option) {
                    return '<div data-solv="' + option.solv_uid + '" class="completion-option">' + option.event_date + ': ' + option.name + ' (' + option.solv_uid + ')</div>';
                });
                jQuery('#solv-completion').html(options.join(''));
                jQuery('#solv-completion .completion-option').on('click', function (e) {
                    solv = e.target.getAttribute('data-solv');
                    jQuery('#meta_solv').val(solv);
                })
            });
        }, 500);
    })
    jQuery('#meta_go2ol').val(go2ol || '');
    </script>
    <?php
}

function olz_register_termine_meta_boxes() {
    add_meta_box('termine', 'Termin', 'olz_termine_meta_box', 'termine', 'normal', 'high');
}
add_action('add_meta_boxes_termine', 'olz_register_termine_meta_boxes');


function save_termintyp_meta($term_id, $tt_id) {
    $res = preg_match("/\#([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})/", $_POST['color'], $matches);
    add_term_meta($term_id, 'color_red', hexdec($matches[1]), true);
    add_term_meta($term_id, 'color_green', hexdec($matches[2]), true);
    add_term_meta($term_id, 'color_blue', hexdec($matches[3]), true);
}
add_action('created_termin-typ', 'save_termintyp_meta', 10, 2);

function update_termintyp_meta($term_id, $tt_id) {
    $res = preg_match("/\#([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})/", $_POST['color'], $matches);
    update_term_meta($term_id, 'color_red', hexdec($matches[1]));
    update_term_meta($term_id, 'color_green', hexdec($matches[2]));
    update_term_meta($term_id, 'color_blue', hexdec($matches[3]));
}
add_action('edited_termin-typ', 'update_termintyp_meta', 10, 2);

function olz_termintyp_color_add_field($taxonomy) {
    echo "<div class='form-field term-group-wrap'>
        <label for='tag-color'>Farbe</label>
        <input type='color' name='color' id='tag-color'>
    </div>";
    print_r($taxonomy);
}
add_action('termin-typ_add_form_fields', 'olz_termintyp_color_add_field', 10, 2);

function olz_termintyp_color_edit_field($term, $taxonomy) {
    $red = str_pad(dechex(get_term_meta($term->term_id, 'color_red', true)), 2, "0", STR_PAD_LEFT);
    $green = str_pad(dechex(get_term_meta($term->term_id, 'color_green', true)), 2, "0", STR_PAD_LEFT);
    $blue = str_pad(dechex(get_term_meta($term->term_id, 'color_blue', true)), 2, "0", STR_PAD_LEFT);
    $color = "#".$red.$green.$blue;
    echo "<tr class='form-field term-group-wrap'>
        <th scope='row'><label for='tag-color'>Farbe</label></th>
        <td><input type='color' name='color' value='".$color."' id='tag-color'></td>
    </tr>";
    print_r($term, $taxonomy);
}
add_action('termin-typ_edit_form_fields', 'olz_termintyp_color_edit_field', 10, 2);


function olz_termine_posts_columns($defaults) {
    $arr = array();
    $arr['cb'] = $defaults['cb'];
    $arr['termine-date'] = "Zeit";
    $arr['title'] = $defaults['title'];
    $arr['termine-location'] = "Ort";
    foreach ($defaults as $key=>$value) {
        if ($key!='cb' && $key!='title') $arr[$key] = $value;
    }
    return $arr;
}
function olz_termine_posts_custom_columns($column_name, $id) {
    if ($column_name=='termine-date') {
        $time_start = strtotime(get_post_meta($id, 'timerange_start', true));
        $time_end = strtotime(get_post_meta($id, 'timerange_end', true));
        echo date("d.m.Y H:i", $time_start).($time_start!=$time_end?"<br />\n".date("d.m.Y H:i", $time_end):"");
    } else if ($column_name=='termine-location') {
        $lat = get_post_meta($id, 'location_lat', true);
        $lng = get_post_meta($id, 'location_lng', true);
        if (!$lat && !$lng) {
            echo "";
        } else {
            $nice_lat = number_format($lat, 4, '.', '').($lat<0 ? 'S' : 'N');
            $nice_lng = number_format($lng, 4, '.', '').($lng<0 ? 'W' : 'E');
            echo $nice_lat.", ".$nice_lng;
        }
    }
}
function olz_termine_posts_sorting($columns) {
    $columns['termine-date'] = 'termine-date';
    return $columns;
}
function olz_termine_posts_orderby($vars) {
    if (isset($vars['orderby'])) {
        if ($vars['orderby']=='termine-date') {
            $vars = array_merge($vars, array(
                'orderby'=>'meta_value',
                'meta_key'=>'timerange_start',
            ));
        }
    }
    return $vars;
}
function olz_termine_head() {
    if ($_GET['post_type'] == 'termine') {
        ?>
<style>
    .column-termine-date {
        width:150px;
    }
    .column-termine-location {
        width:150px;
    }
</style>
        <?php
    }
}
add_filter('manage_termine_posts_columns', 'olz_termine_posts_columns', 5);
add_action('manage_termine_posts_custom_column', 'olz_termine_posts_custom_columns', 5, 2);
add_filter('manage_edit-termine_sortable_columns', 'olz_termine_posts_sorting');
add_filter('request', 'olz_termine_posts_orderby');
add_action('admin_head', 'olz_termine_head');

?>
