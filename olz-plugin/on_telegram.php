#!/usr/local/bin/php
<?php

require_once(dirname(__FILE__).'/server_config_link.php');
require_once(dirname(__FILE__).'/functions_telegram.php');


$event = telegram_event();
$msg_text = telegram_event('message.text', '');
$msg_chat_id = telegram_event('message.chat.id', '');
$msg_user_id = telegram_event('message.from.id', '');

telegram_api('sendChatAction', array(
    'chat_id' => $msg_chat_id,
    'action' => 'typing',
));

require_once($deployment['public-root'].'/wp-load.php');
echo json_encode($event, JSON_PRETTY_PRINT);


function get_wp_user() {
    $uq = new WP_User_Query(array('meta_key' => 'telegram-chat-id', 'meta_value' => $msg_chat_id));
    foreach ($uq->results as $key => $user) {
        return $user;
    }
    $uq = new WP_User_Query(array('meta_key' => 'telegram-user-id', 'meta_value' => $msg_user_id));
    foreach ($uq->results as $key => $user) {
        return $user;
    }
    return null;
}

function set_wp_user($msg_pin, $msg_chat_id, $msg_user_id) {
    $uq = new WP_User_Query(array('meta_key' => 'telegram-pin', 'meta_value' => $msg_pin));
    $num_results = count($uq->results);
    if ($num_results != 1) {
        return "Beim Einloggen ist ein Fehler aufgetreten:\n$num_results Benutzer mit diesem PIN.";
    }
    $wp_user = $uq->results[0];
    update_user_meta($wp_user->ID, 'telegram-chat-id', $msg_chat_id);
    update_user_meta($wp_user->ID, 'telegram-user-id', $msg_user_id);
    return $wp_user;
}

$wp_user = get_wp_user();


// Login Link

if (preg_match('/^\/start ([A-Za-z0-9]{6})$/', $msg_text, $matches)) {
    $wp_user = set_wp_user($matches[1], $msg_chat_id, $msg_user_id);
    telegram_answer($msg_chat_id, "Hallo, ".$wp_user->first_name."!");
}


if ($wp_user == null) {
    if (preg_match('/^\?$/', $msg_text)) {
        telegram_answer($msg_chat_id, "*Du bist nicht eingeloggt*\nBitte gib diesen Code hier ein:");
    }
    if (preg_match('/^\s*([A-Za-z0-9]{6})\s*$/', $msg_text, $matches)) {
        $wp_user = set_wp_user($matches[1], $msg_chat_id, $msg_user_id);
        telegram_answer($msg_chat_id, "Hallo, ".$wp_user->first_name."!");
    }
    telegram_answer($msg_chat_id, "Bitte gib den Code ein:  (Hilfe: \"?\")");
}

telegram_answer($msg_chat_id, "Hä?");

/*
echo json_encode(telegram_api('sendMessage', array(
    'chat_id' => '17089367',
    'text' => 'Hoi.',
)), JSON_PRETTY_PRINT);
*/
/*
echo json_encode(telegram_api('sendPhoto', array(
    'chat_id' => '17089367',
    'photo' => 'https://github.com/mediaelement/mediaelement-files/raw/master/big_buck_bunny.jpg',
    'caption' => 'Lueged mal da!',
)), JSON_PRETTY_PRINT);
*/
/*
echo json_encode(telegram_api('sendAudio', array(
    'chat_id' => '17089367',
    'audio' => 'https://github.com/mediaelement/mediaelement-files/blob/master/AirReview-Landmarks-02-ChasingCorporate.mp3?raw=true',
    'caption' => 'Losed mal da!',
)), JSON_PRETTY_PRINT);
*/
/*
echo json_encode(telegram_api('sendDocument', array(
    'chat_id' => '17089367',
    'document' => 'https://github.com/mediaelement/mediaelement-files/blob/master/echo-hereweare.webm?raw=true',
    'caption' => 'Läsed mal da!',
)), JSON_PRETTY_PRINT);
*/
/*
echo json_encode(telegram_api('sendVideo', array(
    'chat_id' => '17089367',
    'video' => 'https://github.com/mediaelement/mediaelement-files/blob/master/big_buck_bunny.mp4?raw=true',
    'caption' => 'Lueged mal da!',
)), JSON_PRETTY_PRINT);
*/
/*
echo json_encode(telegram_api('sendVideoNote', array(
    'chat_id' => '17089367',
    'video_note' => 'https://github.com/mediaelement/mediaelement-files/blob/master/big_buck_bunny.mp4?raw=true',
)), JSON_PRETTY_PRINT);
*/
/*
echo json_encode(telegram_api('sendLocation', array(
    'chat_id' => '17089367',
    'latitude' => '47.275593',
    'longitude' => '8.554580',
)), JSON_PRETTY_PRINT);
*/
/*
echo json_encode(telegram_api('sendVenue', array(
    'chat_id' => '17089367',
    'latitude' => '47.275593',
    'longitude' => '8.554580',
    'title' => 'OL Zimmerberg',
    'address' => 'Landforst, Zimmerberg',
)), JSON_PRETTY_PRINT);
*/
/*
echo json_encode(telegram_api('sendContact', array(
    'chat_id' => '17089367',
    'phone_number' => '+41 76 302 02 70',
    'first_name' => 'OL',
    'last_name' => 'Zimmerberg',
)), JSON_PRETTY_PRINT);
*/
/*
echo json_encode(telegram_api('sendChatAction', array(
    'chat_id' => '17089367',
    'action' => 'typing',
)), JSON_PRETTY_PRINT);
*/
/*
echo json_encode(telegram_api('sendMessage', array(
    'chat_id' => '17089367',
    'text' => 'Hoi.',
    'reply_markup' => json_encode(array(
        'keyboard' => array(
            array('Vielleicht', 'Später'),
            array('Ja', 'Nein'),
        ),
        'one_time_keyboard' => true,
        'resize_keyboard' => true,
    )),
)), JSON_PRETTY_PRINT);
*/

?>
