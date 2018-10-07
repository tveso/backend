<?php
/**
 * Date: 09/08/2018
 * Time: 18:07
 */

namespace App\Jobs\UpdateTmdbJobs;


use App\EntityManager;
use App\Jobs\UpdateSearchFieldJob;
use App\Services\TheMovieDb\TheMovieDbClient;
use App\Services\TheMovieDb\TmdbTvShowService;
use MongoDB\UpdateResult;


class UpdateTvShowsJob
{
    /**
     * @var EntityManager
     */
    private $entityManager;


    /**
     * @var TmdbTvShowService
     */
    private $tmdbTvShowService;
    /**
     * @var TheMovieDbClient
     */
    private $themoviedb;


    const POSPATH = 'util/tmdb/tvshows_pos.txt';

    const TVSHOWSPATH = 'util/tmdb/tvshows.json';
    /**
     * @var UpdateSearchFieldJob
     */
    private $updateSearchFieldJob;

    /**
     * UpdateTvShowsJob constructor.
     * @param EntityManager $entityManager
     * @param TmdbTvShowService $tmdbTvShowService
     * @param TheMovieDbClient $themoviedb
     * @param UpdateSearchFieldJob $fieldJob
     */
    public function __construct(EntityManager $entityManager, TmdbTvShowService $tmdbTvShowService,
                                TheMovieDbClient $themoviedb, UpdateSearchFieldJob $fieldJob)
    {
        $this->entityManager = $entityManager;
        $this->tmdbTvShowService = $tmdbTvShowService;
        $this->themoviedb = $themoviedb;
        $this->updateSearchFieldJob = $fieldJob;
    }


    private function formatTvshowBeforeUpdate(array $entity)
    {
        $entity['updated_at'] = (new \DateTime('now'))->format('Y-m-d');

        return $entity;
    }

    public function updateByTMdbDates(?string $startDate, ?string $endDate)
    {
        $changes = $this->changes($startDate, $endDate);
        foreach ($changes as $key=>$tvshow){
            $id = $tvshow["id"];
            try{
                $tvshowDetails = $this->getTvshowDetails($id);
            } catch (\Exception $e){
                continue;
            }
            $imdb_id = $tvshowDetails["external_ids"]["imdb_id"] ?? null;
            if(is_null($imdb_id)) {
                continue;
            }
            $tvshowDetails = $this->updateSeasons($tvshowDetails);
            $tvshowDetails = $this->formatTvshowBeforeUpdate($tvshowDetails);
            $updateResult = $this->store($tvshowDetails, $imdb_id);
            $this->updateSearchField($imdb_id);
            if($updateResult->getModifiedCount()>0) {
                $this->log($tvshowDetails, $imdb_id);
            }
        }
    }


    public function getLatestTvshows()
    {
        $lastId = $this->entityManager->findOneBy(['type'=> 'tvshow'], 'movies', ['sort'=> ['id'=> -1]]);
        $lastId = $lastId["id"]+1;
        $lastTmdbId = $this->tmdbTvShowService->latest()["id"];
        for($i=$lastId;$i<=$lastTmdbId;$i++){
            try{
                $tvShowDetails = $this->getTvshowDetails($i);
            } catch (\Exception $e){
                echo $e->getMessage()."\n";
                continue;
            }
            $imdb_id = $tvShowDetails["external_ids"]["imdb_id"] ?? null;
            if(is_null($imdb_id)) {
                continue;
            }
            $tvShowDetails = $this->updateSeasons($tvShowDetails);
            $this->insertOrUpdate($tvShowDetails);
            echo "Insertada serie {$tvShowDetails["name"]}\n";
            $this->updateSearchField($imdb_id);
        }
    }

    public function changes(?string $startDate, ?string $endDate)
    {
        $page = 1;
        $totalPages = 2;
        $result = [];
        $params = [];
        if(!is_null($startDate)){
            $params['start_date'] = $startDate;
        }
        if(!is_null($endDate)){
            $params['end_date'] = $endDate;
        }
        while($page<=$totalPages){
            $params['page'] = $page;
            $request = $this->themoviedb->request('tv/changes', $params);
            $request = json_decode($request, 1);
            $totalPages = $request["total_pages"];
            echo "$page Pagina de $totalPages\n";
            $filter = array_filter($request['results'], function($a){
                return $a['adult'] !== true;
            });
            $page = $page+1;
            $result = array_merge($result, $filter);
        }

        return $result;
    }



    public function getTvshowDetails($id)
    {
        $tmdbData = $this->tmdbTvShowService->get($id);
        $tmdbData['type'] = "tvshow";

        return $tmdbData;
    }
    public function updateSeasons(array $tmdbData) {
        if(sizeof($tmdbData["seasons"])>0){
            $firstSeason = $tmdbData["seasons"][0]["season_number"];
            for($i = 0;$i<sizeof($tmdbData["seasons"]);$i++){
                try{
                    $actualSeason = $this->getSeason($tmdbData["id"], $firstSeason+$i);
                } catch (\Exception $e){
                    continue;
                }
                $tmdbData["seasons"][$i] = $actualSeason;
            }
        }
        return $tmdbData;
    }
    public function getSeason(string $tmdb_id, int $number = 1)
    {
        $params = [];
        $params["append_to_response"] = 'videos,images,credits,keywords,external_ids';
        $params["include_image_language"] = 'en';
        $response = $this->themoviedb->request("tv/$tmdb_id/season/$number", $params);

        return json_decode($response,1);
    }

    public function store($tvshowDetails, $imdb_id) : UpdateResult
    {
        $query = ["_id" => $imdb_id];
        $tvshowDetails["_id"] = $imdb_id;
        $updateData = ['$set'=> $tvshowDetails];
        return $this->entityManager->update($query, $updateData,'movies', ['upsert'=> true]);
    }

    private function log(array $tvshow, string $id)
    {
        $message = "Serie: $id ->  ". $tvshow["name"] ." añadida";
        print($message."\n");
    }


    private function updateSearchField($imdb_id)
    {
        $entity = $this->entityManager->findOneBy(["_id"=> $imdb_id],'movies')->getArrayCopy();
        $this->updateSearchFieldJob->updateEntity($entity);
    }

    private function insertOrUpdate($tvDetails)
    {
        $tvDetails["_id"] = $tvDetails["external_ids"]["imdb_id"];
        $tvDetails["imdb_id"] =$tvDetails["external_ids"]["imdb_id"];
        $tvDetails["type"] = 'tvshow';

        $this->entityManager->insertOfUpdate($tvDetails, 'movies');
    }
}