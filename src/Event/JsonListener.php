<?php
/**
 * Date: 19/09/2018
 * Time: 2:46
 */

namespace App\Event;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class JsonListener implements EventSubscriberInterface
{


    public function onKernelRequest(GetResponseEvent $event)
    {
       $request = $event->getRequest();
       if(!$this->isJsonRequest($request)){
           return false;
       }
       if($this->transform($request)){
           return true;
       }

       return false;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'kernel.request' => 'onKernelRequest'
        );
    }

    private function isJsonRequest(Request $request)
    {
        $contentType = $request->getContentType();
        return strstr($contentType, "json") && !is_null($request->getContentType());
    }

    private function transform(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        $request->request->replace($data);
    }



}