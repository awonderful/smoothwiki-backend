<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::get('/api/system/info', 'SystemController@info');

Route::prefix('api')->middleware(['auth:web'])->group(function () {
    Route::get('user/info',       'UserController@getViewerInfo');
    
    Route::get('tree/get',          'TreeController@getTree');
    Route::get('tree/node/append',  'TreeController@appendChildNode');
    Route::get('tree/node/rename',  'TreeController@renameNode');
    Route::get('tree/node/remove',  'TreeController@removeNode');
    Route::get('tree/node/restore', 'TreeController@restoreNode');
    Route::get('tree/node/move',    'TreeController@moveNode');
    Route::get('tree/version',      'TreeController@getTreeVersion');
    Route::get('tree/trash/get',    'TreeController@getTrashTree');

    Route::post('article-page/article/add',         'ArticlePageController@addArticle');
    Route::post('article-page/article/update',      'ArticlePageController@updateArticle');
    Route::get ('article-page/article/move',        'ArticlePageController@moveArticle');
    Route::get ('article-page/article/transfer',    'ArticlePageController@moveArticleToAnotherNode');
    Route::get ('article-page/article/remove',      'ArticlePageController@removeArticle');
    Route::get ('article-page/article/get',         'ArticlePageController@getArticle');
    Route::get ('article-page/article/level',       'ArticlePageController@setArticleLevel');
    Route::get ('article-page/article/versions',    'ArticlePageController@getArticleHistoryVersions');
    Route::get ('article-page/article/history/get', 'ArticlePageController@getHistoryArticle');
    Route::get ('article-page/get',                 'ArticlePageController@getPage');
    Route::get ('article-page/version',             'ArticlePageController@getPageVersion');
    Route::get ('article-page/trash/get',           'ArticlePageController@getPageTrashArticles');

    Route::post('attachment/upload',       'AttachmentController@upload');
    Route::post('attachment/upload/chunk', 'AttachmentController@uploadInChunks');
    Route::get ('attachment/download',     'AttachmentController@download');
    Route::get ('attachment/thumbnail',    'AttachmentController@thumbnail');
    Route::get ('attachment/list/article', 'AttachmentController@getArticleAttachments');
    Route::get ('attachment/list/ids',     'AttachmentController@getAttachmentsByIds');

    Route::get('space/list',          'SpaceController@getSpaces');
    Route::post('space/create',       'SpaceController@createSpace');
    Route::post('space/update',       'SpaceController@updateSpace');
    Route::get('space/remove',        'SpaceController@removeSpace');
    Route::get('space/rename',        'SpaceController@renameSpace');
    Route::get('space/member/add',    'SpaceController@addSpaceMember');
    Route::get('space/member/remove', 'SpaceController@removeSpaceMember');
    Route::get('space/member/list',   'SpaceController@getSpaceMembers');

    Route::get('search',        'SearchController@search');
});