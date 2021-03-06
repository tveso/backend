<?php


namespace App\Services;


use App\Auth\UserService;
use App\EntityManager;
use App\Jobs\UpdateSearchFieldJob;
use App\Util\FindQueryBuilder;
use App\Util\PipelineBuilder\PipelineBuilder;
use MongoDB\BSON\Regex;
use MongoDB\Model\BSONArray;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Traversable;


class FindService extends AbstractShowService
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
     * FindService constructor.
     * @param EntityManager $entityManager
     * @param UserService $userService
     * @param CacheService $cacheService
     * @param ShowService $showService
     */
    public function __construct(EntityManager $entityManager, UserService $userService, CacheService $cacheService)
    {
        $this->entityManager = $entityManager;
        $this->user = $userService->getUser();
        $this->userService = $userService;
        $this->cacheService = $cacheService;
    }


    public function allCached(array $opts = [], string $collection = 'movies', int $time = 60*60*24)
    {
        $key = md5(serialize($opts).$collection);
        $data = $this->cacheService->getItem($key);
        if(!$this->cacheService->hasItem($key)){
            $data = $this->all($opts, $collection);
            $this->cacheService->save($key, $data, $time);
        }

        return $data;
    }


    public function all(array $opts = [], string $collection = 'movies')
    {
        $pipelineData = $this->getPipeline($opts);
        $pipeline = $pipelineData['pipeline'];
        $options = $pipelineData['opts'];
        $collection = $this->entityManager->getCollection($collection);
        $aggregateResult = $collection->aggregate($pipeline, $options);
        $data = $this->bsonArrayToArray($aggregateResult);

        return  $data;
    }

    public function getPipeline(array $opts = [])
    {
        $qb = new FindQueryBuilder($opts);
        $pipeline = $qb->build();
        $options = ($opts['opts']) ?? [];
        $options+=['maxTimeMS' => 30000];

        return  ['pipeline' => $pipeline, 'opts' => $options];
    }

    public static function bsonArrayToArray($bsonArray)
    {
        if (is_array($bsonArray)) {
            return $bsonArray;
        }
        $array = iterator_to_array($bsonArray);
        return json_decode(json_encode($array), 1);
    }


}