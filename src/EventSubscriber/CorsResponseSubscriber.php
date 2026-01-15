<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 256)]
#[AsEventListener(event: KernelEvents::RESPONSE)]
final class CorsResponseSubscriber
{
    private const ALLOWED_METHODS = 'GET, OPTIONS, POST, PUT, PATCH, DELETE';
    private const ALLOWED_HEADERS = 'Content-Type, Authorization';

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->isApiRequest($request->getPathInfo())) {
            return;
        }

        if ($request->getMethod() !== 'OPTIONS') {
            return;
        }

        $response = new Response('', Response::HTTP_NO_CONTENT);
        $this->applyCorsHeaders($response);
        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->isApiRequest($event->getRequest()->getPathInfo())) {
            return;
        }

        $this->applyCorsHeaders($event->getResponse());
    }

    private function applyCorsHeaders(Response $response): void
    {
        $headers = $response->headers;
        $headers->set('Access-Control-Allow-Origin', '*');
        $headers->set('Access-Control-Allow-Methods', self::ALLOWED_METHODS);
        $headers->set('Access-Control-Allow-Headers', self::ALLOWED_HEADERS);
    }

    private function isApiRequest(string $path): bool
    {
        return $path === '/api' || str_starts_with($path, '/api/');
    }
}
