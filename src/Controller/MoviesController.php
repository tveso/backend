<?php

namespace App\Controller;

use App\Services\CommentsService;
use App\Services\FindService;
use App\Services\MoviesService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/** @Route("/api/movies", name="movies_")
 *  @Cache(expires="+3600 seconds")
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
     * MoviesController constructor.
     * @param MoviesService $moviesService
     * @param FindService $findService
     * @param CommentsService $commentsService
     */
    public function __construct(MoviesService $moviesService, FindService $findService, CommentsService $commentsService)
    {
        $this->moviesService = $moviesService;
        $this->findService = $findService;
        $this->commentsService = $commentsService;
    }


    /**
     * @Route("/popular", name="popular_movies")
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
        $data = $this->moviesService->upcoming();

        return $this->jsonResponse($data);
    }


    /**
     * @Route("/{id}", name="getMovies")
     * @param string $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getMovie(string $id)
    {
        $data = $this->moviesService->getById($id);
        $data['comments'] = $this->commentsService->getAll($data["_id"]);

        return $this->jsonResponse($data);
    }


}
