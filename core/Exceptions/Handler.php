<?php

declare(strict_types=1);

namespace Core\Exceptions;

use Core\Http\Exceptions\HttpException;
use Core\Http\Exceptions\MethodNotAllowedException;
use Core\Http\Exceptions\ValidationException;
use Core\Http\Request;
use Core\Http\Response;

class Handler
{
    public function render(Request $request, \Throwable $e): Response
    {
        $message = $e->getMessage() . "|" . $e->getFile() . ":" . $e->getLine();
        $statusCode = $e instanceof HttpException ? $e->getStatusCode() : $this->getCalculateStatusCode($e);

        try {

            $response = new Response();

            if ($e instanceof MethodNotAllowedException) {
                $allowedMethodsStr = implode(', ', $e->getAllowedMethods());
                $response = $response->withHeader('Allow', $allowedMethodsStr);
            }

            logger()->error($message);

            if (str_contains($request->header('Accept'), 'application/json')) {
                $jsonBody = $e instanceof ValidationException
                    ? json_encode(['errors' => $e->errors])
                    : json_encode(['error' => $e->getMessage(), 'code' => $statusCode]);
                return $response->withStatus($statusCode)->withBody($jsonBody);
            }
            $htmlBody = $e instanceof ValidationException
                ? view("errors.$statusCode", ['errors' => $e->errors])
                : view("errors.$statusCode", ['message' => $e->getMessage()]);
            return $response->withStatus($statusCode)->withBody($htmlBody);
        } catch (\Throwable $th) {
            return (new Response(
                code: 500,
                body: view("errors.500", ['message' => $th->getMessage()]),
            ));
        }
    }

    private function getCalculateStatusCode(\Throwable $e)
    {
        return ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
    }
}
