<?php
/**
 * Date: 13/08/2018
 * Time: 23:48
 */

namespace App\Util;



use MongoDB\BSON\Regex;

class FindQueryBuilder
{


    /**
     * @var array
     */
    private $params;
    /**
     * @var array
     */
    private $executors = ['genres' => 'getGenres',
        'maxseasons'=> 'getMaxSeasons',
        'year'=> 'getYear',
        'yearmax' =>'getMaxYear',
        'yearmin' => 'getMinYear',
        'dateFilter' => 'getDateFilter',
        'dateEpisode' => 'getDateEpisode',
        'famous' => 'getFamous',
        'duration' => 'getDuration',
        'pipelines' => 'addPipelines',
        'sort'=>'addSortPipeline',
        'page' => 'addPage',
        'limit' => null,
        'gender' => 'inArray',
        'place_of_birth' => 'setPlaceOfBirth'
    ];
    /**
     * @var PipelineBuilder\PipelineBuilder
     */
    private $pipelineBuilder;

    public function __construct(array $params)
    {
        $this->params = $params;
        $this->pipelineBuilder = new PipelineBuilder\PipelineBuilder();
    }

    public function build()
    {
        foreach ($this->params as $key=>$value){
            if(in_array($key, ['opts', 'pipe_order'])) continue;
            if(isset($this->executors[$key])){
                $executor = $this->executors[$key];
                if(method_exists($this, $executor)){
                    $this->{$executor}($value, $key);
                } else {
                    continue;
                }
            }else{
                $this->pipelineBuilder->getPipe('$match')->setValue([$key=>$value]);
            }
        }
        if(isset($this->params['pipe_order'])){
            $this->pipelineBuilder->order($this->params['pipe_order']);

            return $this->pipelineBuilder->getQuery();
        }
        $this->pipelineBuilder->order(['$match'=> 6, '$group' => 5, '$project' => 4, '$sort'=> 3, '$skip'=> -1, '$limit' => -2]);

        return $this->pipelineBuilder->getQuery();
    }

    /**
     * @param $value
     * @param $key
     * @throws \Exception
     */
    private function inArray($value, $key)
    {
        if($value!== ''){
            if(is_string($value)){
                $value = explode(",", $value);
                $value = array_map(function($a) {
                    return intval($a);
                }, $value);
            }
            $mathpipe = $this->pipelineBuilder->getPipe('$match');
            $mathpipe->setValue(['gender' => ['$in' => $value]]);
        }
    }


    private function getGenres($value)
    {
        $result = [];
        $genres = $value;
        if($genres!== ''){
            if(is_string($genres)){
                $genres = explode(",", $genres);
            }
            $result['genres']['$all'] = [];
            foreach ($genres as $g){
                $result['genres']['$all'][] = ['$elemMatch'=>["id"=>intval($g)]];
            }
        }
        $matchPipe = $this->pipelineBuilder->getPipe('$match', []);
        $matchPipe->setValue($result);
    }

    private function getMaxSeasons($value)
    {
        $result = [];
        $maxseasons =  $value;
        $result['seasons']['$size'] = intval($maxseasons);

        $matchPipe = $this->pipelineBuilder->getPipe('$match', []);
        $matchPipe->setValue($result);
    }

    private function getYear($value)
    {
        $result = [];
        $year = $value;
        $year = explode(",", $year);
        $result['year']['$in'] = $year;
        $matchPipe = $this->pipelineBuilder->getPipe('$match', []);
        $matchPipe->setValue($result);
    }

    private function getMinYear($value)
    {
        $result = [];
        $minyear = $value;
        $result['$gte'] = "$minyear";

        $matchPipe = $this->pipelineBuilder->getPipe('$match');
        $value = $matchPipe->getQuery();
        $value['$and'][]['year'] = $result;
        $matchPipe->setValue($value);
    }

