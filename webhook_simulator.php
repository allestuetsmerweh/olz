<?php

// Server Configuration
$branch_config_path = $argv[1] . '/config-dev.json';
$general_config_path = $argv[1] . '/config.json';
$config_path = is_file($branch_config_path) ? $branch_config_path : $general_config_path;
$_CONFIG = json_decode(file_get_contents($config_path), true);
$telegram_api_url = 'https://api.telegram.org/bot'.$_CONFIG['telegram_bot_token'].'/';

$server_domain = $argv[3];
$webhook_simulator_config_path = $argv[1] . '/webhook_simulator.json';
$webhook_simulator_config = json_decode(file_get_contents($webhook_simulator_config_path), true);
$max_message_id = $webhook_simulator_config['max_message_id'];
if (!$max_message_id) {
    $max_message_id = 0;
}

$arr = explode(':', $server_domain);
sleep(1);

while (true) {
    $url = $telegram_api_url.'getUpdates?offset='.($max_message_id+1).'&timeout=60';
    $api_ctx = stream_context_create(array('http' => array('timeout' => 70)));
    $api_resp = json_decode(file_get_contents($url, false, $api_ctx), true);
    if (!$api_resp || !$api_resp['ok']) {
        echo "API response was not OK. Waiting and retrying...\n";
        sleep(5);
        continue;
    }
    echo "API response (for offset ".($max_message_id+1).") contains ".count($api_resp['result'])." results. Processing...\n";
    if (count($api_resp['result']) > 0) {
        $max_message_id = 0;
    }
    foreach ($api_resp['result'] as $key => $value) {
        echo "Processing message ".$value['update_id']."...\n";
        $max_message_id = max($value['update_id'], $max_message_id);
        $url = 'http://'.$server_domain.'/_on_telegram.php';
        $dev_ctx = stream_context_create(array(
            'http' => array(
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($value, JSON_PRETTY_PRINT),
                'timeout' => 30,
            ),
        ));
        $dev_resp = json_decode(file_get_contents($url, false, $dev_ctx), true);
        // TODO: Action based on webhook response (currently not used)
    }
    $webhook_simulator_config['max_message_id'] = $max_message_id;
    file_put_contents($webhook_simulator_config_path,
        json_encode($webhook_simulator_config, JSON_PRETTY_PRINT));
}

?>
