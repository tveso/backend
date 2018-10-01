<?php
/**
 * Date: 24/09/2018
 * Time: 15:24
 */

namespace App\Jobs\UpdateTmdbJobs;


use App\EntityManager;
use App\Jobs\UpdateSearchFieldJob;
use App\Services\TheMovieDb\TmdbMovieService;
use App\Services\TheMovieDb\TmdbService;
use App\Services\TheMovieDb\TmdbTvShowService;
use GuzzleHttp\Exception\ClientException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;

class FixDataJob
{

    /**
     * @var TmdbService
     */
    private $tmdbService;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var UpdateSearchFieldJob
     */
    private $updateSearchFieldJob;
    /**
     * @var TmdbMovieService
     */
    private $tmdbMovieService;
    /**
     * @var TmdbTvShowService
     */
    private $tmdbTvShowService;

    public function __construct(TmdbService $tmdbService, EntityManager $entityManager,
                                UpdateSearchFieldJob $updateSearchFieldJob, TmdbMovieService $tmdbMovieService, TmdbTvShowService $tmdbTvShowService)
    {
        $this->tmdbService = $tmdbService;
        $this->entityManager = $entityManager;
        $this->updateSearchFieldJob = $updateSearchFieldJob;
        $this->tmdbMovieService = $tmdbMovieService;
        $this->tmdbTvShowService = $tmdbTvShowService;
    }

    public function fixResourcesNotInTmdb()
    {
        $query = ["id" => ['$exists'=> false]];
        $resources = $this->entityManager->find($query, 'movies');
        foreach ($resources as $value){
            try{
                $resource = $this->tmdbService->findAndGetDetails($value["_id"]);
            } catch (ClientException $e){
                if($e->getCode()=== 404) {
                    $this->remove($value["_id"]);
                }
                continue;
            }
            if(!empty($resource)){
                $this->update($value["_id"], $resource);
                echo "{$value['title']} actualizada\n";
                continue;
            }
            $this->remove($value["_id"]);
            echo "{$value["_id"]} borrada de la BD porque no está en TMDB\n";
        }
    }

    private function update(string $id, array $resource)
    {
        $resource["type"] = (isset($resource["name"])) ? 'tvshow' : 'movie';
        $this->entityManager->update(["_id"=>$id], ['$set'=> $resource], 'movies');
        $resource["_id"] = $id;
        $this->updateSearchFieldJob->updateEntity($resource);
    }

    public function missingIds(array $query = ['type'=> 'movie'])
    {
        $opts = ['sort'=> ['id' => -1]];
        $type = $query['type'];
        $results = $this->entityManager->find($query, 'movies', $opts);
        $lastId = null;
        foreach ($results as $result) {
            if(is_null($lastId)) {
                $lastId = $result["id"];
                continue;
            }
            $currId = $result['id'];
            $difference = $lastId-$currId;
            if($difference>1) {
                for($i=1;$i<$difference;$i++) {
                    $sum = $currId+$i;
                    $this->findAndInsert($sum, $type);
                }
            }
            $lastId = $currId;
            sleep(1);
        }
    }

    private function remove($id)
    {
        $this->entityManager->delete(["_id" => $id],'movies');
    }

    public function addIdsEpisodes()
    {
        $id = new ObjectId();
        $query = $this->entityManager->find(["type" => "tvshow", "seasons" => ['$not' => ['$size'=> 0]]], 'movies');
        foreach ($query as $key=>$value){
            $seasons = $value['seasons'];
            foreach ($value['seasons'] as $s=>$season) {
                if(!isset($season['episodes'])) {
                    continue;
                }
                foreach ($season['episodes'] as $e=>$episode) {
                    $episode["_id"] = (new ObjectId())->__toString();
                    $seasons[$s][$e] = $episode;
                }
            }
            $id = $value["_id"];
            $this->entityManager->update(["_id" => $id], ['$set'=> ['seasons'=> $seasons]], 'movies');
        }

    }

    private function findAndInsert(int $id, string $type)
    {
        try{
            $resource = [];
            if($type === 'movie'){
                $resource = $this->tmdbMovieService->get($id);
                $resource["_id"] = $resource["imdb_id"];
            }
            if($type === 'tvshow') {
                $resource = $this->tmdbTvShowService->get($id);
                $resource["_id"] = $resource["external_ids"]["imdb_id"];
            }
            $resource["type"] = $type;
            $this->insert($resource);
        } catch (\Exception $e){
            if($e->getCode()=== 404) {
                return;
            }
            echo $e->getMessage()."\n";
        }
    }

    private function insert(array $resource)
    {
        $resource = $this->updateSearchFieldJob->prepareEntity($resource);
        if(is_null($resource["_id"])){
            echo "{$resource["id"]} no insertada\n";
            return;
        }
        $this->entityManager->insert($resource,'movies');
        $message = ($resource["type"]==='movie') ? "Insertada película {$resource["title"]}\n" :
            "Insertada serie {$resource["name"]}\n";

        echo $message;
    }

}