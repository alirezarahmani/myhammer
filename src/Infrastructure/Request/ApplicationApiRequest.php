<?php

namespace MyHammer\Infrastructure\Request;

use Symfony\Component\HttpFoundation\Request;

class ApplicationApiRequest implements ApiRequest
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function get(string $key)
    {
        // TODO: Implement get() method.
    }
}
