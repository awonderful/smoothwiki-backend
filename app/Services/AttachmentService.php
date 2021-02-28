<?php

namespace App\Services;

use App\Models\Attachment;
use App\Exceptions\AttachmentNotExistException;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class AttachmentService {
    public function addAttachment(int $spaceId, int $nodeId, int $articleId, UploadedFile $requestFile): int {
        $uploader = 0;

        $path = "$spaceId/$nodeId";
        $newFilename = microtime(true).'_'.$uploader;
        $requestFile->storeAs($path, $newFilename);

        return Attachment::addAttachment($spaceId, $nodeId, $articleId, $uploader, [
          'original_filename' => $requestFile->getClientOriginalName(),
          'size'              => $requestFile->getSize(),
          'extension'         => $requestFile->extension(),
          'store_filename'    => "$path/$newFilename"
        ]);
    }

    public function getAttachmentById(int $attachmentId): Object {
        return Attachment::getAttachmentById($attachmentId);
    }

    public function getAttachments(int $spaceId, int $nodeId, int $articleId, array $fields = ['*']): Collection {
        return Attachment::getAttachments($spaceId, $nodeId, $articleId, $fields);
    }

    //--------------------------upload large file--------------------------------
    public function initUploadInChunks(int $spaceId, int $nodeId, int $articleId, string $filename, int $size): array {
        $uploader = 0;

        $path = "$spaceId/$nodeId";
        $newFilename = microtime(true).'_'.$uploader;
        $chunkSize = floor(1024 * 1024 * 0.9);
        $chunkCount = floor(($size + $chunkSize - 1) / $chunkSize);
        Storage::put("$path/$newFilename", '');

        $file = [
            'original_filename' => $filename,
            'size'              => $size,
            'extension'         => substr(strrchr($filename, '.'), 1),
            'store_filename'     => "$path/$newFilename"
        ];

        $chunk = [
            'chunk_upload'   => 1,
            'chunk_size'     => $chunkSize,
            'chunk_count'    => $chunkCount,
            'chunk_progress' => 0,
            'chunk_finished' => 0
        ];

        $attachmentId = Attachment::addAttachment($spaceId, $nodeId, $articleId, $uploader, $file, $chunk);

        return [
            'attachmentId' => $attachmentId,
            'chunkSize'    => $chunkSize,
            'chunkCount'   => $chunkCount
        ];
    }

    public function uploadInChunks(int $spaceId, int $nodeId, int $articleId, int $attachmentId, UploadedFile $requestFile): void {
        $uploader = 0;

        $attachment = $this->getAttachmentById($attachmentId);
        if ($attachment->space_id !== $spaceId || $attachment->node_id !== $nodeId || $attachment->article_id !== $articleId) {
            throw new AttachmentNotExistException();
        }

        Storage::append($attachment->store_filename, $requestFile->get(), null);
        Attachment::updateAttachmentChunkState($spaceId, $nodeId, $articleId, $attachmentId, [
            'chunk_progress' => $attachment->chunk_progress + 1
        ]);
    }

    public function finishUploadInChunks(int $spaceId, int $nodeId, int $articleId, int $attachmentId): void {
        $uploader = 0;

        $attachment = $this->getAttachmentById($attachmentId);
        if ($attachment->space_id !== $spaceId || $attachment->node_id !== $nodeId || $attachment->article_id !== $articleId) {
            throw new AttachmentNotExistException();
        }

        Attachment::updateAttachmentChunkState($spaceId, $nodeId, $articleId, $attachmentId, [
            'chunk_finished' => 1
        ]);
    }


}