<?php
/**
 * Date: 15/08/2018
 * Time: 2:12
 */

namespace App\Jobs;


use App\EntityManager;

class UpdateSearchFieldJob
{


    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * UpdateSearchField constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function exec(array $query = ['$stitle'=> ['$exists' => [false]]])
    {
        $entities = $this->entityManager->find($query,'movies');
        foreach ($entities as $key=>$value){
           $this->updateEntity($value);
        }
    }

    public function updateEntity(array $entity)
    {
        if(isset($entity['primaryTitle'])){
            $stitle = $this->prepareString($entity['primaryTitle']);
        }
        if(isset($entity['original_title'])){
            $stitle = $this->prepareString($entity['original_title']);
        }
        if(isset($entity['original_name'])){
            $stitle = $this->prepareString($entity['original_name']);
        }
        $estitle = '';
        if(isset($entity['name'])){
            $estitle = $this->prepareString($entity['name']);
        }else if(isset($value['titles'])){
            $estitle = $this->prepareString($entity['titles']['ES']);
        }
        $title = "$stitle\n$estitle";
        $entity['stitle'] = $title;

        $this->entityManager->replace($entity,'movies');
        return $entity;
    }

    private function prepareString($string)
    {
        $chars = "',:#@|!¿?=)(/&%\$·`´*'-";
        $chars = str_split($chars);
        $replace= [["á","a"],["é","e"],["í","i"],["ó","o"],["ú","u"], ['ñ','n']];
        foreach ($chars as $c){
            $string = str_replace($c,"",$string);
        }
        foreach ($replace as $r){
            $string = str_replace($r[0],$r[1], $string);
        }

        return mb_convert_encoding(strtolower($string), "UTF-8");
    }
}