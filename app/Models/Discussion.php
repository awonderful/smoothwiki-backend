<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use App\Libraries\Util;
use App\Exceptions\UnknownException;
use App\Exceptions\ArticleUpdatedException;

class Discussion extends Model
{
    const CREATED_AT = 'ctime';
    const UPDATED_AT = 'mtime';

    protected $table = 'article';
    protected $primaryKey = 'id';

    protected $fillable = ['type', 'title', 'body', 'search', 'ext'];

    public static function getArticlesWithPageVersion(int $spaceId, int $nodeId): array {
        return DB::transaction(function() use ($spaceId, $nodeId) {
            $articles = static::where('space_id', $spaceId)
                        ->where('node_id', $nodeId)
                        ->where('deleted', 0)
                        ->orderBy('pos', 'asc');
            $node = TreeNode::getNodeById($nodeId);
            return [
                'articles' => $articles,
                'version'  => $node['version'],
            ];
        });
    }

    public static function getArticles(int $spaceId, int $nodeId): Collection {
        return static::where('space_id', $spaceId)
                    ->where('node_id', $nodeId)
                    ->where('deleted', 0)
                    ->orderBy('pos', 'asc');
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

    public static function addArticle(int $spaceId, int $nodeId, int $author, array $article): Article {
        return DB::transaction(function() use ($spaceId, $nodeId, $author, $article) {
            $newArticle = new Article();
            $newArticle->space_id = $spaceId;
            $newArticle->node_id = $nodeId;
            $newArticle->author = $author;
            $newArticle->version = Util::version();
            $newArticle->fill($article);
            $newArticle->save();

            $nodeVersion = TreeNode::regenerateNodeVersion($spaceId, config('dict.TreeNodeCategory.MAIN'), $nodeId);

            return [
                'articleId'      => $newArticle->id,
                'articleVersion' => $newArticle->version,
                'nodeVersion'    => $nodeVersion,
            ];
        });
    }

    public static function appendArticleRegardOfIntegraty(int $spaceId, int $nodeId, string $nodeVersion, int $author, array $article): Article {
         return DB::transaction(function() use ($spaceId, $nodeId, $author, $article) {
            $newArticle = new Article();
            $newArticle->space_id = $spaceId;
            $newArticle->node_id = $nodeId;
            $newArticle->author = $author;
            $newArticle->version = Util::version();
            $newArticle->fill($article);
            $newArticle->save();

            $newNodeVersion = Util::version();
            $affectedRows = TreeNode::where('space_id', $spaceId)
                    ->where('category', config('dict.TreeNodeCategory.MAIN'))
                    ->where('id', $nodeId)
                    ->where('version', $nodeVersion)
                    ->where('deleted', 0)
                    ->update([
                        'version' => $newNodeVersion
                    ]);

            if ($affectedRows != 1) {
                throw new PageUpdatedException();
            }

            return [
                'articleId'      => $newArticle->id,
                'articleVersion' => $newArticle->version,
                'nodeVersion'    => $newNodeVersion,
            ];
        });       
    }

    public static function updateArticle(int $spaceId, int $category, int $nodeId, int $articleId, string $articleVersion, int $author, array $article): string {
        return DB::transaction(function() use ($spaceId, $category, $nodeId, $articleId, $articleVersion, $author, $article) {
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
            $articleHistory->node_id = $curArticle->node_id;
            $articleHistory->title   = $curArticle->title;
            $articleHistory->body    = $curArticle->body;
            $articleHistory->ext     = $curArticle->ext;
            $articleHistory->version = $curArticle->version;
            $articleHistory->author  = $curArticle->author;
            $articleHistory->ctime   = $curArticle->ctime;
            $articleHistory->save();

            $curArticle->fill($article);
            $curArticle->author = $author;
            $curArticle->version = Util::version();
            $curArticle->save();

            $newNodeVersion = TreeNode::regenerateNodeVersion($spaceId, $category, $nodeId);
  
            return [
                'articleVersion' => $curArticle->version,
                'nodeVersion'    => $newNodeVersion,
            ];
        });
    }

    public static function modifyArticlePoses(int $spaceId, int $category, int $nodeId, int $poses) {
        return DB::transaction(function() use ($spaceId, $nodeId, $poses) {
            foreach ($poses as $articleId => $pos) {
                $affectedRows = static::where('space_id', $spaceId)
                    ->where('node_id', $node_id)
                    ->where('id', $articleId)
                    ->where('is_deleted', 0)
                    ->update([
                        'pos' => $pos
                    ]);

                if ($affectedRows != 1) {
                    throw new UnknownException();
                }
            }

            return TreeNode::regenerateNodeVersion($spaceId, $category, $nodeId);
        });
    }
}