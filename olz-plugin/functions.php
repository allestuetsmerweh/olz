<?php

function dateval($str) {
    $timestamp = strtotime($str);
    return $timestamp ? date('Y-m-d', $timestamp) : null;
}

function datetimeval($str) {
    $timestamp = strtotime($str);
    return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
}

function dateandtimeval($datestr, $timestr) {
    if ($timestr) {
        return datetimeval($datestr.' '.$timestr);
    } else {
        return dateval($datestr);
    }
}

function update_meta_from_dict($post_id, $meta_key, $dict, $conversion=null, $set='default') {
    if (isset($dict[$meta_key])) {
        $value = $dict[$meta_key];
        if (is_callable($conversion)) {
            $value = call_user_func($conversion, $value);
            if ($set == 'default') {
                $set = $value;
            }
        }
        if (is_callable($set)) {
            $set = call_user_func($set, $value);
        }
        if ($set) {
            update_post_meta($post_id, $meta_key, $value);
        } else {
            delete_post_meta($post_id, $meta_key);
        }
    }
}

?>
