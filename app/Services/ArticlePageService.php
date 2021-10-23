<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleHistory;
use App\Models\TreeNode;
use App\Models\Users;
use App\Models\Attachment;
use App\Exceptions\PageUpdatedException;
use App\Exceptions\TreeNotExistException;
use App\Exceptions\ArticleNotExistException;
use App\Exceptions\UnfinishedDBOperationException;
use App\Exceptions\IllegalOperationException;
use App\Services\PermissionChecker;
use App\Services\PresenceChecker;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ArticlePageService {

    public function getArticles(int $spaceId, int $nodeId, $fields): Collection {
        PermissionChecker::readSpace($spaceId);

        return Article::getArticles($spaceId, $nodeId, $fields);
    }

    public function isPageWritable(int $spaceId, $nodeId): bool {
        $rows = TreeNode::getTrashNodes($spaceId, 1, ['id' => $nodeId]);
        if ($rows->isNotEmpty()){
            return false;
        }

        return PermissionChecker::getSpacePermission($spaceId, 'write');
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
        PermissionChecker::readSpace($spaceId);

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
        PermissionChecker::writeSpace($spaceId);
        PresenceChecker::node($spaceId, $nodeId);

        $author = Auth::id();

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
        PermissionChecker::writeSpace($spaceId);
        PresenceChecker::node($spaceId, $nodeId);

        $author = Auth::id();

        return Article::updateArticle($spaceId, $nodeId, $articleId, $articleVersion, $author, $article);
    }

    public function setArticleLevel(int $spaceId, int $nodeId, int $articleId, $level): void {
        PermissionChecker::writeSpace($spaceId);
        PresenceChecker::node($spaceId, $nodeId);

        Article::setArticleLevel($spaceId, $nodeId, $articleId, $level);
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
        PermissionChecker::writeSpace($spaceId);
        PresenceChecker::node($spaceId, $nodeId);

        $operator = Auth::id();

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
        PermissionChecker::readSpace($spaceId);

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
        PermissionChecker::writeSpace($spaceId);
        PresenceChecker::node($spaceId, $nodeId);

        if ($articleId == $prevArticleId) {
            return;
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

    public function moveArticleToAnotherNode ($spaceId, $nodeId, $articleId, $toNodeId, $toPrevArticleId) {
        PermissionChecker::writeSpace($spaceId);
        PresenceChecker::node($spaceId, $nodeId);
        PresenceChecker::node($spaceId, $toNodeId);

        if ($nodeId != $toNodeId) {
            Article::moveArticleToAnotherNode($spaceId, $nodeId, $articleId, $toNodeId);
        }
        $this->moveArticle($spaceId, $toNodeId, $articleId, $toPrevArticleId);
    }

    public function getArticleHistoryVersions ($spaceId, $nodeId, $articleId) {
        PermissionChecker::readSpace($spaceId);

        $article = Article::getArticleById($spaceId, $nodeId, $articleId);
        if (empty($article)) {
            $trashArticle = Article::getTrashArticleById($spaceId, $nodeId, $articleId);
            if (empty($trashArticle)) {
                throw new ArticleNotExistException();
            }
        }

        $versions = ArticleHistory::getArticleHistoryVersions($articleId);

        $userMap = [];
        $uids = [];
        foreach ($versions as $version) {
            $uids[] = $version->author;
        }
        $users = Users::getUsers($uids);
        foreach ($users as $user) {
            $userMap[$user->id] = $user;
        }

        $items = [];
        foreach ($versions as $version) {
            $items[] = [
                'version'    => $version->version,
                'stime'      => $version->stime,
                'author'     => $version->author,
                'authorName' => isset($userMap[$version->author]) 
                                ? $userMap[$version->author]->name
                                : ''
            ];
        }

        return $items;
    }

    public function getHistoryArticle ($spaceId, $nodeId, $articleId, $version) {
        PermissionChecker::readSpace($spaceId);

        return ArticleHistory::getArticle($articleId, $version);
    }
}