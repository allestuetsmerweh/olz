<?php

// Server Configuration
$deployment = json_decode(file_get_contents(dirname(__FILE__).'/deployment.json'), true);
$branch_config_path = $deployment['secrets-root'] . '/config-' . $deployment['branch'] . '.json';
$general_config_path = $deployment['secrets-root'] . '/config.json';
$config_path = is_file($branch_config_path) ? $branch_config_path : $general_config_path;
$_CONFIG = json_decode(file_get_contents($config_path), true);

?>
