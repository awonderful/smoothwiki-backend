<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Exceptions\UnfinishedDBOperationException;

class SpaceMember extends Model
{
    const CREATED_AT = 'ctime';
    const UPDATED_AT = 'mtime';

    protected $table = 'space_member';
    protected $primaryKey = ['space_id', 'uid'];
    public    $incrementing = false;

    public static function addMember(int $spaceId, int $uid, int $role): void {
        $member = new SpaceMember();
        $member->space_id = $spaceId;
        $member->uid  = $uid;
        $member->role = $role;
        $succ = $member->save();
        if (!$succ) {
            throw new UnfinishedDBOperationException();
        }
    }

    public static function removeMember(int $spaceId, int $uid): void {
        static::where('space_id', $spaceId)
            ->where('uid', $uid)
            ->where('deleted', 0)
            ->update([
                'deleted' => 1
            ]);
    }

    public static function restoreMemberAndSetRole(int $spaceId, int $uid, int $role): void {
        static::where('space_id', $spaceId)
            ->where('uid', $uid)
            ->where('deleted', 1)
            ->update([
                'role' => $role,
                'deleted' => 0
            ]);
    }

    public static function setMemberRole(int $spaceId, int $uid, int $role): void {
        static::where('space_id', $spaceId)
            ->where('uid', $uid)
            ->where('deleted', 0)
            ->update([
                'role' => $role
            ]);
    }

    public static function getRemovedMemberByUid(int $spaceId, int $uid): ?SpaceMember {
        return static::where('space_id', $spaceId)
                    ->where('uid', $uid)
                    ->where('deleted', 1)
                    ->first();
    }

    public static function getMemberByUid(int $spaceId, int $uid): ?SpaceMember {
        return static::where('space_id', $spaceId)
                    ->where('uid', $uid)
                    ->where('deleted', 0)
                    ->first();
    }

    public static function getMembers(int $spaceId): Collection {
        return static::select('*')
                    ->where('space_id', $spaceId)
                    ->where('deleted', 0)
                    ->get();
    }

    public static function getUserSpaceMemberRecords(int $uid): Collection {
        return static::where('uid', $uid)
                    ->where('deleted', 0)
                    ->get();
    }
}
