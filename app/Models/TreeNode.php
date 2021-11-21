<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use App\Libraries\Util;
use App\Exceptions\TreeUpdatedException;

class TreeNode extends Model
{
    const CREATED_AT = 'ctime';
    const UPDATED_AT = 'mtime';

    protected $table = 'tree_node';
    protected $primaryKey = 'id';

    protected $fillable = ['pid', 'title', 'pos', 'type'];

    public static function getNodes(int $spaceId, int $treeId, array $conditions = [], array $fields = ['*']): collection {
        return static::where('space_id', $spaceId)
            ->where('tree_id', $treeId)
            ->where('deleted', 0)
            ->where($conditions)
            ->orderby('pos', 'asc')
            ->get($fields);
    }

    public static function getTrashNodes(int $spaceId, int $treeId, array $conditions = [], array $fields = ['*']): collection {
        return static::where('space_id', $spaceId)
            ->where('tree_id', $treeId)
            ->where('deleted', 1)
            ->where($conditions)
            ->orderby('pos', 'asc')
            ->get($fields);
    }

    public static function getNodeById(int $spaceId, int $treeId, int $nodeId): ?TreeNode {
         return static::where('space_id', $spaceId)
            ->where('tree_id', $treeId)
            ->where('deleted', 0)
            ->where('id',      $nodeId)
            ->first();
    }

    public static function getTrashNodeById(int $spaceId, int $treeId, int $nodeId): ?TreeNode {
         return static::where('space_id', $spaceId)
            ->where('tree_id', $treeId)
            ->where('deleted', 1)
            ->where('id',      $nodeId)
            ->first();
    }

    public static function getRootNode(int $spaceId, int $treeId): ?TreeNode {
        return static::where('space_id', $spaceId)
            ->where('tree_id', $treeId)
            ->where('pid',     0)
            ->where('deleted', 0)
            ->first();
    }

    public static function getMaxChildPos(int $spaceId, int $treeId, int $pid): ?int {
        return static::where('space_id', $spaceId)
                    ->where('tree_id',   $treeId)
                    ->where('pid',       $pid)
                    ->where('deleted',   0)
                    ->max('pos');
    }

    /**
     * modify some nodes' attributes, and regenerate the root node's version in the meantime.
     * @param int $spaceId
     * @param int $treeId
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
    public static function modifyNodes(int $spaceId, int $treeId, string $treeVersion, array $updates): string {
        return DB::transaction(function() use ($spaceId, $treeId, $treeVersion, $updates) {
            foreach ($updates as $id => $update) {
                $affectedRows = static::where('space_id', $spaceId)
                    ->where('tree_id',  $treeId)
                    ->where('id',       $id)
                    ->where('deleted',  0)
                    ->update($update);

                if ($affectedRows != 1) {
                    throw new TreeUpdatedException();
                }
            }

            $newTreeVersion = util::version();
            $affectedRows = static::where('space_id', $spaceId)
                ->where('tree_id',  $treeId)
                ->where('pid',      0)
                ->where('version',  $treeVersion)
                ->where('deleted',  0)
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
     * modify some trash nodes' attributes, and regenerate the root node's version in the meantime.
     * @param int $spaceId
     * @param int $treeId
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
    public static function modifyTrashNodes(int $spaceId, int $treeId, string $treeVersion, array $updates): string {
        return DB::transaction(function() use ($spaceId, $treeId, $treeVersion, $updates) {
            foreach ($updates as $id => $update) {
                $affectedRows = static::where('space_id', $spaceId)
                    ->where('tree_id',  $treeId)
                    ->where('id',       $id)
                    ->where('deleted',  1)
                    ->update($update);

                if ($affectedRows != 1) {
                    throw new TreeUpdatedException();
                }
            }

            $newTreeVersion = util::version();
            $affectedRows = static::where('space_id', $spaceId)
                ->where('tree_id',  $treeId)
                ->where('pid',      0)
                ->where('version',  $treeVersion)
                ->where('deleted',  0)
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
     * @param int $treeId
     * @param string $treeVersion
     * @param array $node
     * @return array
     *      [
     *          'id' => ...,
     *          'treeVersion' => ...
     *      ]
     * @throws IllegalOperationException, TreeUpdatedException, UnfinishedDBOperationException
     */
    public static function addChildNode(int $spaceId, int $treeId, string $treeVersion, array $node): array {
        if ($node['pid'] <= 0) {
            throw new IllegalOperationException();
        }

        $rs = DB::transaction(function() use($spaceId, $treeId, $treeVersion, $node) {
            $treeNode = new TreeNode();
            $treeNode->space_id = $spaceId;
            $treeNode->tree_id  = $treeId;
            $treeNode->version  = Util::version();
            $treeNode->fill($node);
            $succ = $treeNode->save();
            if (!$succ) {
                throw new UnfinishedDBOperationException();
            }

            $newTreeVersion = util::version();
            $affectedRows = static::where('space_id', $spaceId)
                ->where('tree_id',  $treeId)
                ->where('pid',      0)
                ->where('version',  $treeVersion)
                ->where('deleted',  0)
                ->update([
                    'version' => $newTreeVersion
                ]);

            if ($affectedRows != 1) {
                throw new TreeUpdatedException();
            }

            return [
                'nodeId' => $treeNode->id,
                'treeVersion' => $newTreeVersion,
            ];
        });

        return $rs;
    }



