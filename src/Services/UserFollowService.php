<?php
/**
 * Date: 06/11/2018
 * Time: 20:39
 */

namespace App\Services;


use App\Auth\UserService;
use App\EntityManager;
use MongoDB\BSON\ObjectId;
use Symfony\Component\ExpressionLanguage\Tests\Node\Obj;

class UserFollowService implements Service
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var FollowService
     */
    private $followService;


    /**
     * UserFollowStatsService constructor.
     * @param EntityManager $entityManager
     * @param UserService $userService
     * @param FollowService $followService
     */
    public function __construct(EntityManager $entityManager, UserService $userService, FollowService $followService)
    {

        $this->entityManager = $entityManager;
        $this->userService = $userService;
        $this->followService = $followService;
    }

    /**
     * @param string $name
     * @return array
     */
    public function getShowsAndEpisodeStats(string $name): array
    {
        $user = $this->userService->findByName($name);
        $id = $user->getId();
        $result = [];
        $result['tvshow_favorites'] = $this->getFavorites($id, 'tvshow', 1, 6);
        $result['movie_favorites'] = $this->getFavorites($id, 'movie', 1, 6);
        $result['movie_last_watched'] = $this->getLastWatched($id, 'movie', 1, 6);
        $result['episode_last_watched'] = $this->getLastWatched($id, 'episode', 1, 6);

        return $result;
    }

    /**
     * @param string $name
     * @return array
     */
    public function getCountFollowShowsInfo(string $name): array
    {
        $user = $this->userService->findByName($name);
        $id = $user->getId();
        $pipeline = [
            ['$match' => ['user' => $id]],
            ['$group' => ['_id' => ['type' => '$type','mode' => '$mode'], 'count' => ['$sum' => 1]]]
        ];
        $opts['pipeline'] = $pipeline;

        $data = $this->entityManager->aggregate($pipeline, [], 'follows');
        $data = FindService::bsonArrayToArray($data);
        $result = ['movie_pending_count' => 0, 'movie_watched_count' => 0, 'tvshow_pending_count' => 0,
            'tvshow_finalized_count' => 0, 'tvshow_following_count' => 0, 'episode_watched_count' => 0,
            'episode_pending_count' => 0];
        foreach ($data as $key=> $value) {
            $k = $value['_id']['type']."_".$value['_id']['mode']."_count";
            $result[$k] = $value['count'];
        }

        return $result;

    }

    private function getFavorites(ObjectId $id, string $type, int $page, int $limit): array
    {
        $opts = ['user' => $id, 'mode' => 'favorite', 'type' => $type, 'page' => $page, 'limit' => $limit, 'sort' => 'updated_at'];

        return $this->followService->getUserFollowsShows($opts);
    }

    /**
     * @param ObjectId $id
     * @param string $type
     * @param int $page
     * @param int $limit
     * @return array
     */
    private function getLastWatched(ObjectId $id, string $type, int $page, int $limit): array
    {
        $opts = ['user' => $id, 'mode' => 'watched',  'type' => $type, 'page' => $page, 'limit' => $limit, 'sort' => 'updated_at'];

        return $this->followService->getUserFollowsShows($opts);
    }
}