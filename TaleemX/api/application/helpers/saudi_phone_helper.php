<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('normalize_saudi_phone_e164')) {

    /**
     * Normalize common Saudi inputs to E.164 with +966 prefix (9-digit national after 966).
     * Accepts +9665xxxxxxxx, 9665..., 96605... (trunk 0), 009665..., 05xxxxxxxx, 5xxxxxxxx (mobile).
     */
    function normalize_saudi_phone_e164($str)
    {
        if ($str === null) {
            return '';
        }
        $s = trim((string) $str);
        if ($s === '') {
            return '';
        }
        $s = preg_replace('/[\s\-\(\)\.\/]+/', '', $s);
        if (strpos($s, '+966') === 0) {
            if (preg_match('/^\+9660([1-9]\d{8})$/', $s, $m)) {
                return '+966' . $m[1];
            }

            return $s;
        }
        if (strpos($s, '00966') === 0) {
            $t = '+966' . substr($s, 5);
            if (preg_match('/^\+9660([1-9]\d{8})$/', $t, $m)) {
                return '+966' . $m[1];
            }

            return $t;
        }
        // 966 without +: optional national trunk 0, then 9 digits (e.g. 966512345678 or 9660512345678)
        if (preg_match('/^966(?:0([1-9]\d{8})|([1-9]\d{8}))$/', $s, $m)) {
            $national = !empty($m[1]) ? $m[1] : $m[2];

            return '+966' . $national;
        }
        if (preg_match('/^0(5\d{8})$/', $s, $m)) {
            return '+966' . $m[1];
        }
        if (preg_match('/^(5\d{8})$/', $s, $m)) {
            return '+966' . $m[1];
        }

        return $s;
    }
}

if (!function_exists('is_valid_saudi_e164_phone')) {

    function is_valid_saudi_e164_phone($str)
    {
        $n = normalize_saudi_phone_e164($str);

        return (bool) preg_match('/^\+966[1-9]\d{8}$/', $n);
    }
}

if (!function_exists('saudi_phone_normalize_post_fields')) {

    /**
     * @param string[] $fields POST keys to normalize after successful validation
     */
    function saudi_phone_normalize_post_fields(array $fields)
    {
        foreach ($fields as $f) {
            if (!isset($_POST[$f])) {
                continue;
            }
            $v = $_POST[$f];
            if ($v === null || $v === '') {
                continue;
            }
            $_POST[$f] = normalize_saudi_phone_e164($v);
        }
    }
}
