<?php
/**
 * Date: 13/09/2018
 * Time: 2:39
 */

namespace App\Event;


use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class ParamConverterListener implements EventSubscriberInterface
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $event->getController();
    }

    public static function getSubscribedEvents()
    {
        return array(
            'kernel.controller' => 'onKernelController'
        );
    }
}