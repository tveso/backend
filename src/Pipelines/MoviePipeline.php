<?php
/**
 * Date: 07/01/2019
 * Time: 2:49
 */

namespace App\Pipelines;


class MoviePipeline extends AbstractPipeline
{

    public function project()
    {
        return [['$project' => ["title"=>1, "name"=>1, "original_title"=> 1,"original_name"=>1, "poster_path"=>1,
            "backdrop_path"=>1, "ratings"=>1, "vote_average"=>1, "vote_count"=> 1, 'type' => 1, 'userRate' => 1,
            "year"=> 1, "release_date"=> 1, "first_air_date" => 1, 'userFollow' => 1, "next_episode_to_air"=> 1,
            'rank' => 1, 'popularity'=> 1, 'rating' => 1, 'updated_at' => 1]]];
    }
}