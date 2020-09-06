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

    public static function addMember(int $spaceId, int $memberType, int $memberId, $role) {
        $member = new SpaceMember();
        $member->space_id    = $spaceId;
        $member->member_type = $memberType;
        $member->member_id   = $memberId;
        $member->role        = $role;
        $succ = $member->save();
        if (!$succ) {
            throw new UnfinishedSavingException();
        }
    }
}
