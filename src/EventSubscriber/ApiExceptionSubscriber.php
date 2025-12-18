<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\ApiProblemException;
use App\Http\Response\ProblemResponseFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\ConstraintViolationInterface;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ApiExceptionSubscriber
{
    public function __construct(private readonly ProblemResponseFactory $problemResponseFactory, private readonly LoggerInterface $logger, private readonly bool $debug)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable instanceof ApiProblemException) {
            $event->setResponse($this->problemResponseFactory->create($throwable));
            return;
        }

        if ($throwable instanceof ValidationFailedException) {
            $event->setResponse($this->problemResponseFactory->create(ApiProblemException::validation($this->formatViolations($throwable->getViolations()))));
            return;
        }

        if ($throwable instanceof AuthenticationException) {
            $event->setResponse($this->problemResponseFactory->create(ApiProblemException::unauthorized('Authentication failed.')));
            return;
        }

        if ($throwable instanceof AccessDeniedException || $throwable instanceof AccessDeniedHttpException) {
            $event->setResponse($this->problemResponseFactory->create(ApiProblemException::forbidden()));
            return;
        }

        if ($throwable instanceof NotFoundHttpException) {
            $event->setResponse($this->problemResponseFactory->create(ApiProblemException::notFound()));
            return;
        }

        if ($throwable instanceof MethodNotAllowedHttpException) {
            $event->setResponse($this->problemResponseFactory->create(ApiProblemException::fromStatus(Response::HTTP_METHOD_NOT_ALLOWED, 'Method Not Allowed', 'The requested HTTP method is not allowed.', 'METHOD_NOT_ALLOWED')));
            return;
        }

        if ($throwable instanceof HttpExceptionInterface) {
            $event->setResponse($this->problemResponseFactory->create(ApiProblemException::fromStatus($throwable->getStatusCode(), Response::$statusTexts[$throwable->getStatusCode()] ?? 'Error', $throwable->getMessage() ?: 'Request failed.', strtoupper(str_replace(' ', '_', Response::$statusTexts[$throwable->getStatusCode()] ?? 'ERROR')), [], $throwable->getHeaders())));
            return;
        }

        $this->logger->error('Unhandled exception', ['exception' => $throwable]);

        $event->setResponse($this->problemResponseFactory->create(ApiProblemException::internal($this->debug ? $throwable->getMessage() : 'An unexpected error occurred.')));
    }

    private function formatViolations(iterable $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            if (!$violation instanceof ConstraintViolationInterface) {
                continue;
            }

            $path = $violation->getPropertyPath();
            $key = $path === '' ? 'payload' : $path;
            $errors[$key][] = $violation->getMessage();
        }

        return $errors;
    }
}
