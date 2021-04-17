<?php

namespace App\Services;

use App\Models\TreeNode;
use App\Exceptions\TreeUpdatedException;
use App\Exceptions\TreeNotExistException;
use App\Exceptions\UnfinishedDBOperationException;
use App\Services\PermissionChecker;

class TreeService {

    /**
     * get the version string of tree tree
     * @param int $spaceId
     * @param int $treeId
     * @return string the version string of the tree
     * @throws TreeNotExistException
     */
    public function getTreeVersion(int $spaceId, int $treeId): string {
        PermissionChecker::readSpace($spaceId);

        $rootNode = TreeNode::getRootNode($spaceId, $treeId);

        if (empty($rootNode)) {
            throw new TreeNotExistException();
        }

        return $rootNode['version'];
    }

    /**
     * get the tree
     * @param int $spaceId
     * @param int $treeId
     * @return array
     *   example:
     *      [
     *          'tree' => [
     *              'spaceId'   => 1,
     *              'id'        => 100,
     *              'pid'       => 0,
     *              'title'     => 'ROOT',
     *              'pos'       => 0,
     *              'children'  => [
     *                  [
     *                      ...
     *                      ...
     *                  ],
     *                  ...
     *                  ...
     *              ]
     *          ],
     *          'version' => 'dfg87ssdfg7'
     *      ]
     * @throws TreeNotExistException
     */
    public function getTree(int $spaceId, int $treeId): array {
        PermissionChecker::readSpace($spaceId);

        $rows = TreeNode::getNodes($spaceId, $treeId, [], ['id', 'pid', 'tree_id', 'type', 'title', 'version']);
        if ($rows->isEmpty()) {
            throw new TreeNotExistException();
        }

        $pidMap = [];
        foreach ($rows as $row) {
            $pid = $row['pid'];
            if (!isset($pidMap[$pid])) {
                $pidMap[$pid] = [];
            }
            $pidMap[$pid][] = $row;
        }

        $root = $pidMap[0][0];
        $treeVersion = $root->version;
        $tree = [
            'spaceId'  => $spaceId,
            'type'     => $root->type,
            'id'       => $root->id,
            'pid'      => 0,
            'title'    => $root->title,
            'pos'      => 0,
        ];

        $stack = [&$tree];
        while (true) {
            $size = count($stack);
            if ($size == 0) {
                break;
            }

            $node = &$stack[$size - 1];
            unset($stack[$size - 1]);

            $id = $node['id'];
            if (isset($pidMap[$id])) {
                $node['hasChild'] = true;
                $node['children'] = [];
                foreach ($pidMap[$id] as $childNode) {
                    $child = [
                        'spaceId'  => $childNode->space_id,
                        'type'     => $childNode->type,
                        'id'       => $childNode->id,
                        'pid'      => $childNode->pid,
                        'title'    => $childNode->title,
                        'pos'      => $childNode->pos,
                    ];
                    $node['children'][] = &$child;
                    $stack[count($stack)] = &$child;
                    unset($child);
                }
            }

            unset($node);
        }

        return [
            'tree' => $tree,
            'treeVersion' => $treeVersion,
        ];
    }

