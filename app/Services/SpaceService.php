<?php

namespace App\Services;

use App\Models\TreeNode;
use App\Models\Space;
use App\Models\SpaceMenu;
use App\Exceptions\PageUpdatedException;
use App\Exceptions\TreeNotExistException;
use App\Exceptions\UnfinishedSavingException;
use Illuminate\Database\Eloquent\Collection;

class SpaceService {
    public function createSpace(int $type, string $title, string $desc): int {
        $creator = 2;

        return Space::createSpace($type, $title, $desc, $creator);
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
}