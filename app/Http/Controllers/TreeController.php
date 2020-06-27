<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\TreeService;
use Illuminate\Http\Request;

class TreeController extends Controller {
    public function getTree(Request $request) {
        $spaceId = $request->input('spaceId');
        $category = $request->input('category');

        $service = new TreeService();
        $tree = $service->getTree($spaceId, $category);

        return $this->dataResult($tree);
    }
}