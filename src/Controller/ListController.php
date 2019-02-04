<?php
/**
 * Date: 26/10/2018
 * Time: 23:36
 */

namespace App\Controller;
use App\Form\ListForm;
use App\Services\CommentsService;
use App\Services\ListService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
    /**
     * @var CommentsService
     */
    private $commentsService;

    public function __construct(ListService $listService,  CommentsService $commentsService)
    {

        $this->listService = $listService;
        $this->commentsService = $commentsService;
    }


    /**
     * @Route("/all", name="all")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function list(Request $request)
    {
        $result = $this->listService->all($request->query->all());

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
     * @Route("/user/{id}", name="getUserLists")
     * @param string $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getUserLists($id, Request $request)
    {
        $result = $this->listService->userLists($id, $request->query->all());

        return $this->jsonResponse($result);
    }
    /**
     * @Route("/{id}/edit", name="edit",  methods={"POST"})
     * @param ListForm $list
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @ParamConverter("list", converter="class")
     */
    public function edit($id, ListForm $list)
    {
        $data = $this->listService->edit($id, $list);

        return $this->jsonResponse($data);
    }


    /**
     * @Route("/{id}", name="get")
     */
    public function get(string $id)
    {
        $result = $this->listService->get($id);
        $result['comments'] = $this->commentsService->getAll($result["_id"]['$oid']);

        return $this->jsonResponse($result);
    }

    /**
     * @Route("/{id}/movies", name="getMovies")
     * @throws \Exception
     */
    public function getMovies(string $id, Request $request)
    {
        $result = $this->listService->getShows($id, $request->query->all());

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
     * @Route("/{id}/delete", name="delete")
     * @param string $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function delete(string $id)
    {
        $result = $this->listService->delete($id);

        return $this->okResponse();
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
    /**
     * @Route("/{id}/episodes", name="getEpisodes")
     * @param string $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getEpisodes(string $id, Request $request)
    {
        $result = $this->listService->getEpisodes($id, $request->query->all());

        return $this->jsonResponse($result);
    }

    /**
     * @Route("/{listId}/add", name="addToList")
     * @param string $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function addToList($listId, Request $request)
    {
        $resourceId = $request->get('resource');
        $resourceType = $request->get('type');
        if (is_null($resourceId) or is_null($resourceType)) {
            throw new BadRequestHttpException();
        }
        $result = $this->listService->addToList($resourceId, $listId, $resourceType);

        return $this->jsonResponse($result);
    }


}