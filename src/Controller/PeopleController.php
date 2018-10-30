<?php

namespace App\Controller;


use App\Services\CommentsService;
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
     * @var CommentsService
     */
    private $commentsService;

    /**
     * MoviesController constructor.
     * @param PeopleService $peopleService
     */
    public function __construct(PeopleService $peopleService, CommentsService $commentsService)
    {

        $this->peopleService = $peopleService;
        $this->commentsService = $commentsService;
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
     * @Route("/{id}/shows", name="getshows")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getShows(string $id, Request $request)
    {
        $result = $this->peopleService->getShowsByPerson($id, 1, 1500);
        return $this->jsonResponse($result);
    }

    /**
     * @Route("/{id}", name="get")
     * @param string $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getOne(string $id, Request $request)
    {
        $result = $this->peopleService->getById($id);
        $result['comments'] = $this->commentsService->getAll($result["_id"]);
        return $this->jsonResponse($result);
    }



}
