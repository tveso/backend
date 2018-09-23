<?php
/**
 * Date: 09/07/2018
 * Time: 21:39
 */

namespace App\Services\TheMovieDb;


use App\EntityManager;


class TmdbMovieService
{

    private $themoviedb;


    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(TheMovieDbClient $theMovieDb)
    {
        $this->themoviedb = $theMovieDb;
    }




    public function get($tmdb_id)
    {
        $params = [];
        $params["append_to_response"] = 'videos,images,credits,keywords,external_ids';
        $params["include_image_language"] = 'en';
        $response = $this->themoviedb->request("movie/$tmdb_id", $params);

        return json_decode($response,1);
    }

    public function latest()
    {
        $response = $this->themoviedb->request("movie/latest");

        return json_decode($response,1);
    }


}