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
$logs = @file($fz_log_last, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);

if( $logs === false ) {
	exit('Can\'t read log file: '.$fz_log_last);
}

// get last FilzeZilla ftp session time
$ses_last_ts = @file_get_contents($fz_log_id_file);

if( ! $ses_last_ts ) {
	$ses_last_ts = time();
}

$ses_data = [];

for ( $i = 0; $i < count($logs); $i++ ) {

    $s = explode(' ', str_replace(['(', ')', '"'], '', $logs[$i]));

   // skip wrong FTP user name
   if( ( isset($s[4]) and $s[4] != $ftp_user_name ) ) {
        continue;
   }
   
   // test data time string
   if( !isset($s[1]) || !isset($s[2]) ) {
       continue;
   }
   
   $ses_ts = strtotime($s[1].' '.$s[2]);
   
   // test timestamp
   if( $ses_ts === false ) {
       continue;
   }
   
   // skip old sessions
   if( $ses_ts <= $ses_last_ts ) {
       continue;
   }
   
   // set FileZilla log id 
   $ses_id = intval($s[0]);

    // mark disconnected session
   if (isset($s[6]) && $s[6] == "disconnected.") {
       $ses_data[$ses_id]['CLOSED'] = $ses_ts;
   }

    // file transferred
    if (isset($s[8]) && $s[8] == "transferred") {
        $pi = pathinfo($s[9]);
        if (isset($pi['extension'])) {
            switch ($pi['extension']) {
                // uploaded screenshots files
                case 'jpg':
                        $ses_data[$ses_id][] = [ 'type' => 'photo', 'file' => $s[9], 'capt' => $ses_id.'-'.$pi['filename'] ];
                    break;
                // uploaded videos files
                case 'h264':
                        $ses_data[$ses_id][] = [ 'type' => 'url',   'file' => $s[9] ];
                    break;
            }
        }
    }
}

//print_r($ses_data);

// send Telegram messages
$TG = new telegram\TGapi($botToken, $chatID);

$send_urls = [];
$ses_ts = 0;

foreach ($ses_data as $sk => $sd) {
    // send message to Telegram use only closed FTP server sessions
    if (isset($sd['CLOSED'])) {
        foreach ($sd as $f) {
            if (is_array($f)) {
                switch ($f['type']) {
                    case 'photo':
                        $TG->sendPhoto($ftp_root . $f['file'], $f['capt']);
                        break;
                    // combine URLs to video files to one message
                    case 'url':
                        $send_urls[] = $http_url . str_replace($ftp_root_sub, '', $f['file']);
                        break;
                }
                echo PHP_EOL;
            }
        }
        // save last FTP timestamp
        $ses_ts = $sd['CLOSED'];
    }
}

// send video urls
if (!empty($send_urls)) {
    $TG->sendMessage(implode(PHP_EOL, $send_urls));
}

// save last closed session time
if( $ses_ts !== 0 ) {
    file_put_contents($fz_log_id_file, $ses_ts);
}
