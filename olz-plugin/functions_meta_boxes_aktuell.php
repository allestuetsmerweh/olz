<?php

$post_type = 'aktuell';

function olz_aktuell_save_meta_box_data($post_id) {
    update_meta_from_dict($post_id, 'notification_first', $_POST, null, true);
    update_meta_from_dict($post_id, 'notification_last', $_POST, null, true);
    update_meta_from_dict($post_id, 'expiration', $_POST, null, true);
    update_meta_from_dict($post_id, 'disappearance', $_POST, null, true);
}
add_save_post_action($post_type, 'olz_aktuell_save_meta_box_data');

function olz_aktuell_timeline_meta_box($post) {
    wp_nonce_field('olz_save_meta_box_data', 'olz_meta_box_nonce');

    $notification_first = strtotime(get_post_meta($post->ID, 'notification_first', true));
    $notification_last = strtotime(get_post_meta($post->ID, 'notification_last', true));
    $expiration = strtotime(get_post_meta($post->ID, 'expiration', true));
    $disappearance = strtotime(get_post_meta($post->ID, 'disappearance', true));
    if (0<$notification_first) $notification_first = date("Y-m-d", $notification_first);
    else $notification_first = date("Y-m-d");
    if (0<$notification_last) $notification_last = date("Y-m-d", $notification_last);
    else $notification_last = date("Y-m-d");
    if (0<$expiration) $expiration = date("Y-m-d", $expiration);
    else $expiration = date("Y-m-d");
    if (0<$disappearance) $disappearance = date("Y-m-d", $disappearance);
    else $disappearance = date("Y-m-d");
    echo "<script>
    var notification_first = ".json_encode($notification_first).";
    var notification_last = ".json_encode($notification_last).";
    var expiration = ".json_encode($expiration).";
    var disappearance = ".json_encode($disappearance).";
    </script>";
    ?>
    <canvas id="aktuell_timeline" style="width:100%; height:40px;"></canvas>
    <table class="form-table">
        <tr><th scope="row"><label for="aktuell_notification_first">Erste Benachrichtigung: </label></th><td><input type='date' id='aktuell_notification_first' name='notification_first' value='' /> (z.B. Anmeldungs-Start)<p class="description">Ab diesem Datum werden die Besucher ein erstes Mal darauf aufmerksam gemacht</p></td></tr>
        <tr><th scope="row"><label for="aktuell_notification_last">Letzte Benachrichtigung: </label></th><td><input type='date' id='aktuell_notification_last' name='notification_last' value='' /> (z.B. 1 Woche vor Meldeschluss)<p class="description">Ab diesem Datum werden die Besucher, die den Hinweis weggeklickt haben, ein zweites Mal darauf aufmerksam gemacht</p></td></tr>
        <tr><th scope="row"><label for="aktuell_expiration">Ablaufen: </label></th><td><input type='date' id='aktuell_expiration' name='expiration' value='' /> (z.B. Meldeschluss)<p class="description">Kurz vor diesem Datum wird speziell darauf aufmerksam gemacht</p></td></tr>
        <tr><th scope="row"><label for="aktuell_disappearance">Verschwinden: </label></th><td><input type='date' id='aktuell_disappearance' name='disappearance' value='' /> (z.B. Datum des Anlasses oder Meldeschluss, wenn keine Nachmeldung möglich)<p class="description">Ab diesem Datum wird nichts mehr angezeigt</p></td></tr>
    </table>
    <script>
    var update_timeline = function () {
        var cnv = jQuery("#aktuell_timeline").get(0);
        var wid = jQuery("#aktuell_timeline").width();
        var hei = jQuery("#aktuell_timeline").height();
        cnv.width = wid;
        cnv.height = hei;
        var ctx = cnv.getContext("2d");
        var t1 = Date.parse(notification_first);
        var t2 = Date.parse(notification_last);
        var t3 = Date.parse(expiration);
        var t4 = Date.parse(disappearance);
        var t_range = t4-t1;
        var t0 = t1-86400000*5;
        var t5 = t4+86400000*5;
        var min_margin = Math.floor(t_range*0.1/86400000)*86400000;
        if (86400000*5<min_margin) {
            t0 = t1-min_margin;
            t5 = t4+min_margin;
        }
        var v_range = t5-t0;
        var wid_per_day = wid*86400000/v_range;
        if (20<wid_per_day) {
            var firstx = -1;
            for (var t=t0; t<t5; t+=86400000) {
                var d = new Date(t);
                var x = (t-t0)*wid_per_day/86400000;
                if (d.getDate()==1) {
                    if (firstx==-1) firstx = x;
                    ctx.fillRect(x, 0, 1, hei-10);
                    ctx.fillText(window.monthNames[d.getMonth()], x+2, 10);
                } else {
                    ctx.fillRect(x, 10, 1, hei-20);
                }
                ctx.fillText(d.getDate(), x+2, 20);
            }
            if (firstx==-1 || 100<firstx) {
                var d = new Date(t0);
                ctx.fillText(window.monthNames[d.getMonth()], 2, 10);
            }
        } else if (0.7<wid_per_day) {
            var firstx = -1;
            for (var t=t0; t<t5; t+=86400000) {
                var d = new Date(t);
                if (d.getDate()==1) {
                    var x = (t-t0)*wid_per_day/86400000;
                    if (d.getMonth()==0) {
                        if (firstx==-1) firstx = x;
                        ctx.fillRect(x, 0, 1, hei-10);
                        ctx.fillText(d.getYear()+1900, x+2, 10);
                    } else {
                        ctx.fillRect(x, 10, 1, hei-20);
                    }
                    ctx.fillText((3<wid_per_day?window.monthNames[d.getMonth()]:d.getMonth()+1), x+2, 20);
                }
            }
            if (firstx==-1 || 30<firstx) {
                var d = new Date(t0);
                ctx.fillText(d.getYear()+1900, 2, 10);
            }
        } else {
            for (var t=t0; t<t5; t+=86400000) {
                var d = new Date(t);
                if (d.getDate()==1 && d.getMonth()==0) {
                    var x = (t-t0)*wid_per_day/86400000;
                    ctx.fillRect(x, 10, 1, hei-20);
                    ctx.fillText(d.getYear()+1900, x+2, 20);
                }
            }
        }
        var x1 = (t1-t0)*wid_per_day/86400000;
        var x2 = (t2-t0)*wid_per_day/86400000;
        var x3 = (t3-t0)*wid_per_day/86400000;
        var x4 = (t4-t0)*wid_per_day/86400000;
        ctx.fillStyle = "rgb(0,200,0)";
        ctx.fillRect(x1, hei-10, (x2-x1), 10);
        ctx.fillStyle = "rgb(255,200,0)";
        ctx.fillRect(x2, hei-10, (x3-x2), 10);
        ctx.fillStyle = "rgb(230,0,0)";
        ctx.fillRect(x3, hei-10, (x4-x3), 10);
    };
    jQuery("#aktuell_notification_first").val(notification_first);
    jQuery("#aktuell_notification_last").val(notification_last);
    jQuery("#aktuell_expiration").val(expiration);
    jQuery("#aktuell_disappearance").val(disappearance);
    update_timeline();
    jQuery(window).resize(function () {
        update_timeline();
    });
    var update_notification_first = function (e) {
        notification_first = jQuery("#aktuell_notification_first").val();
        if (notification_last<notification_first) {
            notification_last = notification_first;
            jQuery("#aktuell_notification_last").val(notification_last);
        }
        if (expiration<notification_first) {
            expiration = notification_first;
            jQuery("#aktuell_expiration").val(expiration);
        }
        if (disappearance<notification_first) {
            disappearance = notification_first;
            jQuery("#aktuell_disappearance").val(disappearance);
        }
        update_timeline();
    };
    var update_notification_last = function (e) {
        notification_last = jQuery("#aktuell_notification_last").val();
        if (notification_last<notification_first) {
            notification_first = notification_last;
            jQuery("#aktuell_notification_first").val(notification_first);
        }
        if (expiration<notification_last) {
            expiration = notification_last;
            jQuery("#aktuell_expiration").val(expiration);
        }
        if (disappearance<notification_last) {
            disappearance = notification_last;
            jQuery("#aktuell_disappearance").val(disappearance);
        }
        update_timeline();
    };
    var update_expiration = function (e) {
        expiration = jQuery("#aktuell_expiration").val();
        if (expiration<notification_first) {
            notification_first = expiration;
            jQuery("#aktuell_notification_first").val(notification_first);
        }
        if (expiration<notification_last) {
            notification_last = expiration;
            jQuery("#aktuell_notification_last").val(notification_last);
        }
        if (disappearance<expiration) {
            disappearance = expiration;
            jQuery("#aktuell_disappearance").val(disappearance);
        }
        update_timeline();
    };
    var update_disappearance = function (e) {
        disappearance = jQuery("#aktuell_disappearance").val();
        if (disappearance<notification_first) {
            notification_first = disappearance;
            jQuery("#aktuell_notification_first").val(notification_first);
        }
        if (disappearance<notification_last) {
            notification_last = disappearance;
            jQuery("#aktuell_notification_last").val(notification_last);
        }
        if (disappearance<expiration) {
            expiration = disappearance;
            jQuery("#aktuell_expiration").val(expiration);
        }
        update_timeline();
    };
    jQuery("#aktuell_notification_first").on('blur', update_notification_first);
    jQuery("#aktuell_notification_last").on('blur', update_notification_last);
    jQuery("#aktuell_expiration").on('blur', update_expiration);
    jQuery("#aktuell_disappearance").on('blur', update_disappearance);
    </script>
    <?php
}

function olz_register_aktuell_meta_boxes() {
    add_meta_box('aktuell-timeline', 'Zeitlinie', 'olz_aktuell_timeline_meta_box', 'aktuell', 'normal', 'high');
}
add_action('add_meta_boxes_aktuell', 'olz_register_aktuell_meta_boxes');

?>
