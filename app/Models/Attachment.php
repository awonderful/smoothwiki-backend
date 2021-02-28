<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class Attachment extends Model
{
    const CREATED_AT = 'ctime';
    const UPDATED_AT = 'mtime';

    protected $table = 'attachment';
    protected $primaryKey = 'id';

    protected $fillable = ['store_filename', 'original_filename', 'size', 'extension', 'chunk_count', 'chunk_size', 'chunk_progress', 'chunk_finished'];

    public static function addAttachment(int $spaceId, int $nodeId, int $articleId, int $uploader, array $file, array $chunk = []):int {
        $attachment = new Attachment();
        $attachment->space_id          = $spaceId;
        $attachment->node_id           = $nodeId;
        $attachment->article_id        = $articleId;
        $attachment->uploader          = $uploader;
        $attachment->fill($file);
        $attachment->fill($chunk);

        $attachment->save();
        return $attachment->id;
    }

    public static function updateAttachmentChunkState(int $spaceId, int $nodeId, int $articleId, int $attachmentId, array $chunk): int {
        return static::where('id', $attachmentId)
            ->where('space_id', $spaceId)
            ->where('node_id', $nodeId)
            ->where('article_id', $articleId)
            ->update($chunk);
    }

    public static function getAttachmentById(int $attachmentId): Object {
        return static::where('id', $attachmentId)
            ->first();
    }

    public static function attachToArticle(int $spaceId, int $nodeId, int $articleId, array $attachmentIds): void {
        static::where('space_id', $spaceId)
            ->where('node_id', $nodeId)
            ->where('article_id', 0)
            ->whereIn('id', $attachmentIds)
            ->update(['article_id' => $articleId]);
    }

    public static function getAttachments(int $spaceId, int $nodeId, int $articleId, array $fields = ['*']): Collection {
        return static::where('space_id', $spaceId)
            ->where('node_id', $nodeId)
            ->where('article_id', $articleId)
            ->where('deleted', 0)
            ->select(...$fields)
            ->get();
    }
}
