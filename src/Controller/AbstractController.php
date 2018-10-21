<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as AC;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Symfony\Component\Validator\ConstraintViolationListInterface;


abstract class AbstractController extends AC
{
    public function index()
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/AbstractController.php',
        ]);
    }

    public function jsonResponse($data, int $code = 200, array $headers = [])
    {
        if(is_null($data)){
            throw new NotFoundHttpException();
        }
        $response = $this->json($data, $code, $headers);

        return $response;
    }

    protected  function noValidPostParamsResponse(ConstraintViolationListInterface $errors)
    {
        $arrError = [];
        foreach ($errors as $value){
            $arrError[$value->getPropertyPath()] = $value->getMessage();
        }
        return $this->error(500, "Data sended is not validated", ["errors" => $arrError]);
    }

    protected function okResponse(string $message = "OK")
    {
        $data = ["code"=> 200, "message"=>$message];

        return $this->jsonResponse($data, 200);
    }

    protected function error(int $code, string $message="", array $data = [])
     {
        $data = ["code"=> $code, "message"=>$message] + $data;

        return $this->jsonResponse($data,$code);
    }


    /**
     * @param $data
     * @param Request $request
     * @param int $maxAge
     * @return JsonResponse
     * @throws \Exception
     */
    protected function jsonResponseCached($data, Request $request, int $maxAge = 3600)
    {
        $response = new JsonResponse($data);

        return $response;
    }

    /**
     * @param Request $request
     * @param TokenStorageInterface $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @return JsonResponse
     * @throws \Exception
     */
    protected function isLogged(Request $request, TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker)
    {
        if($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')){

            return $this->jsonResponseCached(["user" => $tokenStorage->getToken()->getUser()], $request);
        }
        return $this->jsonResponse(["user" => null]);
    }
}
