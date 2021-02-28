<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleHistory;
use App\Models\TreeNode;
use App\Models\Attachment;
use App\Exceptions\PageUpdatedException;
use App\Exceptions\TreeNotExistException;
use App\Exceptions\UnfinishedDBOperationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ArticlePageService {

    public function getArticles(int $spaceId, int $nodeId, $fields): Collection {
        return Article::getArticles($spaceId, $nodeId, $fields);
    }

    /**
     * get the articles' versions
     * @param int $spaceId
     * @param int $nodeId
     * @return array
     *      [
     *          [
     *              'id'      =>  ...,
     *              'version' =>  ...
     *          ],
     *          [
     *              ...
     *          ],
     *          ...
     *      ]
     */
    public function getVersions(int $spaceId, int $nodeId): array {
        return Article::getArticles($spaceId, $nodeId, ['id', 'version'])->toArray();
    }

    /**
     * add an article
     * @param int $spaceId
     * @param int $nodeId
     * @param array $article
     * @param array $prevArticle
     */
    public function addArticle(int $spaceId, int $nodeId, array $article, int $prevArticleId, array $attachmentIds): Article {
        $author = 0;

        $newArticle = Article::addArticle($spaceId, $nodeId, $article, $author);
        $this->moveArticle($spaceId, $nodeId, $newArticle->id, $prevArticleId);

        try {
            Log::error("spaceId:$spaceId nodeId:$nodeId articleId:{$newArticle->id} attachmentIds:".join(',', $attachmentIds));
            Attachment::attachToArticle($spaceId, $nodeId, $newArticle->id, $attachmentIds);
        } catch (\Exception $e) {
            Log::error("function addArticle spaceId:$spaceId nodeId:$nodeId articleId:{$newArticle->id} attachmentIds:".join(',', $attachmentIds));
            Log::error($e->getMessage());
        }

        return $newArticle;
    }

    /**
     * update an article
     * @param int $spaceId
     * @param int $nodeId
     * @param int $articleId
     * @param string $articleVersion
     * @param array $article
     *      [
     *          'title'  => ...,
     *          'body'   => ...,
     *          'search' => ...
     *      ]
     * @return string the new version of the article
     * @throws ArticleNotExistException, ArticleRemovedException, ArticleUpdatedException, UnfinishedDBOperationException
     */
    public function updateArticle(int $spaceId, int $nodeId, int $articleId, string $articleVersion, array $article): string {
        $author = 0;

        return Article::updateArticle($spaceId, $nodeId, $articleId, $articleVersion, $author, $article);
    }

     /**
     * remove an article
     * @param int $spaceId
     * @param int $nodeId
     * @param int $articleId
     * @param string $articleVersion
     * @return void 
     * @throws ArticleNotExistException, ArticleUpdatedException
     */
    public function removeArticle(int $spaceId, int $nodeId, int $articleId, string $articleVersion): void {
        $operator = 0;

        Article::removeArticle($spaceId, $nodeId, $articleId, $articleVersion, $operator);
    }

    /**
     * get an article
     * @param int $spaceId
     * @param int $nodeId
     * @param int $articleId
     * @throws ArticleNotExistException
     */
     public function getArticleById(int $spaceId, int $nodeId, int $articleId): Article {
        return Article::getArticleById($spaceId, $nodeId, $articleId);
    }

    /**
     * move an article
     * @param int $spaceId
     * @param int $nodeId
     * @param int $articleId
     * @param int $prevArticleId
     * @return void
     * @throws PageUpdatedException
     */
    public function moveArticle(int $spaceId, int $nodeId, int $articleId, int $prevArticleId): void {
        if ($articleId == $prevArticleId) {
            throw new IllegalOperationException();
        }

        $prevArticleIndex = -1;
        $prevArticle = null;
        $opArticleExist = false;

        $articles = Article::getArticles($spaceId, $nodeId, ['id', 'pos'])->all();
        foreach ($articles as $idx => $article) {
            if ($article->id == $prevArticleId) {
                $prevArticle = $article;
                $prevArticleIndex = $idx;
            } elseif ($article->id == $articleId) {
                $opArticleExist = true;
            }
        }

        if (empty($articles) || !$opArticleExist || ($prevArticleId > 0 && empty($prevArticle))) {
            throw new PageUpdatedException();
        }

        //to be the first article
        if ($prevArticleId == 0) {
            if ($articles[0]->id != $articleId) {
                $newPos = $articles[0]->pos - 1000;
                Article::modifyArticlePoses($spaceId, $nodeId, [$articleId => $newPos]);
            }
            return;
        }

        //to be the last article
        if ($prevArticleIndex == count($articles) - 1) {
            if ($articles[$prevArticleIndex]->id != $articleId) {
                $newPos = $articles[$prevArticleIndex]->pos + 1000;
                Article::modifyArticlePoses($spaceId, $nodeId, [$articleId => $newPos]);
            }
            return;
        }

        $prevArticle = $articles[$prevArticleIndex];
        $nextArticle = $articles[$prevArticleIndex + 1];
        if ($nextArticle->id == $articleId) {
            return;
        }

        $prevPos = $prevArticle->pos;
        $nextPos = $nextArticle->pos;
        if ($nextPos - $prevPos > 2) {
            $newPos = ($prevPos + $nextPos) / 2;
            Article::modifyArticlePoses($spaceId, $nodeId, [$articleId => $newPos]);
        } else {
            $modifyPoses = [];
            for ($i = $prevArticleIndex + 1; $i < count($articles); $i++) {
                $tmpArticle = $articles[$i];
                $modifyPoses[$tmpArticle->id] = $tmpArticle['pos'] + 2000;
            }
            $modifyPoses[$articleId] = $prevPos + 1000;
            Article::modifyArticlePoses($spaceId, $nodeId, $modifyPoses);
        }

        return;
    }
}