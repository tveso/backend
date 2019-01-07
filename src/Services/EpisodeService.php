<?php


namespace App\Services;


use App\Auth\UserService;
use App\EntityManager;
use App\Jobs\UpdateSearchFieldJob;
use App\Services\TheMovieDb\TmdbEpisodeService;
use App\Util\FindQueryBuilder;
use App\Util\PipelineBuilder\PipelineBuilder;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\Model\BSONArray;
use Symfony\Component\ExpressionLanguage\Tests\Node\Obj;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Traversable;


class EpisodeService extends AbstractShowService
{

    /** @Inject()
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var \App\Auth\User|string
     */
    private $user;
    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var CacheService
     */
    private $cacheService;
    /**
     * @var FindService
     */
    private $findService;
    /**
     * @var TmdbEpisodeService
     */
    private $episodeService;

    /**
     * FindService constructor.
     * @param EntityManager $entityManager
     * @param UserService $userService
     * @param CacheService $cacheService
     * @param FindService $findService
     * @param TmdbEpisodeService $episodeService
     */
    public function __construct(EntityManager $entityManager, UserService $userService, CacheService $cacheService,
                                FindService $findService, TmdbEpisodeService $episodeService)
    {
        $this->entityManager = $entityManager;
        $this->user = $userService->getUser();
        $this->userService = $userService;
        $this->cacheService = $cacheService;
        $this->findService = $findService;
        $this->episodeService = $episodeService;
    }

    public function findPendingEpisodes($limit = 30, $page = 1)
    {
        $lastEpisodesWatchedNumerical = $this->findLastEpisodesWatchedNumerical($limit, $page);

        return $lastEpisodesWatchedNumerical;
    }


    public function findLastEpisodesWatchedNumerical($limit = 30, $page = 1)
    {
        $page = 1;
        $skip = ($page-1)*$limit;
        $userId = $this->user->getId();
        $query = [];
        $query[] = ['$match' => ['user' => new ObjectId($userId), 'type' => 'episode']];
        $query[] = ['$sort' => ['season_number' => -1, 'episode_number' => -1]];
        $query[] = ['$group' => ["_id" => '$tvshow_parent', "episodes" => ['$push' =>
            ['episode_number' => '$episode_number', 'season_number' => '$season_number', 'parent_id' => '$tvshow_parent']]]];
        $query[] = ['$addFields' => [
            'episode' => ['$arrayElemAt' => ['$episodes', 0]]
        ]];
        $query[] = ['$limit' => $limit];
        $query[] = ['$skip' => $skip];
        $query[] = ['$lookup' => [
            'from' => 'episodes',
            'localField' => 'episode.parent_id',
            'foreignField' => 'show_id',
            'as' => 'episodes'
            ],
        ];
        $today = (new \DateTime())->format('Y-m-d');
        $query[] = ['$addFields' => [
            'episodesFiltered' => [
                '$filter' => [
                    'input' => '$episodes',
                    'as' => 'item',
                    'cond' => [
                        '$and' => [
                        ['$or'=> [
                            ['$gt' =>['$$item.season_number', '$episode.season_number'] ],
                            ['$and' => [
                                ['$eq' => ['$$item.season_number', '$episode.season_number']],
                                ['$gt' => ['$$item.episode_number', '$episode.episode_number']]
                            ]
                            ]
                        ]
                        ],
                            ['$gte' => [$today, '$$item.air_date']],
                            ['$ne' => ['$$item.air_date', null]]
                        ]
                    ]
                ]
            ]
        ]];
        $query[] = ['$unwind' => ['path' => '$episodesFiltered', 'preserveNullAndEmptyArrays' => false]];
        $query[] = ['$replaceRoot' => ['newRoot' => '$episodesFiltered']];
        $query[] = ['$project' => [  'crew' =>0, 'cast' => 0]];
        $query[] = ['$sort' => ['season_number' => 1, 'episode_number'=> 1]];
        $query[] = ['$group' => ["_id" => '$show_id', "episodes" => ['$push' => '$$ROOT']]];
        $query[] = ['$addFields' => ['episode' => [
            '$arrayElemAt' => ['$episodes', 0]
        ]]];
        $query[] = ['$replaceRoot' => ['newRoot' => '$episode']];
        $query[] = ['$project' => [  'episodes.crew' =>0, 'episodes.cast' => 0]];
        $query = array_merge($query,  $this->addEpisodeShowName());
        $data = $this->entityManager->aggregate($query,[], 'follows');
        $data = FindService::bsonArrayToArray($data);

        return $data;
    }

