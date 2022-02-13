<?php

namespace App\Services;

use App\Models\Attachment;
use App\Exceptions\AttachmentNotExistException;
use App\Exceptions\InvalidImageException;
use App\Services\PermissionChecker;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class AttachmentService {
    public function addAttachment(int $spaceId, int $nodeId, int $articleId, UploadedFile $requestFile): int {
        PermissionChecker::writeSpace($spaceId);

        $uploader = Auth::id();

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
        $attachment = Attachment::getAttachmentById($attachmentId);

        if ($attachment === null) {
            throw new AttachmentNotExistException();
        }

        PermissionChecker::readSpace($attachment->space_id);

        return $attachment;
    }

    public function getArticleAttachments(int $spaceId, int $nodeId, int $articleId, array $fields = ['*']): Collection {
        PermissionChecker::readSpace($spaceId);

        return Attachment::getArticleAttachments($spaceId, $nodeId, $articleId, $fields);
    }

    public function getAttachmentsByIds(int $spaceId, array $attachmentIds, array $fields = ['*']): Collection {
        PermissionChecker::readSpace($spaceId);

        return Attachment::getAttachmentsByIds($spaceId, $attachmentIds, $fields);
    }

    //--------------------------upload large file--------------------------------
    public function initUploadInChunks(int $spaceId, int $nodeId, int $articleId, string $filename, int $size): array {
        PermissionChecker::writeSpace($spaceId);

        $uploader = Auth::id();

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
        PermissionChecker::writeSpace($spaceId);

        $uploader = Auth::id();

        $attachment = $this->getAttachmentById($attachmentId);
        if ($attachment->space_id !== $spaceId || $attachment->node_id !== $nodeId || $attachment->article_id !== $articleId || $attachment->uploader !== $uploader) {
            throw new AttachmentNotExistException();
        }

        Storage::append($attachment->store_filename, $requestFile->get(), null);
        Attachment::updateAttachmentChunkState($spaceId, $nodeId, $articleId, $attachmentId, [
            'chunk_progress' => $attachment->chunk_progress + 1
        ]);
    }

    public function finishUploadInChunks(int $spaceId, int $nodeId, int $articleId, int $attachmentId): void {
        PermissionChecker::writeSpace($spaceId);

        $uploader = Auth::id();

        $attachment = $this->getAttachmentById($attachmentId);
        if ($attachment->space_id !== $spaceId || $attachment->node_id !== $nodeId || $attachment->article_id !== $articleId || $attachment->uploader !== $uploader) {
            throw new AttachmentNotExistException();
        }

        Attachment::updateAttachmentChunkState($spaceId, $nodeId, $articleId, $attachmentId, [
            'chunk_finished' => 1
        ]);
    }

    public function generateThumbnail(int $attachmentId, int $maxWidth, int $maxHeight) {
        $attachment = $this->getAttachmentById($attachmentId);
        if (!in_array($attachment->extension, config('dict.ImageExts'))) {
            throw new InvalidImageException();
        }

        $imgCnt = Storage::get($attachment->store_filename);
        return Image::make($imgCnt)->resize($maxWidth, $maxHeight, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
    }
}