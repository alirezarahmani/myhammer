<?php

namespace MyHammer\Infrastructure\Request;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class ApiWebRequest extends ParameterBag implements ApiRequestInterface
{
    /**
     * @var Request $request
     */
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }
}
