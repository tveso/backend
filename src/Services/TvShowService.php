<?php
/**
 * Date: 16/07/2018
 * Time: 0:38
 */

namespace App\Services;


use App\EntityManager;

class TvShowService
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
     * TvShowService constructor.
     * @param FindService $findService
     * @param EntityManager $entityManager
     */
    public function __construct(FindService $findService, EntityManager $entityManager)
    {
        $this->findService = $findService;
        $this->entityManager = $entityManager;
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
            "dateEpisode"=> ">={$date->format("Y-m-1")};<={$date->format("Y-m-$numDaysMonth")}"];

        return $this->findService->all($query);
    }


}