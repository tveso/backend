<?php
/**
 * Date: 09/07/2018
 * Time: 21:39
 */

namespace App\Services\TheMovieDb;


use App\EntityManager;
use App\Services\CacheService;


class TmdbEpisodeService
{

    private $themoviedb;


    /**
     * @var CacheService
     */
    private $cacheService;

    public function __construct(TheMovieDbClient $theMovieDb, CacheService $cacheService)
    {
        $this->themoviedb = $theMovieDb;
        $this->cacheService = $cacheService;
    }



    public function credits($showId, $seasonNumber, $epiosodeNumber)
    {
        $params = [];
        $response = $this->themoviedb->request("tv/$showId/season/$seasonNumber/episode/$epiosodeNumber/credits", $params);

        return json_decode($response,1);
    }



}