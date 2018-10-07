<?php
/**
 * Date: 09/07/2018
 * Time: 21:37
 */

namespace App\Controller;


use App\Entity\Show;
use App\Services\CommentsService;
use App\Services\FollowService;
use App\Services\TvShowService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** @Route("/api/tvshows", name="movies_")
 *  @Cache(expires="+3600 seconds")
 */
class TvShowsController extends AbstractController
{

    /**
     * @var TvShowService
     */
    private $tvshowService;
    /**
     * @var FollowService
     */
    private $followService;

    /**
     * @var CommentsService
     */
    private $commentsService;

    /**
     * TvShowController constructor.
     * @param TvShowService $tvshowService
     */
    public function __construct(TvShowService $tvshowService, FollowService $followService, CommentsService $commentsService)
    {
        $this->tvshowService = $tvshowService;
        $this->followService = $followService;
        $this->commentsService = $commentsService;
    }


    /**
     * @Route("/upcoming", name="upcoming")
     */
    public function upcoming() : Response
    {
        $data = $this->tvshowService->upcoming();

        return $this->jsonResponse($data);
    }

    /**
     * @Route("/{id}", name="get")
     */
    public function get(string $id)
    {
        $data = $this->tvshowService->getById($id);
        $data['comments'] = $this->commentsService->getAll($data["_id"]);

        return $this->jsonResponse($data);
    }


    /**
     * @Route("/{id}/update/{season}", name="updateepisodeseasons")
     * @param int $season
     * @param string $id
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateEpisodeSeason(int $season, string $id)
    {
        $data = $this->tvshowService->updateSeasonEpisodes($id,$season);

        return $this->get($data["_id"]);
    }

    /**
     * @Route("/{id}/follow", name="followtvshow")
     * @param Request $request
     * @param string $id
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function followTvshow(Request $request, string $id)
    {
        $mode = $request->query->get('mode');
        $this->followService->followTvshow($id, $mode);
        return $this->okResponse();
    }


}