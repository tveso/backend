<?php
/**
 * Date: 17/10/2018
 * Time: 16:21
 */

namespace App\Services;


use App\EntityManager;
use App\Jobs\UpdateSearchFieldJob;
use App\Util\PipelineBuilder\PipelineBuilder;
use MongoDB\BSON\Regex;

class PeopleService
{

    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var FindService
     */
    private $findService;

    public function __construct(EntityManager $entityManager, FindService $findService)
    {

        $this->entityManager = $entityManager;
        $this->findService = $findService;
    }


    public function all(array $opts = [])
    {
        $page = ($opts["page"]) ?? 1;
        $limit = min(($opts["limit"]) ?? 30,100);
        $skip = ($page- 1)*$limit;
        $sort = ($opts["sort"]) ?? 'popularity';
        $pipelineBuilder = new PipelineBuilder();
        $pipelineBuilder->addPipe('$match')->addField('adult', false);
        $pipelineBuilder->addPipe('$project')->addFields(['name' => 1, 'profile_path'=>1, 'external_ids'=> 1,
            'birthday'=> 1,'gender'=>1, 'popularity'=> 1,'known_for_department'=> 1, 'place_of_birth'=> 1,"id" => 1]);
        $pipelineBuilder->addPipe('$sort')->addField($sort, -1);
        $pipelineBuilder->addPipe('$skip', $skip);
        $pipelineBuilder->addPipe('$limit', $limit);
        $opts['pipelines'] = $pipelineBuilder->getQuery();
        $result = $this->findService->allCached($opts, 'people');

        return $result;
    }

    public function findPlaceOfBirths(string $search, int $limit = 10)
    {
        $limit = max(($limit ?? 10),100);
        $pipelineBuilder = new PipelineBuilder();
        $regexBody = new Regex("$search",'im');
        $pipelineBuilder->addPipe('$group')->addField('_id', '$place_of_birth');
        $pipelineBuilder->addPipe('$match', collect(["_id" => $regexBody]));
        $pipelineBuilder->addPipe('$limit', $limit);
        $opts['pipelines'] = $pipelineBuilder->getQuery();
        $opts['pipe_order'] = ['$group' => 6, '$match' => 4,'$limit' => 3];
        $result = $this->findService->all($opts, 'people');
        $result = array_map(function($a){
            return $a["_id"];
        }, $result);

        return $result;
    }

    public function getById(int $id)
    {
        $pb = new PipelineBuilder();
        $pb->addPipe('$match', ["_id" => $id]);
        $pb->addPipe('$lookup')->setValue([
            'from' =>'movies',
            'localField' => '_id',
            'foreignField' => 'credits.cast.id',
            'as' => 'cast'
        ]);
        $pb->addPipe('$lookup')->setValue([
            'from' =>'movies',
            'localField' => '_id',
            'foreignField' => 'credits.crew.id',
            'as' => 'crew'
        ]);
        $pb->addPipe('$project')->setValue(['name' => 1, 'profile_path'=>1, 'external_ids'=> 1,
                'birthday'=> 1,'gender'=>1, 'popularity'=> 1,'known_for_department'=> 1, 'place_of_birth'=> 1,
                'imdb_id' => 1, 'id' => 1, 'biography' => 1,'tagged_images' => 1, 'images' => 1, 'type' => 1,
                'cast._id' => 1, 'cast.title' => 1, 'cast.year' => 1, 'cast.name' => 1, 'cast.vote_count' => 1, 'cast.vote_item' => 1,
                'cast.genres' => 1, 'cast.poster_path' => 1,'cast.backdrop_path' => 1,'cast.rating'  => 1, 'cast.vote_average' => 1,
                'crew._id' => 1, 'crew.title' =>  1, 'crew.year' => 1, 'crew.name' => 1, 'crew.vote_count' => 1, 'crew.vote_item' =>1,
                'crew.genres' => 1, 'crew.poster_path' => 1,'crew.backdrop_path' => 1,'crew.rating'  => 1, 'crew.vote_average' => 1,
                'cast.type' => 1, 'crew.type' => 1]
        );
        $opts =['pipelines' => $pb->getQuery(), 'pipe_order' => ['$match' => 4, '$lookup'=> 3, '$project' => 1]];
        $result = $this->findService->allCached($opts, 'people');
        if(empty($result)) {
            return $result;
        }
        return $result[0];

    }
}