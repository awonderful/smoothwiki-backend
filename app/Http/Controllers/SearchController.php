<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Libraries\Result;
use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller {
    public function searchInSpace(Request $request) {
        $request->validate([
            'spaceId'     => ['required', 'integer', 'min:1'],
            'keyword'     => ['required', 'string',  'min:1']
        ]);

        $spaceId = $request->input('spaceId');
        $keyword = $request->input('keyword');

        $service = new SearchService();
        $items = $service->searchInSpace($spaceId, $keyword);
        return Result::data([
            'items' => $items,
        ]);
    }
}
