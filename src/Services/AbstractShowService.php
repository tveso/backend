<?php
/**
 * Date: 06/10/2018
 * Time: 22:57
 */

namespace App\Services;


use App\Util\PipelineBuilder;
use MongoDB\BSON\ObjectId;

abstract class AbstractShowService implements Service
{

    public function addUserRatingPipeLine(string $userId, string $idname = '_id')
    {
        return [['$lookup' => [
            'from' => 'ratings',
            'let' => ['mid'=> '$'.$idname],
            'pipeline' => [
                ['$match' => [
                    '$expr' => [
                        '$and'=> [
                            ['$eq' => ['$show', '$$mid']],
                            ['$eq' => ['$user', new ObjectId($userId)]]
                        ]
                    ]
                ]]
            ],
            'as' => 'userRate'
        ]],
            ['$unwind'=>[
            'path'=> '$userRate',
            'preserveNullAndEmptyArrays' => true
        ]]
        ];
    }

    public function addShowsPipeLines(string $userId)
    {
        return array_merge(
            $this->getProjection(),
            $this->addUserRatingPipeLine($userId),
            $this->addFollowPipeLine($userId));
    }


    public function addFollowPipeLine(string $userId, string $idname = '_id')
    {
        return [['$lookup' => [
            'from' => 'follows',
            'let' => ['mid'=> '$'.$idname],
            'pipeline' => [
                ['$match' => [
                    '$expr' => [
                        '$and'=> [
                            ['$eq' => ['$show', '$$mid']],
                            ['$eq' => ['$user', new ObjectId($userId)]]
                        ]
                    ]
                ]]
            ],
            'as' => 'userFollow'
        ]],
            ['$unwind'=>[
                'path'=> '$userFollow',
                'preserveNullAndEmptyArrays' => true
            ]]];
    }


    public function getSimpleProject()
    {
        return ["title"=>1, "name"=>1, "original_title"=> 1,"original_name"=>1, "poster_path"=>1,
            "backdrop_path"=>1, "ratings"=>1, "vote_average"=>1, "vote_count"=> 1, 'type' => 1, 'userRate' => 1,
            "year"=> 1, "release_date"=> 1, "first_air_date" => 1, 'userFollow' => 1, "next_episode_to_air"=> 1,
            'rank' => 1, 'popularity'=> 1, 'rating' => 1, 'updated_at' => 1];
    }

    public function addLimitPipeline(int $limit = 30, int $page = 1)
    {
        $skip = ($page- 1)*$limit;
        return [['$skip' => $skip], ['$limit'=> $limit]];
    }

    public function addEpisodeShowName()
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

    public function addSortPipeline(?string $property)
    {
        if(is_null($property)) {
            return [[]];
        }
        return [['$sort'=> [$property => -1]]];
    }

    public function getProjection()
    {
        return [['$project'=> $this->getSimpleProject()]];
    }

    public function merge(...$pipelines)
    {
        return array_merge($pipelines);
    }

}