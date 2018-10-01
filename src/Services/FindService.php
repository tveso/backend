<?php


namespace App\Services;


use App\EntityManager;
use App\Jobs\UpdateSearchFieldJob;
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
        $results = $this->textSearch($string, $limit, $page);
        if(empty($results)){
            $results = $this->patternSearch($string,$limit,$page);
        }
        return $results;
    }



    public function patternSearch(string $string, $limit = 100, $page = 1)
    {
        $options = [];
        $string = UpdateSearchFieldJob::prepareString($string);
        $regexBody = new Regex("^$string",'im');
        $search = ["stitle"=> ['$regex' => $regexBody]];
        $options["sort"] = ["popularity" => -1, "rating.numVotes" => -1];
        $options["projection"] = [ "seasons" =>0, "credits"=>0, "videos"=> 0, "images" =>0];
        $options["skip"] = $skip = ($page-1)*$limit;
        $options["limit"] = $limit;
        $collection = $this->entityManager->getCollection('movies');

        return $collection->find($search,$options)->toArray();
    }



    private function textSearch($string, $limit, $page)
    {
        $options = [];
        $string =  UpdateSearchFieldJob::prepareString($string);
        $string = "\"$string\"";
        $search = ['$text' => ['$search'=>$string]];
        $options["sort"] = ["textScore" => -1, "rating.numVotes" => -1];
        $options["projection"] = ['score' => ['$meta' => "textScore"], "seasons" =>0, "credits"=>0, "videos"=> 0, "images" =>0, "search_title" => 0];
        $options["skip"] = $skip = ($page-1)*$limit;
        $options["limit"] = $limit;
        $collection = $this->entityManager->getCollection('movies');

        return $collection->find($search,$options)->toArray();
    }


}