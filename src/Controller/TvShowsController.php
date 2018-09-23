<?php
/**
 * Date: 09/07/2018
 * Time: 21:37
 */

namespace App\Controller;


use App\Services\TvShowService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
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
     * TvShowController constructor.
     * @param TvShowService $tvshowService
     */
    public function __construct(TvShowService $tvshowService)
    {
        $this->tvshowService = $tvshowService;
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
     * @Route("/{id}/links", name="links")
     * @param string $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getEpisodeLinks(string $id, Request $request)
    {
        $season = $request->query->get('season');
        $episode =  $request->query->get('episode');
        if(is_null($season) or is_null($episode)){
            return $this->error(404, "Not found");
        }
        $data = $this->tvshowService->getEpisodeSeasonLinks($id,$season,$episode);

        return $this->jsonResponse($data);
    }




}