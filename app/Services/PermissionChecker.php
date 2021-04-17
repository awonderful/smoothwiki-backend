<?php

namespace App\Services;

use App\Models\Space;
use App\Models\SpaceMember;
use App\Exceptions\PermissionDeniedException;
use App\Exceptions\SpaceNotExistException;
use Illuminate\Support\Facades\Auth;

class PermissionChecker {
    public static function getSpacePermission(int $spaceId, string $permisionType): bool {
        $spacePermissionMapStr = session('spacePermissionMap');
        $spacePermissionMap = $spacePermissionMapStr !== null
                            ? json_decode($spacePermissionMapStr, true)
                            : [];

        if (!isset($spacePermissionMap[$spaceId])) {
            $space = Space::getSpaceById($spaceId);
            if ($space === null) {
                throw new SpaceNotExistException();
            }
            $spacePermissionMap[$spaceId] = [];
            if ($space->others_read === 1) {
                $spacePermissionMap[$spaceId]['read'] = true;
            }
            if ($space->others_write === 1) {
                $spacePermissionMap[$spaceId]['write'] = true;
            }
        }

        if (!isset($spacePermissionMap[$spaceId][$permisionType])) {
            $userId = Auth::id();
            $rows = SpaceMember::getMembers($spaceId);
            foreach ($rows as $row) {
                if ($row->uid === $userId) {
                    $roleDict = config('dict.SpaceMemberRole');

                    switch ($row->role) {
                        case $roleDict['CREATOR']:
                            $spacePermissionMap[$spaceId]['read']    = true;
                            $spacePermissionMap[$spaceId]['write']   = true;
                            $spacePermissionMap[$spaceId]['admin']   = true;
                            $spacePermissionMap[$spaceId]['remove']  = true;
                            break;

                        case $roleDict['ADMIN']:
                            $spacePermissionMap[$spaceId]['read']    = true;
                            $spacePermissionMap[$spaceId]['write']   = true;
                            $spacePermissionMap[$spaceId]['admin']   = true;
                            $spacePermissionMap[$spaceId]['remove']  = false;
                            break;

                        case $roleDict['GENERAL']:
                            $spacePermissionMap[$spaceId]['read']    = true;
                            $spacePermissionMap[$spaceId]['write']   = true;
                            $spacePermissionMap[$spaceId]['admin']   = false;
                            $spacePermissionMap[$spaceId]['remove']  = false;
                            break;
                    }
                }
            }
            if (!isset($spacePermissionMap[$spaceId]['read'])) {
                $spacePermissionMap[$spaceId]['read'] = false;
            }
            if (!isset($spacePermissionMap[$spaceId]['write'])) {
                $spacePermissionMap[$spaceId]['write'] = false;
            }
            if (!isset($spacePermissionMap[$spaceId]['admin'])) {
                $spacePermissionMap[$spaceId]['admin'] = false;
            }
            if (!isset($spacePermissionMap[$spaceId]['remove'])) {
                $spacePermissionMap[$spaceId]['remove'] = false;
            }
        }

        $newSpacePermissionMapStr = json_encode($spacePermissionMap);
        if ($spacePermissionMapStr !== $newSpacePermissionMapStr) {
            session('spacePermissionMap', $newSpacePermissionMapStr);
        }

        return $spacePermissionMap[$spaceId][$permisionType];
    }

    /**
     * check if the current user has the permission to read data from the space
     */
    public static function readSpace(int $spaceId) {
        $havePermission = static::getSpacePermission($spaceId, 'read');

        if (!$havePermission) {
            throw new PermissionDeniedException();
        }
    }

    /**
     * check if the current user has the permission to write data into the space
     */
    public static function writeSpace(int $spaceId) {
        $havePermission = static::getSpacePermission($spaceId, 'write');

        if (!$havePermission) {
            throw new PermissionDeniedException();
        }
    }

    /**
     * check if the current user has the permission to administrate the space
     */
    public static function administrateSpace(int $spaceId) {
        $havePermission = static::getSpacePermission($spaceId, 'admin');

        if (!$havePermission) {
            throw new PermissionDeniedException();
        }
    }

    /**
     * check if the current user has the permission to remove the space
     */
    public static function removeSpace(int $spaceId) {
        $havePermission = static::getSpacePermission($spaceId, 'remove');

        if (!$havePermission) {
            throw new PermissionDeniedException();
        }
    }
}