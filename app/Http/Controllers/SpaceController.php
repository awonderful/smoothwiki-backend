<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SpaceService;
use App\Models\Space;
use App\Libraries\Result;
use Illuminate\Http\Request;

class SpaceController extends Controller {
    public function createPersonalSpace(Request $request) {
        try {
            $service = new SpaceService();
            $spaceId = $service->createPersonalSpace();
            return Result::data([
                'id' => $spaceId,
            ]);

        } catch (PersonalSpaceAlreadyExistException $e) {
            return Result::error('PERSONAL_SPACE_ALREADY_EXIST');
        }
    }

    public function createGroupSpace(Request $request) {
        $request->validate([
            'title'        => ['required', 'max:200'],
            'desc'         => ['required', 'max:10000'],
            'type'         => ['required', 'integer', 'in:'.implode(',', config('dict.SpaceType'))],
        ]);

        try {
            $title         = $request->input('title');
            $desc          = $request->input('desc');
            $type          = $request->input('type');

            $service = new SpaceService();
            $spaceId = $service->createSpace($type, $title, $desc);
            return Result::data([
                'id' => $spaceId,
            ]);

        } catch (PageNotExistException $e) {
            return Result::error('PAGE_NOT_EXIST');
        }       
    }
}
 