    /**
     * @param array $opts
     * @return array
     */
    public function userWatchedEpisodes(array $opts = []): array
    {
        if(!isset($opts['user'])) {
            throw new InvalidArgumentException();
        }
        $userId = $opts['user'];
        $mode = ($opts['mode']) ?? 'pending';
        $limit = min(($opts["limit"]) ?? 30,100);
        $page = ($opts["page"]) ?? 1;
        unset($opts['mode']);
        unset($opts['user']);
        unset($opts['limit']);
        unset($opts['page']);
        if($mode === 'pending') {
            return $this->findPendingEpisodes($limit, $page);
        }
        $sort = $opts["sort"] ?? null;
        $opts['pipelines'] = array_merge($this->addLimitPipeline($limit, $page), $this->addSortPipeline($sort),
            $this->getProjection());
        $pb = new FindQueryBuilder($opts);
        $showPipelines = $pb->build();
        $pipelines['pipelines'] = [
            ['$match' => ['user' => new ObjectId($userId), 'mode' => ['$in' => $mode]]],
            ['$sort' => ['updated_at' => -1]],
            ['$lookup' =>
                [
                    'from' => 'movies',
                    'localField' => 'show',
                    'foreignField' => '_id',
                    'as' => 'shows'
                ]],
            ['$unwind' => ['path' => '$shows']],
            ['$addFields' => ['shows.updated_at' => '$updated_at']],
            ['$replaceRoot' => ['newRoot' => '$shows']],
        ];
        $userDataPipeline = array_merge($this->addUserRatingPipeLine($this->user->getId()),
            $this->addFollowPipeLine($this->user->getId()));
        $pipelines['pipelines'] = array_merge($pipelines['pipelines'], $showPipelines, $userDataPipeline);
        $pipelines["pipe_order"] = ['$project' => -5];

        $result = $this->findService->all($pipelines,'follows');

        return $result;
    }

    public function get(string $id)
    {
        $id = intval($id);
        $userId = $this->user->getId();
        $pipeline['pipelines'] = [['$match' => ['_id' => $id]]];
        $userDataPipeline = array_merge($this->addUserRatingPipeLine($userId),
            $this->addFollowPipeLine($userId));
        $pipeline['pipelines']  = array_merge( $pipeline['pipelines'],
            $userDataPipeline,
            $this->addBeforeEpisode(),
            $this->addEpisodeShowName());
        $pipeline['pipelines'][]['$project'] = ['showp'=> 0, 'showDocument' => 0];
        $pipeline['pipelines'] = array_merge($pipeline['pipelines'],  $this->addNextEpisodePipe());
        $result = $this->findService->all($pipeline,'episodes');
        if(empty($result)) {
            return [];
        }
        $result = $result[0];
        $result = $this->updateCredits($result);

        return $result;
    }

    public function addNextEpisodePipe()
    {
        $query[] = ['$lookup' => [
            'from' => 'episodes',
            'let' => ['sid' => '$show_id', 'sn' => '$season_number', 'en' => '$episode_number'],
            'pipeline' => [
                ['$match' =>
                ['$expr' =>[
                    '$and' => [
                        ['$or'=> [
                            ['$gt' =>['$season_number', '$$sn'] ],
                            ['$and' => [
                                ['$eq' => ['$season_number', '$$sn']],
                                ['$gt' => ['$episode_number', '$$en']]
                            ]
                            ]
                        ]
                        ],
                        ['$eq' => ['$show_id', '$$sid']]
                        ]
                ]
                ]
                ],
                ['$sort' => ['season_number' => 1, 'episode_number' => 1]],
                ['$project' => ['season_number' => 1, 'episode_number' => 1, 'still_path' => 1, 'show_id' => 1,
                    '_id' => 1]]
            ],
            'as' => 'next_episode'
        ],
        ];
        $query[] = ['$addFields' => ['next_episode' => [
            '$arrayElemAt' => ['$next_episode', 0]
        ]]];

        return $query;
    }
    public function addBeforeEpisode()
    {
        $query[] = ['$lookup' => [
            'from' => 'episodes',
            'let' => ['sid' => '$show_id', 'sn' => '$season_number', 'en' => '$episode_number'],
            'pipeline' => [
                ['$match' =>
                    ['$expr' =>[
                        '$and' => [
                            ['$eq' => ['$show_id', '$$sid']],
                            ['$or'=> [
                                ['$lt' =>['$season_number', '$$sn'] ],
                                ['$and' => [
                                    ['$eq' => ['$season_number', '$$sn']],
                                    ['$lt' => ['$episode_number', '$$en']]
                                ]
                                ]
                            ]
                            ],

                        ]
                    ]
                    ]
                ],
                ['$sort' => ['season_number' => -1, 'episode_number' => -1]],
                ['$limit' => 1],
                ['$project' => ['season_number' => 1, 'episode_number' => 1, 'still_path' => 1, 'show_id' => 1,
                    '_id' => 1]]
            ],
            'as' => 'before_episode'
        ],
        ];
        $query[] = ['$addFields' => ['before_episode' => [
            '$arrayElemAt' => ['$before_episode', 0]
        ]]];

        return $query;
    }

    private function updateCredits(array $episode)
    {
        if(isset($episode['credits'])) {
            return $episode;
        }
        try{
            $episode['credits'] = $this->episodeService->credits(
                $episode['show']['id'],
                $episode['season_number'],
                $episode['episode_number']);
            $this->entityManager->update(['_id' => $episode['_id']], ['$set' => ['credits' => $episode['credits']]],
                'episodes');
        } catch (\Exception $exception) {
            $episode['credits'] = ['cast' => [], 'crew' => []];
            return $episode;
        }

        return $episode;
    }

}