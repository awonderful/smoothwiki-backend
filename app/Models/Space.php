<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Space extends Model
{
    const CREATED_AT = 'ctime';
    const UPDATED_AT = 'mtime';

    protected $table = 'space';
    protected $primaryKey = 'id';

    public static function createSpace(int $type, string $title, string $desc, int $creator) {
        return DB::transaction(function() use($type, $title, $creator) {
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

            $mainTreeRoot = new TreeNode();
            $mainTreeRoot->space_id = $spaceId;
            $mainTreeRoot->pid      = 0;
            $mainTreeRoot->category = config('dict.TreeNodeCategory.MAIN');
            $mainTreeRoot->title    = 'MAIN';
            $mainTreeRoot->type     = config('dict.TreeNodeType.ARTICLE');
            $mainTreeRoot->version  = Util::version();
            $mainTreeRoot->ext      = '';
            $succ = $mainTreeRoot->save();
            if (!$succ) {
                throw new UnfinishedSavingException();
            }

            $trashTreeRoot = new TreeNode();
            $trashTreeRoot->space_id = $spaceId;
            $trashTreeRoot->pid      = 0;
            $trashTreeRoot->category = config('dict.TreeNodeCategory.TRASH');
            $trashTreeRoot->title    = 'TRASH';
            $trashTreeRoot->type     = config('dict.TreeNodeType.ARTICLE');
            $trashTreeRoot->version  = Util::version();
            $trashTreeRoot->ext      = '';
            $succ = $trashTreeRoot->save();
            if (!$succ) {
                throw new UnfinishedSavingException();
            }

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
