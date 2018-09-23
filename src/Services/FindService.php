<?php


namespace App\Services;


use App\EntityManager;
use App\Util\FindQueryBuilder;
use MongoDB\BSON\Regex;
class FindService
{

    /** @Inject()
     * @var EntityManager
     */
    private $entityManager;

    /**
     * FindService constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    public function all(array $opts = [])
    {
        $qb = new FindQueryBuilder($opts);
        $params = $qb->build();
        $search = $params['query'];
        $options = $params['options'];

        $collection = $this->entityManager->getCollection('movies');
        $searched = $collection->find($search,$options)->toArray();

        return  $searched;
    }

    public function search(string $string, $limit = 100, $page = 1)
    {
        $results = $this->patternSearch($string,$limit,$page);
        return $results;
    }



    public function patternSearch(string $string, $limit = 100, $page = 1)
    {
        $search = [];
        $options = [];
        $string = $this->prepareString($string);
        $regexBody = new Regex("$string",'im');
        $search['$or'][] = ["stitle"=> ['$regex' => $regexBody]];
        $options["sort"] = ["rating.numVotes" => -1];
        $options["projection"] = ['score' => ['$meta' => "textScore"], "seasons" =>0, "credits"=>0, "videos"=> 0, "images" =>0];
        $options["skip"] = $skip = ($page-1)*$limit;
        $options["limit"] = $limit;
        $collection = $this->entityManager->getCollection('movies');

        return $collection->find($search,$options)->toArray();
    }

    private function prepareString($string)
    {
        $chars = "',:#@|!¿?=)(/&%\$·`´*'-";
        $chars = str_split($chars);
        $replace= [["á","a"],["é","e"],["í","i"],["ó","o"],["ú","u"]];
        foreach ($chars as $c){
            $string = str_replace($c,"",$string);
        }
        foreach ($replace as $r){
            $string = str_replace($r[0],$r[1], $string);
        }

        return strtolower($string);
    }




}