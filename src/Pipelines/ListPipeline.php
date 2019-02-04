<?php
/**
 * Date: 02/01/2019
 * Time: 11:49
 */

namespace App\Pipelines;


class ListPipeline extends AbstractPipeline
{
    public function user()
    {
        $query[] = ['$lookup' => [
            'from' => 'users',
            'foreignField' => '_id',
            'localField' => 'user',
            'as' => 'user'
        ]];
        $query[] = ['$unwind' => ['path' => '$user', 'preserveNullAndEmptyArrays' => true]];
        $query[] = ['$project' => ['user' => ['_id' => 1,'username' => 1,'avatar' => 1,'roles' => 1], '_id' => 1, 'description' => 1,
            'episodes' => 1, 'tvshows' => 1, 'movies' => 1, 'people' => 1,  'created_at' => 1,'updated_at' => 1,'title' => 1, 'type' => 1, 'stats' => 1]];
        return $query;
    }

    public function movies()
    {
        $query[] = ['$addFields' => ['tvshows' => ['$slice' => ['$tvshows', 0, 100]]]];
        $query[] = ['$lookup' => [
            'from' => 'movies',
            'localField' => 'movies',
            'foreignField' => '_id',
            'as' => 'movies'
        ]];
        return $query;
    }

    public function tvshows()
    {
        $query[] = ['$addFields' => ['tvshows' => ['$slice' => ['$tvshows', 0, 100]]]];
        $query[] = ['$lookup' => [
            'from' => 'movies',
            'localField' => 'tvshows',
            'foreignField' => '_id',
            'as' => 'tvshows'
        ]];
        return $query;
    }

    public function people()
    {
        $query[] = ['$addFields' => ['people' => ['$slice' => ['$people', 0, 100]]]];
        $query[] = ['$lookup' => [
            'from' => 'people',
            'localField' => 'people',
            'foreignField' => '_id',
            'as' => 'people'
        ]];
        return $query;
    }
    public function episodes()
    {
        $query[] = ['$addFields' => ['episodes' => ['$slice' => ['$episodes', 0, 100]]]];
        $query[] = ['$lookup' => [
            'from' => 'episodes',
            'localField' => 'episodes',
            'foreignField' => '_id',
            'as' => 'episodes'
        ]];
        return $query;
    }
    public function resourcesCount()
    {
        return
        [
            [
                '$addFields' => [
                    'stats' => ['count' => ['episodes' => ['$size' => '$episodes'], 'movies' => ['$size' => '$movies'],
                        'tvshows' => ['$size' => '$tvshows'], 'people' => ['$size' => '$people']]]
                ]
            ]
        ];
    }

}