<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Libraries\Result;
use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller {
    public function search(Request $request) {
        $request->validate([
            'spaceId'     => ['nullable', 'integer', 'min:0'],
            'keyword'     => ['required', 'string',  'min:1'],
            'pageSize'    => ['required', 'integer', 'min:1', 'max:100'],
            'whichPage'   => ['required', 'integer', 'min:1'],
            'range'       => ['required', 'string'],
        ]);

        $range     = $request->input('range');
        $spaceId   = $request->input('spaceId', 0);
        $whichPage = $request->input('whichPage');
        $pageSize  = $request->input('pageSize');
        $keyword   = $request->input('keyword');

        $symbols = ['-', '+', '<', '>', '@', '(', ')', '{', '}', '~', "'", '"', '`', '?', '%', '=', '*', '&', '^', '$', '#', ':', '[', ']', '|', '/', '\\'];
        $keyword = str_replace($symbols, '', $keyword);
        $keyword = preg_replace('/ {1,}/', '', $keyword);
        $keyword = trim($keyword);

        $service = new SearchService();
        $data = $service->search($range, $keyword, $whichPage, $pageSize, $spaceId);
        return Result::data($data);
    }
}
