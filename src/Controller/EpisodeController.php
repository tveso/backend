<?php


namespace App\Controller;


use App\Services\CommentsService;
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
     * @var CommentsService
     */
    private $commentsService;

    /**
     * EpisodesController constructor.
     * @param EpisodeService $episodeService
     */
    public function __construct(EpisodeService $episodeService, CommentsService $commentsService)
    {

        $this->episodeService = $episodeService;
        $this->commentsService = $commentsService;
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


    /**
     * @param string $id
     * @Route("/{id}", name="getOne")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getOne($id)
    {
        $data = $this->episodeService->get($id);
        $data['comments'] = $this->commentsService->getAll($data["_id"]);

        return $this->json($data);
    }



}