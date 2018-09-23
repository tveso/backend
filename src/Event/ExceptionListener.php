<?php
/**
 * Date: 13/09/2018
 * Time: 2:39
 */

namespace App\Event;


use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class ExceptionListener implements EventSubscriberInterface
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $this->logger->alert($exception->getMessage());
        $message = ["error"=> 500, "message" => "There was an error trying to process the request"];
        $response = new JsonResponse(json_encode($message), 500,[], true);
        if($exception instanceof InsufficientAuthenticationException){
            $message = ["error"=> 403, "message" => "You don't have access to this resource."];
            $response = new JsonResponse(json_encode($message), 403, [], true);
        }
        $response->setMaxAge('3600');

        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return array(
            'kernel.exception' => 'onKernelException'
        );
    }
}