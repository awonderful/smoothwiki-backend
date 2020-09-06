<?php

namespace App\Services;

use App\Models\TreeNode;
use App\Models\Space;
use App\Exceptions\PageUpdatedException;
use App\Exceptions\TreeNotExistException;
use App\Exceptions\UnfinishedSavingException;
use Illuminate\Database\Eloquent\Collection;

class SpaceService {
    public function createSpace(int $type, string $title, string $desc): int {
        $creator = 0;

        return Space::createSpace(type, title, desc, creator);
    }
}