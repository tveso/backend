<?php


namespace App\Controller;


use App\Services\FindService;
use App\Services\FollowService;
use App\Services\RecommendatorService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/** @Route("/api/follow", name="find_")
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
     * @Route("/{id}", name="follow")
     * @param Request $request
     * @param string $id
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function followTvshow(Request $request, string $id)
    {
        $mode = $request->query->get('type');
        $this->followService->follow($id, $mode);
        return $this->okResponse();
    }
}