<?php
/**
 * Date: 09/07/2018
 * Time: 21:39
 */

namespace App\Services\TheMovieDb;



class TmdbTvShowService
{

    /**
     * @var TheMovieDbClient
     */
    private $themoviedb;



    public function __construct(TheMovieDbClient $theMovieDb)
    {
        $this->themoviedb = $theMovieDb;
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



}