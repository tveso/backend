<?php


namespace App\Controller;


use App\Services\FindService;
use App\Services\FollowService;
use App\Services\RecommendatorService;
use MongoDB\BSON\ObjectId;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/** @Route("/api/follow", name="follow_")
 */
class FollowController extends AbstractController
{
    /**
     * @var FollowService
     */
    private $followService;


    /**
     * FollowController constructor.
     * @param FollowService $followService
     */
    public function __construct(FollowService $followService)
    {
        $this->followService = $followService;
    }
    /**
     * @Route("/find", name="userfollow")
     * @param Request $request
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function find(Request $request)
    {
        $opts = $request->query->all();
        $data = $this->followService->getUserFollowsShows($opts);
        return $this->jsonResponse($data);
    }
    /**
     * @Route("/lists", name="lists")
     * @param Request $request
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function lists(Request $request)
    {
        $opts = $request->query->all();
        $data = $this->followService->getUserFollowLists($opts);
        return $this->jsonResponse($data);
    }

    /**
     * @Route("/episode", name="watchepisode")
     * @param Request $request
     * @param string $id
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function watchEpisode(Request $request)
    {
        $episode = $request->query->get('episode');
        $markUnseen =  $request->query->get('cancel') ?? false;
        $watchBefore =  $request->get('previousEpisodes') ?? false;
        $markUnseen = ($markUnseen === 'true') ? true : false;
        $watchBefore = ($watchBefore === 'true') ? true : false;
        $data = $this->followService->watchEpisodes($episode, $markUnseen, $watchBefore);
        return $this->json($data);
    }


    /**
     * @Route("/{id}", name="follow")
     * @param Request $request
     * @param string $id
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function followTvshow(Request $request, string $id)
    {
        $mode = $request->query->get('type');
        $type = $request->query->get('resourceType');
        $this->followService->follow($id, $mode, $type);
        return $this->okResponse();
    }




}