    /**
     * get the trash tree
     * @param int $spaceId
     * @param int $treeId
     * @return array
     *   example:
     *      [
     *          'tree' => [
     *              'spaceId'   => 1,
     *              'id'        => 100,
     *              'pid'       => 0,
     *              'title'     => 'ROOT',
     *              'pos'       => 0,
     *              'children'  => [
     *                  [
     *                      ...
     *                      ...
     *                  ],
     *                  ...
     *                  ...
     *              ]
     *          ],
     *          'version' => 'dfg87ssdfg7'
     *      ]
     * @throws TreeNotExistException
     */
    public function getTrashTree(int $spaceId, int $treeId): array {
        PermissionChecker::readSpace($spaceId);

        $rows = TreeNode::getTrashNodes($spaceId, $treeId, [], ['id', 'pid', 'tree_id', 'type', 'title', 'version', 'mtime']);

        //generate the $map
        // [
        //    $id1 => $node1,
        //    $id2 => $node2,
        //    $id3 => $node3
        // ]
        // In php an object is passed by reference
        /**
         * generate the $map
         * [
         *      id1 => (object)[
         *          'id'  => id1,
         *          'pid' => pid1,
         *           ...
         *      ],
         *      ...
         * ]
         */
        $map = [];
        foreach ($rows as $row) {
            $map[$row->id] = (object)[
                'spaceId'  => $row->space_id,
                'type'     => $row->type,
                'id'       => $row->id,
                'pid'      => $row->pid,
                'title'    => $row->title,
                'mtime'    => $row->mtime
            ];
        }

        /**
         * $map = [
         *      id1 => (object)[
         *          'id'  => id1,
         *          'pid' => pid1,
         *          ...
         *          'hasChild' => true,
         *          'children' => [
         *              (object) [
         *                  'id'  => ...,
         *                  'pid' => ...,
         *                  ...
         *                  'hasChild' => true,
         *                  'children' => [
         *                      ...
         *                  ]
         *              ]
         *          ]
         *      ]
         * ]
         */
        foreach ($map as $id => $node) {
            $pid = $node->pid;
            if ($pid > 0 && isset($map[$pid])) {
                $map[$pid]->hasChild = true;
                if (!isset($map[$pid]->children)) {
                    $map[$pid]->children = [];
                }
                array_push($map[$pid]->children, $node);
            }
        }

        //remove the nodes that are children of other nodes
        $removeIds = [];
        foreach ($map as $id => $tmp) {
            $pid = $tmp->pid;
            if ($pid > 0 && isset($map[$pid])) {
                $removeIds[] = $id;
            }
        }
        foreach ($removeIds as $id) {
            unset($map[$id]);
        }

        //sort
        $nodes = array_values($map);
        usort($nodes, function($node1, $node2) {
            $ts1 = strtotime($node1->mtime);
            $ts2 = strtotime($node2->mtime);

            return $ts1 - $ts2;
        });

        //return
        $tree = [
            'spaceId'  => $spaceId,
            'type'     => 0,
            'id'       => 0,
            'pid'      => 0,
            'title'    => '回收站',
            'pos'      => 0,
        ];
        if (!empty($map)) {
            $tree['hasChild'] = true;
            $tree['children'] = $nodes;
        }

        return [
            'tree' => $tree
        ];
    }



    /**
     * get a node's child nodes
     * @param $spaceId
     * @param $treeId
     * @param $nodeId
     * @return collection
     */
    public function getChildNodes($spaceId, $treeId, $nodeId, $fields): collection {
        PermissionChecker::readSpace($spaceId);

        return TreeNode::getNodes($spaceId, $treeId, [['pid', '=', $nodeId]], $fields);
    }

    /**
     * get a node's all descendent nodes
     * @param $spaceId
     * @param $treeId
     * @param $nodeId
     * @return an array of node objects
     */
    public function getDescendentNodes($spaceId, $treeId, $nodeId, $fields): array {
        PermissionChecker::readSpace($spaceId);

        //generate the $pidMap
        $rows = TreeNode::getNodes($spaceId, $treeId, [], ['id', 'pid']);
        $pidMap = [];
        foreach ($rows as $row) {
            $pid = $row->pid;
            if (!isset($pidMap[$pid])) {
                $pidMap[$pid] = [];
            }
            $pidMap[$pid][] = $row;
        }

        //get the node
        $curNode = null;
        foreach ($rows as $row) {
            if ($row->id === $nodeId) {
                $curNode = $row;
            }
        }

        //use a stack to find out all the descendent nodes
        $descendentNodes = [];

        $stack = [$curNode];
        while (true) {
            $node = array_pop($stack);
            if ($node === null) {
                break;
            }

            $descendentNodes[] = $node;
            if (isset($pidMap[$node->id])) {
                foreach ($pidMap[$node->id] as $childNode) {
                    array_push($stack, $childNode);
                }
            }
        }

        return $descendentNodes;
    }

