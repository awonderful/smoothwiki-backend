<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use App\Libraries\Util;
use App\Exceptions\UnfinishedDBOperationException;
use App\Exceptions\ArticleUpdatedException;
use App\Exceptions\ArticleNotExistException;
use App\Exceptions\ArticleRemovedException;
use App\Exceptions\PageUpdatedException;
use App\Models\Attachment;

class Search
{

	public static function searchInSpace(int $spaceId, string $keywordStr) {
		$keywords = explode(' ', $keywordStr);

		$nodeTitleConditions = [];
		$articleTitleConditions = [];
		foreach ($keywords as $keyword) {
			$nodeTitleConditions[] = ['title', 'like', "%{$keyword}%"];
			$articleTitleConditions[] = ['title', 'like', "%{$keyword}%"];
		}

		$nodeTitle = DB::table('tree_node')
							->select('id', 'title AS rstitle',  DB::raw("'' as rscnt"), DB::raw(config('dict.SearchType.NODE_TITLE').' AS searchType'))
		          ->where('space_id', $spaceId)
							->where('deleted', 0)
							->where($nodeTitleConditions);
		$articleTitle = DB::table('article')
							->select('id', 'title AS rstitle', 'search as rscnt', DB::raw(config('dict.SearchType.ARTICLE_TITLE').' AS searchType'))
							->where('space_id', $spaceId)
							->where('deleted', 0)
							->where($articleTitleConditions);
		$articleBody = DB::table('article')
							->select('id', 'title as rstitle', 'search AS rscnt', DB::raw(config('dict.SearchType.ARTICLE_BODY').' AS searchType'))
							->where('space_id', $spaceId)
							->where('deleted', 0)
							->whereRaw('MATCH(search) AGAINST(?)', array($keywordStr));
		return $nodeTitle
						->union($articleTitle)
						->union($articleBody)
						->get();
	}

}