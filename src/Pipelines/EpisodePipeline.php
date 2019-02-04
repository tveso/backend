<?php
/**
 * Date: 02/01/2019
 * Time: 11:49
 */

namespace App\Pipelines;


class EpisodePipeline extends AbstractPipeline
{

    public function __construct()
    {
    }

    public function show()
    {
        $query = [];
        $query[] = ['$lookup' => ['from' => 'movies', 'localField' => 'show_id', 'foreignField' => 'id', 'as' => 'showDocument']];
        $query[] = ['$addFields' => [
            'showp' => [
                '$filter' => [
                    'input'=> '$showDocument',
                    'as' => 'item',
                    'cond' => ['$eq' => ['$$item.type', 'tvshow']]
                ]
            ]
        ]];
        $query[] = ['$unwind' => ['path' => '$showp', 'preserveNullAndEmptyArrays' => true]];
        $query[] = ['$addFields' => ['show' => ['name' => '$showp.name',
            'popularity' => '$showp.popularity','_id' => '$showp._id', 'id'=>'$showp.id', 'type' => '$showp.type',
            'poster_path' => '$showp.poster_path', 'rating' => '$showp.rating', "vote_average"=>'$showp.vote_average',
            "vote_count"=> '$showp.vote_count']]];
        $query[] = ['$addFields' => [ 'showDocument' => null, 'showp' => null]];

        return $query;
    }
}