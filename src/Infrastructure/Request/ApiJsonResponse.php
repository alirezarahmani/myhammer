<?php

namespace MyHammer\Infrastructure\Request;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiJsonResponse extends JsonResponse implements ApiResponseInterface
{
    private $message;
    protected $data;

    public function __construct($data = null)
    {
        parent::__construct($data);
    }

    public function error(string $message)
    {
        $this->message = $message;
        return $this;
    }

    public function setJson($data)
    {
        return parent::setJson(
            json_encode(
                [
                    'Data' => json_decode($data),
                    'Status' => $this->message ? 'True' : 'False',
                    'Message' => $this->message
                ],
                $this->encodingOptions
            )
        );
    }
}
