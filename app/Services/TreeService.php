<?php

namespace App\Services;

use App\Models\TreeNode;
use App\Exceptions\TreeUpdatedException;
use App\Exceptions\TreeNotExistException;
use App\Exceptions\UnfinishedSavingException;

class TreeService {

    /**
     * get the version string of tree tree
     * @param int $spaceId
     * @param int $treeId
     * @return string the version string of the tree
     * @throws TreeNotExistException
     */
    public function getTreeVersion(int $spaceId, int $treeId): string {
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
        $rows = TreeNode::getNodes($spaceId, $treeId, ['id', 'pid', 'tree_id', 'type', 'title']);
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
            'spaceId'  => $root->tree_id,
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
                $node['children'] = [];
                foreach ($pidMap[$id] as $childNode) {
                    $child = [
                        'spaceId'  => $childNode->tree_id,
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
     * @throws TreeUpdatedException, IllegalOperationException, UnfinishedSavingException
     */
    public function renameNode(int $spaceId, int $treeId, string $treeVersion, int $nodeId, string $newTitle): string {
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
}