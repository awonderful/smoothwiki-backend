<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use App\Libraries\Util;

class TreeNode extends Model
{
    const CREATED_AT = 'ctime';
    const UPDATED_AT = 'mtime';

    protected $table = 'tree_node';
    protected $primaryKey = 'id';

    protected $fillable = ['pid', 'title', 'pos', 'group_read', 'group_write', 'other_read', 'other_write', 'guest_read', 'guest_write'];

    public static function getNodes(int $spaceId, int $category, array $conditions = [], array $fields = ['*']): collection {
        return static::where('space_id', $spaceId)
            ->where('category', $category)
            ->where('deleted', 0)
            ->orderby('pos', 'asc')
            ->get($fields);
    }

    public static function getNodeById(int $spaceId, int $category, int $nodeId): ?TreeNode {
         return static::where('space_id', $spaceId)
            ->where('category', $category)
            ->where('deleted', 0)
            ->where('id', $nodeId)
            ->first();
    }

    public static function getRootNode(int $spaceId, int $category): ?TreeNode {
        return static::where('space_id', $spaceId)
            ->where('category', $category)
            ->where('pid', 0)
            ->where('deleted', 0)
            ->first();
    }

    public static function getMaxChildPos(int $spaceId, int $category, int $pid): ?int {
        return static::where('space_id', $spaceId)
                    ->where('category', $category)
                    ->where('pid', $pid)
                    ->where('deleted', 0)
                    ->max('pos');
    }

    /**
     * modify some nodes' attributes, and regenerate the root node's version in the meantime.
     * @param int $spaceId
     * @param int $category
     * @param string $treeVersion
     * @param array $updates
     *      [
     *          id1 => [
     *              column1 => val1,
     *              column2 => val2,
     *              ...
     *          ],
     *          id2 => [
     *              ...
     *          ],
     *          ...
     *      ]
     * @return string the new version string of the tree
     * @throws TreeUpdatedException
     */
    public static function modifyNodes(int $spaceId, int $category, string $treeVersion, array $updates): string {
        return DB::transaction(function() use ($spaceId, $category, $treeVersion, $updates) {
            foreach ($updates as $id => $update) {
                $affectedRows = static::where('space_id', $spaceId)
                    ->where('category', $category)
                    ->where('id', $id)
                    ->where('deleted', 0)
                    ->update($update);

                if ($affectedRows != 1) {
                    throw new TreeUpdatedException();
                }
            }

            $newTreeVersion = util::version();
            $affectedRows = static::where('space_id', $spaceId)
                ->where('category', $category)
                ->where('pid', 0)
                ->where('version', $treeVersion)
                ->where('deleted', 0)
                ->update([
                    'version' => $newTreeVersion
                ]);

            if ($affectedRows != 1) {
                throw new TreeUpdatedException();
            }

            return $newTreeVersion;
        });
    }

    /**
     * add a non-root node, and regenerate the root node's version string in the meantime.
     * @param int $spaceId
     * @param int $category
     * @param string $treeVersion
     * @param array $node
     * @return array
     *      [
     *          'id' => ...,
     *          'treeVersion' => ...
     *      ]
     * @throws IllegalOperationException, TreeUpdatedException, UnkonwnException
     */
    public static function addChildNode(int $spaceId, int $category, string $treeVersion, array $node): array {
        if ($node['pid'] <= 0) {
            throw new IllegalOperationException();
        }

        $rs = DB::transaction(function() use($spaceId, $category, $treeVersion, $node) {
            $treeNode = new TreeNode();
            $treeNode->space_id = $spaceId;
            $treeNode->category = $category;
            $treeNode->version  = Util::version();
            $treeNode->fill($node);
            $succ = $treeNode->save();
            if (!$succ) {
                throw new UnknownException();
            }

            $newTreeVersion = util::version();
            $affectedRows = static::where('space_id', $spaceId)
                ->where('category', $category)
                ->where('pid', 0)
                ->where('version', $treeVersion)
                ->where('deleted', 0)
                ->update([
                    'version' => $newTreeVersion
                ]);

            if ($affectedRows != 1) {
                throw new TreeUpdatedException();
            }

            return [
                'id' => $treeNode->id,
                'treeVersion' => $newTreeVersion,
            ];
        });

        return $rs;
    }

    /**
     * regenerate a non-root node's version
     * @param int $spaceId
     * @param int $category
     * @param int $nodeId
     * @return string the new version string
     */
    public static function regenerateNodeVersion(int $spaceId, int $category, int $nodeId): string {
        $newVersion = Util::version();

        $affectedRows = static::where('space_id', $spaceId)
            ->where('category', $category)
            ->where('pid', '>', 0)
            ->where('deleted', 0)
            ->update([
                'version' => $newVersion
            ]);

        if ($affectedRows == 0) {
            throw new UnknownException();
        }

        return $newVersion;
    }
}
