<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AttachmentService;
use App\Libraries\Result;
use App\Libraries\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller {
    public function upload(Request $request) {
        $request->validate([
            'spaceId'   => ['required', 'integer', 'min:1'],
            'nodeId'    => ['required', 'integer', 'min:1'],
            'articleId' => ['required', 'integer', 'min:0'],
            'file'      => ['required', 'file']
        ]);

        $spaceId   = $request->input('spaceId');
        $nodeId    = $request->input('nodeId');
        $articleId = $request->input('articleId');
        $file      = $request->file('file');

        $service = new AttachmentService();
        $attachmentId = $service->addAttachment($spaceId, $nodeId, $articleId, $file);
        return Result::data([
            'id'  => $attachmentId,
            'url' => config('app.url').'/api/attachment/download?attachmentId='.$attachmentId
        ]);
    }

    public function uploadInChunks(Request $request) {
            $request->validate([
                'spaceId'    => ['required', 'integer', 'min:1'],
                'nodeId'     => ['required', 'integer', 'min:1'],
                'articleId'  => ['required', 'integer', 'min:0'],
                'phase'      => ['required', 'string',  'in:start,upload,finish'],
            ]);

            $spaceId   = $request->input('spaceId');
            $nodeId    = $request->input('nodeId');
            $articleId = $request->input('articleId');
            $phase     = $request->input('phase');


            if ($phase === 'start') {
                $request->validate([
                    'size' => ['required', 'integer', 'min:1'],
                    'name' => ['required', 'string',  'max:200'],
                ]);

                $name = $request->input('name');
                $size = $request->input('size');
                $service = new AttachmentService();
                $res = $service->initUploadInChunks($spaceId, $nodeId, $articleId, $name, $size);
                return Result::data([
                    'chunk_size' => $res['chunkSize'],
                    'session_id' => $res['attachmentId']
                ]);

            } elseif ($phase === 'upload') {
                $request->validate([
                    'start_offset' => ['required', 'integer', 'min:0'],
                    'session_id'   => ['required', 'integer', 'min:1'],
                    'chunk'        => ['required', 'file']
                ]);
              
                $attachmentId = $request->input('session_id');
                $chunk = $request->file('chunk');
                $service = new AttachmentService();
                $service->uploadInChunks($spaceId, $nodeId, $articleId, $attachmentId, $chunk);
                return Result::succ();

            } elseif ($phase === 'finish') {
                $request->validate([
                    'session_id'   => ['required', 'integer', 'min:1'],
                ]);
 
                $attachmentId = $request->input('session_id');
                $service = new AttachmentService();
                $service->finishUploadInChunks($spaceId, $nodeId, $articleId, $attachmentId);
                return Result::succ();
            }
    }

    public function download(Request $request) {
        $request->validate([
            'attachmentId'  => ['required', 'integer', 'min:1'],
            'forceDownload' => [            'integer', 'in:0,1']
        ]);

        $attachmentId  = $request->input('attachmentId');
        $forceDownload = $request->boolean('forceDownload');

        $service = new AttachmentService();
        $attachment = $service->getAttachmentById($attachmentId);
        $headers = [
            'Content-Type' => Util::getMineType($attachment->extension)
        ];
        if (!$forceDownload) {
            $headers['Content-Disposition'] = 'inline';
        }

        return Storage::download($attachment->store_filename, $attachment->original_filename, $headers);
    }

    public function getAttachments(Request $request) {
         $request->validate([
            'spaceId'   => ['required', 'integer', 'min:1'],
            'nodeId'    => ['required', 'integer', 'min:1'],
            'articleId' => ['required', 'integer', 'min:0'],
        ]);

        $spaceId   = $request->input('spaceId');
        $nodeId    = $request->input('nodeId');
        $articleId = $request->input('articleId');

        $service = new AttachmentService();
        $attachments = $service->getAttachments($spaceId, $nodeId, $articleId, ['id', 'original_filename', 'extension', 'size', 'uploader', 'ctime', 'mtime']);

        return Result::data([
            'attachments'  => $attachments->toArray()
        ]);
    }
}