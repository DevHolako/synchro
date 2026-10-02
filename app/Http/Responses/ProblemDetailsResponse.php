<?php

namespace App\Http\Responses;

use App\Exceptions\HardConflictException;
use App\Exceptions\SoftConflictException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class ProblemDetailsResponse
{
    public const string CONTENT_TYPE = 'application/problem+json';

    /**
     * Build an RFC 7807 problem details JSON response from an exception.
     */
    public static function fromThrowable(Throwable $e, Request $request): JsonResponse
    {
        $status = self::resolveStatusCode($e);
        $title = self::resolveTitle($e, $status);
        $detail = self::resolveDetail($e, $status);
        $type = self::resolveType($status);
        $instance = $request->getRequestUri();

        $payload = [
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => $instance,
        ];

        if ($e instanceof ValidationException) {
            $payload['errors'] = $e->errors();
            $payload['invalid_params'] = array_map(
                fn (string $field, array $messages): array => [
                    'name' => $field,
                    'reason' => $messages[0] ?? '',
                ],
                array_keys($e->errors()),
                array_values($e->errors()),
            );
        }

        if ($e instanceof HardConflictException || $e instanceof SoftConflictException) {
            $payload['conflicts'] = $e->result->toArray();
        }

        return new JsonResponse($payload, $status, [
            'Content-Type' => self::CONTENT_TYPE,
        ]);
    }

    private static function resolveStatusCode(Throwable $e): int
    {
        if ($e instanceof ValidationException) {
            return $e->status;
        }

        if ($e instanceof AuthenticationException) {
            return Response::HTTP_UNAUTHORIZED;
        }

        if ($e instanceof AuthorizationException) {
            return Response::HTTP_FORBIDDEN;
        }

        if ($e instanceof ModelNotFoundException) {
            return Response::HTTP_NOT_FOUND;
        }

        if ($e instanceof HardConflictException) {
            return Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        if ($e instanceof SoftConflictException) {
            return Response::HTTP_CONFLICT;
        }

        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode();
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private static function resolveTitle(Throwable $e, int $status): string
    {
        if ($e instanceof ValidationException) {
            return 'Validation Failed';
        }

        if ($e instanceof AuthenticationException) {
            return 'Unauthenticated';
        }

        if ($e instanceof AuthorizationException) {
            return 'Forbidden';
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return 'Resource Not Found';
        }

        if ($e instanceof HardConflictException) {
            return 'Hard Scheduling Conflict';
        }

        if ($e instanceof SoftConflictException) {
            return 'Soft Scheduling Conflict';
        }

        return Response::$statusTexts[$status] ?? 'An Error Occurred';
    }

    private static function resolveDetail(Throwable $e, int $status): string
    {
        if ($e instanceof AuthenticationException) {
            return 'Unauthenticated.';
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return 'The requested resource could not be found.';
        }

        if ($status === Response::HTTP_INTERNAL_SERVER_ERROR && ! config('app.debug')) {
            return 'An unexpected error occurred.';
        }

        return $e->getMessage() ?: (Response::$statusTexts[$status] ?? 'An error occurred.');
    }

    private static function resolveType(int $status): string
    {
        return match ($status) {
            Response::HTTP_BAD_REQUEST => 'https://synchro.isga.ma/problems/bad-request',
            Response::HTTP_UNAUTHORIZED => 'https://synchro.isga.ma/problems/unauthenticated',
            Response::HTTP_FORBIDDEN => 'https://synchro.isga.ma/problems/forbidden',
            Response::HTTP_NOT_FOUND => 'https://synchro.isga.ma/problems/not-found',
            Response::HTTP_METHOD_NOT_ALLOWED => 'https://synchro.isga.ma/problems/method-not-allowed',
            Response::HTTP_CONFLICT => 'https://synchro.isga.ma/problems/conflict',
            Response::HTTP_UNPROCESSABLE_ENTITY => 'https://synchro.isga.ma/problems/validation-error',
            Response::HTTP_TOO_MANY_REQUESTS => 'https://synchro.isga.ma/problems/too-many-requests',
            default => 'https://synchro.isga.ma/problems/internal-server-error',
        };
    }
}
