<?php
/**
 * Date: 26/10/2018
 * Time: 23:36
 */

namespace App\Controller;


use App\Auth\UserService;
use App\Services\FindService;
use App\Services\MoviesService;
use App\Services\RecommendatorService;
use App\Services\TvShowService;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/main", name="main_")
 */
class MainController extends AbstractController
{

    /**
     * @var FindService
     */
    private $findService;
    /**
     * @var TvShowService
     */
    private $tvShowService;
    /**
     * @var MoviesService
     */
    private $moviesService;
    /**
     * @var RecommendatorService
     */
    private $recommendatorService;
    /**
     * @var UserService
     */
    private $userService;

    public function __construct(FindService $findService, TvShowService $tvShowService, MoviesService $moviesService,
                                RecommendatorService $recommendatorService, UserService $userService)
    {

        $this->findService = $findService;
        $this->tvShowService = $tvShowService;
        $this->moviesService = $moviesService;
        $this->recommendatorService = $recommendatorService;
        $this->userService = $userService;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     * @Route("/all", name="all")
     */
    public function getMainData()
    {
        $result = [];
        $result['popularMovies'] = $this->moviesService->popular();
        $result['playingTvshows'] = $this->tvShowService->upcoming();
        $type = (random_int(0,1)===0) ? 'movie' : 'tvshow';
        $result['userRecommended'] = $this->recommendatorService->findRecommendedShows(
            ['mode' => 'automatic', 'page' => 1, 'type' => $type, 'length'=>1]);

        return $this->jsonResponse($result);
    }
}