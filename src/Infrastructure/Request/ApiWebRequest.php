<?php

namespace MyHammer\Infrastructure\Request;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class ApiWebRequest extends ParameterBag implements ApiRequestInterface
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
