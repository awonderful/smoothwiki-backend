<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    const CREATED_AT = 'ctime';
    const UPDATED_AT = 'mtime';

    protected $table = 'attachment';
    protected $primaryKey = 'id';

    public static function addAttachment(int $spaceId, int $nodeId, int $uploader, array $file):int {
        $attachment = new Attachment();
        $attachment->space_id          = $spaceId;
        $attachment->node_id           = $nodeId;
        $attachment->original_filename = $file['originalFilename'];
        $attachment->store_filename    = $file['storeFilename'];
        $attachment->extension         = $file['extension'];
        $attachment->size              = $file['size'];
        $attachment->uploader          = $uploader;

        $attachment->save();
        return $attachment->id;
    }

    public static function getAttachmentById(int $attachmentId): Object {
        return static::where('id', $attachmentId)
            ->first();
    }
}
