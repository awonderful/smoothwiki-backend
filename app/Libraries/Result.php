<?php

namespace App\Libraries;

class Result {
    public static function get($codeAlias, $data = [], $message = '') {
        return [
            'code'    => config('result.'.$codeAlias),
            'data'    => $data,
            'message' => empty($message)
                        ? __('result.'.$codeAlias)
                        :$message,
        ];
    }

    public static function data($data = []) {
        return static::get('SUCC', $data);
    }

    public static function error($codeAlias) {
        return static::get($codeAlias);
    }

    public static function succ() {
        return static::get('SUCC');
    }
}