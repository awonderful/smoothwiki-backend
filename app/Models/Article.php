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
use App\Models\Search;

class Article extends Model
{
    const CREATED_AT = 'ctime';
    const UPDATED_AT = 'mtime';

    protected $table = 'article';
    protected $primaryKey = 'id';

    protected $fillable = ['type', 'title', 'body', 'search', 'ext'];

    public static function getArticles(int $spaceId, int $nodeId, $fields = ['*']): Collection {
        return static::where('space_id', $spaceId)
                    ->where('node_id',   $nodeId)
                    ->where('deleted',   0)
                    ->orderBy('pos', 'asc')
                    ->get($fields);
    }

    public static function getArticleById(int $spaceId, int $nodeId, int $articleId): Article {
        return static::where('space_id', $spaceId)
                    ->where('node_id',   $nodeId)
                    ->where('id',        $articleId)
                    ->where('deleted',   0)
                    ->first();
    }

    public static function getTrashArticleById(int $spaceId, int $nodeId, int $articleId): Article {
        return static::where('space_id', $spaceId)
                    ->where('node_id',   $nodeId)
                    ->where('id',        $articleId)
                    ->where('deleted',   1)
                    ->first();
    }


    public static function getMaxArticlePos(int $spaceId, int $nodeId): ?int {
        return static::where('space_id', $spaceId)
                    ->where('node_id',   $nodeId)
                    ->where('deleted',   0)
                    ->max('pos');
    }

    /**
     * update an article, after making a backup in the table 'article_history', and then regenerate a new version.
     * @param int $spaceId
     * @param int $nodeId
     * @param int $author
     * @param array $article
     *  [
     *      'title'  => ...,
     *      'body'   => ...,
     *      'search' => ...
     *  ]
     * @return Article
     * @throws UnfinishedDBOperationException
     */
    public static function addArticle(int $spaceId, int $nodeId, array $article, int $author): Article {
        return DB::transaction(function() use ($spaceId, $nodeId, $article, $author) {
            $pos = Article::where('node_id', $nodeId)->max('pos');
            if (!is_numeric($pos)) {
                $pos = 1000;
            } else {
                $pos += 1000;
            }

            $newArticle = new Article();
            $newArticle->space_id = $spaceId;
            $newArticle->node_id  = $nodeId;
            $newArticle->author   = $author;
            $newArticle->pos      = $pos;
            $newArticle->stime    = DB::raw('NOW()');
            $newArticle->version  = Util::version();
            $newArticle->fill($article);
            
            $succ = $newArticle->save();
            if (!$succ) {
                throw new UnfinishedDBOperationException;
            }

            Search::insertObject($spaceId, [
                'type'    => config('dict.SearchObjectType.ARTICLE'),
                'id'      => $newArticle->id,
                'title'   => $article['title'],
                'content' => $article['search']
            ]);

            return $newArticle;
        });
    }


    /**
     * remove an article 
     * @param int $spaceId
     * @param int $nodeId
     * @param int $operator
     * @return Article
     * @throws ArticleNotExistException, ArticleUpdatedException, UnfinishedDBOperationException
     */
    public static function removeArticle(int $spaceId, int $nodeId, int $articleId, string $articleVersion, int $operator): void {
        DB::transaction(function() use ($spaceId, $nodeId, $articleId, $articleVersion, $operator) {
            $article = static::where('space_id', $spaceId)
                ->where('node_id',  $nodeId)
                ->where('id',       $articleId)
                ->first();
            if (empty($article)) {
                throw new ArticleNotExistException();
            }
            if ($article->deleted === 0 && $article->version !== $articleVersion) {
                throw new ArticleUpdatedException();
            }
            if ($article->deleted === 1) {
                return;
            }

            $affectedRows = static::where('space_id', $spaceId)
                ->where('node_id',  $nodeId)
                ->where('id',       $articleId)
                ->where('version',  $articleVersion)
                ->where('deleted',  0)
                ->update([
                    'deleted' => 1,
                ]);
            if ($affectedRows !== 1) {
                throw new UnfinishedDBOperationException();
            }

            Search::updateObject($spaceId, [
                'type'    => config('dict.SearchObjectType.ARTICLE'),
                'id'      => $articleId,
                'deleted' => 1
            ]);
        });
    }

