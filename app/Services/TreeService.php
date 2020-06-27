<?php

namespace App\Services;

use App\Models\TreeNode;
use App\Exceptions\TreeUpdatedException;

class TreeService {

    public function getNodes($spaceId, $category) {
        return TreeNode::where('space_id', $spaceId)
            ->where('category', $category)
            ->where('deleted', 0)
            ->orderby('pos', 'asc')
            ->get();
    }

    public function getTree($spaceId, $category) {
        $rows = $this->getNodes($spaceId, $category);

        $pidMap = [];
        foreach ($rows as $row) {
            $pid = $row['pid'];
            if (!isset($pidMap[$pid])) {
                $pidMap[$pid] = [];
            }
            $pidMap[$pid][] = $row;
        }

        $root = $pidMap[0][0];
        $stack = [&$root];

        while (true) {
            $node = array_pop($stack);
            if (empty($node)) {
                break;
            }

            $id = $node['id'];
            if (isset($pidMap[$id])) {
                $node['children'] = $pidMap[$id];
                foreach ($node['children'] as $child) {
                    $stack[] = &$child;
                }
            }
        }

        return $root;
    }

    public function createNode($node) {
        $row = TreeNode::where('space_id', $node['space_id'])
            ->where('category', $node['category'])
            ->where('id', $node['pid'])
            ->where('deleted', 0)
            ->first();

        if (empty($row)) {
            throw new TreeUpdatedException();
        }

        $treeNode = new TreeNode();
        $TreeNode->space_id = $node['space_id'];
        $treeNode->category = $node['category'];
        $treeNode->pid      = $node['pid'];
        $treeNode->title    = $node['title'];
        $treeNode->version  = 0;

        $treeNode->save();
    }

    public function renameNode($nodeId, $title) {
        $row = TreeNode::where('id', $node['pid'])
            ->where('deleted', 0)
            ->first();

        if (empty($row) || $row['version'] != $version) {
            throw new TreeUpdatedException();
        }

        $succ = TreeNode::where('id', $nodeId)
            ->where('deleted', 0)
            ->where('version', $version)
            ->update(['title' => $title]);

    }

    public function delNode($nodeId) {

    }

    public function moveNode($nodeId, $oldPid, $newPid, $newPrevId, $newNextId) {

    }
}