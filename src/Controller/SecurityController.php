<?php
/**
 * Date: 18/09/2018
 * Time: 4:09
 */

namespace App\Controller;



use App\Auth\UserService;
use App\Form\UserRegistrationForm;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
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

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @Route("/api/security/login", name="login")
     * @param Request $request
     * @param TokenStorageInterface $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @return Response
     * @Method({"POST"})
     */
    public function login(Request $request, TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker)
    {
        return $this->isLogged($tokenStorage, $authorizationChecker);
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
     * @param TokenStorageInterface $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @return Response
     */
    public function isLogged(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker)
    {
        if($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')){

            return $this->jsonResponse(["user" => $tokenStorage->getToken()->getUser()]);
        }
        return $this->jsonResponse(["user" => null]);
    }

    /**
     * @param UserRegistrationForm $user
     * @param ValidatorInterface $validator
     * @return Response
     * @Route("/api/security/register", name="register")
     * @Method({"POST"})
     * @ParamConverter("user", converter="class")
     */
    public function register(UserRegistrationForm $user, ValidatorInterface $validator)
    {
        $errors = $validator->validate($user);
        if(count($errors)>0){

            return $this->noValidPostParamsResponse($errors);
        }
        try{
            $this->userService->register($user);
        } catch (\Exception $exception){
            return $this->error(500, "Couldn't register user");
        }

        return $this->okResponse("User register correctly");
    }

    public function failure(AuthenticationException $exception)
    {
        return $this->jsonResponse(["error"=> 401, "message"=> 'Need Credentials']);
    }


}