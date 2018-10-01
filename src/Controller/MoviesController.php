<?php

namespace App\Controller;

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
     * MoviesController constructor.
     * @param MoviesService $moviesService
     * @param FindService $findService
     */
    public function __construct(MoviesService $moviesService, FindService $findService)
    {
        $this->moviesService = $moviesService;
        $this->findService = $findService;
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

        return $this->jsonResponse($data);
    }


}
