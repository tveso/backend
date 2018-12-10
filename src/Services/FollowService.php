<?php
/**
 * Date: 28/09/2018
 * Time: 18:05
 */

namespace App\Services;


use App\Auth\User;
use App\Auth\UserService;
use App\Entity\Movie;
use App\Entity\TvShow;
use App\EntityManager;
use App\Util\FindQueryBuilder;
use App\Util\PipelineBuilder\PipelineBuilder;
use MongoDB\BSON\ObjectId;
use MongoDB\Operation\Find;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\ValidatorException;

class FollowService extends AbstractShowService
{
    /**
     * @var FindService
     */
    private $findService;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var User|string
     */
    private $user;
    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var ShowService
     */
    private $showService;


    /**
     * TvShowService constructor.
     * @param FindService $findService
     * @param EntityManager $entityManager
     * @param UserService $userService
     * @param ShowService $showService
     */
    public function __construct(FindService $findService, EntityManager $entityManager, UserService $userService,
                                ShowService $showService)
    {
        $this->findService = $findService;
        $this->entityManager = $entityManager;
        $this->user = $userService->getUser();
        $this->userService = $userService;
        $this->showService = $showService;
    }

    /**
     * @param string $id
     * @param string $type
     * @param null|string $mode
     * @return bool
     * @throws \Exception
     */
    public function follow(string $id, ?string $mode, string $type)
    {
        $type = $this->checkShowExists($id);
        if($type === 'movie') {
            $this->checkValidMode($mode, Movie::FOLLOW_MODES);
        }
        if($type === 'tvshow') {
            $this->checkValidMode($mode, TvShow::FOLLOW_MODES);
        }
        $mode = strtolower($mode);
        $values = $this->updateShowFollowState($id, $mode, $type);

        return $values;
    }


    /**
     * @param string $mode
     * @param array $availableModes
     * @throws \Exception
     */
    private function checkValidMode(string $mode, array $availableModes = [])
    {
        $mode = strtolower($mode);
        if(!in_array($mode, $availableModes) and $mode !== 'cancel'){
            throw new \Exception("'$mode' is not a valid mode");
        }
    }

    /**
     * @param string $id
     * @param string $mode
     * @return
     * @throws \Exception
     */
    private function checkShowExists(string $id)
    {
        $entities = $this->entityManager->find(["_id" => $id], 'movies')->toArray();
        if(empty($entities)){
            throw new \Exception("$id tvshow was not found");
        }

        return $entities[0]['type'];
    }

    private function updateShowFollowState($id, string $mode, string $type)
    {
        $data = $this->entityManager->findOneBy(['user'=> new ObjectId($this->user->getId()), 'show' => $id, 'type' => $type], 'follows');
        if($mode === 'cancel') {
            if(is_null($data)) {
                return 0;
            }
            return $this->entityManager->delete(["_id"=> $data["_id"]],'follows')->getDeletedCount();
        }
        $data["updated_at"] = (new \DateTime())->getTimestamp();
        $data["mode"] = $mode;
        $data["show"] = $id;
        $data["user"] = new ObjectId($this->user->getId());
        $data['type'] = $type;
        if(!isset($data["_id"])){
            $data["_id"] = new ObjectId();
        }
        $updated = $this->entityManager->replace($data, 'follows');

        return $updated->getModifiedCount();
    }

