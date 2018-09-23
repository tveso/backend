<?php
/**
 * Date: 09/08/2018
 * Time: 18:07
 */

namespace App\Jobs\UpdateTmdbJobs;


use App\EntityManager;
use App\Jobs\UpdateSearchFieldJob;
use App\Services\TheMovieDb\TheMovieDbClient;
use App\Services\TheMovieDb\TmdbMovieService;
use MongoDB\UpdateResult;

class UpdateMoviesJob
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TmdbMovieService
     */
    private $tmdbMovieService;


    const POSPATH = 'util/tmdb/movies_pos.txt';

    const MOVIESPATH = 'util/tmdb/movies.json';
    /**
     * @var
     */
    private $themoviedb;
    /**
     * @var UpdateSearchFieldJob
     */
    private $updateSearchFieldJob;

    /**
     * UpdateMoviesJob constructor.
     * @param EntityManager $entityManager
     * @param TmdbMovieService $tmdbMovieService
     * @param TheMovieDbClient $theMovieDbClient
     * @param UpdateSearchFieldJob $updateSearchFieldJob
     */
    public function __construct(EntityManager $entityManager, TmdbMovieService $tmdbMovieService,
                                TheMovieDbClient $theMovieDbClient, UpdateSearchFieldJob $updateSearchFieldJob)
    {
        $this->entityManager = $entityManager;
        $this->tmdbMovieService = $tmdbMovieService;
        $this->themoviedb = $theMovieDbClient;
        $this->updateSearchFieldJob = $updateSearchFieldJob;
    }


    public function updateByTMdbDates(?string $startDate, ?string $endDate)
    {
        $changes = $this->changes($startDate, $endDate);

        foreach ($changes as $key=>$movie){
            $id = $movie["id"];
            try{
                $movieDetails = $this->getMovieDetails($id);
            } catch (\Exception $e){
                continue;
            }

            $imdb_id = $movieDetails["external_ids"]["imdb_id"] ?? null;
            if(is_null($imdb_id) or $imdb_id==='') {
                continue;
            }
            $updateResult = $this->store($movieDetails, $imdb_id);
            $this->updateSearchField($imdb_id);
            if($updateResult->getModifiedCount()>0) {
                $this->log($movieDetails, $imdb_id);
            }
        }
    }



    private function changes(?string $startDate, ?string $endDate)
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
            $request = $this->themoviedb->request('movie/changes', $params);
            $request = json_decode($request, 1);
            $totalPages = $request["total_pages"];
            echo "$page Pagina de $totalPages\n";
            $page = $page+1;
            $result = $request + array_filter($request['results'], function($a){
                    return $a['adult'] === false;
                });
        }

        return $result['results'];
    }

    public function getLatestMovies()
    {
        $lastId = $this->entityManager->findOneBy(['type'=> 'movie'], 'movies', ['sort'=> ['id'=> -1]]);
        $lastId = $lastId["id"]+1;
        $lastTmdbId = $this->tmdbMovieService->latest()["id"];
        for($i=$lastId;$i<=$lastTmdbId;$i++){
            try{
                $movieDetails = $this->getMovieDetails($i);
            } catch (\Exception $e){
                continue;
            }

            $imdb_id = $movieDetails["external_ids"]["imdb_id"] ?? null;
            if(is_null($imdb_id)) {
                continue;
            }
            $this->insertOrUpdate($movieDetails);
            echo "Insertada película {$movieDetails["title"]}\n";
            $this->updateSearchField($imdb_id);
        }
    }


    public function getMovieDetails($id)
    {
        $tmdbData = $this->tmdbMovieService->get($id);
        $tmdbData['type'] = "movie";

        return $tmdbData;
    }

    public function store($movieDetails, $imdb_id) : UpdateResult
    {
        $query = ["_id" => $imdb_id];
        $movieDetails["updated_at"] = (new \DateTime('now'))->format('Y-m-d');
        $updateData = ['$set'=> $movieDetails];
        $movieDetails["_id"] = $imdb_id;
        return $this->entityManager->update($query, $updateData,'movies', ['upsert' => true]);
    }



    private function updateSearchField($imdb_id)
    {
        $entity = $this->entityManager->findOneBy(["_id"=> $imdb_id],'movies')->getArrayCopy();
        $this->updateSearchFieldJob->updateEntity($entity);
    }

    private function log(array $tvshow, string $id)
    {
        $message = "Pelicula: $id ->  ". $tvshow["original_title"] ." añadida";
        print($message."\n");
    }

    private function insertOrUpdate($movieDetails)
    {
        $movieDetails["_id"] = $movieDetails["imdb_id"];
        $movieDetails["type"] = 'movie';

        $this->entityManager->insertOfUpdate($movieDetails, 'movies');
    }
}