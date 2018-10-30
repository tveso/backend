<?php
/**
 * Date: 09/07/2018
 * Time: 21:39
 */

namespace App\Services\TheMovieDb;



use App\Services\CacheService;

class TmdbTvShowService
{

    /**
     * @var TheMovieDbClient
     */
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



    public function get($tmdb_id)
    {
        $params = [];
        $params["append_to_response"] = 'videos,images,credits,keywords,external_ids';
        $params["include_image_language"] = 'en';
        $response = $this->themoviedb->request("tv/$tmdb_id", $params);

        return json_decode($response,1);
    }


    public function latest()
    {
        $response = $this->themoviedb->request("tv/latest");

        return json_decode($response,1);
    }

        public function getReviews($id, int $page = 1)
        {
        $key = md5('reviewstv'.$id.$page);
        $result = $this->cacheService->getItem($key);
        if($this->cacheService->hasItem($key)) {
            return $result;
        }
        $result = $this->themoviedb->request("tv/$id/reviews", ['page' => $page, 'language' => 'en-US']);
        $result = json_decode($result, 1);
        $result = $result['results'];
        $this->cacheService->save($key, $result, 60*60*30);

        return $result;
        }


}