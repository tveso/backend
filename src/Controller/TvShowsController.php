<?php
/**
 * Date: 09/07/2018
 * Time: 21:37
 */

namespace App\Controller;


use App\Services\CommentsService;
use App\Services\FollowService;
use App\Services\RecommendatorService;
use App\Services\TvShowService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** @Route("/api/tvshows", name="movies_")
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
     * @var RecommendatorService
     */
    private $recommendatorService;

    /**
     * TvShowController constructor.
     * @param TvShowService $tvshowService
     * @param FollowService $followService
     * @param CommentsService $commentsService
     * @param RecommendatorService $recommendatorService
     */
    public function __construct(TvShowService $tvshowService, FollowService $followService,
                                CommentsService $commentsService, RecommendatorService $recommendatorService)
    {
        $this->tvshowService = $tvshowService;
        $this->followService = $followService;
        $this->commentsService = $commentsService;
        $this->recommendatorService = $recommendatorService;
    }


    /**
     * @Route("/upcoming", name="upcoming")
     * @throws \Exception
     */
    public function upcoming(Request $request) : Response
    {
        $data = $this->tvshowService->upcoming();

        return $this->jsonResponseCached($data, $request);
    }

    /**
     * @Route("/{id}", name="get")
     * @throws \Exception
     */
    public function getTvShow(string $id, Request $request)
    {
        $data = $this->tvshowService->getById($id);
        $data['comments'] = $this->commentsService->getAll($data["_id"]);

        return $this->jsonResponseCached($data, $request);
    }

    /**
     * @Route("/{id}/episodes", name="gettvshowsseasonepisodes")
     * @param string $id
     * @param Request $request
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getTvShowsSeasonEpisodes(string $id, Request $request)
    {
        $seasonNumber = $request->get('season') ?? 1;
        $seasonNumber = intval($seasonNumber);
        $data = $this->tvshowService->getTvShowsSeasonEpisodes($id, $seasonNumber);

        return $this->jsonResponse($data);
    }



    /**
     * @Route("/{id}/update/{season}", name="updateepisodeseasons")
     * @param int $season
     * @param string $id
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function updateEpisodeSeason(int $season, string $id,  Request $request)
    {
        $data = $this->tvshowService->updateSeasonEpisodes($id,$season);

        return $this->getTvShow($data["_id"], $request);
    }



}