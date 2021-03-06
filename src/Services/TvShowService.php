<?php
/**
 * Date: 16/07/2018
 * Time: 0:38
 */

namespace App\Services;


use App\Auth\User;
use App\Auth\UserService;
use App\Entity\Entity;
use App\Entity\TvShow;
use App\EntityManager;
use App\Pipelines\PipelineFactory;
use App\Util\FindQueryBuilder;
use MongoDB\BSON\ObjectId;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TvShowService extends AbstractShowService
{


    /**
     * @var FindService
     */
    private $findService;

    /**
     *
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var User
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



    public function getById(string $id)
    {
        $query = [];
        $query['_id'] = $id;
        $query['pipelines'] = array_merge($this->addLimitPipeline(1, 1), $this->addUserRatingPipeLine($this->user->getId()),
            $this->addFollowPipeLine($this->user->getId()));
        $result = $this->showService->setUserDataIntoShows([["_id" => $id]],  [['$project' => ['seasons.episodes' => 0]]]);
        if(isset($result[0])) return $result[0];
        return ["_id"=> null];
    }


    public function updateSeasonEpisodes(string $id, int $seasonNumber)
    {
        $seasons = $this->entityManager->findOnebyId($id,'movies')->getArrayCopy();
        foreach ($seasons["seasons"] as $k=>&$v){
            if($v["season_number"] === $seasonNumber) {
                $episodesBd = $this->entityManager->find(["season"=> "$seasonNumber","parent" => "$id"],
            "episodes")->toArray();
                foreach ($v["episodes"] as $i=>&$j){
                    $episodeBd = array_values(array_filter($episodesBd, function ($a) use($j){
                        return $a["episode"] == $j["episode_number"];
                    }));
                    if(sizeof($episodeBd)=== 0) {
                        continue;
                    }
                    $j = $this->updateEpisodeScore($j,$episodeBd[0]);
                }
            }
        }
        $this->entityManager->replace($seasons,'movies');
        return $seasons;
    }


    private function updateEpisodeScore($episode, $episodeBd)
    {
        if(!isset($episodeBd["rating"])) return $episode;
            $episode['rating'] = $episodeBd['rating'];
            $episode["imdb_id"] = $episodeBd["_id"];

        return $episode;
    }

    public function popular($page = 1)
    {
        $data = $this->findService->all(['page'=> $page, 'limit'=> 6,'type'=>'tvshow','sort'=>'popularity']);

        return $data;
    }

    /**
     * @param int $id
     * @param int $number
     * @return mixed
     * @throws \Exception
     */
    public function getTvShowsSeasonEpisodes(int $id, int $number)
    {
        $userId = $this->user->getId();

        $pipeline =
            [
                ['$match' => ['show_id' => $id, 'season_number' => $number]],
                ['$sort' => ['episode_number' => 1, '_id' => 1]]
            ];
        $pf = new PipelineFactory($pipeline);
        $pf->add('common', ['follow', [$userId, 'id']], ['rating', [$userId, 'id']], 'show');
        $pipeline = $pf->getPipeline();
        $entities = $this->entityManager->aggregate($pipeline, [], 'episodes');

        $data = FindService::bsonArrayToArray($entities);
        return $data;
    }


    public function upcoming(int $limit = 30, $page = 1 )
    {
        $date = new \DateTime('now');
        $numDaysMonth = cal_days_in_month(CAL_GREGORIAN, intval($date->format('m')),
            intval($date->format('Y')));
        $opts = ["dateEpisode"=> ">={$date->format("Y-m-01")};<={$date->format("Y-m-$numDaysMonth")}"];
        $date = new \DateTime('now');
        $opts['pipelines'] = array_merge([['$match'=> ['type' => 'tvshow',
            'next_episode_to_air.air_date' => $date->format('Y-m-d')]]],
            $this->addSortPipeline('popularity'), $this->addLimitPipeline($limit, $page),
            $this->getProjection(),$this->addUserRatingPipeLine($this->user->getId()),
            $this->addFollowPipeLine($this->user->getId()));
        $opts['pipe_order'] = ['$match' => 6, '$sort' => 4,'$project' => 3];
        $data = $this->findService->all($opts, 'movies');

        return $data;
    }

    public function onAir(int $limit = 30, int $page = 1)
    {
        $date = new \DateTime('now');
        $opts['pipelines'] = array_merge([['$match'=> ['type' => 'tvshow',
            'next_episode_to_air.air_date' => $date->format('Y-m-d')]]],
            $this->addSortPipeline('popularity'), $this->addLimitPipeline($limit, $page),
            $this->getProjection(),$this->addUserRatingPipeLine($this->user->getId()),
            $this->addFollowPipeLine($this->user->getId()));
        $opts['pipe_order'] = ['$match' => 6, '$sort' => 4,'$project' => 3];
        $data = $this->findService->all($opts, 'movies');

        return $data;
    }



}