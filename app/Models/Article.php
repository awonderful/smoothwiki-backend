<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use App\Libraries\Util;
use App\Exceptions\UnfinishedSavingException;
use App\Exceptions\ArticleUpdatedException;
use App\Exceptions\PageUpdatedException;

class Article extends Model
{
    const CREATED_AT = 'ctime';
    const UPDATED_AT = 'mtime';

    protected $table = 'article';
    protected $primaryKey = 'id';

    protected $fillable = ['type', 'title', 'body', 'search', 'ext'];

    public static function getArticles(int $spaceId, int $treeId, int $nodeId, $fields = ['*']): Collection {
        return static::where('space_id', $spaceId)
                    ->where('tree_id', $treeId)
                    ->where('node_id', $nodeId)
                    ->where('deleted', 0)
                    ->orderBy('pos', 'asc')
                    ->get($fields);
    }

    public static function getArticleById(int $spaceId, int $treeId, int $nodeId, int $articleId): Article {
        return static::where('space_id', $spaceId)
                    ->where('tree_id', $treeId)
                    ->where('node_id', $nodeId)
                    ->where('id', $articleId)
                    ->where('deleted', 0);
    }

    public static function getMaxArticlePos(int $spaceId, int $treeId, int $nodeId): ?int {
        return static::where('space_id', $spaceId)
                    ->where('tree_id', $treeId)
                    ->where('node_id', $nodeId)
                    ->where('deleted', 0)
                    ->max('pos');
    }

    /**
     * update an article, after making a backup in the table 'article_history', and then regenerate a new version.
     * @param int $spaceId
     * @param int $treeId
     * @param int $nodeId
     * @param int $author
     * @param array $article
     *  [
     *      'title'  => ...,
     *      'body'   => ...,
     *      'search' => ...
     *  ]
     * @return Article
     * @throws UnfinishedSavingException
     */
    public static function addArticle(int $spaceId, int $treeId, int $nodeId, array $article, int $author): Article {
        $newArticle = new Article();
        $newArticle->space_id = $spaceId;
        $newArticle->tree_id  = $treeId;
        $newArticle->node_id  = $nodeId;
        $newArticle->author   = $author;
        $newArticle->pos      = 0;
        $newArticle->version  = Util::version();
        $newArticle->fill($article);
        
        $succ = $newArticle->save();
        if (!$succ) {
            throw new UnfinishedSavingException;
        }

        return $newArticle;
    }

    /**
     * after copying the article into the table 'article_history', update it, and then generate a new version.
     * @param int $spaceId
     * @param int $treeId
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
     * @throws ArticleUpdatedException, UnfinishedSavingException
     */
    public static function updateArticle(int $spaceId, int $treeId, int $nodeId, int $articleId, string $articleVersion, int $author, array $article): string {
        return DB::transaction(function() use ($spaceId, $treeId, $nodeId, $articleId, $articleVersion, $author, $article) {
            $curArticle = static::where('space_id', $spaceId)
                ->where('tree_id', $treeId)
                ->where('node_id', $nodeId)
                ->where('id', $articleId)
                ->where('version', $articleVersion)
                ->where('deleted', 0)
                ->first();
            if (empty($curArticle)) {
                throw new ArticleUpdatedException();
            }

            $articleHistory = new ArticleHistory();
            $articleHistory->article_id = $curArticle->id;
            $articleHistory->title      = $curArticle->title;
            $articleHistory->body       = $curArticle->body;
            $articleHistory->ext        = $curArticle->ext;
            $articleHistory->version    = $curArticle->version;
            $articleHistory->author     = $curArticle->author;
            $articleHistory->ctime      = $curArticle->ctime;
            $succ = $articleHistory->save();
            if (!$succ) {
                throw new UnfinishedSavingException();
            }

            $newVersion = Util::version();
            $affectedRows = static::where('space_id', $spaceId)
            ->where('tree_id', $treeId)
            ->where('node_id', $nodeId)
            ->where('id', $articleId)
            ->where('version', $articleVersion)
            ->where('deleted', 0)
            ->update([
                'title'   => $article['title'],
                'body'    => $article['body'],
                'search'  => $article['search'],
                'author'  => $author,
                'version' => $newVersion,
            ]);
            if ($affectedRows !== 1) {
                throw new ArticleUpdatedException();
            }

            return $newVersion;
        });
    }

    /**
     * modify articles' positions.
     * @param int $spaceId
     * @param int $treeId
     * @param int $nodeId
     * @param array $poses
     *  [
     *      id1 => pos1,
     *      id2 => pos2,
     *      ...
     *  ]
     * @return void
     */
    public static function modifyArticlePoses(int $spaceId, int $treeId, int $nodeId, array $poses): void {
        DB::transaction(function() use ($spaceId, $treeId, $nodeId, $poses) {
            foreach ($poses as $articleId => $pos) {
                $affectedRows = static::where('space_id', $spaceId)
                    ->where('tree_id', $treeId)
                    ->where('node_id', $nodeId)
                    ->where('id', $articleId)
                    ->where('deleted', 0)
                    ->update([
                        'pos' => $pos
                    ]);

                if ($affectedRows != 1) {
                    throw new PageUpdatedException();
                }
            }
        });
    }
}