<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ArticlePageService;
use App\Models\TreeNode;
use App\Libraries\Result;
use Illuminate\Http\Request;

class ArticlePageController extends Controller {
    public function getPage(Request $request) {
        $request->validate([
            'spaceId'       => ['required', 'integer', 'min:1'],
            'nodeId'        => ['required', 'integer', 'min:1'],
        ]);

        $spaceId       = $request->input('spaceId');
        $nodeId        = $request->input('nodeId');

        $service = new ArticlePageService();
        $articles = $service->getArticles($spaceId, $nodeId, ['id', 'space_id as spaceId', 'node_id as nodeId', 'type', 'title', 'body', 'search', 'author', 'version', 'ctime', 'mtime']);
        return Result::data([
            'articles' => $articles->all(),
        ]);
    }

    public function getPageVersion(Request $request) {
         $request->validate([
            'spaceId'     => ['required', 'integer', 'min:1'],
            'nodeId'      => ['required', 'integer', 'min:1'],
        ]);

        $spaceId    = $request->input('spaceId');
        $nodeId     = $request->input('nodeId');

        $service = new ArticlePageService();
        $version = $service->getVersions($spaceId, $nodeId);
        return Result::data([
            'versions' => $version,
        ]);
    }

    public function addArticle(Request $request) {
         $request->validate([
            'spaceId'       => ['required', 'integer', 'min:1'],
            'nodeId'        => ['required', 'integer', 'min:1'],
            'type'          => ['required', 'integer', 'in:'.implode(',', config('dict.ArticleType'))],
            'title'         => ['present',  'string',  'max:200'],
            'body'          => ['present',  'string',  'max:10485680'],
            'search'        => ['present',  'string',  'max:10485680'],
            'prevArticleId' => ['required', 'integer', 'min:0'],
        ]);

        $spaceId       = $request->input('spaceId');
        $nodeId        = $request->input('nodeId');
        $type          = $request->input('type');
        $title         = $request->input('title');
        $body          = $request->input('body');
        $search        = $request->input('search');
        $prevArticleId = $request->input('prevArticleId');

        $article = [
            'type'   => $type,
            'title'  => $title,
            'body'   => $body,
            'search' => $search,
        ];

        $service = new ArticlePageService();
        $article = $service->addArticle($spaceId, $nodeId, $article, $prevArticleId);
        return Result::data([
            'id'      => $article->id,
            'version' => $article->version,
        ]);
    }

    public function updateArticle(Request $request) {
         $request->validate([
            'spaceId'        => ['required', 'integer', 'min:1'],
            'nodeId'         => ['required', 'integer', 'min:1'],
            'articleId'      => ['required', 'integer', 'min:1'],
            'articleVersion' => ['required', 'string',  'max:100'],
            'title'          => ['present',  'string',  'max:200'],
            'body'           => ['present',  'string',  'max:10485680'],
            'search'         => ['present',  'string',  'max:10485680'],
        ]);

        $spaceId        = $request->input('spaceId');
        $nodeId         = $request->input('nodeId');
        $articleId      = $request->input('articleId');
        $articleVersion = $request->input('articleVersion');
        $title          = $request->input('title');
        $body           = $request->input('body');
        $search         = $request->input('search');

        $article = [
            'title'  => $title,
            'body'   => $body,
            'search' => $search,
        ];

        $service = new ArticlePageService();
        $version = $service->updateArticle($spaceId, $nodeId, $articleId, $articleVersion, $article);
        return Result::data([
            'version' => $version,
        ]);
    }

    public function moveArticle(Request $request) {
         $request->validate([
            'spaceId'       => ['required', 'integer', 'min:1'],
            'nodeId'        => ['required', 'integer', 'min:1'],
            'articleId'     => ['required', 'integer', 'min:1'],
            'prevArticleId' => ['required', 'integer', 'min:0'],
        ]);

        $spaceId        = $request->input('spaceId');
        $nodeId         = $request->input('nodeId');
        $articleId      = $request->input('articleId');
        $prevArticleId  = $request->input('prevArticleId');

        $service = new ArticlePageService();
        $article = $service->moveArticle($spaceId, $nodeId, $articleId, $prevArticleId);

        return Result::succ();
    }

    public function removeArticle(Request $request) {
         $request->validate([
            'spaceId'        => ['required', 'integer', 'min:1'],
            'nodeId'         => ['required', 'integer', 'min:1'],
            'articleId'      => ['required', 'integer', 'min:1'],
            'articleVersion' => ['required', 'string',  'max:100'],
        ]);

        $spaceId        = $request->input('spaceId');
        $nodeId         = $request->input('nodeId');
        $articleId      = $request->input('articleId');
        $articleVersion = $request->input('articleVersion');

        $service = new ArticlePageService();
        $version = $service->removeArticle($spaceId, $nodeId, $articleId, $articleVersion);
        return Result::data([
            'version' => $version,
        ]);
    }
}
 