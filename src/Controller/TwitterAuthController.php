<?php

namespace App\Controller;


use App\Services\Auth\TwitterAuthService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;


/** @Route("/api/security/auth/twitter", name="config_")
 */
class TwitterAuthController extends AbstractController
{

    /**
     * @var TwitterAuthService
     */
    private $twitterAuthService;

    public function __construct(TwitterAuthService $twitterAuthService)
    {

        $this->twitterAuthService = $twitterAuthService;
    }

    /**
     * @Route("/url", name="get")
     * @param Request $request
     * @return Response
     * @throws \Abraham\TwitterOAuth\TwitterOAuthException
     */
    public function getUrl()
    {
        $data = $this->twitterAuthService->getAuthUrl("http://tveso.tv:4200/login");

        return $this->jsonResponse(["url" => $data]);
    }

    /**
     * @Route("/register", name="register")
     * @param Request $request
     * @param TokenStorageInterface $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @return Response
     * @throws \Abraham\TwitterOAuth\TwitterOAuthException
     * @throws \Exception
     */
    public function register(Request $request, TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker)
    {
        $verifierToken = $request->query->get('oauth_verifier');
        if(is_null($verifierToken)) {
            throw new \Exception();
        }
        $this->twitterAuthService->logInOrSignup($verifierToken);


        return $this->isLogged( $request,$tokenStorage, $authorizationChecker);
    }
}