    /**
     * @param array $opts
     * @return array
     */
    public function getUserFollowsShows(array $opts = []): array
    {
        if(!isset($opts['user'])) {
            throw new InvalidArgumentException();
        }
        $userId = $opts['user'];
        $mode = ($opts['mode']) ?? 'pending';
        $limit = min(($opts["limit"]) ?? 30,100);
        unset($opts['mode']);
        unset($opts['user']);
        unset($opts['limit']);
        $mode = explode(",", $mode);
        $page = ($opts["page"]) ?? 1;
        $sort = $opts["sort"] ?? null;
        $opts['pipelines'] = array_merge($this->addLimitPipeline($limit, $page), $this->addSortPipeline($sort),
            $this->getProjection());
        $pb = new FindQueryBuilder($opts);
        $showPipelines = $pb->build();
        $pipelines['pipelines'] = [
            ['$match' => ['user' => new ObjectId($userId), 'mode' => ['$in' => $mode]]],
            ['$sort' => ['updated_at' => -1]],
            ['$lookup' =>
                [
                    'from' => 'movies',
                    'localField' => 'show',
                    'foreignField' => '_id',
                    'as' => 'shows'
                ]],
            ['$unwind' => ['path' => '$shows']],
            ['$addFields' => ['shows.updated_at' => '$updated_at']],
            ['$replaceRoot' => ['newRoot' => '$shows']],
        ];
        $userDataPipeline = array_merge($this->addUserRatingPipeLine($this->user->getId()),
            $this->addFollowPipeLine($this->user->getId()));
        $pipelines['pipelines'] = array_merge($pipelines['pipelines'], $showPipelines, $userDataPipeline);
        $pipelines["pipe_order"] = ['$project' => -5];
        $result = $this->findService->all($pipelines,'follows');

        return $result;
    }

    /**
     * @param int $id
     * @param bool $markUnseen
     * @param bool $previousEpisodes
     * @return array|int|null|object
     * @throws \Exception
     */
    public function watchEpisodes(int $id, bool $markUnseen = false, bool $previousEpisodes = false)
    {
        if($markUnseen) {
           $this->updateShowFollowState($id, 'cancel', 'episode');
            return ["status" => 200, "message" => 'Episode mark as unseen'];
        }
        $episode =  $this->entityManager->findOneBy(["_id" => $id],
            'episodes');
        if (is_null($episode)){
            throw new \InvalidArgumentException();
        }
        $show = $this->entityManager->findOneBy(["id" => $episode['show_id'], 'type' => 'tvshow'],
            'movies');
        $show = FindService::bsonArrayToArray($show);
        $this->markWatchEpisode($id, $show["id"], $episode['season_number'], $episode['episode_number']);
        $this->followTvshowIfNotFollowed($show["_id"]);
        if($previousEpisodes === true) {
            $this->watchPreviousEpisodes($episode['season_number'], $episode['episode_number'],  $episode['show_id']);
        }

        return $this->entityManager->findOneBy(['show' => $id], 'follows');
    }

    public function markWatchEpisode($id,  $parentId, int $season_number, int $episode_number)
    {
        $userId = $this->user->getId();
        $follow = [];
        $follow['updated_at'] = time();
        $follow['tvshow_parent'] = $parentId;
        $follow['season_number'] = $season_number;
        $follow['episode_number'] = $episode_number;
        $follow['mode'] = 'watched';
        $follow['show'] = $id;
        $follow['user'] = $userId;
        $follow['type'] = 'episode';
        $this->entityManager->update(["show" => $id], ['$set' => $follow], 'follows', ['upsert' => true]);
    }

    private function watchPreviousEpisodes(int $seasonNumber, int $episodeNumber, int $showId)
    {
        $episodes = $this->entityManager->aggregate([
            ['$match' => ['show_id' => $showId, '$or' =>
                [['season_number'=> ['$lt'=> $seasonNumber]],
                ['$and'=> [
                    ['season_number'=> $seasonNumber],
                    ['episode_number' => ['$lt'=> $episodeNumber]],
                ]]
            ], 'season_number' => ['$gt' => 0]]
        ]], [], 'episodes');
        foreach ($episodes as $episode) {
            $this->markWatchEpisode($episode["id"], $episode['show_id'], $episode['season_number'], $episode['episode_number']);
        }
        return true;
    }


    /**
     * @param $id
     * @throws \Exception
     */
    private function followTvshowIfNotFollowed($id)
    {
        $type = 'tvshow';
        $data = $this->entityManager->findOneBy(['user'=> $this->user->getId(), 'show' => $id, 'type' => $type], 'follows');
        if(is_null($data)){
            $this->follow($id, 'following', '');
            return;
        }
        return;
    }

}