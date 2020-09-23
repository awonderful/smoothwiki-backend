<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\SpaceMember;

class Space extends Model
{
    const CREATED_AT = 'ctime';
    const UPDATED_AT = 'mtime';

    protected $table = 'space';
    protected $primaryKey = 'id';

    public static function createSpace(int $type, string $title, string $desc, int $creator) {
        return DB::transaction(function() use($type, $title, $desc, $creator) {
            $space = new Space();
            $space->type    = $type;
            $space->title   = $title;
            $space->desc    = $desc;
            $space->creator = $creator;
            $succ = $space->save();
            if (!$succ) {
                throw new UnfinishedSavingException();
            }
            $spaceId = $space->id;

            $spaceMember = new SpaceMember();
            $spaceMember->space_id    = $spaceId;
            $spaceMember->member_type = config('dict.SpaceMemberType.PERSON');
            $spaceMember->member_id   = $creator;
            $spaceMember->role        = config('dict.SpaceMemberRole.ADMIN');
            $spaceMember->status      = config('dict.SpaceMemberStatus.APPROVED');
            $succ = $spaceMember->save();
            if (!$succ) {
                throw new UnfinishedSavingException();
            }

            return $spaceId;
        });
    }

    public static function modifySpace(int $spaceId, string $title, string $desc) {
        $affectedRows = static::where('space_id', $spaceId)
            ->where('deleted', 0)
            ->update([
                'title' => $title,
                'desc'  => $desc,
            ]);

        if ($affectedRows != 1) {
            throw new SpaceNotExistException();
        }
    }

    public static function getSpaceById(int $spaceId): Space {
        return static::where('space_id', $spaceId)
            ->where('deleted', 0)
            ->get();
    }
}
