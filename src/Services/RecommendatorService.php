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
use App\Util\PipelineBuilder\PipelineBuilder;
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
     * @var CacheService
     */
    private $cacheService;

    /**
     * RecommendatorService constructor.
     * @param EntityManager $entityManager
     * @param UpdateMoviesJob $updateMoviesJob
     * @param UpdateTvShowsJob $updateTvShowsJob
     * @param UserService $userService
     * @param FindService $findService
     * @param ShowService $showService
     * @param CacheService $cacheService
     */
    public function __construct(EntityManager $entityManager,
                                UpdateMoviesJob $updateMoviesJob,
                                UpdateTvShowsJob $updateTvShowsJob,
                                UserService $userService,
                                FindService $findService,
                                ShowService $showService,
                                CacheService $cacheService)
    {
        $this->entityManager = $entityManager;
        $this->updateMoviesJob = $updateMoviesJob;
        $this->updateTvShowsJob = $updateTvShowsJob;
        $this->userService = $userService;
        $this->user = $userService->getUser();
        $this->findService = $findService;
        $this->showService = $showService;
        $this->cacheService = $cacheService;
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
        if(is_null($show)){
            return $result;
        }
        $query = ["shows" => [$id], "type" => $show['type'], 'page' => $page,'mode'=>'chose'];

        return $this->findRecommendedShows($query)['shows'];
    }


    /**
     * @param array $query
     * @return array
     * @throws \Exception
     */
    public function findRecommendedShows(array $query = [])
    {
        $default = ['mode' => 'automatic', 'page' => 1, 'type' => null, 'shows' => [], 'length' => 10];
        $query = array_merge($default, $query);
        $userId = $this->user->getId();
        $ids = $query["shows"];
        $type = $query['type'];
        $mode = $query['mode'];
        $page = $query['page'];
        if($mode === 'automatic'){
            $shows = array_slice($this->getUserFollowsShows($userId), 0, $query['length']);
        }
        if($mode === 'chose'){
            $shows = $this->getShowsByIds($ids);
        }
        $result = $this->getShowsRecommendByOtherShows($shows, $page, $type);
        return $result;
    }

    /**
     * @param $shows
     * @param int $page
     * @param string $type
     * @return array
     * @throws \Exception
     */
    public function getShowsRecommendByOtherShows($shows, int $page = 1, ?string $type = 'movie')
    {
        $data = ["castIds" => [], "crewIds" => [], "keywords" => [], "networks" => [], "genres" => [], "type" => $type,
            "showIds" => [], 'production_companies' => [], 'production_countries' => [], 'page' => $page, 'limit' => 30,
            'casts'=> [], 'crews' => [], 'original_language' => []];
        $userId = $this->user->getId();
        $userShows = $this->getUserFollowsShowsIds($userId);
        foreach ($shows as $show) {
            $this->getShowRecommendData($show, $data);
        }
        $data['showIds'] = array_merge($data['showIds'], $userShows);
        $normalizedData = $this->normalize($data);
        $result = $this->recommend($normalizedData);
            $result = $this->showService->setUserDataIntoShows($result,
                array_merge([['$sort'=> ['popularity' => -1]], ['$project' =>  ["title"=>1, "name"=>1, "original_title"=> 1,
                    "original_name"=>1, "poster_path"=>1, 'original_language' => 1,
                    "backdrop_path"=>1, "ratings"=>1, "vote_average"=>1, "vote_count"=> 1, 'type' => 1, 'userRate' => 1,
                    "year"=> 1, "release_date"=> 1, "first_air_date" => 1, 'userFollow' => 1, "next_episode_to_air"=> 1,
                    'rank' => 1, 'popularity'=> 1, 'rating' => 1, 'genres'=> 1, 'networks' => 1, 'credits'=> 1, 'keywords' => 1]]]));
        $resultAux = $this->rateShows($result, $normalizedData);
        $result = [];
        $result['shows'] = $resultAux;
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
        $data["showIds"][] = $show['_id'];
        $data["genres"][] =  $show['genres'];
        $year = ($show["type"] === 'movie') ? 'release_date' : 'first_air_date';
        $year = $show[$year];
        $year = explode('-', $year)[0];
        $year = intval($year);
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
        $data["original_language"]=array_merge($data['original_language'], [$show['original_language']]);
        $data['casts']= array_merge($data['casts'], $show['credits']['cast']);
        $data['crews']= array_merge($data['crews'], $show['credits']['crew']);
    }

    public function getUserFollowsShowsIds(string $id)
    {
        $result = $this->getUserFollowsShows($id);
        return array_map(function($a){
            return $a["_id"];
        }, $result);
    }

    public function getShowsByIds(array $ids = [])
    {
        $pipelines['pipelines'] = [
            ['$match' => ['_id' => ['$in' => $ids]]],
            ['$project' => [
                'id' => 1,
                'credits' => 1,
                'production_company' => 1,
                'production_country' => 1,
                'original_language' => 1,
                'networks' => 1,
                'keywords' => 1,
                'popularity' => 1,
                'title' => 1,
                'name' => 1,
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

    /**
     * @param array $data
     * @return mixed
     */
    public function recommend(array $data)
    {
        $default = ['limit' => 30, 'page' => 1, 'year' => 1970, 'popularity' => 5, 'genres' => []];
        $data = array_merge($default, $data);
        $limit = $data['limit'];
        $page = $data['page'];
        $minYear = strval(intval($data['year'])-20);
        if($data['type'] === 'movie') {
            $keywordsKey = 'keywords.keywords';
        } else {
            $keywordsKey = 'keywords.results';
        }
        $shows = [['$match'=> ['type'=> $data['type'], '_id' => ['$nin' => $data['showIds']],
            'year' => ['$gte' => $minYear], 'adults' => ['$ne' => true]]],
            ['$sort'=> ['popularity'=> -1]],['$skip' => ($page-1)*$limit],['$limit' => $limit]];
        if(!empty($data['genres'])){
            $shows[0]['$match']+=['$or'=> [
                ['credits.cast.id' => ['$in' => $data['castIds']]],
                ['credits.crew.id' => ['$in'=> $data['crewIds']]],
                [$keywordsKey => ['$in'=> $data['keywords']]],
            ]];
        }
        $shows = $this->findService->all(['pipelines'=> $shows, 'opts'=> ['allowUseDisk'=> true]]);
        if(empty($shows)){
            return $this->recommendByGenres($data);
        }

        return $shows;
    }


    /**
     * @param $data
     * @return array
     * @throws \Exception
     */
    private function normalize($data)
    {
        $result = [];
        $normalizableValues = ['genres', 'crewIds', 'castIds', 'networks', 'keywords', 'casts', 'crews'];
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
            if(sizeof($counts)>10) {
                $counts = array_slice($counts, 0,10);
            }


        return $counts;
    }

    private function rateShows(array $shows, array $data)
    {
        foreach ($shows as &$value){
            $rating = 0;
            $genresEquals = sizeof($this->intersect($value['genres'], $data['genres']))*1;
            $keywordsKey = ($value['type'] === 'movie') ? 'keywords' : 'results';
            $keywordsEquals = sizeof($this->intersect($value['keywords'][$keywordsKey], $data['keywords']))*3;
            $ids = $this->getArrayIds($value['credits']['cast']);
            $castEquals = sizeof($this->intersect($ids, $data['casts']))*1.1;
            $ids = $this->getArrayIds($value['credits']['crew']);
            $crewEquals = sizeof($this->intersect($ids, $data['crews']))*1.5;
            if(in_array($value['original_language'], $data['original_language'])){
                $rating+=2.5;
            }
            if($value['type'] === 'tvshow'){
                $rating += sizeof($this->intersect($value['networks'], $data['networks']));
            }
            $rating = $rating + $genresEquals + $keywordsEquals + $castEquals + $crewEquals;
            $value['rate'] = $rating;
            unset($value['credits']);
        }
        usort($shows, function ($a, $b){
            return $b['rate'] - $a['rate'];
        });
        $shows = array_slice($shows, 0, 30);
        return $shows;
    }

    private function intersect(array $array1, array $array2)
    {
        $result = [];
        foreach ($array2 as $key=>$value){
            if(in_array($value, $array1)){
                $result[] = $value;
            }

        }
        return $result;
    }

    public function getUserFollowsShows($id)
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
            ['$project' => ["_id" => 1, "genres" => 1, "keywords" => 1, 'credits' => 1, 'type' => 1, 'release_date' => 1,
                'first_air_date' => 1, 'original_language' => 1]]
        ];
        $pipelines["pipe_order"] = ['$project' => -5];
        $result = $this->findService->allCached($pipelines,'follows');
        if(empty($result)) {
            return [];
        }

        return $result;
    }

    private function recommendByGenres($data)
    {
        $default = ['limit' => 30, 'page' => 1, 'year' => 1970, 'popularity' => 5, 'genres' => []];
        $data = array_merge($default, $data);
        $limit = $data['limit'];
        $page = $data['page'];
        $minYear = strval(intval($data['year'])-20);
        $shows = [['$match'=> ['type'=> $data['type'], '_id' => ['$nin' => $data['showIds']],
            'year' => ['$gte' => $minYear], 'adults' => ['$ne' => true]]],
            ['$sort'=> ['popularity'=> -1]],['$skip' => ($page-1)*$limit],['$limit' => $limit]];
        if(!empty($data['genres'])){
            $shows[0]['$match']+=['$or'=> [
               ['genres' => ['$in'=> $data['genres']]],
            ]];
        }
        $shows = $this->findService->allCached(['pipelines'=> $shows, 'opts'=> ['allowUseDisk'=> true]]);

        return $shows;
    }


}