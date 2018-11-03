<?php

require_once(dirname(__FILE__).'/server_config_link.php');
require_once(dirname(__FILE__).'/common_link/wgs84_ch1903.php');
require_once(dirname(__FILE__).'/utils/general.php');
require_once(dirname(__FILE__).'/utils/solv.php');
require_once($deployment['public-root'].'/wp-load.php');

function update_solv() {
    global $wpdb, $solv_events_fields;
    header("Content-Type:text/plain;charset=utf-8");

    init_solv();

    $current_year = date('Y');
    $year = 2006;
    while (true) {
        if ($year > $current_year + 5) {
            echo "Too far future\n";
            break;
        }
        $last_updated_key = 'last_updated_'.$year;
        $update_interval = get_update_interval($year);
        $last_updated = get_solv_setting($last_updated_key, '0000-00-00 00:00:00');
        $current_date = date('Y-m-d H:i:s');
        if (strtotime($last_updated) > strtotime($current_date) - $update_interval) {
            echo $year . ": SKIP (last updated " . $last_updated . ")\n";
            $year++;
            continue;
        }
        set_solv_setting($last_updated_key, $current_date);

        $data = load_solv_fixtures($year);

        echo $year . ": " . count($data) . " Events\n";

        $last_modification = array();
        $old_solv_uids = array();
        $res_e = $wpdb->get_results($wpdb->prepare("SELECT solv_uid, last_modification FROM solv_events WHERE YEAR(event_date)=%d", $year));
        foreach ($res_e as $index => $row_e) {
            $last_modification[$row_e->solv_uid] = $row_e->last_modification;
            $old_solv_uids[$row_e->solv_uid] = false;
        }

        foreach ($data as $data_index => $csv_row) {
            $uid = $csv_row['unique_id'];
            if (!isset($last_modification[$uid])) {
                $sql_key = array();
                $sql_val_ph = array();
                $sql_val = array();
                $event = array();
                foreach ($solv_events_fields as $field_index=>$field) {
                    $value = call_user_func($field[4], $csv_row[$field[2]]);
                    $sql_key[] = '`'.$field[0].'`';
                    $sql_val_ph[] = $field[3];
                    $sql_val[] = $value;
                    $event[$field[0]] = $value;
                }
                $wpdb->query($wpdb->prepare("INSERT INTO solv_events (".implode(", ", $sql_key).") VALUES (".implode(", ", $sql_val_ph).")", $sql_val));
                on_solv_updated(null, $event);
                echo "INSERTED ".$uid."\n";
            } else if ($last_modification[$uid] != $csv_row['last_modification']) {
                $sql_tmp = array();
                $sql_val = array();
                $old_event_orig = $wpdb->query($wpdb->prepare("SELECT * FROM solv_events WHERE solv_uid='%d'", $uid));
                $old_event = array();
                $event = array();
                foreach ($solv_events_fields as $field_index=>$field) {
                    $old_value = call_user_func($field[4], $old_event_orig[$field[2]]);
                    $value = call_user_func($field[4], $csv_row[$field[2]]);
                    $sql_tmp[] = "`".$field[0]."`='".$field[3]."'";
                    $sql_val[] = $value;
                    $old_event[$field[0]] = $old_value;
                    $event[$field[0]] = $value;
                }
                $sql_val[] = $uid;
                $wpdb->query($wpdb->prepare("UPDATE solv_events SET ".implode(", ", $sql_tmp)." WHERE solv_uid='%d'", $sql_val));
                on_solv_updated($old_event, $event);
                echo "UPDATED ".$uid."\n";
            }
            $old_solv_uids[$uid] = true;
        }
        foreach ($old_solv_uids as $uid => $still_exists) {
            if (!$still_exists) {
                $old_event_orig = $wpdb->query($wpdb->prepare("SELECT * FROM solv_events WHERE solv_uid='%d'", $uid));
                $old_event = array();
                foreach ($solv_events_fields as $field_index=>$field) {
                    $old_value = call_user_func($field[4], $old_event_orig[$field[2]]);
                    $old_event[$field[0]] = $old_value;
                }
                $wpdb->delete('solv_events', array('solv_uid' => intval($uid)));
                on_solv_updated($old_event, null);
                echo "DELETED ".$uid."\n";
            }
        }

        /*
        $url = 'https://www.o-l.ch/cgi-bin/fixtures?mode=results&year='.$year.'&json=1';
        echo "Loading $url...\n";
        $resp = json_decode(utf8_encode(file_get_contents($url)));
        foreach ($resp->ResultLists as $index => $result) {
            if (!isset($result->UniqueID) || $result->UniqueID==0) {
                continue;
            }
            $rank_data[$result->UniqueID] = $result;
        }
        */

        if (count($data) < 3) {
            echo "Too few data\n";
            break;
        }
        $year++;

    /*
        foreach ($data as $index => $solv_event) {
            if ($solv_event['kind'] != 'foot') {
                echo "    IGNORE: Non-foot: ".$solv_event['kind']."\n";
                continue;
            }
            if ($solv_event['national'] != 1 && $solv_event['region'] != 'ZH/SH' && $solv_event['region'] != '') {
                echo "    IGNORE: Foreign regional: ".$solv_event['region']."\n";
                continue;
            }
            $bq = new WP_Query(array(
                'post_type' => array('termine'),
                'posts_per_page' => 1,
                'meta_key' => 'solv',
                'meta_value' => $solv_event['unique_id'],
                'meta_compare' => '='
            ));
            if ($bq->have_posts()) {
                echo "    IGNORE: Existing ".$solv_event['unique_id']."\n";
                continue;
            }
            echo json_encode($solv_event)."\n";
        }
    */
    }
}

