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

class Search extends Model
{
	const CREATED_AT = 'ctime';
	const UPDATED_AT = 'mtime';

	protected $table = 'search';
	protected $primaryKey = 'id';

	protected $fillable = ['object_type', 'object_id', 'object_title', 'object_content', 'object_deleted'];

	public static function insertObject(int $spaceId, array $object): int {
		$search = new Search();
		$search->space_id       = $spaceId;
		$search->space_deleted  = 0;
		$search->object_type    = $object['type'];
		$search->object_id      = $object['id'];
		$search->object_title   = $object['title'];
		$search->object_content = $object['content'];
		$search->save();

		return $search->id;
	}

	public static function updateObject(int $spaceId, array $object): void {
		$update = [];

		if (isset($object['title'])) {
			$update['object_title'] = $object['title'];
		}
		if (isset($object['content'])) {
			$update['object_content'] = $object['content'];
		}
		if (isset($object['deleted'])) {
			$update['object_deleted'] = $object['deleted'];
		}

		static::where('space_id',      $spaceId)
				->where('object_type',    $object['type'])
				->where('object_id',      $object['id'])
				->update($update);
	}

	public static function setIsSpaceDeleted(int $spaceId, int $isSpaceDeleted): void {
		static::where('space_id', $spaceId)
				->update(['space_deleted' => $isSpaceDeleted]);
	}

	public static function setIsSpacePublic(int $spaceId, int $isSpacePublic): void {
		static::where('space_id', $spaceId)
				->update(['space_public' => $isSpacePublic]);
	}

	public static function searchInSpace(int $spaceId, string $keyword, int $whichPage, int $pageSize): array {
		$query1 = static::where('space_id', $spaceId)
					->where('object_type', '!=', config('dict.SearchObjectType.SPACE'))
					->where('object_deleted', 0)
					->whereRaw('MATCH (object_title, object_content) AGAINST (? IN NATURAL LANGUAGE MODE)', $keyword);
		$query2 = clone $query1;

		$count = $query1->count();
		$rows = $query2->select('*')
					->selectRaw('space_id, space_deleted, object_type, object_id, object_title, object_content, MATCH (object_title, object_content) AGAINST (? IN NATURAL LANGUAGE MODE) AS score', [$keyword])
					->orderBy('object_type', 'asc')
					->orderBy('score', 'desc')
					->offset(($whichPage - 1) * $pageSize)
					->limit($pageSize)
					->get();

		return [
			'count' => $count,
			'items' => $rows
		];
	}

	public static function searchInUser(int $uid, string $keyword, int $whichPage, int $pageSize): array {
		$query1 = static::whereIn('space_id', function ($q) use ($uid) {
						$q->select('space_member.space_id')
						  	->from('space_member')
							->leftJoin('space', 'space.id', '=', 'space_member.space_id')
							->where('space_member.uid', '=', $uid)
							->where('space_member.deleted', '=', 0)
							->where('space.deleted', '=', 0);
					})
					->where('object_deleted', 0)
					->whereRaw('MATCH (object_title, object_content) AGAINST (? IN NATURAL LANGUAGE MODE)', [$keyword]);
		$query2 = clone $query1;

		$count = $query1->count();
		$rows = $query2->select('*')
					->selectRaw('space_id, space_deleted, object_type, object_id, object_title, object_content, MATCH (object_title, object_content) AGAINST (? IN NATURAL LANGUAGE MODE) AS score', [$keyword])
					->orderBy('object_type', 'asc')
					->orderBy('score', 'desc')
					->offset(($whichPage - 1) * $pageSize)
					->limit($pageSize)
					->get();

		return [
			'count' => $count,
			'items' => $rows
		];
	}

	public static function searchInSite(int $uid, string $keyword, int $whichPage, int $pageSize): array {
		$query1 = static::
					where(function ($q) use ($uid) {
						$q->whereNotIn('space_id', function ($sq) {
							$sq->select('space_id')
								->from('space')
								->where('deleted', '=', 1)
								->orWhere('others_read', '=', 0);
						})
						->orWhereIn('space_id', function($sq) use ($uid) {
							$sq->select('space_member.space_id')
								->from('space_member')
								->leftJoin('space', 'space.id', '=', 'space_member.space_id')
								->where('space_member.uid', '=', $uid)
								->where('space_member.deleted', '=', 0)
								->where('space.deleted', '=', 0);
						});
					})
					->where('object_deleted', 0)
					->whereRaw('MATCH (object_title, object_content) AGAINST (? IN NATURAL LANGUAGE MODE)', $keyword);
		$query2 = clone $query1;

		$count = $query1->count();
		$rows = $query2->select('*')
					->selectRaw('space_id, space_deleted, object_type, object_id, object_title, object_content, MATCH (object_title, object_content) AGAINST (? IN NATURAL LANGUAGE MODE) AS score', [$keyword])
					->orderBy('object_type', 'asc')
					->orderBy('score', 'desc')
					->offset(($whichPage - 1) * $pageSize)
					->limit($pageSize)
					->get();

		return [
			'count' => $count,
			'items' => $rows
		];
	}
}