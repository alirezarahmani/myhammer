<?php

namespace MyHammer\Infrastructure\Request;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiSuccessResponse  extends JsonResponse implements ApiResponseInterface
{
    private $message;
    protected $data;

    public function __construct($data = null, $message = null)
    {
        parent::__construct($data);
        $this->message = $message;
    }

    public function setJson($data)
    {
        return parent::setJson(
            json_encode(
                [
                    'Data' => json_decode($data),
                    'Status' => 'Success',
                    'Message' => $this->message
                ],
                $this->encodingOptions
            )
        );
    }
}
