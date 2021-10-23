<?php

namespace App\Services;

use App\Models\Search;
use App\Exceptions\UnfinishedDBOperationException;
use App\Exceptions\IllegalOperationException;
use App\Services\PermissionChecker;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class SearchService {
		public function searchInSpace(int $spaceId, string $keyword) {
			PermissionChecker::readSpace($spaceId);

			return Search::searchInSpace($spaceId, $keyword);
		}
}