<?php

/*
* Organigramm Editor
*/

function organigramm_data() {
    $data = array('organigramm'=>array(0=>array('name'=>"OL Zimmerberg", 'besetzung'=>array(), 'children'=>array(), 'level'=>0)), 'besetzungen'=>array(), 'users'=>array());
    $queue = array(0);
    for ($i=0; $i<count($queue); $i++) {
        $q = new WP_Query(array('post_type'=>'organigramm', 'post_parent'=>$queue[$i], 'orderby'=>'menu_order', 'order'=>'ASC'));
        for ($j=0; $j<count($q->posts); $j++) {
            $ressort = array('name'=>$q->posts[$j]->post_title, 'besetzung'=>array(), 'children'=>array(), 'level'=>$data['organigramm'][$queue[$i]]['level']+1);
            $bq = new WP_Query(array('post_type'=>'besetzung', 'meta_key'=>'organigramm', 'meta_value'=>$q->posts[$j]->ID, 'meta_compare'=>'='));
            for ($l=0; $l<count($bq->posts); $l++) {
                $uid = get_post_meta($bq->posts[$l]->ID, 'user', true);
                $u = new WP_User($uid);
                $ressort['besetzung'][] = intval($bq->posts[$l]->ID);
                $data['besetzungen'][intval($bq->posts[$l]->ID)] = array('uid'=>intval($uid));
                $data['users'][intval($uid)] = array('name'=>$u->display_name);
            }
            $data['organigramm'][$queue[$i]]['children'][] = intval($q->posts[$j]->ID);
            $data['organigramm'][intval($q->posts[$j]->ID)] = $ressort;
            $queue[] = intval($q->posts[$j]->ID);
        }
    }
    return $data;
}

function olz_ajax_add_besetzung() {
    $args = array(
        'post_type' => 'besetzung',
        'post_status' => 'publish',
    );
    $postid = wp_insert_post($args, $wp_error);
    update_post_meta($postid, 'user', intval($_POST['userid']));
    update_post_meta($postid, 'organigramm', intval($_POST['organigrammid']));
    return organigramm_data();
}
add_action('wp_ajax_add_besetzung', olz_ajaxify('olz_ajax_add_besetzung'));

function olz_ajax_delete_besetzung() {
    wp_delete_post($_POST['besetzungid']);
    return organigramm_data();
}
add_action('wp_ajax_delete_besetzung', olz_ajaxify('olz_ajax_delete_besetzung'));

function olz_ajax_add_organigramm() {
    $args = array(
        'post_type' => 'organigramm',
        'post_status' => 'publish',
        'post_parent' => intval($_POST['parentid']),
        'post_title' => '',
    );
    $postid = wp_insert_post($args, $wp_error);
    return organigramm_data();
}
add_action('wp_ajax_add_organigramm', olz_ajaxify('olz_ajax_add_organigramm'));

function olz_ajax_delete_organigramm() {
    wp_delete_post($_POST['organigrammid']);
    return organigramm_data();
}
add_action('wp_ajax_delete_organigramm', olz_ajaxify('olz_ajax_delete_organigramm'));

function olz_ajax_edit_organigramm_title() {
    $args = array(
        'ID' => intval($_POST['organigrammid']),
        'post_title' => $_POST['title'],
    );
    $postid = wp_update_post($args, $wp_error);
    return organigramm_data();
}
add_action('wp_ajax_edit_organigramm_title', olz_ajaxify('olz_ajax_edit_organigramm_title'));

function olz_ajax_edit_organigramm_order() {
    $ids_positions = json_decode($_POST['ids_positions'], true);
    foreach ($ids_positions as $index => $id_position) {
        $args = array(
            'ID' => intval($id_position[0]),
            'menu_order' => floatval($id_position[1]),
        );
        $postid = wp_update_post($args, $wp_error);
    }
    return organigramm_data();
}
add_action('wp_ajax_edit_organigramm_order', olz_ajaxify('olz_ajax_edit_organigramm_order'));

