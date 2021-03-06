<?php
/**
 * Date: 15/08/2018
 * Time: 2:12
 */

namespace App\Jobs;


use App\EntityManager;
use MongoDB\BSON\Regex;
use MongoDB\Driver\Cursor;
use MongoDB\Model\BSONDocument;

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

    public function updateSearchFieldByLanguages()
    {
        $query = $this->entityManager->find(['$or'=>
            [['title' => ['$regex'=> new Regex('([áéíóúàèìòù]{1,})')]],
                ['name' => ['$regex'=> new Regex('([áéíóúàèìòù]{1,})')]]]],'movies');
        foreach ($query as $value){
            $this->updateEntity($value->getArrayCopy());
        }
    }

    public function updateEntity(array $entity, string $collection = 'movies')
    {
        $entity = $this->prepareEntity($entity);
        $this->entityManager->replace($entity,$collection);

        return $entity;
    }

    public function prepareEntity(array $entity)
    {
        $title = '';
        if($entity["type"] === 'movie') {
            $title = $entity['title'];
            if(isset($entity['original_title']) and $entity['title']!==$entity['original_title']){
                $secondTitle=$entity['original_title'];
            }
        }
        if($entity["type"] === 'tvshow') {
            $title = $entity['name'];
            if(isset($entity['original_name']) and $entity['name']!==$entity['original_name']){
                $secondTitle=$entity['original_name'];
            }
        }
        $title = $this->prepareString($title);
        $entity['search_title'] = [];
        for($i=1; $i<=strlen($title); $i++){
            $substr = utf8_encode(substr($title, 0, $i));
            $entity['search_title'][] = $substr;
        }
        if(isset($secondTitle)){
            $secondTitle = $this->prepareString($secondTitle);
            for($i=1; $i<=strlen($title); $i++){
                $substr = utf8_encode(substr($secondTitle, 0, $i));
                $entity['search_title'][] = $substr;
            }
        }

        return $entity;
    }

    public static function prepareString(string $string) : string
    {
        $string =mb_strtolower($string, 'UTF-8');
        $chars = "',:#@|!¿?=)(/&%\$·`´*'- .";
        $chars = str_split($chars);
        $replace= [["á","a"],["é","e"],["í","i"],["ó","o"],["ú","u"], ['ç','s'], ['ñ','n']];
        foreach ($chars as $c){
            $string = str_replace($c,"",$string);
        }
        foreach ($replace as $r){
            $string = str_replace($r[0],$r[1], $string);
        }

        return $string;
    }

}