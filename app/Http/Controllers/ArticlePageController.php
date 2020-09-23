<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ArticlePageService;
use App\Models\TreeNode;
use App\Libraries\Result;
use App\Exceptions\ArticleUpdatedException;
use App\Exceptions\PageUpdatedException;
use Illuminate\Http\Request;

class ArticlePageController extends Controller {
    public function getPage(Request $request) {
        $request->validate([
            'spaceId'       => ['required', 'integer', 'min:1'],
            'treeId'        => ['required', 'integer', 'min:1'],
            'nodeId'        => ['required', 'integer', 'min:1'],
        ]);

        try {
            $spaceId       = $request->input('spaceId');
            $treeId        = $request->input('treeId');
            $nodeId        = $request->input('nodeId');

            $service = new ArticlePageService();
            $articles = $service->getArticles($spaceId, $treeId, $nodeId);
            return Result::data([
                'articles' => $articles->all(),
            ]);

        } catch (PageNotExistException $e) {
            return Result::error('PAGE_NOT_EXIST');
        }
       
    }

    public function getPageVersion(Request $request) {
         $request->validate([
            'spaceId'     => ['required', 'integer', 'min:1'],
            'treeId'      => ['required', 'integer', 'min:1'],
            'nodeId'      => ['required', 'integer', 'min:1'],
        ]);

        try {
            $spaceId    = $request->input('spaceId');
            $treeId     = $request->input('treeId');
            $nodeId     = $request->input('nodeId');

            $service = new ArticlePageService();
            $version = $service->getVersions($spaceId, $treeId, $nodeId);
            return Result::data([
                'version' => $version,
            ]);

        } catch (PageNotExistException $e) {
            return Result::error('PAGE_NOT_EXIST');
        }
    }

    public function addArticle(Request $request) {
         $request->validate([
            'spaceId'       => ['required', 'integer', 'min:1'],
            'treeId'        => ['required', 'integer', 'min:1'],
            'nodeId'        => ['required', 'integer', 'min:1'],
            'type'          => ['required', 'integer', 'in:'.implode(',', config('dict.ArticleType'))],
            'title'         => ['required', 'max:200'],
            'body'          => ['required', 'max:10485680'],
            'search'        => ['required', 'max:10485680'],
            'prevArticleId' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $spaceId       = $request->input('spaceId');
            $treeId        = $request->input('treeId');
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
            $article = $service->addArticle($spaceId, $treeId, $nodeId, $article, $prevArticleId);
            return Result::data([
                'id'      => $article->id,
                'version' => $article->version,
            ]);
         } catch (PageNotExistException $e) {
            return Result::error('PAGE_NOT_EXIST');
         }
    }

    public function updateArticle(Request $request) {
         $request->validate([
            'spaceId'        => ['required', 'integer', 'min:1'],
            'treeId'         => ['required', 'integer', 'min:1'],
            'nodeId'         => ['required', 'integer', 'min:1'],
            'articleId'      => ['required', 'integer', 'min:1'],
            'articleVersion' => ['required', 'max:100'],
            'title'          => ['required', 'max:200'],
            'body'           => ['required', 'max:10485680'],
            'search'         => ['required', 'max:10485680'],
        ]);

        try {
            $spaceId        = $request->input('spaceId');
            $treeId         = $request->input('treeId');
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
            $version = $service->updateArticle($spaceId, $treeId, $nodeId, $articleId, $articleVersion, $article);
            return Result::data([
                'version' => $version,
            ]);

        } catch (PageNotExistException $e) {
            return Result::error('PAGE_NOT_EXIST');
        } catch (ArticleUpdatedException $e) {
            return Result::error('ARTICLE_UPDATED');
        }
    }

    public function moveArticle(Request $request) {
         $request->validate([
            'spaceId'       => ['required', 'integer', 'min:1'],
            'treeId'        => ['required', 'integer', 'min:1'],
            'nodeId'        => ['required', 'integer', 'min:1'],
            'articleId'     => ['required', 'integer', 'min:1'],
            'prevArticleId' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $spaceId        = $request->input('spaceId');
            $treeId         = $request->input('treeId');
            $nodeId         = $request->input('nodeId');
            $articleId      = $request->input('articleId');
            $prevArticleId  = $request->input('prevArticleId');

            $service = new ArticlePageService();
            $article = $service->moveArticle($spaceId, $treeId, $nodeId, $articleId, $prevArticleId);

            return Result::succ();

        } catch (PageNotExistException $e) {
            return Result::error('PAGE_NOT_EXIST');
        } catch (PageUpdatedException $e) {
            return Result::error('PAGE_UPDATED');
        }
    }
}
 