    /**
     * after copying the article into the table 'article_history', update it, and then generate a new version.
     * @param int $spaceId
     * @param int $nodeId
     * @param int $articleId
     * @param int $articleVersion
     * @param int $author
     * @param array $article
     *  [
     *      'title'  => ...,
     *      'body'   => ...,
     *      'search' => ...
     *  ]
     * @return string the new version of the article
     * @throws ArticleNotExistException, ArticleRemovedException, ArticleUpdatedException, UnfinishedDBOperationException
     */
    public static function updateArticle(int $spaceId, int $nodeId, int $articleId, string $articleVersion, int $author, array $article): string {
        return DB::transaction(function() use ($spaceId, $nodeId, $articleId, $articleVersion, $author, $article) {
            $curArticle = static::where('space_id', $spaceId)
                ->where('node_id',  $nodeId)
                ->where('id',       $articleId)
                ->first();
            if (empty($curArticle)) {
                throw new ArticleNotExistException();
            }
            if ($curArticle->deleted === 1) {
                throw new ArticleRemovedException();
            }
            if ($curArticle->deleted === 0 && $curArticle->version !== $articleVersion) {
                throw new ArticleUpdatedException();
            }

            $articleHistory = new ArticleHistory();
            $articleHistory->article_id = $curArticle->id;
            $articleHistory->title      = $curArticle->title;
            $articleHistory->body       = $curArticle->body;
            $articleHistory->search     = $curArticle->search;
            $articleHistory->ext        = $curArticle->ext;
            $articleHistory->version    = $curArticle->version;
            $articleHistory->author     = $curArticle->author;
            $articleHistory->stime      = $curArticle->stime;
            $succ = $articleHistory->save();
            if (!$succ) {
                throw new UnfinishedDBOperationException();
            }

            $newVersion = Util::version();
            $affectedRows = static::where('space_id', $spaceId)
            ->where('node_id',  $nodeId)
            ->where('id',       $articleId)
            ->where('version',  $articleVersion)
            ->where('deleted',  0)
            ->update([
                'title'   => $article['title'],
                'body'    => $article['body'],
                'search'  => $article['search'],
                'author'  => $author,
                'stime'   => DB::raw('NOW()'),
                'version' => $newVersion,
            ]);
            if ($affectedRows !== 1) {
                throw new UnfinishedDBOperationException();
            }

            Search::updateObject($spaceId, [
                'type'    => config('dict.SearchObjectType.ARTICLE'),
                'id'      => $articleId,
                'title'   => $article['title'],
                'content' => $article['search']
            ]);

            return $newVersion;
        });
    }

    /**
     * modify articles' positions.
     * @param int $spaceId
     * @param int $nodeId
     * @param array $poses
     *  [
     *      id1 => pos1,
     *      id2 => pos2,
     *      ...
     *  ]
     * @return void
     */
    public static function modifyArticlePoses(int $spaceId, int $nodeId, array $poses): void {
        DB::transaction(function() use ($spaceId, $nodeId, $poses) {
            foreach ($poses as $articleId => $pos) {
                $affectedRows = static::where('space_id', $spaceId)
                    ->where('node_id',  $nodeId)
                    ->where('id',       $articleId)
                    ->where('deleted',  0)
                    ->update([
                        'pos' => $pos
                    ]);

                if ($affectedRows != 1) {
                    throw new PageUpdatedException();
                }
            }
        });
    }

    public static function setArticleLevel(int $spaceId, int $nodeId, int $articleId, int $level): void {
        static::where('space_id', $spaceId)
                ->where('node_id', $nodeId)
                ->where('id', $articleId)
                ->where('deleted', 0)
                ->update([
                    'level' => $level
                ]);
    }

    public static function moveArticleToAnotherNode(int $spaceId, int $nodeId, int $articleId, int $toNodeId): void {
        DB::transaction(function() use ($spaceId, $nodeId, $articleId, $toNodeId) {
            $maxPos = static::where('space_id', $spaceId)
                    ->where('node_id', $nodeId)
                    ->max('pos');
            if (!is_numeric($maxPos)) {
                $maxPos = 0;
            }
            static::where('space_id', $spaceId)
                    ->where('node_id', $nodeId)
                    ->where('id', $articleId)
                    ->where('deleted', 0)
                    ->update([
                        'node_id' => $toNodeId,
                        'pos'     => $maxPos + 1000
                    ]);
            Attachment::where('space_id', $spaceId)
                    ->where('node_id', $nodeId)
                    ->where('article_id', $articleId)
                    ->update([
                        'node_id' => $toNodeId
                    ]);
        });
    }

    public static function getTrashArticles(int $spaceId, int $nodeId, $fields = ['*']): Collection {
        return static::where('space_id', $spaceId)
                    ->where('node_id',   $nodeId)
                    ->where('deleted',   1)
                    ->orderBy('mtime', 'desc')
                    ->get($fields);
    }

    public static function getArticlesFromMultiplePages(array $nodeIds, $fields = ['*']): Collection {
        return static::whereIn('node_id', $nodeIds)
                    ->where('deleted', 0)
                    ->orderBy('pos', 'asc')
                    ->get($fields);
    }

    public static function getArticlesByIds(array $articleIds): Collection {
        return static::whereIn('id', $articleIds)
                    ->get(['*']);
    }
}