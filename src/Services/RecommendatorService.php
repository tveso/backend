<?php
/**
 * Date: 14/08/2018
 * Time: 16:24
 */

namespace App\Services;


use App\EntityManager;
use App\Jobs\UpdateTmdbJobs\UpdateMoviesJob;
use App\Jobs\UpdateTmdbJobs\UpdateTvShowsJob;

class RecommendatorService
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
     * RecommendatorService constructor.
     * @param EntityManager $entityManager
     * @param UpdateMoviesJob $updateMoviesJob
     * @param UpdateTvShowsJob $updateTvShowsJob
     */
    public function __construct(EntityManager $entityManager, UpdateMoviesJob $updateMoviesJob, UpdateTvShowsJob $updateTvShowsJob)
    {
        $this->entityManager = $entityManager;
        $this->updateMoviesJob = $updateMoviesJob;
        $this->updateTvShowsJob = $updateTvShowsJob;
    }


    public function recommendTvshow($show, int $page = 1, int $limit = 12)
    {
        $collection = $this->entityManager->getCollection('movies');
        $array = [];
        $castIds = $this->getArrayIds($show['credits']['cast']);
        $crewIds = $this->getArrayIds($show['credits']['crew']);
        $keywords = $show["keywords"];
        $genres = $show["genres"];
        $originCountry = $show['origin_country'];
        $networks = $show["networks"];
        $array[] = ['$match' =>
            ['type'=> $show["type"],
                'rating.numVotes' => ['$gte' => 20000],
            'keywords.results' =>
                ['$exists'=> true],
                "_id"=> ['$ne'=>$show["_id"]]]];
        $array[] = ['$project'=>
            [
                'title' => 1,
                'titles' => 1,
                'backdrop_path' => 1,
                'type'=> 1,
                'poster_path' =>1,
                'rating' => 1,
                'year' => 1,
                'rank' => [
                    '$let' =>
                    [
                        'vars' =>
                        [
                            'keywordsequal' => [
                                '$size'=>
                                    ['$setIntersection' => ['$keywords.results', $keywords['results']]]
                            ],
                            'castsequal' => [
                            '$size'=>
                                ['$setIntersection' => ['$credits.cast.id', $castIds]]
                            ],
                            'crewequal' => [
                                '$size'=>
                                    ['$setIntersection' => ['$credits.crew.id', $crewIds]]
                            ],
                            'genresequal' => [
                                '$size'=>
                                    ['$setIntersection' => ['$genres', $genres]]
                            ],
                            'origincountry' =>[
                                '$size'=>
                                    ['$setIntersection' => ['$origin_country', $originCountry]]
                            ],
                            'networksequal' => [
                                '$size'=>
                                    ['$setIntersection' => ['$networks', $networks]]
                            ],
                        ],
                        'in' =>
                        [
                            '$sum' =>
                            ['$$keywordsequal', '$$castsequal',  ['$multiply'=> ['$$crewequal',1.1]], '$$genresequal', '$$origincountry',
                                ['$multiply'=> ['$$networksequal',0.7]]]
                        ]
                    ],
                ]
            ]
            ];

        $array[] = ['$sort' => ['rank' => -1]];
        $array[] = ['$limit' => $limit];
        $opts = ['allowDiskUse' => true];
        $result = $collection->aggregate($array, $opts);

        return iterator_to_array($result);
    }

    public function recommendMovies($show, int $page = 1, int $limit = 14)
    {
        $collection = $this->entityManager->getCollection('movies');
        $array = [];
        $castIds = $this->getArrayIds($show['credits']['cast']);
        $crewIds = $this->getArrayIds($show['credits']['crew']);
        $keywords = $show["keywords"];
        $genres = $show["genres"];
        $array[] = ['$match' =>
            ['type'=> $show["type"],
                'rating' => ['$exists' => true],
                'keywords.keywords' =>
                    ['$exists'=> true],
                "_id"=> ['$ne'=>$show["_id"]]]];
        $array[] = ['$project'=>
            [
                'title' => 1,
                'titles' => 1,
                'backdrop_path' => 1,
                'type'=> 1,
                'poster_path' =>1,
                'rating' => 1,
                'year' => 1,
                'rank' => [
                    '$let' =>
                        [
                            'vars' =>
                                [
                                    'keywordsequal' => [
                                        '$size'=>
                                            ['$setIntersection' => ['$keywords.keywords', $keywords['keywords']]]
                                    ],
                                    'castsequal' => [
                                        '$size'=>
                                            ['$setIntersection' => ['$credits.cast.id', $castIds]]
                                    ],
                                    'crewequal' => [
                                        '$size'=>
                                            ['$setIntersection' => ['$credits.crew.id', $crewIds]]
                                    ],
                                    'genresequal' => [
                                        '$size'=>
                                            ['$setIntersection' => ['$genres', $genres]]
                                    ],
                                ],
                            'in' =>
                                [
                                    '$sum' =>
                                        ['$$keywordsequal', '$$castsequal',  ['$multiply'=> ['$$crewequal',1.1]], '$$genresequal']
                                ]
                        ],
                ]
            ]
        ];

        $array[] = ['$sort' => ['rank' => -1]];
        $array[] = ['$limit' => $limit];
        $opts = ['allowDiskUse' => true];
        $result = $collection->aggregate($array, $opts);

        return iterator_to_array($result);
    }

    private function getArrayIds($cast)
    {
        $cast = iterator_to_array($cast);
        return array_map(function ($cast){
            return $cast["id"];
        }, $cast);
    }

    public function recommend(string $id)
    {
        $show = $this->entityManager->findOnebyId($id, 'movies');
        if(is_null($show)){
            return [];
        }
        //$this->update($show);
        switch ($show["type"]){
            case 'tvshow':
                return $this->recommendTvshow($show);
            case 'movie':
                return $this->recommendMovies($show);
        }
    }

    private function updateShow(string $id)
    {
        $details = $this->updateTvShowsJob->getTvshowDetails($id);
        $this->updateTvShowsJob->store($details, $id);
    }

    private function updateMovie($id)
    {
        $details = $this->updateMoviesJob->getMovieDetails($id);
        $this->updateMoviesJob->store($details, $id);
    }

    private function update($show)
    {
        if(isset($show["id"])){
            return null;
        }
        switch ($show["type"]){
            case 'tvshow':
                $this->updateShow($show["_id"]);
                break;
            case 'movie':
                $this->updateMovie($show["_id"]);
                break;
        }
    }
}