<?php

require_once(dirname(__FILE__).'/server_config_link.php');

/*
* E-Mail-Anpassungen
*/

function olz_phpmailer_init($phpmailer) {
    $phpmailer->Mailer = 'smtp';
    $phpmailer->Sender = 'noreply@mysmtp.com';
    $phpmailer->SMTPSecure = 'tls';

    $phpmailer->Host = $_CONFIG['smtp_host'];
    $phpmailer->Port = $_CONFIG['smtp_port'];

    $phpmailer->SMTPAuth = TRUE;
    $phpmailer->Username = $_CONFIG['smtp_username'];
    $phpmailer->Password = $_CONFIG['smtp_password'];
    $phpmailer = apply_filters('wp_mail_smtp_custom_options', $phpmailer);
}
add_action('phpmailer_init', 'olz_phpmailer_init');

?>