function init_solv() {
    global $wpdb, $solv_events_fields;

    $settings_tbl = $wpdb->get_row("SHOW TABLES LIKE 'solv_settings'");
    if (!$settings_tbl) {
        $wpdb->query("CREATE TABLE solv_settings (
            k VARCHAR(255) NOT NULL PRIMARY KEY,
            value TEXT NOT NULL
        )");
    }
    $db_version = intval(get_solv_setting('db_version', '0'));
    if ($db_version < 1) {
        $wpdb->query("DROP TABLE IF EXISTS solv_events");
        $col_defs = implode(', ', array_map(function ($field) {
            return $field[0] . ' ' . $field[1];
        }, $solv_events_fields));
        $wpdb->query("CREATE TABLE solv_events (".$col_defs.")");
        $db_version = 1;
    }
    set_solv_setting('db_version', $db_version);
    return $db_version;
}

function get_solv_setting($key, $default) {
    global $wpdb;
    $get_stmt = "SELECT value FROM solv_settings WHERE k=%s";
    $result = $wpdb->get_row($wpdb->prepare($get_stmt, $key));
    return $result ? $result->value : $default;
}

function set_solv_setting($key, $value) {
    global $wpdb;
    $wpdb->delete('solv_settings', array('k' => $key));
    $wpdb->insert('solv_settings', array('k' => $key, 'value' => $value));
}

function get_update_interval($year) {
    $cur_year = date('Y');
    if ($year < $cur_year) {
        return add_random_noise(90 * 86400);
    } else if ($year == $cur_year) {
        return add_random_noise(3 * 3600);
    } else if ($year <= $cur_year + 1) {
        return add_random_noise(10 * 86400);
    } else {
        return add_random_noise(90 * 86400);
    }
}

function add_random_noise($value, $factor=0.05) {
    $range = $value * $factor;
    return $value - $range + rand(-1000, 1000) * $range / 1000;
}

function on_solv_updated($old_event, $event) {
    if ($event != null) {
        insert_if_necessary($event);
    }
}

function insert_if_necessary($event) {
    $is_foot = $event['kind'] == 'foot';
    $is_national = $event['national'] == 1;
    $is_regional = $event['region'] == 'ZH/SH';
    $is_milchsuppe = preg_match('/milchsuppe/', $event['name']);

    if (!$is_foot) {
        echo "    IGNORE: Non-foot: ".$event['kind']."\n";
        return;
    }
    if (!$is_national && !$is_regional && !$is_milchsuppe) {
        echo "    IGNORE: Foreign regional: ".$event['region']."\n";
        return;
    }
    $bq = new WP_Query(array(
        'post_type' => array('termine'),
        'posts_per_page' => 1,
        'meta_key' => 'solv',
        'meta_value' => $event['solv_uid'],
        'meta_compare' => '='
    ));
    if ($bq->have_posts()) {
        echo "    IGNORE: Existing ".$event['solv_uid']."\n";
        return;
    }

    $timestamp = strtotime($event['event_date']);
    $text = "";

    $postid = wp_insert_post(array(
        'post_content'      => nl2br($text),
        'post_name'         => strtolower(str_replace(' ', '_', $event['name']).'_'.date('Y', $timestamp)),
        'post_title'        => $event['name'],
        'post_status'       => 'publish',
        'post_type'         => 'termine',
        'post_author'       => "",
        'ping_status'       => 'closed',
        'post_parent'       => 0,
        'to_ping'           => '',
        'pinged'            => '',
        'post_password'     => '',
        'post_excerpt'      => "",
        'post_date'         => date("Y-m-d H:i:s"),
        'post_date_gmt'     => gmdate("Y-m-d H:i:s"),
        'comment_status'    => 'open',
    ));

    add_post_meta($postid, 'timerange_start', date('Y-m-d', $timestamp));
    add_post_meta($postid, 'timerange_end', date('Y-m-d', $timestamp));
    if (0<$event['coord_x'] && 0<$event['coord_y']) {
        add_post_meta($postid, 'location_lat', CHtoWGSlat(floatval($event['coord_x']), floatval($event['coord_y'])));
        add_post_meta($postid, 'location_lng', CHtoWGSlong(floatval($event['coord_x']), floatval($event['coord_y'])));
    }
    add_post_meta($postid, 'solv', $event['solv_uid']);
    wp_set_object_terms($postid, 'ol', 'termin-typ', true);
}

?>
