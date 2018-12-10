<?php


namespace App\Controller;


use App\Services\EpisodeService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/** @Route("/api/episodes", name="episodes_")
 */
class EpisodeController extends AbstractController
{

    /**
     * @var EpisodeService
     */
    private $episodeService;

    /**
     * EpisodesController constructor.
     * @param EpisodeService $episodeService
     */
    public function __construct(EpisodeService $episodeService)
    {

        $this->episodeService = $episodeService;
    }


    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     * @Route("/pending", name="pending")
     * @throws \Exception
     */
    public function pending(Request $request)
    {
        $data = $this->episodeService->findPendingEpisodes();

        return $this->json($data);
    }




}