<?php
/**
 * Date: 13/09/2018
 * Time: 3:05
 */

namespace App\Controller;


use App\Services\TvShowService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/** @Route("/api/links", name="links_")
 *  @Cache(expires="+3600 seconds")
 */
class LinkController extends AbstractController
{
    private $tvshowService;

    /**
     * LinkController constructor.
     * @param $tvshowService
     */
    public function __construct(TvShowService $tvshowService)
    {
        $this->tvshowService = $tvshowService;
    }


    /**
     * @param string $id
     * @Route("/{id}", name="get")
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     * @throws \Exception
     */
    public function getEpisodeLink(string $id)
    {
        $id = $this->tvshowService->getLinkUrl($id);

        return $this->json(["link" => $id]);
    }

}