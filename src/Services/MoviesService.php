<?php


namespace App\Services;


use App\Auth\UserService;
use App\EntityManager;
use App\Util\FindQueryBuilder;
use Doctrine\Common\Cache\MongoDBCache;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class MoviesService extends AbstractShowService
{
    /**
     * @Inject()
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @Inject()
     * @var FindService
     */
    private $findService;
    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var \App\Auth\User|string
     */
    private $user;
    /**
     * @var CacheService
     */
    private $cacheService;
    /**
     * @var ShowService
     */
    private $showService;


    /**
     * MoviesService constructor.
     * @param EntityManager $entityManager
     * @param FindService $findService
     * @param UserService $userService
     * @param CacheService $cacheService
     * @param ShowService $showService
     */
    public function __construct(EntityManager $entityManager,
                                FindService $findService,
                                UserService $userService,
                                CacheService $cacheService,
                                ShowService $showService)
    {
        $this->entityManager = $entityManager;
        $this->findService = $findService;
        $this->userService = $userService;
        $this->user = $userService->getUser();
        $this->cacheService = $cacheService;
        $this->showService = $showService;
    }


    /**
     * @return array
     */
    public function popular()
    {
        $query['type'] = 'movie';
        $query['sort'] = 'popularity';
        $query['pipelines'] = $this->addLimitPipeline();
        $data = $this->findService->all($query);
        $pipelines = array_merge([['$sort' => ['popularity' => -1]]], $this->getProjection());
        return $this->showService->setUserDataIntoShows($data, $pipelines);
    }

    public function upcoming()
    {


        return [];
    }

    public function getById(string $id)
    {
        $query = [];
        $query['_id'] = $id;
        $query['pipelines'] = array_merge($this->addLimitPipeline(1, 1), $this->addUserRatingPipeLine($this->user->getId()),
            $this->addFollowPipeLine($this->user->getId()));
        $result = $this->findService->allCached($query, 'movies', 60*60*24);
        $result = $this->showService->setUserDataIntoShows($result);
        if(isset($result[0])) return $result[0];
        return ["_id"=> null];
    }




}