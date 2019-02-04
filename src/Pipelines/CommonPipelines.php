<?php
/**
 * Date: 07/01/2019
 * Time: 2:36
 */

namespace App\Pipelines;


use App\Util\FindQueryBuilder;
use MongoDB\BSON\ObjectId;

class CommonPipelines extends AbstractPipeline
{
    public function limit(int $limit = 30, int $page = 1)
    {
        $skip = ($page- 1)*$limit;
        return [['$skip' => $skip], ['$limit'=> $limit]];
    }

    public function sort(string $property, int $mode = -1)
    {
        return [['$sort' => [$property => $mode]]];
    }
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


    public function rating(string $userId, string $idname = '_id')
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

    /**
     * @param bool $value
     * @return array|mixed
     */
    public function nondeleted()
    {
        return [['$match' => ['$or' =>  [['deleted' => ['$exists' => false]],['deleted' => false]]]]];
    }

    /**
     * @param array $opts
     * @param $userId
     * @return array|mixed
     */
    public function filter(array $opts)
    {
        unset($opts['mode']);
        unset($opts['user']);
        unset($opts['limit']);
        $page = ($opts["page"]) ?? 1;
        $limit = min(($opts["limit"]) ?? 30,100);
        $sort = ($opts["sort"]) ?? 'popularity';
        $opts['pipelines'] = $this->pipe([], ['sort', [$sort]], ['limit', [$limit, $page]]);
        $opts['pipe_order'] = ['$match' => 6, '$sort' => 4,'$project' => 3];
        $pipelines = ($this->filterPipeline($opts));
        $pipelines = $pipelines['pipeline'];
        return $pipelines;
    }

    public function filterPipeline(array $opts = [])
    {
        $qb = new FindQueryBuilder($opts);
        $pipeline = $qb->build();
        $options = ($opts['opts']) ?? [];
        $options+=['maxTimeMS' => 30000];

        return  ['pipeline' => $pipeline, 'opts' => $options];
    }
}