<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Http\UploadedFile;

class AttachmentService {
    public function addAttachment(int $spaceId, int $nodeId, int $articleId, UploadedFile $requestFile): int {
        $uploader = 0;

        $path = "$spaceId/$nodeId";
        $newFilename = microtime(true);
        $requestFile->storeAs($path, $newFilename);

        return Attachment::addAttachment($spaceId, $nodeId, $uploader, [
          'originalFilename' => $requestFile->getClientOriginalName(),
          'size'             => $requestFile->getSize(),
          'extension'        => $requestFile->extension(),
          'storeFilename'    => "$path/$newFilename"
        ]);
    }

    public function getAttachmentById(int $attachmentId): Object {
        return Attachment::getAttachmentById($attachmentId);
    }
}