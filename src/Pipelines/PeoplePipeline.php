<?php
/**
 * Date: 07/01/2019
 * Time: 3:19
 */

namespace App\Pipelines;


class PeoplePipeline extends AbstractPipeline
{

    public function project()
    {
        return [['$project'=>['name' => 1, 'profile_path'=>1, 'external_ids'=> 1,
        'birthday'=> 1,'gender'=>1, 'popularity'=> 1,'known_for_department'=> 1, 'place_of_birth'=> 1,"id" => 1]]];
    }
}