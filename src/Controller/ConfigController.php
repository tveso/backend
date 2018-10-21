<?php

namespace App\Controller;


use App\Services\ConfigService;
use GuzzleHttp\Psr7\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\Routing\Annotation\Route;

/** @Route("/api/config", name="config_")
 */
class ConfigController extends AbstractController
{

    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * ConfigController constructor.
     * @param ConfigService $configService
     */
    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * @Route("/genres", name="genres")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function genres(\Symfony\Component\HttpFoundation\Request $request)
    {
        $data = $this->configService->getGenres();

       return $this->jsonResponseCached($data, $request, 10800);
    }
}