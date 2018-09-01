<?php

namespace MyHammer\Infrastructure\Request;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiErrorResponse  extends JsonResponse implements ApiResponseInterface
{
    private $message;

    public function __construct($message = null)
    {
        $this->message = $message;
    }

    public function setJson()
    {
        return parent::setJson(
            json_encode(
                [
                    'Data' => null,
                    'Status' => 'Failed',
                    'Message' => $this->message
                ],
                $this->encodingOptions
            )
        );
    }
}
