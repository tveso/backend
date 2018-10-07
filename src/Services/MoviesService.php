<?php


namespace App\Services;


use App\Auth\UserService;
use App\EntityManager;
use App\Util\FindQueryBuilder;
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
     * MoviesService constructor.
     * @param EntityManager $entityManager
     * @param FindService $findService
     * @param UserService $userService
     */
    public function __construct(EntityManager $entityManager, FindService $findService, UserService $userService)
    {
        $this->entityManager = $entityManager;
        $this->findService = $findService;
        $this->userService = $userService;
        $this->user = $userService->getUser();
    }


    public function popular()
    {
        $userId = $this->user->getId();
        $query = ["limit"=> 12, "page"=> 1, "type"=>"movie", "sort" => "popularity"];
        $query['pipelines'][]['$project'] = FindQueryBuilder::getSimpleProject();
        $query['pipelines'][] = $this->addUserRatingPipeLine($userId);
        return $this->findService->all($query);
    }

    public function upcoming()
    {
        $date = new \DateTime('now');
        $query = ["limit"=> 12, "page"=> 1, "type"=>"movie", "sort" => "release_date", "status"=> "Released",
            "dateFilter"=> "<={$date->format('Y-m-d')}"];
        $userId = $this->user->getId();
        $query['pipelines'][]['$project'] = FindQueryBuilder::getSimpleProject();
        $query['pipelines'][] = $this->addUserRatingPipeLine($userId);

        return $this->findService->all($query);
    }

    public function getById(string $id)
    {
        $result = $this->entityManager->findOnebyId($id,'movies');
        if($result === null) return [];

        return $result;
    }




}