<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Libraries\Result;
use App\Services\SystemService;
use Illuminate\Http\Request;

class SystemController extends Controller {
    public function info(Request $request) {
        $service = new SystemService();
        $data = $service->info();
        return Result::data($data);
    }
}
