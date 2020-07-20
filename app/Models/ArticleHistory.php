<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleHistory extends Model
{
    protected $table = 'article_history';
    protected $primaryKey = 'id';

    public $timestamps = false;
}
