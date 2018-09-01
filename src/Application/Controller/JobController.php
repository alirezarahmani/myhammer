<?php

namespace MyHammer\Application\Controller;

use MyHammer\Infrastructure\Request\RequestInterface;
use MyHammer\Infrastructure\Request\ApiResponseInterface;
use MyHammer\Library\Assert\ValidateException;

class JobController
{
    public function createAction(RequestInterface $request, ApiResponseInterface $response)
    {
        try {
            return $response->asJson();
        } catch (ValidateException $validateException) {
            return $response->error($validateException->getMessage())->asJson();
        }

    }
}
