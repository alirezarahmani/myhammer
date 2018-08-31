<?php

namespace MyHammer\Application\Controller;

use MyHammer\Infrastructure\Request\ApiRequest;
use MyHammer\Infrastructure\Request\ApiResponse;
use MyHammer\Library\Assert\ValidateException;

class JobController
{
    public function createAction(ApiRequest $request, ApiResponse $response)
    {
        try {
            return $response->asJson();
        } catch (ValidateException $validateException) {
            return $response->error($validateException->getMessage())->asJson();
        }
    }
}
