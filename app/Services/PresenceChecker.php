<?php

namespace App\Services;

use App\Models\TreeNode;
use App\Exceptions\TreeNodeNotExistException;
use App\Exceptions\SpaceNotExistException;

class PresenceChecker {
  /**
   * check if a node exists
   * @param int $spaceId
   * @param int $nodeId
   * @return void
   * @throws IllegalOperationException
   */
  public static function node($spaceId, $nodeId): void {
      $node  = TreeNode::getNodeById($spaceId, 1, $nodeId);

      if ($node === null) {
          throw new TreeNodeNotExistException();
      }
  }

}