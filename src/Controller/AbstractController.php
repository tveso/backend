<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as AC;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolation;
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
}
