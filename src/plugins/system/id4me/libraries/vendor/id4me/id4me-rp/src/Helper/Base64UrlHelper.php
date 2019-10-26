<?php

namespace Id4me\RP\Helper;

class Base64UrlHelper
{
    /**
     * @param string $data
     *
     * @return string
     */
    public static function base64urlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param string $data
     *
     * @return boolean|string
     */
    public static function base64urlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }
}
