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
     * TvShowService constructor.
     * @param FindService $findService
     * @param EntityManager $entityManager
     * @param UserService $userService
     */
    public function __construct(FindService $findService, EntityManager $entityManager, UserService $userService)
    {
        $this->findService = $findService;
        $this->entityManager = $entityManager;
        $this->user = $userService->getUser();
    }



    public function getById(string $id)
    {
        $result = $this->entityManager->findOnebyId($id,'movies')->getArrayCopy();
        if($result === null) return [];

        return $result;
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
            $episode["vote_average"] = $episodeBd["rating"]["averageRating"];
            $episode["vote_count"] = $episodeBd["rating"]["numVotes"];
            $episode["imdb_id"] = $episodeBd["_id"];

        return $episode;
    }

    public function popular($page = 1)
    {
        $data = $this->findService->all(['page'=> $page, 'limit'=> 6,'type'=>'tvshow','sort'=>'popularity']);

        return $data;
    }


    public function upcoming()
    {
        $date = new \DateTime('now');
        $numDaysMonth = cal_days_in_month(CAL_GREGORIAN, intval($date->format('m')),
            intval($date->format('Y')));
        $query = ["limit"=> 200, "page"=> 1, "type"=>"tvshow", "sort" => "release_date",
            "dateEpisode"=> ">={$date->format("Y-m-01")};<={$date->format("Y-m-$numDaysMonth")}"];
        $query['pipelines'][]['$project'] = FindQueryBuilder::getSimpleProject();
        $query['pipelines'][] = $this->addUserRatingPipeLine($this->user->getId());
        return $this->findService->all($query);
    }



}