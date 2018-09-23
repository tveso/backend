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
     * @Route("/upcoming", name="upcoming")
     */
    public function upcoming()
    {
        $data = $this->moviesService->upcoming();

        return $this->jsonResponse($data);
    }

    /**
     * @Route("/search", name="search_movies")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->query->get('query');
        $limit = $request->query->get('limit') ?? 10;
        $page = $request->query->get('page') ?? 1;
        $limit = intval($limit);
        $page = intval($page);
        $data = $this->findService->search($query,$limit,$page);

        return $this->jsonResponse($data);;
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
