<?php
/**
 * Date: 03/01/2019
 * Time: 21:12
 */

namespace App\Pipelines;


use MongoDB\BSON\ObjectId;

class FollowPipeline extends AbstractPipeline
{

    public function follow(string $userId, string $idname = '_id')
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

    public function filter($userId, $modes, $name)
    {
        return [
            ['$match' => ['user' => new ObjectId($userId), 'mode' => ['$in' => $modes]]],
            ['$sort' => ['updated_at' => -1]],
            ['$lookup' =>
                [
                    'from' => $name,
                    'localField' => 'show',
                    'foreignField' => '_id',
                    'as' => $name
                ]],
            ['$unwind' => ['path' => '$'.$name]],
            ['$addFields' => [$name.'updated_at' => '$updated_at']],
            ['$replaceRoot' => ['newRoot' => '$'.$name]],
        ];
    }
}