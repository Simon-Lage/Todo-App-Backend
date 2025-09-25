<?php

declare(strict_types=1);

namespace App\Http\Request;

use App\Exception\ApiProblemException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RequestDtoValueResolver implements ValueResolverInterface
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $argument->getType();

        if ($type === null || !is_subclass_of($type, JsonRequestDto::class)) {
            return [];
        }

        $payload = $request->getContent();
        $decoded = $payload === '' ? [] : $this->decodeJson($payload);

        try {
            $dto = $type::fromArray($decoded);
        } catch (\InvalidArgumentException $exception) {
            throw ApiProblemException::fromStatus(400, 'Bad Request', $exception->getMessage() ?: 'Invalid request payload.', 'PAYLOAD_INVALID');
        }

        $violations = $this->validator->validate($dto);

        if ($violations->count() > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$this->resolvePropertyPath($violation)][] = $violation->getMessage();
            }

            throw ApiProblemException::validation($errors);
        }

        return [$dto];
    }

    private function decodeJson(string $payload): array
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw ApiProblemException::fromStatus(400, 'Bad Request', 'Malformed JSON payload.', 'PAYLOAD_INVALID');
        }

        if (!is_array($decoded)) {
            throw ApiProblemException::fromStatus(400, 'Bad Request', 'JSON payload must be an object.', 'PAYLOAD_INVALID');
        }

        return $decoded;
    }

    private function resolvePropertyPath(ConstraintViolationInterface $violation): string
    {
        $path = $violation->getPropertyPath();

        return $path === '' ? 'payload' : $path;
    }
}
