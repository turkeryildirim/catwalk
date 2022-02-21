<?php

namespace Frontastic\Catwalk\NextJsBundle\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class CorsHandler
{
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $method = $event->getRequest()->getRealMethod();

        if ($method === 'OPTIONS') {
            $event->setResponse(new Response());
        }
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;
        $origin = $event->getRequest()->headers->get('origin') ?? $event->getRequest()->getHost();

        $headers->set('Access-Control-Allow-Origin', $origin);
        $headers->set('Access-Control-Allow-Methods', '*');
        $headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Cookie, Frontastic-Session, X-Frontastic-Access-Token');
        $headers->set('Access-Control-Expose-Headers', ' *, Authorization, Frontastic-Session, X-Frontastic-Access-Token');
        $headers->set('Access-Control-Allow-Credentials', 'true');
    }
}
