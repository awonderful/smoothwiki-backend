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
            'id' => $attachmentId,
        ]);
    }

    public function download(Request $request) {
        $request->validate([
            'attachmentId' => ['required', 'integer', 'min:1'],
        ]);

        $attachmentId = $request->input('attachmentId');

        $service = new AttachmentService();
        $attachment = $service->getAttachmentById($attachmentId);

        return Storage::download($attachment->store_filename, $attachment->original_filename);
    }
}