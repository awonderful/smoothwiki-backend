<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    Route::get('tree/node/remove', 'TreeController@removeNode');
    Route::get('tree/node/move',   'TreeController@moveNode');
    Route::get('tree/version',     'TreeController@getTreeVersion');
    Route::get('tree/trash/get',   'TreeController@getTrashTree');

    Route::post('article-page/article/add',    'ArticlePageController@addArticle');
    Route::post('article-page/article/update', 'ArticlePageController@updateArticle');
    Route::get ('article-page/article/move',   'ArticlePageController@moveArticle');
    Route::get ('article-page/article/remove', 'ArticlePageController@removeArticle');
    Route::get ('article-page/article/get',    'ArticlePageController@getArticle');
    Route::get ('article-page/get',            'ArticlePageController@getPage');
    Route::get ('article-page/version',        'ArticlePageController@getPageVersion');

    Route::post('attachment/upload',       'AttachmentController@upload');
    Route::post('attachment/upload/chunk', 'AttachmentController@uploadInChunks');
    Route::get ('attachment/download',     'AttachmentController@download');
    Route::get ('attachment/list',         'AttachmentController@getAttachments');

    Route::get('space/create',      'SpaceController@createSpace');
    Route::get('space/menu/create', 'SpaceController@createMenu');
    Route::get('space/menu/remove', 'SpaceController@removeMenu');
    Route::get('space/menu/rename', 'SpaceController@renameMenu');
});
