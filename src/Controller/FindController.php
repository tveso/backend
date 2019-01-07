<?php


namespace App\Controller;


use App\Services\FindService;
use App\Services\RecommendatorService;
use App\Services\SearchService;
use App\Services\ShowService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\Routing\Annotation\Route;


/** @Route("/api/find", name="find_")
 */
class FindController extends AbstractController
{

    /**
     * @var FindService
     */
    private $findService;

    /**
     * @var RecommendatorService
     */
    private $recommenatorService;
    /**
     * @var ShowService
     */
    private $showService;
    /**
     * @var SearchService
     */
    private $searchService;

    /**
     * FindController constructor.
     * @param FindService $findService
     * @param RecommendatorService $recommenatorService
     * @param ShowService $showService
     * @param SearchService $searchService
     */
    public function __construct(FindService $findService, RecommendatorService $recommenatorService,
                                ShowService $showService, SearchService $searchService)
    {
        $this->findService = $findService;
        $this->recommenatorService = $recommenatorService;
        $this->showService = $showService;
        $this->searchService = $searchService;
    }

    /**
     * @Route("/search", name="search")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->query->get('query');
        $limit = $request->query->get('limit') ?? 10;
        $page = $request->query->get('page') ?? 1;
        $full = $request->query->get('full');
        $full = filter_var($full, FILTER_VALIDATE_BOOLEAN);
        $limit = intval($limit);
        $page = intval($page);
        $data = $this->searchService->search($query,$limit,$page, $full);

        return $this->json($data);
    }


    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     * @Route("/all", name="all")
     * @throws \Exception
     */
    public function all(Request $request)
    {
        $options = $request->query->all();
        $data = $this->showService->filter($options);

        return $this->json($data);
    }




}