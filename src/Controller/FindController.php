<?php


namespace App\Controller;


use App\Services\FindService;
use App\Services\RecommendatorService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/** @Route("/api/find", name="find_")
 *  @Cache(expires="+3600 seconds")
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
     * FindController constructor.
     * @param FindService $findService
     * @param RecommendatorService $recommenatorService
     */
    public function __construct(FindService $findService, RecommendatorService $recommenatorService)
    {
        $this->findService = $findService;
        $this->recommenatorService = $recommenatorService;
    }


    /**
     * @param string $id
     * @Route("/all", name="all")
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     * @throws \Exception
     */
    public function all(Request $request)
    {
        $options = $request->query->all();
        $data = $this->findService->all($options);

        return $this->jsonResponse($data);
    }

    /**
     * @param string $id
     *  @Route("/recommend/{id}", name="recommend")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function recommend(string $id)
    {
        $data = $this->recommenatorService->recommend($id);

        return $this->json($data);
    }
}