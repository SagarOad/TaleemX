<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'third_party/omnipay/vendor/autoload.php';

class Google_authenticator
{

    public $CI;

    public function __construct()
    {
        $this->CI = &get_instance();

    }

    public function generateQR($key="school")
    {

        $gauth         = $this->CI->gauth_model->get();
        $url_parts = parse_url(current_url());
        $domain    = str_replace('www.', '', $url_parts['host']);
        $username = $key;
        $g        = new \Google\Authenticator\GoogleAuthenticator();
        $secret   = $this->createSecret();
        $qr_url   = $g->getURL($username, $domain, $secret);
        return json_encode(array('secret' => $secret, 'url' => $qr_url));

    }

    public function verifyQR($secret, $code)
    {
        $g = new \Google\Authenticator\GoogleAuthenticator();
        if ($g->checkCode($secret, $code)) {
            return true;
        } else {
            return false;
        }

    }

    public function createSecret($secretLength = 16)
    {
        $validChars = $this->getValidChar();

        // Valid secret lengths are 80 to 640 bits
        if ($secretLength < 16 || $secretLength > 128) {
            throw new Exception('Bad secret length');
        }
        $secret = '';
        $rnd    = false;
        if (function_exists('random_bytes')) {
            $rnd = random_bytes($secretLength);
        } elseif (function_exists('mcrypt_create_iv')) {
            $rnd = mcrypt_create_iv($secretLength, MCRYPT_DEV_URANDOM);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $rnd = openssl_random_pseudo_bytes($secretLength, $cryptoStrong);
            if (!$cryptoStrong) {
                $rnd = false;
            }
        }
        if ($rnd !== false) {
            for ($i = 0; $i < $secretLength; ++$i) {
                $secret .= $validChars[ord($rnd[$i]) & 31];
            }
        } else {
            throw new Exception('No source of secure random');
        }

        return $secret;
    }

    protected function getValidChar()
    {
        return array(
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', //  7
            'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', // 15
            'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', // 23
            'Y', 'Z', '2', '3', '4', '5', '6', '7', // 31
            '=', // padding char
        );
    }

}
