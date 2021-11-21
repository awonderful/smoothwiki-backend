<?php

namespace App\Services;

use App\Models\Search;
use App\Models\Space;
use App\Models\TreeNode;
use App\Models\Article;
use App\Exceptions\UnfinishedDBOperationException;
use App\Exceptions\IllegalOperationException;
use App\Services\PermissionChecker;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class SearchService {
		public function searchInSpace(int $spaceId, string $keyword) {
			PermissionChecker::readSpace($spaceId);

			$items = Search::searchInSpace($spaceId, $keyword)->toArray();
			foreach ($items as $idx => $item) {
				$items[$idx]->spaceId = $spaceId;
			}

			$richItems = $this->enrichSearchResult($items);

			return $richItems;
		}

		private function enrichSearchResult($items) {
			$spaceIds = [];
			foreach ($items as $idx => $item) {
				array_push($spaceIds, $item->spaceId);
			}
			$spaces = Space::getSpaces($spaceIds);
			$spaceMap = [];
			foreach ($spaces as $space) {
				$spaceMap[$space->id] = $space;
			}
			foreach ($items as $idx => $item) {
				$spaceId = $item->spaceId;
				if (isset($spaceMap[$spaceId])) {
					$items[$idx]->spaceTitle = $spaceMap[$spaceId]->title;
					$items[$idx]->spaceType = $spaceMap[$spaceId]->type;
				}
			}

			$nodeMap = [];
			$pnodeMap = [];
			$nodes = TreeNode::getMultiTreesNodes($spaceIds);
			foreach ($nodes as $node) {
				$pid = $node->pid;
				if (!isset($pnodeMap[$pid])) {
				 	$pnodeMap[$pid] = [];
				}
				array_push($pnodeMap[$pid], $node);

				$nodeMap[$node->id] = $node;
			}
			foreach ($items as $idx => $item) {
				if ($item->searchType === config('dict.SearchType.NODE_TITLE')) {
					$path = [];
					if (!isset($nodeMap[$item->id])) {
						continue;
					}

					array_push($path, $nodeMap[$item->id]);
					while (true) {
						$curNode = $path[count($path) - 1];
						if (!isset($nodeMap[$curNode->pid])) {
							break;
						}
						array_push($path, $nodeMap[$curNode->pid]);
					}

					$children = isset($pnodeMap[$item->id])
										? $pnodeMap[$item->id]
										: null;

					$items[$idx]->path = array_reverse($path);
					if ($children !== null) {
						$items[$idx]->children = $children;
					}
				}

				if ($item->searchType === config('dict.SearchType.ARTICLE_TITLE') || $item->searchType === config('dict.SearchType.ARTICLE_BODY')) {
					$path = [];
					if (!isset($nodeMap[$item->belongId])) {
						continue;
					}

					array_push($path, $nodeMap[$item->belongId]);
					while (true) {
						$curNode = $path[count($path) - 1];
						if (!isset($nodeMap[$curNode->pid])) {
							break;
						}
						array_push($path, $nodeMap[$curNode->pid]);
					}

					$items[$idx]->path = array_reverse($path);
				}
			}

			$nodeIds = [];
			foreach ($items as $item) {
				if ($item->searchType === config('dict.SearchType.NODE_TITLE')) {
					array_push($nodeIds, $item->id);
				}
			}
			if (count($nodeIds) > 0) {
				$articles = Article::getArticlesFromMultiplePages($nodeIds, ['id', 'type', 'title']);
				$nodeIdArticleMap = [];
				foreach ($articles as $article) {
					$nodeIdArticleMap[$article->node_id] = $article;
				}
				foreach ($items as $idx => $item) {
					if ($item->searchType === config('dict.SearchType.NODE_TITLE') && isset($nodeIdArticleMap[$item->id])) {
						$cntArticles = [];
						foreach ($nodeIdArticleMap[$item->id] as $article) {
							array_push($cntArticle, [
								'id'    => $article->id,
								'type'  => $article->type,
								'title' => $article->title,
							]);
						}
						$items[$idx]->articles = $cntArticles;
					}
				}
			}

			return $items;
		}
}