    /**
     * @param $value
     * @throws \Exception
     */
    private function getMaxYear($value)
    {
        $result = [];
        $maxyear = $value;
        $result['$lte'] = "$maxyear";

        $matchPipe = $this->pipelineBuilder->getPipe('$match');
        $value = $matchPipe->getQuery();
        $value['$and'][]['year'] = $result;
        $matchPipe->setValue($value);
    }

    private function getFamous($value)
    {
        $result = [];
        $famous = $value;
        $famous = ($famous === "false" or $famous === false) ? false : true;
        if($famous === true){
            $result['rating.numVotes']['$gt'] =  20000;
        }

        $matchPipe = $this->pipelineBuilder->getPipe('$match', []);
        $matchPipe->setValue($result);
    }

    private function setPlaceOfBirth($value)
    {
        $this->pipelineBuilder->getPipe('$match')
            ->setValue(["place_of_birth" => ['$regex' => new Regex("^$value", "i")]]);
    }

    private function getDuration($value)
    {
        $result = [];
        $duration = $value;
        $matches = $this->quantityExpresion($duration);
        if(!empty($matches)){
            foreach ($matches["symbol"] as $key=>$value){
                if($value==='') continue;
                $operator = $this->getOperator($value);
                $number = intval($matches['number'][$key]);
                $result['duration'][$operator] = $number;
            }
        }


        $matchPipe = $this->pipelineBuilder->getPipe('$match', []);
        $matchPipe->setValue($result);
    }


    private function getOperator(string $symbol)
    {
        switch ($symbol){
            case '>':
                return '$gt';
            case '>=':
                return '$gte';
            case '<':
                return '$lt';
            case '<=':
                return '$lte';
            default:
                return '$gt';
        }
    }

    public function addPipelines($value)
    {
        $counter = [];
        foreach ($value as $key=>$j){
            foreach ($j as $pipeName=>$val){
                if(isset($counter[$pipeName])){
                    $this->pipelineBuilder->addPipe($pipeName, $val);
                    continue;
                }
                $pipe = $this->pipelineBuilder->getPipe($pipeName);
                $pipe->setValue($val);
                $counter[$pipeName] = 1;
            }
        }
    }

    public function addSortPipeline($value)
    {
        $this->pipelineBuilder->addPipe('$sort')->addField($value, -1);
    }

    private function getDateFilter($value)
    {
        $result = [];
        if(!isset($this->params['type'])){
           return;
        }
        $dateFilter = $value;
        $matches = $this->quantityExpresion($dateFilter);
        if(!empty($matches)){
            foreach ($matches["symbol"] as $key=>$value){
                if($value==='') continue;
                $operator = $this->getOperator($value);
                $date = $matches['number'][$key];
                $field = ($this->params['type']==='movie') ? 'release_date' : 'first_air_date';
                $result[$field][$operator] = $date;
            }
            }

        $matchPipe = $this->pipelineBuilder->getPipe('$match', []);
        $matchPipe->setValue($result);
    }


    private function getDateEpisode($value)
    {
        $result = [];
        if(!isset($this->params['type'])){
            return;
        }
        $duration = $value;
        $matches = $this->quantityExpresion($duration);
        if(!empty($matches)){
            foreach ($matches["symbol"] as $key=>$value){
                if($value==='') continue;
                $operator = $this->getOperator($value);
                $date = $matches['number'][$key];
                $field = 'next_episode_to_air.air_date';
                $result[$field][$operator] = $date;
            }
        }

        $matchPipe = $this->pipelineBuilder->getPipe('$match', []);
        $matchPipe->setValue($result);
    }

    private function addPage($value)
    {
        $limit = min(intval(($this->params['limit']) ?? 30),100);
        $page = max(intval($value),1);
        $skip = $limit*($page-1);
        $this->pipelineBuilder->addPipe('$skip', $skip);
        $this->pipelineBuilder->addPipe('$limit', $limit);
    }

    private function quantityExpresion(string $expr)
    {
        $regex = '/(?<symbol>[<>=]*)(?<number>[0-9\-]*);?/';
        if(preg_match_all($regex,$expr, $matches)){
            return $matches;
        }

        return [];
    }

}