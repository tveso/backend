<?php

namespace App\Jobs\UpdateTmdbJobs;


use App\EntityManager;
use App\Services\TheMovieDb\TheMovieDbClient;
use App\Services\TheMovieDb\TmdbMovieService;
use MongoDB\BSON\ObjectId;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraints\Date;

class UpdateNowPlayingMoviesJob
{


    /**
     * @var TheMovieDbClient
     */
    private $theMovieDbClient;

    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var TmdbMovieService
     */
    private $tmdbMovieService;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UpdateNowPlayingMoviesJob constructor.
     * @param TheMovieDbClient $theMovieDbClient
     * @param EntityManager $entityManager
     */
    public function __construct(TheMovieDbClient $theMovieDbClient, EntityManager $entityManager,
                                TmdbMovieService $tmdbMovieService, LoggerInterface $logger)
    {
        $this->theMovieDbClient = $theMovieDbClient;
        $this->entityManager = $entityManager;
        $this->tmdbMovieService = $tmdbMovieService;
        $this->logger = $logger;
    }

    public function update()
    {
        $res = $this->getIdsNowPlaying();
        $ids = $res["ids"];
        $data = $res["data"];
        try{
            $updated = $this->updatedNotPlayingAnymore($ids);
            $news = $this->updateNowPlaying($ids, $data);
            echo "{$updated->getModifiedCount()} Películas quitadas de carteleras\n";
            echo "{$news} Películas añadidas a carteleras\n";
        } catch (\Exception $e){
            $this->logger->error($e);
        }
    }




    public function getIdsNowPlaying()
    {
        $ids = [];
        $data = [];
        $page = 1;
        $totalPages = 2;
        while($page<=$totalPages){
            $params = ['page'=>$page,'region'=> 'es'];
            $resource = $this->theMovieDbClient->request('movie/now_playing', $params);
            $resource = json_decode($resource,1);
            if(sizeof($resource['results'])<1){
                break;
            }
            $totalPages = $resource['total_pages'];
            echo "Pagina $page de $totalPages\n";
            $ids = $ids +array_map(function($a){
                return intval($a["id"]);
            }, $resource['results']);
            $page+=1;
            $data = $data + $resource['results'];
        }

        return ["ids"=> $ids, "data" => $data];
    }

    public function updatedNotPlayingAnymore(array $ids)
    {
        $body = ['$set'=> ['now_playing'=> false,'updated_at'=> (new \DateTime())->format('Y-m-d')]];
        return $this->entityManager->update(['id'=> ['$nin'=> $ids], 'now_playing'=>true], $body,'movies');
    }

    private function updateNowPlaying(array $ids, array $data)
    {
        $modifies = 0;
        foreach ($data as $movie){
            $find = $this->entityManager->find(["id"=> $movie["id"]],'movies')->toArray();
            if(empty($find)){
                $data = $this->tmdbMovieService->get($movie["id"]);
                $data["_id"] = $data["imdb_id"];
                $imdbId = $data["imdb_id"];
                $data = $data +  ['now_playing'=> true,'updated_at'=> (new \DateTime())->format('Y-m-d')];
                $data = ['$set' => $data];
                $this->entityManager->update(["_id"=> $imdbId], $data, 'movies', ['upsert'=> true]);
                continue;
            }
            $dataUpdated = $movie + ['now_playing'=> true,'updated_at'=> (new \DateTime())->format('Y-m-d')];
            $body = ['$set'=> $dataUpdated];
            $updated = $this->entityManager->update(['id'=> $dataUpdated["id"]], $body,'movies');
            $modifies+= intval($updated->getModifiedCount());
        }

        return $modifies;
    }


}