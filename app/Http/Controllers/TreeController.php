<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\TreeService;
use App\Models\TreeNode;
use App\Libraries\Result;
use App\Exceptions\TreeUpdatedException;
use App\Exceptions\TreeNotExistException;
use Illuminate\Http\Request;

class TreeController extends Controller {
    public function getTree(Request $request) {
        $request->validate([
            'spaceId'     => ['required', 'integer', 'min:1'],
            'treeId'      => ['required', 'integer', 'min:0'],
        ]);

        $spaceId = $request->input('spaceId');
        $treeId = $request->input('treeId');

        $service = new TreeService();
        $tree = $service->getTree($spaceId, $treeId);

        return Result::data($tree);
    }

    public function getTreeVersion(Request $request) {
        $request->validate([
            'spaceId'     => ['required', 'integer', 'min:1'],
            'treeId'      => ['required', 'integer', 'min:0'],
        ]);

        $spaceId = $request->input('spaceId');
        $treeId = $request->input('treeId');

        $service = new TreeService();
        $treeVersion = $service->getTreeVersion($spaceId, $treeId);

        return Result::data([
            'treeVersion' => $treeVersion,
        ]);
    }

    public function appendChildNode(Request $request) {
        $request->validate([
            'spaceId'     => ['required', 'integer', 'min:1'],
            'treeId'      => ['required', 'integer', 'min:0'],
            'treeVersion' => ['required', 'max:40'],
            'type'        => ['required', 'integer', 'min:0'],
            'pid'         => ['required', 'integer', 'min:1'],
            'title'       => ['required', 'max:100'],
        ]);

        $spaceId     = $request->input('spaceId');
        $treeId      = $request->input('treeId');
        $treeVersion = $request->input('treeVersion');
        $type        = $request->input('type');
        $pid         = $request->input('pid');
        $title       = $request->input('title');

        $service = new TreeService();
        $rs = $service->appendChildNode($spaceId, $treeId, $treeVersion, $type, $pid, $title);

        return Result::data([
            'nodeId'      => $rs['nodeId'],
            'treeVersion' => $rs['treeVersion'],
        ]);
    }

    public function renameNode(Request $request) {
         $request->validate([
            'spaceId'     => ['required', 'integer', 'min:1'],
            'treeId'      => ['required', 'integer', 'min:0'],
            'treeVersion' => ['required', 'max:40'],
            'nodeId'      => ['required', 'integer', 'min:1'],
            'newTitle'    => ['required', 'max:100'],
        ]);

        $spaceId     = $request->input('spaceId');
        $treeId      = $request->input('treeId');
        $treeVersion = $request->input('treeVersion');
        $nodeId      = $request->input('nodeId');
        $newTitle    = $request->input('newTitle');

        $service = new TreeService();
        $newTreeVersion = $service->renameNode($spaceId, $treeId, $treeVersion, $nodeId, $newTitle);

        return Result::data([
            'treeVersion' => $newTreeVersion,
        ]);
    }

    public function moveNode(Request $request) {
        $request->validate([
            'spaceId'     => ['required', 'integer', 'min:1'],
            'treeId'      => ['required', 'integer', 'min:0'],
            'treeVersion' => ['required', 'max:40'],
            'nodeId'      => ['required', 'integer', 'min:1'],
            'newPid'      => ['required', 'integer', 'min:1'],
            'newLocation' => ['required', 'integer', 'min:0'],
        ]);

        $spaceId     = $request->input('spaceId');
        $treeId      = $request->input('treeId');
        $treeVersion = $request->input('treeVersion');
        $nodeId      = $request->input('nodeId');
        $newPid      = $request->input('newPid');
        $newLocation = $request->input('newLocation');

        $service = new TreeService();
        $newTreeVersion = $service->moveNode($spaceId, $treeId, $treeVersion, $nodeId, $newPid, $newLocation);

        return Result::data([
            'treeVersion' => $newTreeVersion,
        ]);
    }

    public function removeNode(Request $request) {
        $request->validate([
            'spaceId'     => ['required', 'integer', 'min:1'],
            'treeId'      => ['required', 'integer', 'min:0'],
            'treeVersion' => ['required', 'max:40'],
            'nodeId'      => ['required', 'integer', 'min:1'],
        ]);

        $spaceId     = $request->input('spaceId');
        $treeId      = $request->input('treeId');
        $treeVersion = $request->input('treeVersion');
        $nodeId      = $request->input('nodeId');

        $service = new TreeService();
        $newTreeVersion = $service->removeNodeRecursively($spaceId, $treeId, $treeVersion, $nodeId);

        return Result::data([
            'treeVersion' => $newTreeVersion,
        ]);
    }

    public function restoreNode(Request $request) {
        $request->validate([
            'spaceId'     => ['required', 'integer', 'min:1'],
            'treeId'      => ['required', 'integer', 'min:0'],
            'treeVersion' => ['required', 'max:40'],
            'nodeId'      => ['required', 'integer', 'min:1'],
        ]);

        $spaceId     = $request->input('spaceId');
        $treeId      = $request->input('treeId');
        $treeVersion = $request->input('treeVersion');
        $nodeId      = $request->input('nodeId');

        $service = new TreeService();
        $newTreeVersion = $service->restoreNodeRecursively($spaceId, $treeId, $treeVersion, $nodeId);

        return Result::data([
            'treeVersion' => $newTreeVersion,
        ]);
    }

    public function getTrashTree(Request $request) {
        $request->validate([
            'spaceId'     => ['required', 'integer', 'min:1'],
            'treeId'      => ['required', 'integer', 'min:0'],
        ]);

        $spaceId = $request->input('spaceId');
        $treeId = $request->input('treeId');

        $service = new TreeService();
        $tree = $service->getTrashTree($spaceId, $treeId);

        return Result::data($tree);
    }

}