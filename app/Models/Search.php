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
		$articleBodyConditions = [];
		foreach ($keywords as $keyword) {
			$nodeTitleConditions[] = ['title', 'like', "%{$keyword}%"];
			$articleTitleConditions[] = ['title', 'like', "%{$keyword}%"];
			$articleBodyConditions[] = ['search', 'like', "%{$keyword}%"];
		}

		$nodeTitle = DB::table('tree_node')
							->select('id', 'title',  DB::raw("'' as content"), DB::raw('pid AS belongId'), DB::raw(config('dict.SearchType.NODE_TITLE').' AS searchType'))
		          ->where('space_id', $spaceId)
							->where('pid', '>', 0)
							->where('deleted', 0)
							->where($nodeTitleConditions);
		$articleTitle = DB::table('article')
							->select('id', 'title', 'search as content', DB::raw('node_id AS belongId'), DB::raw(config('dict.SearchType.ARTICLE_TITLE').' AS searchType'))
							->where('space_id', $spaceId)
							->where('deleted', 0)
							->where($articleTitleConditions)
							->whereIn('node_id', function($query) use ($spaceId) {
								$query->select('id')
											->from('tree_node')
											->where('space_id', $spaceId)
											->where('deleted', 0);
							});
		$articleBody = DB::table('article')
							->select('id', 'title', 'search AS content', DB::raw('node_id AS belongId'), DB::raw(config('dict.SearchType.ARTICLE_BODY').' AS searchType'))
							->where('space_id', $spaceId)
							->where('deleted', 0)
							->where($articleBodyConditions)
							->whereIn('node_id', function($query) use ($spaceId) {
								$query->select('id')
											->from('tree_node')
											->where('space_id', $spaceId)
											->where('deleted', 0);
							});
		return $nodeTitle
						->union($articleTitle)
						->union($articleBody)
						->get();
	}
}