<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Exceptions\SpaceMenuNotExistException;
use App\Models\TreeNode;

class SpaceMenu extends Model
{
    const CREATED_AT = 'ctime';
    const UPDATED_AT = 'mtime';

    protected $table = 'space_menu';
    protected $primaryKey = 'id';

    public static function createMenu(int $spaceId, string $title, int $type, int $objectId, string $extend): int {
        $menu = new SpaceMenu();
        $menu->space_id  = $spaceId;
        $menu->title     = $title;
        $menu->type      = $type;
        $menu->object_id = $objectId;
        $menu->extend    = $extend;
        $succ = $menu->save();
        if (!$succ) {
            throw new UnfinishedDBOperationException();
        }

        return $menu->id;
    }

    public static function createMenuWithTree(int $spaceId, string $title, int $type): int {
        return DB::transaction(function() use ($spaceId, $title, $type) {
            $menuId = SpaceMenu::createMenu($spaceId, $title, $type, 0, '');

            $treeId = $menuId;
            TreeNode::createRootNode($spaceId, $treeId, $title);

            return $menuId;
        });
    }

    public static function removeMenu(int $spaceId, int $menuId): void {
        $affectedRows = static::where('space_id', $spaceId)
            ->where('id', $menuId)
            ->update([
                'deleted' => 1
            ]);

        if ($affectedRows != 1) {
            throw new SpaceMenuNotExistException();
        }
    }

    public static function renameMenu(int $spaceId, int $menuId, string $title): int {
        $affectedRows = static::where('space_id', $spaceId)
            ->where('id', $menuId)
            ->where('deleted', 0)
            ->update([
                'title' => $title
            ]);

        if ($affectedRows != 1) {
            throw new SpaceMenuNotExistException();
        }
    }

    public static function getMenus(int $spaceId): Space {
        return static::where('space_id', $spaceId)
            ->where('deleted', 0)
            ->get();
    }
}