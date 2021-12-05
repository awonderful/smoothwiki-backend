<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\SpaceMember;
use App\Models\TreeNode;
use App\Models\Search;
use App\Libraries\Util;
use App\Exceptions\UnfinishedDBOperationException;
use App\Exceptions\SpaceNotExistException;

class Space extends Model
{
    const CREATED_AT = 'ctime';
    const UPDATED_AT = 'mtime';

    protected $table = 'space';
    protected $primaryKey = 'id';

    public static function createSpace(int $type, string $title, string $desc, int $creator, int $othersRead, int $othersWrite): int {
        return DB::transaction(function() use($type, $title, $desc, $creator, $othersRead, $othersWrite) {
            $space = new Space();
            $space->type         = $type;
            $space->title        = $title;
            $space->desc         = $desc;
            $space->others_read  = $othersRead;
            $space->others_write = $othersWrite;
            $succ = $space->save();
            if (!$succ) {
                throw new UnfinishedDBOperationException();
            }
            $spaceId = $space->id;

            SpaceMember::addMember($spaceId, $creator, config('dict.SpaceMemberRole.CREATOR'));
            TreeNode::createRootNode($spaceId, 1, $title);
            Search::insertObject($spaceId, [
                'type'    => config('dict.SearchObjectType.SPACE'),
                'id'      => $spaceId,
                'title'   => $title,
                'content' => $desc
            ]);

            return $spaceId;
        });
    }

    public static function modifySpace(int $spaceId, string $title, string $desc, $othersRead, $othersWrite): void {
        DB::transaction(function() use($spaceId, $title, $desc, $othersRead, $othersWrite) {
            $affectedRows = static::where('id', $spaceId)
                ->where('deleted', 0)
                ->update([
                    'title'        => $title,
                    'desc'         => $desc,
                    'others_read'  => $othersRead,
                    'others_write' => $othersWrite
                ]);

            if ($affectedRows != 1) {
                throw new SpaceNotExistException();
            }

            Search::updateObject($spaceId, [
                'type'    => config('dict.SearchObjectType.SPACE'),
                'id'      => $spaceId,
                'title'   => $title,
                'content' => $desc
            ]);
        });
    }

    public static function removeSpace(int $spaceId): void {
        DB::transaction(function() use($spaceId) {
            static::where('id', $spaceId)
                ->where('deleted', 0)
                ->update([
                    'deleted' => 1
                ]);
            Search::setIsSpaceDeleted($spaceId, 1);
        });
    }

    public static function getSpaceById(int $spaceId): ?Space {
        return static::where('id', $spaceId)
            ->where('deleted', 0)
            ->first();
    }

    public static function getSpaces(array $spaceIds): Collection {
        return static::whereIn('id', $spaceIds)
                    ->where('deleted', 0)
                    ->get();
    }
}