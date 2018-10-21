<?php
/**
 * Date: 09/07/2018
 * Time: 21:39
 */

namespace App\Services\TheMovieDb;



class TmdbPeopleService
{

    /**
     * @var TheMovieDbClient
     */
    private $themoviedb;

    /**
     * TmdbPeopleService constructor.
     * @param TheMovieDbClient $theMovieDb
     */
    public function __construct(TheMovieDbClient $theMovieDb)
    {
        $this->themoviedb = $theMovieDb;
    }



    public function get(string $tmdb_id)
    {
        $params = [];
        $params["append_to_response"] = 'images,external_ids,tagged_images';
        $params["include_image_language"] = 'en';
        $response = $this->themoviedb->request("person/$tmdb_id", $params);
        return json_decode($response,1);
    }


    public function latest()
    {
        $response = $this->themoviedb->request("person/latest");

        return json_decode($response,1);
    }



}