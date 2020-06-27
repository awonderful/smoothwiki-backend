<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpaceMember extends Model
{
    const CREATED_AT = 'ctime';
    const UPDATED_AT = 'mtime';

    protected $table = 'space_member';
    protected $primaryKey = ['space_id', 'member_id'];
    protected $incrementing = false;
}
