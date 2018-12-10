<?php
/**
 * Date: 26/10/2018
 * Time: 23:36
 */

namespace App\Controller;


use App\Auth\UserService;
use App\Services\EpisodeService;
use App\Services\FindService;
use App\Services\MoviesService;
use App\Services\RecommendatorService;
use App\Services\TvShowService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;

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
    /**
     * @var EpisodeService
     */
    private $episodeService;

    public function __construct(FindService $findService, TvShowService $tvShowService, MoviesService $moviesService,
                                RecommendatorService $recommendatorService, UserService $userService, EpisodeService $episodeService)
    {

        $this->findService = $findService;
        $this->tvShowService = $tvShowService;
        $this->moviesService = $moviesService;
        $this->recommendatorService = $recommendatorService;
        $this->userService = $userService;
        $this->episodeService = $episodeService;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     * @Route("/all", name="all")
     */
    public function getMainData()
    {
        $result = [];
        $result['popularMovies'] = $this->moviesService->popular(11);
        $result['playingTvshows'] = $this->tvShowService->onAir(6);
        $result['pendingEpisodes'] = $this->episodeService->findPendingEpisodes(7);

        return $this->jsonResponse($result);
    }
}