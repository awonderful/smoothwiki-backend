<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SpaceService;
use App\Models\Space;
use App\Libraries\Result;
use Illuminate\Http\Request;

class SpaceController extends Controller {
    public function createSpace(Request $request) {
        $request->validate([
            'title'       => ['required', 'string',  'max:200'],
            'desc'        => ['nullable', 'string',  'max:10000'],
            'type'        => ['required', 'integer', 'in:'.implode(',', config('dict.SpaceType'))],
            'othersRead'  => ['required', 'integer', 'in:0,1'],
            'othersWrite' => ['required', 'integer', 'in:0,1'],
        ]);

        $title       = $request->input('title');
        $desc        = $request->input('desc');
        $type        = $request->input('type');
        $othersRead  = $request->input('othersRead');
        $othersWrite = $request->input('othersWrite');

        $service = new SpaceService();
        $spaceId = $service->createSpace($type, $title, $desc, $othersRead, $othersWrite);
        return Result::data([
            'id' => $spaceId,
        ]);
    }

    public function updateSpace(Request $request) {
        $request->validate([
            'spaceId'     => ['required', 'integer', 'min:1'],
            'title'       => ['required', 'string',  'max:200'],
            'desc'        => ['nullable', 'string',  'max:10000'],
            'othersRead'  => ['required', 'integer', 'in:0,1'],
            'othersWrite' => ['required', 'integer', 'in:0,1'],
        ]);

        $spaceId     = $request->input('spaceId');
        $title       = $request->input('title');
        $desc        = $request->input('desc');
        $othersRead  = $request->input('othersRead');
        $othersWrite = $request->input('othersWrite');

        $service = new SpaceService();
        $spaceId = $service->updateSpace($spaceId, $title, $desc, $othersRead, $othersWrite);
        return Result::succ();
    }



    public function removeSpace(Request $request) {
        $request->validate([
            'spaceId' => ['required', 'integer',  'min:1'],
        ]);

        $spaceId = $request->input('spaceId');

        $service = new SpaceService();
        $service->removeSpace($spaceId);
        return Result::succ();
    }

    public function getSpaces(Request $request) {
        $service = new SpaceService();
        $spaces = $service->getCurrentUserSpaces();

        return Result::data([
            'spaces' => $spaces
        ]);
    }

    public function getSpaceMembers(Request $request) {
        $request->validate([
            'spaceId' => ['required', 'integer',  'min:1'],
        ]);

        $spaceId = $request->input('spaceId');

        $service = new SpaceService();
        $members = $service->getSpaceMembers($spaceId);
        return Result::data([
            'members' => $members,
        ]);
    }

    public function addSpaceMember(Request $request) {
        $request->validate([
            'spaceId' => ['required', 'integer',  'min:1'],
            'email'   => ['required', 'string',   'max:100'],
            'role'    => ['required', 'integer',  'in:'.implode(',', config('dict.SpaceMemberRole'))]
        ]);

        $spaceId = $request->input('spaceId');
        $email   = $request->input('email');
        $role    = $request->input('role');

        $service = new SpaceService();
        $member = $service->addSpaceMember($spaceId, $email, $role);
        return Result::data([
            'member' => $member,
        ]);
    }

    public function removeSpaceMember(Request $request) {
        $request->validate([
            'spaceId' => ['required', 'integer',  'min:1'],
            'uid'     => ['required', 'integer',  'min:1'],
        ]);

        $spaceId = $request->input('spaceId');
        $uid     = $request->input('uid');

        $service = new SpaceService();
        $members = $service->removeSpaceMember($spaceId, $uid);
        return Result::succ();
    }


}