    /**
     * regenerate a non-root node's version
     * @param int $spaceId
     * @param int $treeId
     * @param int $nodeId
     * @return string the new version string
     */
    public static function regenerateNodeVersion(int $spaceId, int $treeId, int $nodeId): string {
        $newVersion = Util::version();

        $affectedRows = static::where('space_id', $spaceId)
            ->where('tree_id',  $treeId)
            ->where('id',       $nodeId)
            ->where('pid', '>', 0)
            ->where('deleted',  0)
            ->update([
                'version' => $newVersion
            ]);

        if ($affectedRows == 0) {
            throw new UnfinishedDBOperationException();
        }

        return $newVersion;
    }

    /**
     * create a root node
     * @param int $spaceId
     * @return int the id of the root node
     */
    public static function createRootNode(int $spaceId, int $treeId, string $title): int {
        $treeNode = new TreeNode();
        $treeNode->space_id = $spaceId;
        $treeNode->tree_id  = $treeId;
        $treeNode->title    = $title;
        $treeNode->pid      = 0;
        $treeNode->type     = config('dict.TreeNodeType.ARTICLE_PAGE');
        $treeNode->version  = Util::version();
        $succ = $treeNode->save();

        if (!$succ) {
            throw new UnfinishedDBOperationException();
        }

        return $treeNode->id;
    }

    /**
     * modify nodes of more than one trees
     * @param int $spaceId
     * @param int $treeUpdates
     *  [
     *      [
     *          treeId      => treeId1,
     *          treeversion => version1,
     *          updates     => [
     *              id1     => [
     *                  column1 => val1,
     *                  column2 => val2,
     *                  ...
     *              ],
     *              id2   => [
     *                  column1 => val1,
     *                  column2 => val2,
     *                  ...
     *              ]
     *          ]
     *      ],
    *       ...
     *  ]
     */
    public static function modifyNodesOfMultipleTrees(int $spaceId, array $treeUpdates) {
        return DB::transaction(function() use ($spaceId, $treeUpdates)  {
            $newTreeVersions = [];

            foreach ($treeUpdates as $tmp) {
                $treeId      = $tmp['treeId'];
                $treeVersion = $tmp['treeVersion'];
                $updates     = $tmp['updates'];
                $newTreeVersion = modifyNodes($spaceId, $treeId, $treeVersion, $updates);

                $newTreeVersions[] = [
                    $treeId => $newTreeVersion
                ];
            }

            return $newTreeVersions;
        });
    }


    public static function getMultiTreesNodes(array $spaceIds = [], array $conditions = [], array $fields = ['*']): collection {
        return static::whereIn('space_id', $spaceIds)
            ->where('deleted', 0)
            ->where($conditions)
            ->orderby('pos', 'asc')
            ->get($fields);
    }


}
