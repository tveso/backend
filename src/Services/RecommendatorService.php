<?php
/**
 * Date: 14/08/2018
 * Time: 16:24
 */

namespace App\Services;


use App\Auth\UserService;
use App\EntityManager;
use App\Jobs\UpdateTmdbJobs\UpdateMoviesJob;
use App\Jobs\UpdateTmdbJobs\UpdateTvShowsJob;
use App\Util\ArrayUtil;
use App\Util\RecommendatorQueryBuilder;
use MongoDB\BSON\ObjectId;
use Symfony\Component\Stopwatch\Stopwatch;

class RecommendatorService extends AbstractShowService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var UpdateMoviesJob
     */
    private $updateMoviesJob;
    /**
     * @var UpdateTvShowsJob
     */
    private $updateTvShowsJob;
    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var \App\Auth\User|string
     */
    private $user;
    /**
     * @var FindService
     */
    private $findService;
    /**
     * @var ShowService
     */
    private $showService;

    /**
     * RecommendatorService constructor.
     * @param EntityManager $entityManager
     * @param UpdateMoviesJob $updateMoviesJob
     * @param UpdateTvShowsJob $updateTvShowsJob
     * @param UserService $userService
     * @param FindService $findService
     * @param ShowService $showService
     */
    public function __construct(EntityManager $entityManager,
                                UpdateMoviesJob $updateMoviesJob,
                                UpdateTvShowsJob $updateTvShowsJob,
                                UserService $userService,
                                FindService $findService,
                                ShowService $showService)
    {
        $this->entityManager = $entityManager;
        $this->updateMoviesJob = $updateMoviesJob;
        $this->updateTvShowsJob = $updateTvShowsJob;
        $this->userService = $userService;
        $this->user = $userService->getUser();
        $this->findService = $findService;
        $this->showService = $showService;
    }


    private function getArrayIds($cast, string $id = 'id')
    {
        if(!is_array($cast)) {
            $cast = iterator_to_array($cast);
        }
        return array_map(function ($cast) use($id){
            return $cast[$id];
        }, $cast);
    }

    /**
     * @param string $id
     * @param int $page
     * @return array
     * @throws \Exception
     */
    public function recommendByShowId(string $id, int $page = 1)
    {
        $show = $this->entityManager->findOnebyId($id, 'movies');
        $result = [];
        $userId = $this->user->getId();
        if(is_null($show)){
            return $result;
        }
        $data = ["castIds" => [], "crewIds" => [], "keywords" => [], "networks" => [], "genres" => [],
            "showIds" => [$show["_id"]], 'production_companies' => [], 'production_countries'=> [], 'casts' => [], 'crews' => []];
        $data["showIds"] = array_merge($data["showIds"], $this->getUserFollowsShowsIds($userId));
        $data['type'] = $show['type'];
        $data['limit'] = 30;
        $data['page'] = $page;
        $this->getShowRecommendData($show, $data);
        $data = $this->normalize($data);
        $result = $this->recommend($data);


        $data = $this->showService->setUserDataIntoShows($result, array_merge([['$sort'=> ['popularity' => -1]]],$this->getProjection()));

        return $data;
    }


    /**
     * @param int $page
     * @param string $type
     * @return array
     * @throws \Exception
     */
    public function getShowsRecommendToUser(int $page = 1, string $type = 'movie')
    {
        $userId = $this->user->getId();
        $showsIds = $this->getUserFollowsShowsIds($userId);
        $showsFollowed = $this->getUserFollowsShows($userId, $showsIds);
        $data = ["castIds" => [], "crewIds" => [], "keywords" => [], "networks" => [], "genres" => [], "type" => $type,
            "showIds" => [], 'production_companies' => [], 'production_countries' => [], 'page' => $page, 'limit' => 30,
            'casts'=> [], 'crews' => []];
        $data["showIds"] = $showsIds;
        foreach ($showsFollowed as $show) {
            $this->getShowRecommendData($show, $data);
        }
        $normalizedData = $this->normalize($data);
        $data = $this->recommend($normalizedData);
        $data = $this->showService->setUserDataIntoShows($data, array_merge([['$sort' => ['popularity' => -1]]], $this->getProjection()));
        $result = [];
        $result['shows'] = $data;
        $result['genres'] = $normalizedData['genres'];
        $result['crew'] = $normalizedData['crews'];
        $result['cast'] = $normalizedData['casts'];
        $result['keywords'] = $normalizedData['keywords'];
        $result['languages'] = $normalizedData['original_language'];

        return $result;
    }

    private function getShowRecommendData($show, &$data) {
        $show = ArrayUtil::BSONtoArray($show);
        $castIds = $this->getArrayIds($show["credits"]["cast"]);
        $crewIds = $this->getArrayIds($show['credits']['crew']);
        $data["castIds"][] = $castIds;
        $data["crewIds"][] = $crewIds;
        $data["genres"][] =  $show['genres'];
        $data["original_language"][] = $show["original_language"];
        $year = ($show["type"] === 'movie') ? 'release_date' : 'first_air_date';
        $year = $show[$year];
        $year = explode('-', $year)[0];
        $year = intval($year);
        if(!isset($data['popularity'])){
            $data['popularity'] = $show['popularity'];
        } else {
            $data['popularity'] = ($data['popularity']+$show['popularity']) / 2;
        }
        if(!isset($data['year'])){
            $data['year'] = $year;
        } else {
            $data['year'] = intval(($data['year']+$year)/2);
        }
        if(isset($show["keywords"]["results"])) {
            $data["keywords"][] = $show['keywords']['results'];

        } else {
            $data["keywords"][] = $show["keywords"]["keywords"];
        }
        if(isset($show["networks"])){
            $data["networks"][] = $data['networks'];
        }
        if(isset($show['production_companies'])) {
            $data['production_companies'][] = $show['production_companies'];
        }
        if(isset($show['production_countries'])) {
            $data['production_countries'][] = $show['production_countries'];
        }
        $data['casts']= array_merge($data['casts'], $show['credits']['cast']);
        $data['crews']= array_merge($data['crews'], $show['credits']['crew']);
    }

    public function getUserFollowsShowsIds(string $id)
    {
        $pipelines['pipelines'] = [
            ['$match' => ['user' => new ObjectId($id), 'mode' => ['$in' => ['finalized', 'watched', 'following']]]],
            ['$sort' => ['updated_at' => 1]],
            ['$lookup' =>
                [
                    'from' => 'movies',
                    'localField' => 'show',
                    'foreignField' => '_id',
                    'as' => 'shows'
                ]],
            ['$unwind' => ['path' => '$shows']],
            ['$replaceRoot' => ['newRoot' => '$shows']],
            ['$project' => ["_id" => 1]]
        ];
        $pipelines["pipe_order"] = ['$project' => -5];
        $result = $this->findService->allCached($pipelines,'follows');
        if(empty($result)) {
            return [];
        }

        return array_map(function($a){
            return $a["_id"];
        }, $result);
    }

    public function getUserFollowsShows(string $id, array $ids = [])
    {
        $pipelines['pipelines'] = [
            ['$match' => ['_id' => ['$in' => $ids]]],
            ['$sample' => ['size' => 40]],
            ['$project' => [
                'id' => 1,
                'credits' => 1,
                'production_company' => 1,
                'production_country' => 1,
                'original_language' => 1,
                'networks' => 1,
                'keywords' => 1,
                'popularity' => 1,
                'genres' => 1,
                'release_date' => 1,
                'first_air_date' => 1,
                'type' => 1
            ]],
        ];
        $pipelines['pipe_order'] = ['$project' => -2];
        $result = $this->findService->allCached($pipelines,'movies');
        if(empty($result)) {
            return [];
        }

        return $result;
    }

    public function recommend(array $data)
    {
        $default = ['limit' => 30, 'page' => 1, 'year' => 1970, 'popularity' => 5];
        $data = array_merge($default, $data);
        $limit = $data['limit'];
        $page = $data['page'];
        $minYear = intval($data['year'])-10;
        $minPopularity = min($data['popularity'] - $data['popularity']*0.25, 5);
        $match = ['type' => $data['type'],
            'credits' => ['$exists' => true], 'popularity'=> ['$gte' => $minPopularity]];
        $project =
            [
                'title' => 1,
                'titles' => 1,
                'backdrop_path' => 1,
                'type'=> 1,
                'poster_path' =>1,
                'ratings' => 1,
                'vote_count'=>1,
                'vote_average'=>1,
                'networks' => 1,
                'keywords' => 1,
                'genres' => 1,
                'production_company' => 1,
                'production_country' => 1,
                'release_date' => 1,
                'first_air_date' => 1,
                'rating' => 1,
                'popularity' => 1,
                'year' => 1,
            ];
        $rq = new RecommendatorQueryBuilder($match, $project);
        if(!empty($data["showIds"])){
            $rq->addToMatch(["_id"=> ['$nin' => $data['showIds']]]);
        }
        if(!empty($data['genres'])){
            $rq->addToMatch(["genres"=> ['$in' => $data['genres']]]);
        }
        $rq->addField('rank');
        $rq->addVar('rank', 'cast', '$credits.cast.id', $data["castIds"], 1);
        $rq->addVar('rank', 'crew', '$credits.crew.id', $data["crewIds"], 1.6);
        $rq->addVar('rank', 'genre', '$genres', $data["genres"], 0.8);
        $rq->addVar('rank', 'productioncompanies', '$production_companies',
            $data["production_companies"], 0.7);
        $rq->addVar('rank', 'productioncompanies', '$production_companies',
            $data["production_companies"], 0.7);
        if ($data["type"] === 'movie') {
            $rq->addToMatch(['release_date' => ['$gte' => "$minYear-01-01"]]);
            $rq->addVar('rank', 'keywords', '$keywords.keywords', $data["keywords"], 1);
        }
        if ($data["type"] === 'tvshow') {
            $rq->addToMatch(['first_air_date' => ['$gte' => "$minYear-01-01"]]);
            $rq->addVar('rank', 'keywords', '$keywords.results', $data["keywords"], 1);
            $rq->addVar('rank', 'networks', '$networks', $data["networks"], 1.2);
        }
        $pipeline = $rq->build();
        $pipeline[]['$sort'] = ['rank' => -1];
        $pipeline = array_merge($pipeline, $this->addLimitPipeline($limit, $page));
        $opts = ['pipelines' => $pipeline, 'opts'=> ['allowDiskUse' => true]];
        $result = $this->findService->allCached($opts, 'movies', 60*60*24);

        return $result;
    }

    /**
     * @param $data
     * @return array
     * @throws \Exception
     */
    private function normalize($data)
    {
        $result = [];
        $normalizableValues = ['genres', 'crewIds', 'castIds', 'networks', 'keywords', 'original_language', 'casts', 'crews'];

        foreach ($data as $k=>$v){
            if(is_array($v)){
                $result[$k] = [];
                foreach ($v as $i=>$j){
                    if(is_array($j) and !in_array($k, ['casts', 'crews'])){
                        $result[$k] = array_merge($result[$k], $j);
                    } else {
                        $result[$k][] = $j;
                    }
                }
                if(in_array($k, $normalizableValues)){
                    $result[$k] = $this->normalizeValues($result[$k]);
                }

            } else {
                $result[$k] = $v;
            }
        }

        return $result;
    }

    private function normalizeValues(array $array)
    {
        $counts = [];
        foreach ($array as $key=>$value) {
                $k = $value;
                if(is_array($value)){
                    if( isset($value['id'])){
                        $k = $value['id'];
                    } else {
                        return $array;
                    }
                }
                if(isset($counts[$k])){
                    $counts[$k]['repeated']+=1;
                    continue;
                }
                $counts[$k]['data'] = $value;
                $counts[$k]['repeated'] = 1;
            }
            usort($counts, function($a, $b) {
                return $b['repeated'] - $a['repeated'];
            });
            foreach ($counts as $k=>&$v) {
                $v = $v['data'];
            }
            if(sizeof($counts)>50) {
                $counts = array_slice($counts, 0,5);
            }


        return $counts;
    }


}