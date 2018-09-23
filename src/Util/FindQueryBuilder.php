<?php
/**
 * Date: 13/08/2018
 * Time: 23:48
 */

namespace App\Util;


use MongoDB\BSON\UTCDateTime;

class FindQueryBuilder
{


    private $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function build()
    {
        $options = $this->buildOptions();
        $query = [];
        $query = $query + $this->getGenres() + $this->getMaxSeasons() + $this->getYear() +$this->getMinMaxYear() +
            $this->getFamous() + $this->getDuration() + $this->getType()+$this->getStatus()+$this->getDateFilter()+
        $this->getDateEpisode();

        return ['query'=> $query, 'options' => $options];
    }

    private function buildOptions()
    {
        $options = [];
        $page = intval($this->params["page"] ?? 1);
        $page = ($page>0) ? $page : 1;
        $limit = intval($this->params["limit"] ?? 30);
        $sortType =  $this->params["sort"] ?? 'popularity';
        $options["sort"] = [$sortType => -1];
        $options["skip"] = ($page-1)*$limit;
        $options["limit"] = $limit;
        $options["projection"] = $this->params["projection"] ?? ["seasons" =>0, "credits"=>0, "videos"=> 0, "images" =>0];


        return $options;

    }

    private function getGenres()
    {
        $result = [];
        if(array_key_exists('genres', $this->params)){
            $genres = $this->params['genres'];
            if($genres!== ''){
                if(is_string($genres)){
                    $genres = explode(",", $genres);
                }
                $result['genres']['$all'] = [];
                foreach ($genres as $g){
                    $result['genres']['$all'][] = ['$elemMatch'=>["id"=>intval($g)]];
                }
            }
        }

        return $result;
    }

    private function getMaxSeasons()
    {
        $result = [];
        if(array_key_exists('maxseasons', $this->params)){
            $maxseasons = $this->params['maxseasons'];
            $result['seasons']['$size'] = intval($maxseasons);
        }

        return $result;
    }

    private function getYear()
    {
        $result = [];
        if(array_key_exists('year', $this->params)){
            $year = $this->params['year'];
            $year = explode(",", $year);
            $result['year']['$in'] = $year;
        }

        return $result;
    }

    private function getMinMaxYear()
    {
        $result = [];
        if(array_key_exists('yearmax', $this->params)){
            $maxyear = $this->params['yearmax'];
            $result['year']['$lte'] = "$maxyear";
        }
        if(array_key_exists('yearmin', $this->params)){
            $minyear = $this->params['yearmin'];
            $result['year']['$gte'] = "$minyear";
        }

        return $result;
    }

    private function getFamous()
    {
        $result = [];
        $famous = $this->params["famous"] ?? false;
        $famous = ($famous === "false" or $famous === false) ? false : true;
        if($famous === true){
            $result['rating.numVotes']['$gt'] =  20000;
        }

        return $result;
    }

    private function getDuration()
    {
        $result = [];
        if(array_key_exists('duration', $this->params)){
            $duration = $this->params['duration'];
            $regex = '/(?<symbol>[<>=]*)(?<number>[0-9]*);?/';
            if(preg_match_all($regex,$duration, $matches)){
                foreach ($matches["symbol"] as $key=>$value){
                    if($value==='') continue;
                    $operator = $this->getOperator($value);
                    $number = intval($matches['number'][$key]);
                    $result['duration'][$operator] = $number;
                }
            }
        }

        return $result;
    }

    private function getType()
    {
        return $this->getProperty('type');
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

    private function getStatus()
    {
        return $this->getProperty('status');
    }

    private function getProperty(string $prop)
    {
        $result = [];

        if(isset($this->params[$prop])){
            $result[$prop] = $this->params[$prop];
        }

        return $result;
    }

    private function getDateFilter()
    {
        $result = [];
        if(!isset($this->params['type'])){
            return [];
        }
        if(array_key_exists('dateFilter', $this->params)){
            $duration = $this->params['dateFilter'];
            $regex = '/(?<symbol>[<>=]*)(?<number>.*);?/';
            if(preg_match_all($regex,$duration, $matches)){
                foreach ($matches["symbol"] as $key=>$value){
                    if($value==='') continue;
                    $operator = $this->getOperator($value);
                    $date = $matches['number'][$key];
                    $field = ($this->params['type']==='movie') ? 'release_date' : 'first_air_date';
                    $result[$field][$operator] = $date;
                }
            }
        }

        return $result;
    }


    private function getDateEpisode()
    {
        $result = [];
        if(!isset($this->params['type'])){
            return [];
        }
        if(array_key_exists('dateEpisode', $this->params)){
            $duration = $this->params['dateEpisode'];
            $regex = '/(?<symbol>[<>=]*)(?<number>[0-9\-]*);?/';
            if(preg_match_all($regex,$duration, $matches)){
                foreach ($matches["symbol"] as $key=>$value){
                    if($value==='') continue;
                    $operator = $this->getOperator($value);
                    $date = $matches['number'][$key];
                    $field = 'next_episode_to_air.air_date';
                    $result[$field][$operator] = $date;
                }
            }
        }

        return $result;
    }
}