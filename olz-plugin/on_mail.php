#!/usr/local/bin/php
<?php

$args = array();
$arg_name = null;
foreach (array_slice($argv, 1) as $arg) {
    if (substr($arg, 0, 2) == '--') {
        $arg_name = substr($arg, 2);
        $args[$arg_name] = null;
    } else if ($arg_name !== null) {
        $args[$arg_name] = $arg;
        $arg_name = null;
    }
}

function get_arg($arg_name, $default=null) {
    global $args;
    if (isset($args[$arg_name])) {
        return $args[$arg_name];
    } else {
        return $default;
    }
}


$wp_path = get_arg('wp-path', '../../../');
require_once(dirname(__FILE__).'/'.$wp_path.'/wp-load.php');

$sendmail_path = get_arg('sendmail-path', 'sendmail');


function report_error($subject, $body) {
    $err_info = debug_backtrace()[0];
    mail(get_option('admin_email'), $subject, $body . "\n\nDer Fehler ereignete sich in folgender Datei:\n" . $err_info['file'] . ":" . $err_info['line'] . "\n");
    die('Das E-Mail konnte nicht zugestellt werden.');
}


$parse_fns = array(
    'stdin'=>function ($line_start=0) {
        $input = file_get_contents('php://stdin');
        $cropped = implode("\n", array_slice(explode("\n", $input), $line_start));
        return $cropped;
    },
    'get'=>function ($key) {
        return $_GET[$key];
    },
    'post'=>function ($key) {
        return $_POST[$key];
    },
    'server'=>function ($key) {
        return $_SERVER[$key];
    },
    'static'=>function($val) {
        return $val;
    },
);

function get_parser($str) {
    global $parse_fns;
    $arg_pos = strpos($str, '(');
    $cmd = $str;
    $args = array();
    if ($arg_pos !== false) {
        if (substr($str, strlen($str) - 1, 1) != ')') {
            report_error("Ungültiger Parser", "Der Parser `" . $str . "` ist ungültig.");
        }
        $cmd = substr($str, 0, $arg_pos);
        $args_json = '[' . substr($str, $arg_pos + 1, strlen($str) - $arg_pos - 2) . ']';
        $args = json_decode($args_json, true);
    }
    return call_user_func_array($parse_fns[$cmd], $args);
}


$mail_from = get_arg('mail-from');
if (!is_string($mail_from)) {
    report_error("Mail parser not given", "Kein Parser, der das E-Mail hervorbringen würde, wurde angegeben.");
}
$mail = get_parser($mail_from);
// TODO: Verify mail

$to_from = get_arg('to-from');
if (!is_string($to_from)) {
    report_error("Destination parser not given", "Kein Parser, der den Empfänger der E-Mail hervorbringen würde, wurde angegeben.");
}
$to = get_parser($to_from);
$to_res = preg_match('/^([^\@]+)\@([^\@]+)$/', $to, $to_matches);
if (!$to_res) {
    report_error("Parsed Destination Invalid", "Der Parser für den E-Mail-Empfänger brachte keine gültige E-Mail Adresse hervor: `" . $to . "`");
}
$user_name = $to_matches[1];
$uq = new WP_User_Query(array(
    'fields'=>array('user_login', 'user_email'),
    'search'=>$user_name,
    'search_columns'=>array('user_login'),
));
$fwd = null;
foreach ($uq->results as $uqr) {
    if ($uqr->user_login == $user_name) {
        $fwd = $uqr->user_email;
    }
}
if ($fwd == null) {
    // TODO: Remove this notification
    report_error("Kein Benutzer `" . $user_name . "`", "Kein solcher Benutzer wurde in der Datenbank gefunden.");
}


$io_spec = array(
    0 => array("pipe", "r"), // stdin
    1 => array("pipe", "w"), // stdout
    2 => array("pipe", "w"), // stderr
);

$p = proc_open($sendmail_path . ' ' . json_encode($fwd), $io_spec, $io, NULL, array());
if (!is_resource($p)) {
    report_error("Sendmail Error", "proc_open(`" . $sendmail_path . "`, ...) failed.");
}
fwrite($io[0], $mail);
fclose($io[0]);
$out = stream_get_contents($io[1]);
fclose($io[1]);
$err = stream_get_contents($io[2]);
fclose($io[2]);
$ret = proc_close($p);
if ($ret != 0) {
    report_error("Sendmail Error", "`" . $sendmail_path . "` returned " . $ret . "\n\nstdout\n" . $out . "\n\nstderr\n" . $err);
}

?>
