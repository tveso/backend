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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;
use Symfony\Component\Validator\Exception\ValidatorException;

class ExceptionListener implements EventSubscriberInterface
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $exceptionsMessages = [
        InsufficientAuthenticationException::class => ["message"=>"You don't have access to this resource.", "code" => 403],
        ValidatorException::class => ["message" => "Error in the sended parameters", "code" => 400],
        \InvalidArgumentException::class => ["message" => "Error in the sended parameters", "code" => 400],
        AccessDeniedException::class => ["message"=>"You don't have access to this resource.", "code" => 403],
        'default' => ["message" => "There was an error trying to process the request", "code" => 500]
    ];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $this->logger->alert($exception->getMessage());
        $messageCode = $this->getMesage($exception);
        $message = $messageCode["message"];
        $code = $messageCode["code"];
        $message = ["message" => $message, "code"=> $code];
        $response = new JsonResponse(json_encode($message), $code, [], true);
        $response->setMaxAge('3600');

        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return array(
            'kernel.exception' => 'onKernelException'
        );
    }

    private function getMesage(\Exception $exception)
    {
        if(isset($this->exceptionsMessages[get_class($exception)])){
            return $this->exceptionsMessages[get_class($exception)];
        }

        return $this->exceptionsMessages["default"];
    }
}