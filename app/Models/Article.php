<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use App\Libraries\Util;
use App\Exceptions\UnknownException;
use App\Exceptions\ArticleUpdatedException;
use App\Exceptions\PageUpdatedException;

class Article extends Model
{
    const CREATED_AT = 'ctime';
    const UPDATED_AT = 'mtime';

    protected $table = 'article';
    protected $primaryKey = 'id';

    protected $fillable = ['type', 'title', 'body', 'search', 'ext'];

    public static function getArticles(int $spaceId, int $nodeId, $fields = ['*']): Collection {
        return static::where('space_id', $spaceId)
                    ->where('node_id', $nodeId)
                    ->where('deleted', 0)
                    ->orderBy('pos', 'asc')
                    ->get($fields);
    }

    public static function getArticleById(int $spaceId, int $nodeId, int $articleId): Article {
        return static::where('space_id', $spaceId)
                    ->where('node_id', $nodeId)
                    ->where('id', $articleId)
                    ->where('deleted', 0);
    }

    public static function getMaxArticlePos(int $spaceId, int $nodeId): ?int {
        return static::where('space_id', $spaceId)
                    ->where('node_id', $nodeId)
                    ->where('deleted', 0)
                    ->max('pos');
    }

    public static function addArticle(int $spaceId, int $nodeId, array $article, int $author): Article {
            $newArticle = new Article();
            $newArticle->space_id = $spaceId;
            $newArticle->node_id  = $nodeId;
            $newArticle->author   = $author;
            $newArticle->pos      = 0;
            $newArticle->version  = Util::version();
            $newArticle->fill($article);
            $newArticle->save();

            return $newArticle;
    }

    public static function updateArticle(int $spaceId, int $nodeId, int $articleId, string $articleVersion, int $author, array $article): string {
        return DB::transaction(function() use ($spaceId, $nodeId, $articleId, $articleVersion, $author, $article) {
            $curArticle = static::where('space_id', $spaceId)
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
                throw new UnknownException();
            }

            $newVersion = Util::version();
            $affectedRows = static::where('space_id', $spaceId)
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

    public static function modifyArticlePoses(int $spaceId, int $nodeId, array $poses) {
        return DB::transaction(function() use ($spaceId, $nodeId, $poses) {
            foreach ($poses as $articleId => $pos) {
                $affectedRows = static::where('space_id', $spaceId)
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