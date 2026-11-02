<?php

declare(strict_types=1);

namespace Core\Exceptions;

use Core\Http\Contracts\RequestInterface;
use Core\Http\Exceptions\HttpException;
use Core\Http\Exceptions\MethodNotAllowedException;
use Core\Http\Exceptions\ValidationException;
use Core\Http\Response;

class Handler
{
    public function render(RequestInterface $request, \Throwable $e): Response
    {
        $statusCode = $e instanceof HttpException ? $e->getStatusCode() : $this->getCalculateStatusCode($e);

        $this->report($e);

        try {
            $response = new Response();

            if ($e instanceof MethodNotAllowedException) {
                $allowedMethodsStr = implode(', ', $e->getAllowedMethods());
                $response = $response->withHeader('Allow', $allowedMethodsStr);
            }

            $userMessage = $this->resolveClientMessage($e, $statusCode);

            if ($this->wantsJson($request)) {
                $jsonPayload = $e instanceof ValidationException
                    ? ['errors' => $e->errors]
                    : ['error' => $userMessage, 'code' => $statusCode];

                return $response
                    ->withStatus($statusCode)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody(json_encode($jsonPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            $viewData = $e instanceof ValidationException
                ? ['errors' => $e->errors]
                : ['message' => $userMessage];

            $htmlBody = view("errors.$statusCode", $viewData);

            return $response->withStatus($statusCode)->withBody($htmlBody);
        } catch (\Throwable $fallbackException) {
            return $this->emergencyResponse($fallbackException);
        }
    }

    private function resolveClientMessage(\Throwable $e, int $statusCode): string
    {
        $isProduction = env('APP_ENV', 'development') === 'production';

        if ($e instanceof HttpException) {
            return $e->getMessage();
        }

        if ($isProduction) {
            return match ($statusCode) {
                404 => 'Resource Not Found.',
                405 => 'Method Not Allowed.',
                default => 'Internal Server Error.',
            };
        }

        return $e->getMessage();
    }

    private function wantsJson(RequestInterface $request): bool
    {
        $acceptHeader = $request->header('Accept') ?? '';

        return $request->isJson() || str_contains($acceptHeader, 'application/json');
    }

    private function report(\Throwable $e): void
    {
        $logMessage = \sprintf(
            '%s in %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        logger()->error($logMessage, [
            'exception' => $e::class,
            'code'      => $e->getCode(),
            'trace'     => $e->getTraceAsString(),
        ]);
    }

    private function getCalculateStatusCode(\Throwable $e): int
    {
        return ($e->getCode() >= 400 && $e->getCode() < 600) ? (int) $e->getCode() : 500;
    }

    private function emergencyResponse(\Throwable $th): Response
    {
        try {
            $isProduction = env('APP_ENV', 'development') === 'production';
            $msg = $isProduction ? 'Internal Server Error' : $th->getMessage();

            return new Response(
                code: 500,
                body: view('errors.500', ['message' => $msg])
            );
        } catch (\Throwable) {
            return new Response(
                code: 500,
                body: '500 Internal Server Error'
            );
        }
    }
}
