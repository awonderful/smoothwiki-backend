<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TreeController;
use App\Http\Controllers\ArticlePageController;
use App\Http\Controllers\SpaceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware([])->group(function () {
    Route::get('tree/get', 'TreeController@getTree');
    Route::get('tree/node/append', 'TreeController@appendChildNode');
    Route::get('tree/node/rename', 'TreeController@renameNode');
    Route::get('tree/node/move',   'TreeController@moveNode');
    Route::get('tree/version',     'TreeController@getTreeVersion');

    Route::post('article-page/article/add',    'ArticlePageController@addArticle');
    Route::post('article-page/article/update', 'ArticlePageController@updateArticle');
    Route::get ('article-page/article/move',   'ArticlePageController@moveArticle');
    Route::get ('article-page/get',            'ArticlePageController@getPage');
    Route::get ('article-page/version',        'ArticlePageController@getPageVersion');

    Route::post('space/create', 'SpaceController@createSpace');
});
