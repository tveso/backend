<?php
/**
 * Date: 18/09/2018
 * Time: 4:09
 */

namespace App\Controller;



use App\Auth\UserService;
use App\Form\UserRegistrationForm;
use App\Services\Auth\GoogleAuthService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 *
 **/
class SecurityController extends AbstractController
{


    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var GoogleAuthService
     */
    private $googleAuthService;

    public function __construct(UserService $userService, GoogleAuthService $googleAuthService)
    {
        $this->userService = $userService;
        $this->googleAuthService = $googleAuthService;
    }

    /**
     * @Route("/api/security/login", name="login")
     * @param TokenStorageInterface $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param Request $request
     * @return Response
     * @throws \Exception
     * @Method({"POST", "GET"})
     */
    public function login(TokenStorageInterface $tokenStorage,
                          AuthorizationCheckerInterface $authorizationChecker,
                          Request $request)
    {
        return $this->isLogged( $request,$tokenStorage, $authorizationChecker);
    }


    /**
     * @Route("/api/security/auth/google", name="googlelogin")
     * @param Request $request
     * @param TokenStorageInterface $tokenStorage
     * @param AuthenticationManagerInterface $authenticationManager
     * @return Response
     * @throws \Exception
     * @Method({"POST"})
     */
    public function googlelogin(Request $request, TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker)
    {
        $token = $request->request->get('token');
        $this->googleAuthService->loginOrSignUp($token);

        return $this->isLogged( $request,$tokenStorage, $authorizationChecker);
    }

    /**
     * @Route("/api/security/logout", name="logout")
     */
    public function logout()
    {
        return $this->okResponse();
    }

    /**
     * @Route("/api/security/islogged", name="islogged")
     * @param Request $request
     * @param TokenStorageInterface $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @return Response
     * @throws \Exception
     */
    public function checkLogged(Request $request, TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker)
    {
        return $this->isLogged($request, $tokenStorage, $authorizationChecker);
    }

    /**
     * @param UserRegistrationForm $user
     * @param ValidatorInterface $validator
     * @param Request $request
     * @param TokenStorageInterface $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @return Response
     * @throws \Exception
     * @Route("/api/security/register", name="register")
     * @Method({"POST"})
     * @ParamConverter("user", converter="class")
     */
    public function register(UserRegistrationForm $user, ValidatorInterface $validator, Request $request,
                             TokenStorageInterface $tokenStorage,
                             AuthorizationCheckerInterface $authorizationChecker)
    {
        $errors = $validator->validate($user);
        if(count($errors)>0){

            return $this->noValidPostParamsResponse($errors);
        }
        try{
            $user = $this->userService->registerFromFrom($user);
            $this->userService->login(["_id" => $user['_id']]);
        } catch (\Exception $exception){
            var_dump($exception);
            return $this->error(500, "Couldn't register user");
        }

        return $this->isLogged($request,  $tokenStorage, $authorizationChecker);
    }

    public function failure(AuthenticationException $exception)
    {
        return $this->jsonResponse(["error"=> 401, "message"=> 'Need Credentials']);
    }


}