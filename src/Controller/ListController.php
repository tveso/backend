<?php
/**
 * Date: 26/10/2018
 * Time: 23:36
 */

namespace App\Controller;
use App\Form\ListForm;
use App\Services\ListService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/list", name="list_")
 */
class ListController extends AbstractController
{

    /**
     * @var ListService
     */
    private $listService;

    public function __construct(ListService $listService)
    {

        $this->listService = $listService;
    }


    /**
     * @Route("/all", name="all")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function list(Request $request)
    {
        $result = $this->listService->all($request->request->all());

        return $this->jsonResponse($result);
    }


    /**
     * @Route("/create", name="create", methods={"POST"})
     * @param ListForm $list
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @ParamConverter("list", converter="class")
     */
    public function create(ListForm $list)
    {
        $inserted = $this->listService->create($list);

        return $this->jsonResponse($inserted);
    }

    /**
     * @Route("/edit", name="edit",  methods={"POST"})
     * @param ListForm $list
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @ParamConverter("list", converter="class")
     */
    public function edit(ListForm $list)
    {
        $this->listService->edit($list);

        return $this->okResponse();
    }


    /**
     * @Route("/{id}", name="get")
     */
    public function get(string $id)
    {
        $result = $this->listService->get($id);

        return $this->jsonResponse($result);
    }

    /**
     * @Route("/{id}/movies", name="getMovies")
     * @throws \Exception
     */
    public function getMovies(string $id, Request $request)
    {
        $result = $this->listService->getShows($id, $request->query->all(), 'movies');

        return $this->jsonResponse($result);
    }

    /**
     * @Route("/{id}/tvshows", name="getTvshows")
     * @param string $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getTvshows(string $id, Request $request)
    {
        $result = $this->listService->getShows($id, $request->query->all(), 'tvshows');

        return $this->jsonResponse($result);
    }
    /**
     * @Route("/{id}/people", name="getPeople")
     * @param string $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getPeople(string $id, Request $request)
    {
        $result = $this->listService->getPeople($id, $request->query->all());

        return $this->jsonResponse($result);
    }
}