<?php


namespace App\Controller;


use App\Form\CommentForm;
use App\Services\CommentsService;
use App\Services\RecommendatorService;
use App\Services\TheMovieDb\TmdbMovieService;
use App\Services\TheMovieDb\TmdbTvShowService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Stopwatch\Stopwatch;


/**
 * @Route("/api/recommend", name="recommend_")
 */
class RecommendatorController extends AbstractController
{

    /**
     * @var
     */
    private $recommendatorService;


    /**
     * FollowController constructor.
     * @param RecommendatorService $recommendatorService
     */
    public function __construct(RecommendatorService $recommendatorService)
    {

        $this->recommendatorService = $recommendatorService;
    }


    /**
     * @param string $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @Route("/query", name="byquery")
     * @throws \Exception
     */
    public function recommendByQuery( Request $request)
    {
        $ids = (explode(",", $request->query->get('shows'))) ?? [];
        $page = intval(($request->get('page')) ?? 1);
        $type = $request->get('type');
        $mode = ($request->get('mode')) ?? 'automatic';
        $query = ['shows' => $ids, 'page' => $page, 'type' => $type, 'mode' => $mode];
        $data = $this->recommendatorService->findRecommendedShows($query);
        return $this->jsonResponse($data);
    }

    /**
     * @param string $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @Route("/{id}", name="byshow")
     * @throws \Exception
     */
    public function recommend(string $id, Request $request)
    {
        $page = ($request->query->get('page')) ?? 1;
        $data = $this->recommendatorService->recommendByShowId($id, $page);
        return $this->jsonResponseCached($data, $request);
    }


}