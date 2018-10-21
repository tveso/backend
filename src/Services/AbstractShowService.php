<?php
/**
 * Date: 06/10/2018
 * Time: 22:57
 */

namespace App\Services;


use App\Util\PipelineBuilder;
use MongoDB\BSON\ObjectId;

abstract class AbstractShowService
{

    public function addUserRatingPipeLine(string $userId)
    {
        return [['$lookup' => [
            'from' => 'ratings',
            'let' => ['mid'=> '$_id'],
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
        ]],  ['$unwind'=>[
            'path'=> '$userRate',
            'preserveNullAndEmptyArrays' => true
        ]]];
    }

    public function addShowsPipeLines(string $userId)
    {
        return array_merge(
            $this->getProjection(),
            $this->addUserRatingPipeLine($userId),
            $this->addFollowPipeLine($userId));
    }


    public function addFollowPipeLine(string $userId)
    {
        return [['$lookup' => [
            'from' => 'follows',
            'let' => ['mid'=> '$_id'],
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
            'rank' => 1, 'popularity'=> 1, 'rating' => 1];
    }

    public function addLimitPipeline(int $limit = 30, int $page = 1)
    {
        $skip = ($page- 1)*$limit;
        return [['$skip'=> $skip], ['$limit' => $limit]];
    }

    public function addSortPipeline(string $property)
    {
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