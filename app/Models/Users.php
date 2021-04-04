<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class Users extends Model
{
    const CREATED_AT = 'ctime';
    const UPDATED_AT = 'mtime';

    protected $table = 'users';
    protected $primaryKey = 'id';

    public static function getUsers(array $uids): Collection {
        return static::whereIn('id', $uids)
                    ->get();
    }

    public static function getUserByEmail(string $email): Users {
        return static::where('email', $email)
                    ->first();
    }
}
