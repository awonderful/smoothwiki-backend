<?php

namespace App\Libraries;

class Util {
    public static function version(): string {
        $full = sha1(microtime(true).rand(0, 65535));
        $version = substr($full, 0, 16);

        return $version;
    }
}