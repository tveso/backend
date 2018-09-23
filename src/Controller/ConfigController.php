<?php

namespace App\Controller;


use App\Services\ConfigService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\Routing\Annotation\Route;

/** @Route("/api/config", name="config_")
 *  @Cache(expires="+3600 seconds")
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
     *
     */
    public function genres()
    {
        $data = $this->configService->getGenres();

       return $this->json($data);
    }
}