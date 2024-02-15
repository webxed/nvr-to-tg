<?php

namespace telegram;

class MyCURL
{
    private static $ch = null;

    protected static function init()
    {
        if (empty(self::$ch)) {
            self::$ch = curl_init();
        }
    }

    protected static function close()
    {
        if (!empty(self::$ch)) {
            curl_close(self::$ch);
        }
    }

    protected static function exec($curlOpt = [])
    {
        self::init();

        $optArray = array(
                CURLOPT_RETURNTRANSFER => true,

                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT        => 30,

                CURLOPT_VERBOSE        => false,

                //CURLOPT_FORBID_REUSE   => true,
                CURLOPT_HTTPHEADER => [
                                        'Connection: Keep-Alive',
                                        'Keep-Alive: 300',
                                      ],

                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
        );

        $optArray += $curlOpt ;

        curl_setopt_array(self::$ch, $optArray);

            $result = curl_exec(self::$ch);

            $http_code = curl_getinfo(self::$ch, CURLINFO_HTTP_CODE);

        if ($http_code != 200) {
            return false;
        }

        return $result;
    }
}

class TGapi extends MyCURL
{
    private static $api_url = 'https://api.telegram.org/bot';
    private static $chatID  = '';

    public function __construct($botToken = '', $ChatID = '')
    {
        self::$api_url .= $botToken;
        self::$chatID   = $ChatID;

        parent::init();
    }

    public function __destruct()
    {
        parent::close();
    }

    public function sendMessage(array | string $msg_in)
    {

        if( empty($msg_in) ) {
           echo 'Message is empty!'; 
           return false; 
        }

        echo "sending message to " . self::$chatID . PHP_EOL;

        // templates for text
        $tpl = [
                'label'    => ['<b>', '</b>'],
                'severity' => ['<code>','</code>'],
                'msg'      => ['', ''],
                ];

        // Severity Icons
        $severity = [
              'info'     => '&#x2139',
              'warn'     => '&#x26A0',
              'error'    => '&#x26A1',
              'critical' => '&#x1F525',
        ];

        $msg = '';

        if (is_array($msg_in)) {
            foreach ($tpl as $k => $t) {
               // set icon for severity
                if (($k == 'severity') && isset($severity[$msg_in[$k]])) {
                    $msg .= $severity[$msg_in[$k]] . PHP_EOL;
                }
               // set format for text
                elseif (isset($msg_in[$k])) {
                    $msg .= $t[0] . $msg_in[$k] . $t[1] . PHP_EOL;
                }
            }
        } else {
            $msg = $msg_in;
        }

        $api_url  = self::$api_url . "/sendMessage?chat_id=" . self::$chatID;
        $api_url .=  "&text=" . urlencode($msg) . '&parse_mode=html';

        $curlOpt = array(
                CURLOPT_URL => $api_url,
        );

        echo parent::exec($curlOpt);
    }

    public function sendPhoto(string $photo_file, string $caption = '')
    {

        if (!file_exists($photo_file)) {
            echo 'File is missing: ' . $photo_file . PHP_EOL;
            return false;
        }

        $post_fields = [
            'chat_id'   => self::$chatID,
            'photo'     => new CURLFile($photo_file),
        ];

        if (!empty($caption)) {
            $post_fields['caption'] = $caption;
        }

        $api_url    = self::$api_url . "/sendPhoto";

        $curlOpt = array(
            CURLOPT_URL        => $api_url,
            CURLOPT_POST       => true,

            CURLOPT_HTTPHEADER => ["Content-Type:multipart/form-data"],
            CURLOPT_POSTFIELDS => $post_fields,
        );

        echo parent::exec($curlOpt);
    }
}
