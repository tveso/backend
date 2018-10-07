<?php
/**
 * Date: 06/10/2018
 * Time: 22:57
 */

namespace App\Services;


use MongoDB\BSON\ObjectId;

abstract class AbstractShowService
{

    public function addUserRatingPipeLine(string $userId)
    {
        return ['$lookup' => [
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
        ]];
    }
}