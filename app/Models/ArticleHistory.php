<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class ArticleHistory extends Model
{
    protected $table = 'article_history';
    protected $primaryKey = 'id';

    public $timestamps = false;

    public static function getArticleHistoryVersions(int $articleId): Collection {
        return static::select('version', 'author', 'stime')
                     ->where('article_id', $articleId)
                     ->orderBy('id', 'desc')
                     ->get();
    }

    public static function getArticle(int $articleId, string $version): ArticleHistory {
        return static::where('article_id', $articleId)
                    ->where('version', $version)
                    ->first();
    }
}