    /**
     * append a child node to an existing node
     * @param int $spaceId
     * @param int $treeId
     * @param string $treeVersion
     * @param int $pid
     * @param string $title
     * @return array
     *      [
     *          'id' => ...,
     *          'treeVersion' => ...
     *      ]
     * @throws TreeUpdatedException
     */
    public function appendChildNode(int $spaceId, int $treeId, string $treeVersion, int $type, int $pid, string $title): array {
        PermissionChecker::writeSpace($spaceId);

        $parentNode = TreeNode::getNodeById($spaceId, $treeId, $pid);
        if (empty($parentNode)) {
            throw new TreeUpdatedException();
        }
        $maxChildPos = TreeNode::getMaxChildPos($spaceId, $treeId, $pid);

        $node = [
            'pid'   => $pid,
            'title' => $title,
            'type'  => $type,
            'pos'   => empty($maxChildPos)
                        ? 1000
                        : $maxChildPos + 1000,
        ];
        $latestTreeVersion = $this->getTreeVersion($spaceId, $treeId);
        if ($latestTreeVersion != $treeVersion) {
            throw new TreeUpdatedException();
        }

        return TreeNode::addChildNode($spaceId, $treeId, $treeVersion, $node);
    }

    /**
     * rename a node's title
     * @param int $spaceId
     * @param int $treeId
     * @param string $treeVersion
     * @param int $nodeId
     * @param string $newTitle
     * @return string the new version string of the tree
     * @throws TreeUpdatedException, IllegalOperationException, UnfinishedDBOperationException
     */
    public function renameNode(int $spaceId, int $treeId, string $treeVersion, int $nodeId, string $newTitle): string {
        PermissionChecker::writeSpace($spaceId);

        $node = TreeNode::getNodeById($spaceId, $treeId, $nodeId);
        if (empty($node)) {
            throw new TreeUpdatedException();
        }

        if ($node->pid == 0) {
            throw new IllegalOperationException();
        }

        $latestTreeVersion = $this->getTreeVersion($spaceId, $treeId);
        if ($latestTreeVersion != $treeVersion) {
            throw new TreeUpdatedException();
        }

        $updates = [];
        $updates[$nodeId] = [
            'title' => $newTitle
        ];
        return TreeNode::modifyNodes($spaceId, $treeId, $treeVersion, $updates);
    }

