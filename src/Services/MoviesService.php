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
        $key = md5('popular_movies');
        $data = $this->cacheService->getItem($key);
        if(!$this->cacheService->hasItem($key)){
            $query['type'] = 'movie';
            $query['pipelines'] = array_merge($this->addSortPipeline('popularity'),
                $this->addLimitPipeline(30, 1));
            $data = $this->findService->all($query);
            $this->cacheService->save($key, $data, 60*60*24);
        }

        return $this->showService->setUserDataIntoShows($data, $this->getProjection());
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
        $result = $this->findService->allCached($query);
        $result = $this->showService->setUserDataIntoShows($result);
        if(isset($result[0])) return $result[0];
        return ["_id"=> null];
    }




}