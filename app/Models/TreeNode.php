<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreeNode extends Model
{
    const CREATED_AT = 'ctime';
    const UPDATED_AT = 'mtime';

    protected $table = 'tree_node';
    protected $primaryKey = 'id';
}
