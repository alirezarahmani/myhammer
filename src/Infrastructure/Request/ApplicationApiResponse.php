<?php

namespace MyHammer\Infrastructure\Request;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApplicationApiResponse implements ApiResponse
{
    private $errorMessage;

    public function asJson($input)
    {
        return new JsonResponse(
            [
                'status' => (bool)$this->errorMessage,
                'data' => [
                    'result' => $input,
                    'message' => $this->errorMessage,
                ]
            ]
        );
    }
    
    public function error(string $message)
    {
        $this->errorMessage = $message;
        return $this;
    }
}
