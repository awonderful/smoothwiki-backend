<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleHistory extends Model
{
    protected $table = 'article_history';
    protected $primaryKey = ['article_id', 'version'];
    protected $incrementing = false;

    public $timestamps = false;
}
