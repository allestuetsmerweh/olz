<?php

require_once(dirname(__FILE__).'/server_config_link.php');


// Telegram: Constants

$telegram_api_url = 'https://api.telegram.org/bot'.$_CONFIG['telegram_bot_token'].'/';


// Telegram: Webhook

$_telegram_event = null;
function telegram_event($key=null, $default=null) {
    global $_telegram_event;
    if ($_telegram_event == null) {
        $content = file_get_contents('php://input');
        $_telegram_event = json_decode($content, true);
    }
    if ($key == null) {
        return $_telegram_event;
    }
    $key_path = explode('.', $key);
    $tmp = $_telegram_event;
    foreach ($key_path as $key => $value) {
        if (!isset($tmp[$value])) {
            return $default;
        }
        $tmp = $tmp[$value];
    }
    return $tmp;
}


// Telegram: API

function telegram_api($command, $args) {
    global $telegram_api_url;
    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($args),
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($telegram_api_url.$command, false, $context);
    if (!$result) {
        return array('ok' => false);
    }
    $resp = json_decode($result, true);
    if (!$resp) {
        return array('ok' => false);
    }
    return $resp;
}

function telegram_answer($chat_id, $response) {
    telegram_api('sendMessage', array(
        'chat_id' => $chat_id,
        'text' => $response,
    ));
    exit();
}


?>
