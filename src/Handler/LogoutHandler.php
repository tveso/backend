<?php
/**
 * Date: 29/09/2018
 * Time: 3:16
 */

namespace App\Handler;


use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutHandler implements LogoutSuccessHandlerInterface
{



    /**
     * Creates a Response object to send upon a successful logout.
     *
     * @return Response never null
     */
    public function onLogoutSuccess(Request $request)
    {
        return new JsonResponse(["message"=> "Sucesfull Logged out", "code" => "200"]);
    }
}