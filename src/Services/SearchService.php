<?php
/**
 * Date: 19/10/2018
 * Time: 1:18
 */

namespace App\Services;


use App\EntityManager;
use App\Jobs\UpdateSearchFieldJob;
use App\Util\PipelineBuilder\PipelineBuilder;
use MongoDB\BSON\Regex;
use Symfony\Component\Stopwatch\Stopwatch;

class SearchService extends AbstractShowService
{

    /**
     * @var FindService
     */
    private $findService;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var CacheService
     */
    private $cacheService;

    /**
     * SearchService constructor.
     * @param FindService $findService
     * @param EntityManager $entityManager
     * @param CacheService $cacheService
     */
    public function __construct(FindService $findService, EntityManager $entityManager, CacheService $cacheService)
    {

        $this->findService = $findService;
        $this->entityManager = $entityManager;
        $this->cacheService = $cacheService;
    }
    public function search(string $string, $limit = 100, $page = 1, $advance = false)
    {
        $cacheKey = md5($string.$limit.$page.$advance);
        if ($this->cacheService->hasItem($cacheKey)){
            return $this->cacheService->getItem($cacheKey);
        }
        $results = $this->textSearch($string, $limit, $page);
        if($advance === true){
            $peopleResults = $this->searchPeople($string, $limit, $page);
            $results = array_merge($results, $peopleResults);
        }
        $this->cacheService->save($cacheKey, $results, 60*60*24);
        return $results;
    }



    public function patternSearch(string $string, $limit = 100, $page = 1, string $collection = 'movies')
    {
        $options = [];
        $string = UpdateSearchFieldJob::prepareString($string);
        $regexBody = new Regex("^$string",'im');
        $search = ["stitle"=> ['$regex' => $regexBody]];
        $options["sort"] = ["popularity" => -1, "vote_count"=> -1];
        $options["pipelines"]['$project'] = $this->getSimpleProject();
        $options["skip"] = $skip = ($page-1)*$limit;
        $options["limit"] = $limit;
        $collection = $this->entityManager->getCollection($collection);

        return $collection->find($search,$options)->toArray();
    }


    /**
     * @param $string
     * @param $limit
     * @param $page
     * @return array
     */
    private function textSearch($string, $limit, $page)
    {
        $string =  UpdateSearchFieldJob::prepareString($string);
        $string = "\"$string\"";
        $pipelineBuilder = new PipelineBuilder();
        $match = $pipelineBuilder->addPipe('$match');
        $match->addFields(['$text' => ['$search'=>$string]]);
        $pipelineBuilder->addPipe('$sort') ->addFields(["popularity" => -1, "vote_count"=> -1]);
        $pipelineBuilder->addPipe('$skip',($page-1)*$limit);
        $pipelineBuilder->addPipe('$limit', $limit);
        $pipelineBuilder->addPipe('$project')->addFields($this->getSimpleProject()+['score' => ['$meta' => "textScore"]]);
        $query['pipelines'] = $pipelineBuilder->getQuery();
        $query['opts']=['allowDiskUsage' => 1];
        return $this->findService->all($query);
    }

    private function searchPeople($string, $limit, $page)
    {
        $pipelineBuilder = new PipelineBuilder();
        $regexBody = new Regex("$string",'i');
        $match = $pipelineBuilder->addPipe('$match');
        $match->addFields(['name' => ['$regex'=>$regexBody]]);
        $pipelineBuilder->addPipe('$sort') ->addFields(['popularity'=>-1]);
        $pipelineBuilder->addPipe('$skip',($page-1)*$limit);
        $pipelineBuilder->addPipe('$limit', $limit);
        $pipelineBuilder->addPipe('$project')->addFields(['name' => 1,
            'known_for_department' => 1, 'gender'=> 1, 'profile_path'=>1,'id'=> 1,'popularity'=>1, 'type' => 1]);
        $query['pipelines'] = $pipelineBuilder->getQuery();
        return $this->findService->all($query, 'people');
    }

}