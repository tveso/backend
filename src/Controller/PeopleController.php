<?php

namespace App\Controller;


use App\Services\PeopleService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/** @Route("/api/people", name="people_")
 */
class PeopleController extends AbstractController
{

    /**
     * @var PeopleService
     */
    private $peopleService;

    /**
     * MoviesController constructor.
     * @param PeopleService $peopleService
     */
    public function __construct(PeopleService $peopleService)
    {

        $this->peopleService = $peopleService;
    }


    /**
     * @Route("/all", name="all")
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function all(Request $request)
    {
        $data = $request->query->all();
        return $this->jsonResponse($this->peopleService->all($data));
    }


    /**
     * @Route("/search/birthplace", name="searchbirthplace")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function searchBirthplace(Request $request)
    {
        $search = $request->get('query');
        if(is_null($search) or !$search){
            return $this->jsonResponse([]);
        }
        return $this->jsonResponse($this->peopleService->findPlaceOfBirths($search));
    }

    /**
     * @Route("/{id}", name="searchbirthplace")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getOne(string $id, Request $request)
    {
        $result = $this->peopleService->getById($id);
        return $this->jsonResponse($result);
    }


}
