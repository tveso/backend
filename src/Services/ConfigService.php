<?php


namespace App\Services;


use App\EntityManager;
use App\Services\TheMovieDb\TheMovieDbClient;

class ConfigService
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
     * ConfigService constructor.
     * @param EntityManager $entityManager
     * @param TheMovieDbClient $theMovieDbClient
     */
    public function __construct(EntityManager $entityManager, TheMovieDbClient $theMovieDbClient)
    {
        $this->entityManager = $entityManager;
        $this->theMovieDbClient = $theMovieDbClient;
    }


    public function getGenres()
    {
        return $this->entityManager->find([], 'genres')->toArray();
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