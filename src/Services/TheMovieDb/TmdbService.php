<?php
/**
 * Date: 09/07/2018
 * Time: 21:39
 */

namespace App\Services\TheMovieDb;


use App\EntityManager;


class TmdbService
{

    private $themoviedb;


    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var TmdbMovieService
     */
    private $tmdbMovieService;

    public function __construct(TheMovieDbClient $theMovieDb, TmdbMovieService $tmdbMovieService)
    {
        $this->themoviedb = $theMovieDb;
        $this->tmdbMovieService = $tmdbMovieService;
    }




    public function findByImdb(string $imdb_id)
    {
        $params = ['external_source' => 'imdb_id'];
        $response = $this->themoviedb->request("find/$imdb_id", $params);

        return json_decode($response,1);
    }

    public function findAndGetDetails(string $imdb_id) : array
    {
        $data = $this->findByImdb($imdb_id);
        $result = [];
        foreach ($data as $key=>$value){
            if(!empty($value)){
                $result = $this->tmdbMovieService->get($value[0]["id"]);
            }
        }

        return $result;
    }


}