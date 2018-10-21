<?php

namespace App\Controller;

use App\Services\CommentsService;
use App\Services\FindService;
use App\Services\MoviesService;
use App\Services\RecommendatorService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/** @Route("/api/movies", name="movies_")
 */
class MoviesController extends AbstractController
{
    /**
     * @var MoviesService
     */
    private $moviesService;
    /**
     * @var FindService
     */
    private $findService;
    /**
     * @var CommentsService
     */
    private $commentsService;
    /**
     * @var RecommendatorService
     */
    private $recommendatorService;

    /**
     * MoviesController constructor.
     * @param MoviesService $moviesService
     * @param FindService $findService
     * @param CommentsService $commentsService
     * @param RecommendatorService $recommendatorService
     */
    public function __construct(MoviesService $moviesService, FindService $findService, CommentsService $commentsService,
                                RecommendatorService $recommendatorService)
    {
        $this->moviesService = $moviesService;
        $this->findService = $findService;
        $this->commentsService = $commentsService;
        $this->recommendatorService = $recommendatorService;
    }


    /**
     * @Route("/popular", name="popular_movies")
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function popular()
    {
        return $this->jsonResponse($this->moviesService->popular());
    }

    /**
     * @Route("/upcoming", name="upcoming_movies")
     */
    public function upcoming()
    {
        dump('ey');
        $data = $this->moviesService->upcoming();

        return $this->jsonResponse($data);
    }


    /**
     * @Route("/{id}", name="getMovies")
     * @param string $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getMovie(string $id, Request $request)
    {
        $data = $this->moviesService->getById($id);
        $data['comments'] = $this->commentsService->getAll($data["_id"]);

        return $this->jsonResponseCached($data, $request);
    }


}
