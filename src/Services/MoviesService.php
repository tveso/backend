<?php


namespace App\Services;


use App\EntityManager;



class MoviesService
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
     * MoviesService constructor.
     * @param EntityManager $entityManager
     * @param FindService $findService
     */
    public function __construct(EntityManager $entityManager, FindService $findService)
    {
        $this->entityManager = $entityManager;
        $this->findService = $findService;
    }


    public function popular()
    {
        $query = ["limit"=> 12, "page"=> 1, "type"=>"movie", "sort" => "popularity"];

        return $this->findService->all($query);
    }

    public function upcoming()
    {
        $date = new \DateTime('now');
        $query = ["limit"=> 100, "page"=> 1, "type"=>"movie", "sort" => "release_date", "status"=> "Released",
            "dateFilter"=> "<={$date->format('Y-m-d')}"];

        return $this->findService->all($query);
    }

    public function getById(string $id)
    {
        $result = $this->entityManager->findOnebyId($id,'movies');
        if($result === null) return [];

        return $result;
    }






}