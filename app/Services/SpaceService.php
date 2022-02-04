<?php

namespace App\Services;

use App\Models\Space;
use App\Models\SpaceMenu;
use App\Models\SpaceMember;
use App\Models\Users;
use App\Exceptions\UnfinishedDBOperationException;
use App\Exceptions\IllegalOperationException;
use App\Exceptions\MemberNotExistException;
use App\Services\PermissionChecker;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class SpaceService {
    public function createSpace(int $type, string $title, string $desc, int $othersRead, int $othersWrite): int {
        $creator = Auth::id();

        if ($type === config('dict.SpaceType.PERSON')) {
            $user = Auth::user();
            $title = $user->name;
        }

        return Space::createSpace($type, $title, $desc, $creator, $othersRead, $othersWrite);
    }

    public function updateSpace(int $spaceId, string $title, string $desc, int $othersRead, int $othersWrite): void {
        PermissionChecker::administrateSpace($spaceId);

        $space = Space::getSpaceById($spaceId);
        if ($space->type === config('dict.SpaceType.PERSON')) {
            $title = $space->title;
        }

        Space::modifySpace($spaceId, $title, $desc, $othersRead, $othersWrite);
    }

    public function removeSpace(int $spaceId) {
        PermissionChecker::removeSpace($spaceId);

        Space::removeSpace($spaceId);
    }

    public function createMenu(int $spaceId, string $title, int $type, string $extend = '') {
        switch ($type) {
            case config('dict.SpaceMenuType.WIKI'):
            case config('dict.SpaceMenuType.POST'):
            case config('dict.SpaceMenuType.API'):
            case config('dict.SpaceMenuType.DATABASE'):
                return SpaceMenu::createMenuWithTree($spaceId, $title, $type);

            case config('dict.SpaceMenuType.LINK'):
                return SpaceMenu::createMenu($spaceId, $title, $type, 0, $extend);
        }
    }

    public function renameMenu(int $spaceId, int $menuId, string $title): void {
        SpaceMenu::renameMenu($spaceId, $menuId, $title);
    }

    public function removeMenu(int $spaceId, int $menuId): void {
        SpaceMenu::removeMenu($spaceId, $menuId);
    }

    public function getCurrentUserSpaces(): array {
        $userId = Auth::id();

        $spaceIds = [];
        $rows = SpaceMember::getUserSpaceMemberRecords($userId);
        foreach ($rows as $row) {
            $spaceIds[] = $row->space_id;
        }

        $spaces = [];
        $rows = Space::getSpaces($spaceIds);
        foreach ($rows as $row) {
            $spaces[] = [
                'id'          => $row->id,
                'type'        => $row->type,
                'title'       => $row->title,
                'desc'        => $row->desc,
                'othersRead'  => $row->others_read,
                'othersWrite' => $row->others_write,
                'ctime'       => $row->ctime,
                'mtime'       => $row->mtime
            ];
        }

        return $spaces;
    }

    public function getSpaceMembers(int $spaceId): array {
        $uids = [];

        $members = SpaceMember::getMembers($spaceId);
        foreach ($members as $member) {
            $uids[] = $member['uid'];
        }

        $users = Users::getUsers($uids);
        $map = [];
        foreach ($users as $user) {
            $uid = $user->id;
            $map[$uid] = $user;
        }

        $outMembers = [];
        foreach ($members as $member) {
            $uid = $member->uid;
            $outMembers[] = [
                'spaceId' => $member->space_id,
                'uid'     => $member->uid,
                'role'    => $member->role,
                'ctime'   => $member->ctime,
                'mtime'   => $member->mtime,
                'name'    => $map[$uid]->name,
                'email'   => $map[$uid]->email,
            ];
        }

        return $outMembers;
    }

    public function removeSpaceMember(int $spaceId, int $uid): void {
        PermissionChecker::administrateSpace($spaceId);

        $member = SpaceMember::getMemberByUid($spaceId, $uid);
        if ($member === null) {
            throw new MemberNotExistException();
        }

        if ($member->role === config('dict.SpaceMemberRole.CREATOR')) {
            throw new IllegalOperationException();
        }

        SpaceMember::removeMember($spaceId, $uid);
    }

    public function addSpaceMember(int $spaceId, string $email, int $role): array {
        if ($role === config('dict.SpaceMemberRole.CREATOR')) {
            throw new IllegalOperationException();
        }

        PermissionChecker::administrateSpace($spaceId);

        $user = Users::getUserByEmail($email);
        $uid = $user->id;

        if (SpaceMember::getRemovedMemberByUid($spaceId, $uid)) {
            SpaceMember::restoreMemberAndSetRole($spaceId, $uid, $role);
        } else {
            $member = SpaceMember::getMemberByUid($spaceId, $uid);
            if ($member !== null) {
                SpaceMember::setMemberRole($spaceId, $uid, $role);
            } else {
                SpaceMember::addMember($spaceId, $uid, $role);
            }
        }

        $member = SpaceMember::getMemberByUid($spaceId, $uid);
        return [
            'spaceId' => $spaceId,
            'uid'     => $uid,
            'role'    => $member->role,
            'ctime'   => $member->ctime,
            'mtime'   => $member->mtime,
            'name'    => $user->name,
            'email'   => $user->email
        ];
    }
}