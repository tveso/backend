<?php
/**
 * Date: 17/10/2018
 * Time: 3:04
 */

namespace App\Util;


class RecommendatorQueryBuilder
{

    private $match;
    private $project;
    private $fields;
    private $query;

    public function __construct(array $match, array $project)
    {
        $this->match = $match;
        $this->project = $project;
        $this->query = [0 => ['$match' => $match], 1 => ['$project'=> $project]];
    }


    public function addField(string $fieldName)
    {
        $this->fields[$fieldName] = [];

        return $this;
    }

    public function addVar(string $fieldName, string $varName, string $path, array $data = [], float $multiplier = 1)
    {
        $this->fields[$fieldName][$varName] = ['multiplier' => $multiplier, 'data' => $data, 'path'=> $path];

        return $this;
    }

    public function build()
    {
        foreach ($this->fields as $key=>$value) {
            foreach ($value as $i=>$j){
                $this->query[1]['$project'][$key]['$let']['vars']["${i}equal"]['$size'] =
                ['$setIntersection' => [$j['path'], $j['data']]];
                $this->query[1]['$project'][$key]['$let']['in']['$sum'][] = ['$multiply' => ['$$'."${i}equal", $j['multiplier']]];
            }
        }

        return $this->query;
    }

    public function addToMatch(array $data)
    {
        $this->query[0]['$match'] = array_merge($this->query[0]['$match'], $data);

        return $this;
    }

}