<?php
/**
 * Date: 17/10/2018
 * Time: 16:21
 */

namespace App\Services;


use App\EntityManager;
use App\Jobs\UpdateSearchFieldJob;
use App\Util\PipelineBuilder\PipelineBuilder;
use Doctrine\Common\Annotations\Reader;
use MongoDB\BSON\Regex;
use Symfony\Component\Stopwatch\Stopwatch;

class PeopleService extends AbstractShowService
{

    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var FindService
     */
    private $findService;
    /**
     * @var ShowService
     */
    private $showService;

    public function __construct(EntityManager $entityManager, FindService $findService, ShowService $showService)
    {
        $this->entityManager = $entityManager;
        $this->findService = $findService;
        $this->showService = $showService;
    }


    public function all(array $opts = [])
    {
        $page = ($opts["page"]) ?? 1;
        $limit = min(($opts["limit"]) ?? 30,100);
        $skip = ($page- 1)*$limit;
        $sort = ($opts["sort"]) ?? 'popularity';
        $pipelineBuilder = new PipelineBuilder();
        $pipelineBuilder->addPipe('$match')->addField('adult', false);
        $pipelineBuilder->addPipe('$sort')->addField($sort, -1);
        $pipelineBuilder->addPipe('$skip', $skip);
        $pipelineBuilder->addPipe('$limit', $limit);
        $pipelineBuilder->addPipe('$project')->addFields(['name' => 1, 'profile_path'=>1, 'external_ids'=> 1,
            'birthday'=> 1,'gender'=>1, 'popularity'=> 1,'known_for_department'=> 1, 'place_of_birth'=> 1,"id" => 1]);
        $opts['pipelines'] = $pipelineBuilder->getQuery();
        $opts['pipe_order'] = [];
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
        $pb->addPipe('$project')->setValue(['name' => 1, 'profile_path'=>1, 'external_ids'=> 1,
                'birthday'=> 1,'gender'=>1, 'popularity'=> 1,'known_for_department'=> 1, 'place_of_birth'=> 1,
                'imdb_id' => 1, 'id' => 1, 'biography' => 1,'tagged_images' => 1, 'images' => 1, 'type' => 1
                ]);
        $opts =['pipelines' => $pb->getQuery()];
        $result = $this->findService->all($opts, 'people');
        if(empty($result)) {
            return $result;
        }
        $result = $result[0];

        return $result;
    }

    public function getShowsByPerson(int $id, int $page = 1, int $limit = 30)
    {
        $pb = new PipelineBuilder();
        $pb->addPipe('$match')->setValue(['$or'=> [['credits.cast.id' => $id], ['credits.crew.id' => $id]]]);
        $pb->addPipe('$sort')->setValue(['popularity'=> -1]);
        $pb->addPipe('$skip')->setValue(($page-1)*$limit);
        $pb->addPipe('$limit')->setValue($limit);
        $pb->addPipe('$project')->setValue(["_id" => 1]);
        $opts['pipelines'] = $pb->getQuery();
        $opts['pipe_order'] = [];
        $result = $this->findService->allCached($opts, 'movies', 60*60*24);
        $project = new PipelineBuilder();
        $project->addPipe('$project')->setValue($this->getSimpleProject()+['credits' => 1]);
        $project->addPipe('$sort')->setValue(['year'=> -1]);
        $project->addPipe('$limit')->setValue($limit);
        $result = $this->showService->setUserDataIntoShows($result, $project->getQuery(), false);
        $result = $this->filterCreditsPerson($result, $id);

        return $result;
    }

    private function filterCreditsPerson($result, $id)
    {
        $filterFunction = function ($a) use ($id) {
            return $a["id"] === $id;
        };
        foreach ($result as $key=>$value) {
            $result[$key]['credits']['cast'] = array_values(array_filter($value['credits']['cast'], $filterFunction));
            $result[$key]['credits']['crew'] = array_values(array_filter($value['credits']['crew'], $filterFunction));
        }

        return $result;
    }
}