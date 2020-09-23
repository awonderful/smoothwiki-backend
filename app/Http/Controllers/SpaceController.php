<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SpaceService;
use App\Models\Space;
use App\Libraries\Result;
use App\Exceptions\SpaceMenuNotExistException;
use Illuminate\Http\Request;

class SpaceController extends Controller {
    public function createSpace(Request $request) {
        $request->validate([
            'title'        => ['required', 'max:200'],
            'desc'         => ['required', 'max:10000'],
            'type'         => ['required', 'integer', 'in:'.implode(',', config('dict.SpaceType'))],
        ]);

        $title = $request->input('title');
        $desc  = $request->input('desc');
        $type  = $request->input('type');

        $service = new SpaceService();
        $spaceId = $service->createSpace($type, $title, $desc);
        return Result::data([
            'id' => $spaceId,
        ]);
    }

    public function createMenu(Request $request) {
        $request->validate([
            'spaceId' => ['required', 'integer', 'min:1'],
            'title'   => ['required', 'max:200'],
            'type'    => ['required', 'integer', 'in:'.implode(',', config('dict.SpaceMenuType'))],
        ]);

        try {
            $spaceId = $request->input('spaceId');
            $title   = $request->input('title');
            $type    = $request->input('type');

            $service = new SpaceService();
            $spaceMenuId = $service->createMenu($spaceId, $title, $type);
            return Result::data([
                'id' => $spaceMenuId,
            ]);
        } catch(SpaceMenuNotExistException $e) {
            return Result::Error('SPACE_MENU_NOT_EXIST');
        }
    }

     public function renameMenu(Request $request) {
        $request->validate([
            'spaceId'  => ['required', 'integer', 'min:1'],
            'menuId'   => ['required', 'integer', 'min:1'],
            'title'    => ['required', 'max:200'],
        ]);

        try {
            $spaceId = $request->input('spaceId');
            $menuId  = $request->input('menuId');
            $title   = $request->input('title');

            $service = new SpaceService();
            $spaceMenuId = $service->renameMenu($spaceId, $menuId, $title);
            return Result::Succ();
        } catch(SpaceMenuNotExistException $e) {
            return Result::Error('SPACE_MENU_NOT_EXIST');
        }
    }

   
}