function olz_organigramm_page() {
    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">Organigramm</h1>';
    $uq = get_users();
    echo '<div id="new-besetzung"><div><select style="width:100%;" id="new-besetzung-select">';
    for ($i=0; $i<count($uq); $i++) {
        echo '<option value="'.intval($uq[$i]->ID).'">'.($uq[$i]->data->display_name).'</option>';
    }
    echo '</select></div><div><button type="button" id="new-besetzung-cancel">Abbrechen</button> <button type="submit" id="new-besetzung-submit">Speichern</button></div></div>';
    echo '<div id="edit-title"><div><input type="text" style="width:100%;" id="edit-title-input" /></div><button type="button" id="edit-title-cancel">Abbrechen</button><button type="submit" id="edit-title-submit">Speichern</button></div>';
    echo '<div id="organigramm-0"></div>';
    echo '</div>';
    ?>
    <script type="text/javascript">
    var data = {};

    function add_besetzung(organigrammid) {
        jQuery('#new-besetzung').show();
        jQuery('#new-besetzung-select').focus();
        jQuery('#new-besetzung-cancel').unbind().on('click', function () {
            jQuery('#new-besetzung').hide();
        });
        jQuery('#new-besetzung-submit').unbind().on('click', function () {
            confirm_add_besetzung(organigrammid);
        });
    }
    function confirm_add_besetzung(organigrammid) {
        var userid = jQuery('#new-besetzung-select').val();
        jQuery.post(ajaxurl, {
            'action': 'add_besetzung',
            'userid': userid,
            'organigrammid': organigrammid,
        }, function (organigrammid, response) {
            var tmp = JSON.parse(response);
            if (tmp && tmp.organigramm) {
                data = tmp;
                redraw_organigramm(organigrammid);
            } else {
                alert("Ein Fehler ist aufgetreten");
            }
            jQuery('#new-besetzung').hide();
        }.bind(this, organigrammid));
    }
    function delete_besetzung(besetzungid) {
        var res = window.confirm("Wirklich aus dem Organigramm entfernen?");
        if (!res) return;
        jQuery.post(ajaxurl, {
            'action': 'delete_besetzung',
            'besetzungid': besetzungid,
        }, function (response) {
            var tmp = JSON.parse(response);
            if (tmp && tmp.organigramm) {
                data = tmp;
                redraw_organigramm(0);
            } else {
                alert("Ein Fehler ist aufgetreten");
            }
        });
    }
    function add_organigramm(parentid) {
        jQuery.post(ajaxurl, {
            'action': 'add_organigramm',
            'parentid': parentid,
        }, function (response) {
            var tmp = JSON.parse(response);
            if (tmp && tmp.organigramm) {
                data = tmp;
                redraw_organigramm(parentid);
            } else {
                alert("Ein Fehler ist aufgetreten");
            }
        });
    }
    function delete_organigramm(organigrammid) {
        var res = window.confirm("Wirklich aus dem Organigramm entfernen?");
        if (!res) return;
        jQuery.post(ajaxurl, {
            'action': 'delete_organigramm',
            'organigrammid': organigrammid,
        }, function (response) {
            var tmp = JSON.parse(response);
            if (tmp && tmp.organigramm) {
                data = tmp;
                redraw_organigramm(0);
            } else {
                alert("Ein Fehler ist aufgetreten");
            }
        });
    }
    function edit_title(organigrammid) {
        jQuery('#edit-title').show();
        jQuery('#edit-title-input').val(data.organigramm[organigrammid].name);
        jQuery('#edit-title-input').focus();
        jQuery('#edit-title-cancel').unbind().on('click', function () {
            jQuery('#edit-title').hide();
        });
        jQuery('#edit-title-submit').unbind().on('click', function () {
            confirm_edit_title(organigrammid);
        });
    }
    function confirm_edit_title(organigrammid) {
        var title = jQuery('#edit-title-input').val();
        jQuery.post(ajaxurl, {
            'action': 'edit_organigramm_title',
            'organigrammid': organigrammid,
            'title': title,
        }, function (response) {
            var tmp = JSON.parse(response);
            if (tmp && tmp.organigramm) {
                data = tmp;
                redraw_organigramm(organigrammid);
            } else {
                alert("Ein Fehler ist aufgetreten");
            }
            jQuery('#edit-title').hide();
        });
    }
    function edit_organigramm_order(idsPositions) {
        jQuery.post(ajaxurl, {
            'action': 'edit_organigramm_order',
            'ids_positions': JSON.stringify(idsPositions),
        }, function (response) {
            var tmp = JSON.parse(response);
            if (tmp && tmp.organigramm) {
                data = tmp;
                redraw_organigramm(0);
            } else {
                alert("Ein Fehler ist aufgetreten");
            }
        });
    }
    function redraw_organigramm(id) {
        var jElem = jQuery('#organigramm-'+id);
        jElem.empty();
        var org = data.organigramm[id];
        var html = "";
        var nameAndBesetzungen = function () {
            var html = '';
            html += '<div data-id="'+id+'" class="delete delete-organigramm">x</div>';
            html += '<div data-id="'+id+'" class="title edit-title">'+(org.name.length==0?'???':org.name)+'</div>';
            html += '<div id="besetzungen-'+id+'">';
            for (var i=0; i<org.besetzung.length; i++) {
                var bid = org.besetzung[i];
                var besetzung = data.besetzungen[bid];
                var username = data.users[besetzung.uid].name;
                html += '<div class="besetzung" id="besetzung-'+bid+'"><div data-id="'+bid+'" class="delete delete-besetzung">x</div> '+username+'</div>';
            }
            html += '</div>';
            html += '<div data-id="'+id+'" class="add add-besetzung">Neue Besetzung</div>';
            return html;
        };
        if (org.level==0) {
            html += '<div data-id="'+id+'" class="organigramm candrop-x">';
            for (var i=0; i<org.children.length; i++) {
                html += '<div data-id="'+org.children[i]+'" class="ressort draggable" id="organigramm-'+org.children[i]+'" draggable="true"></div>';
            }
            html += '<div class="ressort"><div data-id="0" class="box add add-organigramm">Neues Ressort</div></div></div>';
        } else if (org.level==1) {
            html += '<div class="box">';
            html += nameAndBesetzungen();
            html += '</div><div data-id="'+id+'" class="candrop-y">';
            for (var i=0; i<org.children.length; i++) {
                html += '<div class="boxconn"></div><div data-id="'+org.children[i]+'" class="box draggable" id="organigramm-'+org.children[i]+'" draggable="true"></div>';
            }
            html += '<div class="boxconn"></div></div><div data-id="'+id+'" class="box add add-organigramm">Neues Sub-Ressort</div>';
        } else if (org.level==2) {
            html += nameAndBesetzungen();
            html += '<div data-id="'+id+'" class="candrop-y">';
            for (var i=0; i<org.children.length; i++) {
                html += '<div data-id="'+org.children[i]+'" class="sub box draggable" id="organigramm-'+org.children[i]+'" draggable="true"></div>';
            }
            html += '</div><div data-id="'+id+'" class="box add add-organigramm">Neue Unterteilung</a></div>';
        } else if (org.level==3) {
            html += nameAndBesetzungen();
        }
        jElem.html(html);
        for (var i=0; i<org.children.length; i++) {
            redraw_organigramm(org.children[i]);
        }
        jQuery('.delete-organigramm').unbind().on('click', function (e) {
            delete_organigramm(e.target.getAttribute('data-id'));
        });
        jQuery('.delete-besetzung').unbind().on('click', function (e) {
            delete_besetzung(e.target.getAttribute('data-id'));
        });
        jQuery('.add-organigramm').unbind().on('click', function (e) {
            add_organigramm(e.target.getAttribute('data-id'));
        });
        jQuery('.add-besetzung').unbind().on('click', function (e) {
            add_besetzung(e.target.getAttribute('data-id'));
        });
        jQuery('.edit-title').unbind().on('click', function (e) {
            edit_title(e.target.getAttribute('data-id'));
        });
        jQuery('.draggable').unbind().on('dragstart', function (e) {
            e.originalEvent.dataTransfer.setData('application/x-olz-organigramm', e.target.getAttribute('data-id'));
        });
        var onDrop = function (isX, e) {
            var org = data.organigramm[e.currentTarget.getAttribute('data-id')];
            var dragId = parseInt(e.originalEvent.dataTransfer.getData('application/x-olz-organigramm'));
            if (org.children.indexOf(dragId)===-1) {
                return;
            }
            var idsPositions = org.children.map(function (child) {
                var rect = jQuery('#organigramm-'+child).get(0).getBoundingClientRect();
                if (isX) {
                    return [child, child===dragId ? e.clientX : rect.left + rect.width/2];
                } else {
                    return [child, child===dragId ? e.clientY : rect.top + rect.height/2];
                }
            }).sort(function (a, b) {
                return a[1] - b[1];
            }).map(function (child, index) {
                return [child[0], index];
            });
            edit_organigramm_order(idsPositions);
        }
        jQuery('.candrop-x').unbind().on('drop', onDrop.bind(this, true));
        jQuery('.candrop-y').unbind().on('drop', onDrop.bind(this, false));
    }
    <?php echo "data = ".json_encode(organigramm_data()).";\n"; ?>
    jQuery(document).ready(function () {
        redraw_organigramm(0);
    });
    </script>
    <?php
}

?>
