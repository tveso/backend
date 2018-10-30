<?php


namespace App\Services;


use App\EntityManager;
use App\Services\TheMovieDb\TheMovieDbClient;
use App\Util\PipelineBuilder\PipelineBuilder;

class ConfigService implements Service
{

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TheMovieDbClient
     */
    private $theMovieDbClient;
    /**
     * @var FindService
     */
    private $findService;

    /**
     * ConfigService constructor.
     * @param EntityManager $entityManager
     * @param TheMovieDbClient $theMovieDbClient
     * @param FindService $findService
     */
    public function __construct(EntityManager $entityManager, TheMovieDbClient $theMovieDbClient, FindService $findService)
    {
        $this->entityManager = $entityManager;
        $this->theMovieDbClient = $theMovieDbClient;
        $this->findService = $findService;
    }


    public function getGenres()
    {
        $pb = new PipelineBuilder();
        $pb->addPipe('$match', []);

        return $this->findService->allCached([], 'genres', 60*60*24*30);
    }

    public function updateGenres()
    {
        $genresMovie = $this->theMovieDbClient->request('genre/movie/list');
        $genresMovie = json_decode($genresMovie, 1 );
        $genresTv = $this->theMovieDbClient->request('genre/tv/list');
        $genresTv = json_decode($genresTv, 1);
        foreach ($genresTv['genres'] as &$value){
            $value["type"] = "tvshow";
        }
        foreach ($genresMovie['genres'] as &$val){
            $val["type"] = "movie";
        }
        $genres = array_merge($genresMovie['genres'],$genresTv['genres']);
        $this->entityManager->insertMany($genres, 'genres');
    }
}