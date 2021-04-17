<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Libraries\Result;
use Illuminate\Http\Request;

class UserController extends Controller {
    public function getViewerInfo(Request $request) {
        return Result::data([
            'user' => $request->user(),
        ]);
    }
}