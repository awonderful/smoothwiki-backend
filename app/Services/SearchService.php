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
		public function search(string $range, string $keyword, int $whichPage, int $pageSize, int $spaceId) {
			$data = null;

			switch ($range) {
				case 'space':
					$data = Search::searchInSpace($spaceId, $keyword, $whichPage, $pageSize);
					break;

				case 'user':
					$uid = Auth::id();
					$data = Search::searchInUser($uid, $keyword, $whichPage, $pageSize);
					break;

				case 'site':
					$uid = Auth::id();
					$data = Search::searchInSite($uid, $keyword, $whichPage, $pageSize);
					break;
			}

			$items = [];
			foreach ($data['items'] as $item ) {
				$items[] = (object) [
					'spaceId'       => $item->space_id,
					'spaceDeleted'  => $item->space_deleted,
					'objectType'    => $item->object_type,
					'objectId'      => $item->object_id,
					'objectTitle'   => $item->object_title,
					'objectContent' => $item->object_content,
				];
			}
			$richItems = $this->enrichSearchResult($items);

			return [
				'count' => $data['count'],
				'items' => $richItems,
				'whichPage' => $whichPage,
				'pageSize'  => $pageSize,
				'pageCount' => intval(($data['count'] + $pageSize - 1) / $pageSize)
			];
		}

		private function enrichSearchResult(array $items) {
			//nodeId
			$articleIds = [];
			$articleMap = [];
			foreach ($items as $idx => $item) {
				$articleIds[] = $item->objectId;
			}
			$articles = Article::getArticlesByIds($articleIds);
			foreach ($articles as $article) {
				$articleMap[$article->id] = $article;
			}
			foreach ($items as $idx => $item) {
				$items[$idx]->nodeId = $articleMap[$item->objectId]->node_id;
			}

			//spaceType, spaceTitle
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

			//path, children
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
				if ($item->objectType === config('dict.SearchObjectType.TREE_NODE')) {
					$path = [];
					if (!isset($nodeMap[$item->objectId])) {
						continue;
					}

					array_push($path, $nodeMap[$item->objectId]);
					while (true) {
						$curNode = $path[count($path) - 1];
						if (!isset($nodeMap[$curNode->pid])) {
							break;
						}
						array_push($path, $nodeMap[$curNode->pid]);
					}

					$children = isset($pnodeMap[$item->objectId])
										? $pnodeMap[$item->objectId]
										: null;

					$items[$idx]->path = array_reverse($path);
					if ($children !== null) {
						$items[$idx]->children = $children;
					}
				}

				if ($item->objectType === config('dict.SearchObjectType.ARTICLE')) {
					$path = [];
					if (!isset($nodeMap[$item->nodeId])) {
						continue;
					}

					array_push($path, $nodeMap[$item->nodeId]);
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

			//articles
			$nodeIds = [];
			foreach ($items as $item) {
				if ($item->objectType === config('dict.SearchObjectType.TREE_NODE')) {
					array_push($nodeIds, $item->objectId);
				}
			}
			if (count($nodeIds) > 0) {
				$articles = Article::getArticlesFromMultiplePages($nodeIds, ['id', 'type', 'title', 'node_id']);
				$nodeIdArticlesMap = [];
				foreach ($articles as $article) {
					if (!isset($nodeIdArticlesMap[$article->node_id])) {
						$nodeIdArticlesMap[$article->node_id] = [];
					}
					$nodeIdArticlesMap[$article->node_id][] = $article;
				}
				foreach ($items as $idx => $item) {
					if ($item->objectType === config('dict.SearchObjectType.TREE_NODE')) {
						$cntArticles = [];
						foreach ($nodeIdArticlesMap[$item->objectId] as $article) {
							array_push($cntArticles, [
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