    /**
     * move a node
     * @param int $spaceId
     * @param int $treeId
     * @param string $treeVersion
     * @param int $nodeId
     * @param int $newPid
     * @param int $newLocation
     * @return string the new version string of the tree
     * @throws TreeUpdatedException, IllegalOperationException
     */
    public function moveNode(int $spaceId, int $treeId, string $treeVersion, int $nodeId, int $newPid, int $newLocation): string {
        PermissionChecker::writeSpace($spaceId);

        //generate idMap and pidMap
        $idMap = [];
        $pidMap = [];
        $nodes = TreeNode::getNodes($spaceId, $treeId);
        foreach ($nodes as $tmpNode) {
            $pid = $tmpNode->pid;
            if (!isset($pidMap[$pid])) {
                $pidMap[$pid] = [];
            }
            $pidMap[$pid][] = $tmpNode;
            $idMap[$tmpNode->id] = $tmpNode;
        }

        //checks: 
        // whether the node to move exists.
        // whether the new parent node exists.
        // whether newLocation is valid.
        if (!isset($idMap[$nodeId])
            || !isset($idMap[$newPid])
            || ($newLocation > 0 && !isset($pidMap[$newPid]))
            || ($newLocation > 0 && isset($pidMap[$newPid]) && $newLocation > count($pidMap[$newPid]))
            ) {
            throw new TreeUpdatedException();
        }

        //the node must not be the root node
        $node = $idMap[$nodeId];
        if ($node->pid == 0) {
            throw new IllegalOperationException();
        }

        //the node must not be an ancestor of the new parent node
        $stack = [$node];
        while (true) {
            $tmpNode = array_pop($stack);
            if (empty($tmpNode)) {
                break;
            }

            if (!isset($pidMap[$tmpNode->id])) {
                continue;
            }

            $childNodes = $pidMap[$tmpNode->id];
            foreach ($childNodes as $childNode) {
                if ($childNode->id == $newPid) {
                    throw new IllegalOperationException();
                }
                array_push($stack, $childNode);
            }
        }

        //check if the node will do move
        if ($node->pid == $newPid) {
            $brothers = isset($pidMap[$newPid])
                ? $pidMap[$newPid]
                : [];
            foreach ($brothers as $idx => $brother) {
                if ($brother->id == $nodeId) {
                    if ($idx == $newLocation) {
                        throw new TreeUpdatedException();
                    }
                    break;
                }
            }
        }

        //calculate the value of node's new pos attribute
        $updates = [];
        $brothers = isset($pidMap[$newPid])
            ? $pidMap[$newPid]
            : [];


        $newPos = 0;
        if ($newLocation == 0 && count($brothers) == 0) {
            $newPos = 1000;
        } elseif ($newLocation == 0 && count($brothers) > 0) {
            $newPos = $brothers[0]->pos - 1000;
        } elseif ($newLocation == count($brothers)) {
            $newPos = $brothers[count($brothers) - 1]->pos + 1000;
        } elseif ($brothers[$newLocation]->pos - $brothers[$newLocation - 1]->pos >= 2) {
            $newPos = floor(($brothers[$newLocation - 1]->pos + $brothers[$newLocation]->pos) / 2);
        } else {
            //adjust the pos of all the nodes behind this node, if there is no integer number between the previous node's pos and the next node's pos
            $newPos = $brothers[$newLocation - 1]->pos + 1000;
            for ($idx = $newLocation; $idx <= count($brothers) - 1; $idx++) {
                $id = $brothers[$idx]->id;
                if ($id != $nodeId) {
                    $updates[$id] = [
                        'pos' => $brothers[$idx]->pos + 2000
                    ];
                }
            } 
        }
        $updates[$nodeId] = [
            'pid' => $newPid,
            'pos' => $newPos,
        ];

        //the node's descendants and itself should inhert the permission attributes from its new parent node.
        /*if ($node->pid != $newPid) {
            $stack = [$node];
            while (true) {
                $tmpNode = array_pop($stack);
                if (empty($tmpNode)) {
                    break;
                }

                $updates[$tmpNode->id]['group_read']  = $idMap[$newPid]->group_read;
                $updates[$tmpNode->id]['group_write'] = $idMap[$newPid]->group_write;
                $updates[$tmpNode->id]['other_read']  = $idMap[$newPid]->other_read;
                $updates[$tmpNode->id]['other_write'] = $idMap[$newPid]->other_write;
                $updates[$tmpNode->id]['guest_read']  = $idMap[$newPid]->guest_read;
                $updates[$tmpNode->id]['guest_write'] = $idMap[$newPid]->guest_write;

                if (!isset($pidMap[$tmpNode->id])) {
                    continue;
                }

                $childNodes = $pidMap[$tmpNode->id];
                foreach ($childNodes as $childNode) {
                    array_push($stack, $childNode);
                }
            }
        }*/

        $latestTreeVersion = $this->getTreeVersion($spaceId, $treeId);
        if ($latestTreeVersion != $treeVersion) {
            throw new TreeUpdatedException();
        }

        return TreeNode::modifyNodes($spaceId, $treeId, $treeVersion, $updates);
    }


