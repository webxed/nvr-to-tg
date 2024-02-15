<?php

header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// script config
include_once('filezilla_log_parser.settings');
// Telegram API
include_once('telegram_api.php');
// get FileZilla files listing
$fz_logs_files = glob($fz_logs_folder . 'fzs-*.log');
// get last file
$fz_log_last   = end($fz_logs_files);
// read actual log
$logs = file($fz_log_last, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
// get last FilzeZilla ftp session id
$ses_last_id = @file_get_contents($fz_log_id_file);
$ses_data = [];

for ($i = 0; $i < count($logs); $i++) {
    $s = explode(' ', str_replace(['(', ')', '"'], '', $logs[$i]));
    $ses_id = intval($s[0]);
// skip wrong users and old ftp sessions
    if (( $s[4] != $ftp_user_name ) or ( $ses_id <= $ses_last_id )) {
        continue;
    }

    // session closed
    if (isset($s[6]) && $s[6] == "disconnected.") {
        $ses_data[$ses_id]['CLOSED'] = true;
    }

    // file transferred
    if (isset($s[8]) && $s[8] == "transferred") {
        $pi = pathinfo($s[9]);
        if (isset($pi['extension'])) {
            switch ($pi['extension']) {
        // Uploaded Screenshots
                case 'jpg':
                        $ses_data[$ses_id][] = [ 'type' => 'photo', 'file' => $s[9], 'capt' => $pi['filename'] ];

                    break;
        // Uploaded Videos
                case 'h264':
                        $ses_data[$ses_id][] = [ 'type' => 'url',   'file' => $s[9] ];
                    break;
            }
        }
    }
}

//print_r($ses_data);
$TG = new telegram\TGapi($botToken, $chatID);

$send_urls = [];
foreach ($ses_data as $sk => $sd) {
// send message to Telegram only about closed ftp server sessions
    if (isset($sd['CLOSED'])) {
        foreach ($sd as $f) {
            if (is_array($f)) {
                switch ($f['type']) {
                    case 'photo':
                        $TG->sendPhoto($ftp_root . $f['file'], $f['capt']);
                        break;
                    // combine message about video files to one message
                    case 'url':
                        $send_urls[] = $http_url . str_replace($ftp_root_sub, '', $f['file']);
                        break;
                }

                echo PHP_EOL;
            }
        }

        // save last ftp session id
        file_put_contents($fz_log_id_file, $sk);
    }
}

// send video url
if (!empty($send_urls)) {
    $TG->sendMessage(implode(PHP_EOL, $send_urls));
}
