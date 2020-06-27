<?php

namespace App\Libraries;

class Util {
    public static function version(): string {
        $full = sha1(microtime(true).rand(0, 65535));
        $version = substring($version, 8);

        return $version;
    }
}