    /**
     * move a node to another tree of the same space
     * @param int $spaceId
     * @param int $nodeId
     * @param int $fromTreeId
     * @param string $fromTreeVersion
     * @param int $toTreeId
     * @param string $toTreeVersion
     * @param int $toPid
     * @param int $toPrevId
     * @return string the new version strings of the tree
     * [
     *   treeId1 => newVersion1,
     *   treeId2 => newVersion2
     * ]
     * @throws TreeUpdatedException, IllegalOperationException
     */
    public function moveNodeToAnotherTree(int $spaceId, int $nodeId, int $fromTreeId, string $fromTreeVersion, int $toTreeId, string $toTreeVersion, int $toPid, int $toLocation): string {
        //basic check
        $node = TreeNode::getNodeById($spaceId, $fromTreeId, $nodeId);
        if (empty($node)) {
            throw new TreeUpdatedException();
        }

        $toParentNode = TreeNode::getNodeById($spaceId, $toTreeId, $toPid);
        if (empty($toParentNode)) {
            throw new TreeNodeNotExistException();
        }

        if ($node->space_id !== $toParentNode->space_id) {
            throw new IllegalOperationException();
        }

        //generate $fromUpdates
        $fromUpdates = [
            $nodeId => [
                'tree_id' => $toTreeId,
                'pid'     => $toPid
            ]
        ];
        $descendentNodes = $this->getDescendentNodes($spaceId, $fromTreeId, $nodeId);
        foreach ($descendentNodes as $node) {
            $fromUpdates[$node->id] = [
                'tree_id' => $toTreeId
            ];
        }

        //generate $toUpdates
        $toUpdates = [];
        $toPos = 1000;
        $toBrothers = TreeNode::getNodes($spaceId, $toTreeId, [['pid', '=', $toPid]], ['id', 'pos']);
        if (count($toBrothers) === 0) {
            $toPos = 1000;
        } elseif ($toLocation === 0) {
            $toPos = $toBrothers[0]['pos'] - 1000;
        } elseif ($toLocation >= count($toBrothers)) {
            $toPos = $toBrothers[count($toBrothers) - 1]['pos'] + 1000;
        } elseif ($toBrothers[$toLocation]['pos'] - $toBrothers[$toLocation - 1]['pos'] > 2) {
            $toPos = floor(($toBrothers[$toPrevIdx + 1]['pos'] + $toBrothers[$toPrevIdx]['pos']) / 2);
        } else {
            $toPos = $toBrothers[$i]['pos'] + 1000;
            for ($i=$toLocation; $i<count($toBrothers); $i++) {
                $id = $toBrothers[$i]['id'];
                $pos = $toBrothers[$i]['pos'];
                $toUpdates[$id] = [
                    'pos' => $pos + 2000
                ];
            }
        }
        $toUpdates[$nodeId] = [
            'pos' => $toPos
        ];

        //check the versions of the two trees
        $latestFromTreeVersion = TreeNode::getTreeVersion($spaceId, $fromTreeVersion);
        if ($latestFromTreeVersion !== $fromTreeVersion) {
            throw new TreeUpdatedException();
        }

        $latestToTreeVersion = TreeNode::getTreeVersion($spaceId, $toTreeId);
        if ($latestToTreeVersion !== $toTreeVersion) {
            throw new TreeUpdatedException();
        }

        //database operation
        $treeUpdates = [
            [
                'treeId'  => $fromTreeId,
                'version' => $fromTreeVersion,
                'updates' => $fromUpdates
            ],
            [
                'treeId'  => $toTreeId,
                'version' => $toTreeVersion,
                'updates' => $toUpdates
            ]
        ];

        return TreeNode::modifyNodesOfMultipleTrees($spaceId, $treeUpdates);
    }

    /**
     * remove a node and all its descendent nodes permanently
     * @param int $spaceId
     * @param int $treeId
     * @param string $treeVersion
     * @param int $nodeId
     * @return string the new version string of the tree
     * @throws TreeUpdatedException, IllegalOperationException
     */
   public function removeNodeRecursively(int $spaceId, int $treeId, string $treeVersion, int $nodeId): string {
        PermissionChecker::writeSpace($spaceId);


        $node = TreeNode::getNodeById($spaceId, $treeId, $nodeId);
        if (empty($node)) {
            throw new TreeUpdatedException();
        }

        if ($node->pid == 0) {
            throw new IllegalOperationException();
        }

        $latestTreeVersion = $this->getTreeVersion($spaceId, $treeId);
        if ($latestTreeVersion != $treeVersion) {
            throw new TreeUpdatedException();
        }

        //remove the nodes
        $updates = [];
        $removeNodes = $this->getDescendentNodes($spaceId, $treeId, $nodeId, ['id']);
        foreach ($removeNodes as $removeNode) {
            $updates[$removeNode->id] = [
                'deleted' => 1
            ];
        }
        return TreeNode::modifyNodes($spaceId, $treeId, $treeVersion, $updates);
    }


}