<?php
/**
 * Date: 17/10/2018
 * Time: 16:21
 */

namespace App\Services;


use App\EntityManager;
use App\Jobs\UpdateSearchFieldJob;
use App\Services\TheMovieDb\TmdbPeopleService;
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
    /**
     * @var TmdbPeopleService
     */
    private $tmdbPeopleService;

    public function __construct(EntityManager $entityManager, FindService $findService, ShowService $showService, TmdbPeopleService $tmdbPeopleService)
    {
        $this->entityManager = $entityManager;
        $this->findService = $findService;
        $this->showService = $showService;
        $this->tmdbPeopleService = $tmdbPeopleService;
    }


    public function all(array $opts = [])
    {
        $page = ($opts["page"]) ?? 1;
        $limit = min(($opts["limit"]) ?? 30,100);
        $skip = ($page- 1)*$limit;
        $sort = ($opts["sort"]) ?? 'popularity';
        $pipelineBuilder = new PipelineBuilder();
        $pipelineBuilder->addPipe('$match')->setValue(['adult'=> ['$ne'=>true]]);
        $pipelineBuilder->addPipe('$sort')->addField($sort, -1);
        $pipelineBuilder->addPipe('$skip', $skip);
        $pipelineBuilder->addPipe('$limit', $limit);
        $pipelineBuilder->addPipe('$project')->addFields(['name' => 1, 'profile_path'=>1, 'external_ids'=> 1,
            'birthday'=> 1,'gender'=>1, 'popularity'=> 1,'known_for_department'=> 1, 'place_of_birth'=> 1,"id" => 1]);
        $opts['pipelines'] = $pipelineBuilder->getQuery();
        $opts['pipe_order'] = ['$match'=> 10, '$sort'=>9, '$limit'=>7,'$skip'=>8,'$project'=>6];
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

    public function getShowsByPerson(int $id, array $options = [])
    {
        $pb = new PipelineBuilder();
        $page = intval((($options['page']) ?? 1));
        $sort = $options['sort'] ?? 'year';
        $limit = 30;
        $skip = ($page-1)*$limit;
        $this->updateCombinedCredits($id);
        $pb->addPipe('$match')->setValue(["_id" => $id]);
        $pb->addPipe('$lookup')->setValue(['from' => 'movies', 'localField' => 'shows.id', 'foreignField' => 'id', 'as' => 'shows']);
        $pb->addPipe('$unwind')->setValue(['path' => '$shows', 'preserveNullAndEmptyArrays' => true]);
        $pb->addPipe('$addFields')->setValue(['shows.character' => '$character', 'shows.job' => '$job',
            'shows.department' => '$department', 'shows.typecredit' => '$type']);
        $pb->addPipe('$replaceRoot')->setValue(['newRoot' => '$shows']);
        $pb->addPipe('$sort')->setValue([$sort => -1]);
        $pipeline = $pb->getQuery();
        $pipelines = $this->findService->getPipeline($options);
        $pipeline = array_merge($pipeline,$pipelines['pipeline']);
        $pipeline[]['$project'] = $this->getSimpleProject()+['job' => 1, 'character' => 1, 'department' => 1, 'typecredit' => 1];
        $pipeline = $this->showService->setUserDataPipeline($pipeline);
        $result = $this->entityManager->aggregate($pipeline, [], 'people');
        return FindService::bsonArrayToArray($result);
    }


    public function updateCombinedCredits($id)
    {
        $person = $this->entityManager->findOneBy(['id' => $id], 'people');
        if (is_null($person)) {
            return;
        }
        if (isset($person['shows'])) {
            return;
        }
        $shows = $this->getCombinedCredits($id);
        $this->entityManager->update(['_id' => $id], ['$set' => ['shows' => $shows]], 'people');
    }

    private function getCombinedCredits($id)
    {
        $combinedShows = $this->tmdbPeopleService->getShowsByPerson($id);
        $shows = [];
        foreach ($combinedShows['cast'] as $key=>$value) {
            $show = ["id" => $value['id'], 'character' => $value['character'],
                'credit_id' => $value['credit_id'], 'type' => 'cast'];
            if (isset($value['episode_count'])){
                $show['episode_count'] = $value['episode_count'];
            }
            $shows[] = $show;
        }
        foreach ($combinedShows['crew'] as $key=>$value) {
            $show = ["id" => $value['id'], 'department' => $value['department'],
                'credit_id' => $value['credit_id'], 'type' => 'crew', 'job' => $value['job']];
            if (isset($value['episode_count'])){
                $show['episode_count'] = $value['episode_count'];
            }
            $shows[] = $show;
        }

        return $shows;
    }
}