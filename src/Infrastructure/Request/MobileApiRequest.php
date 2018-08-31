<?php

namespace MyHammer\Infrastructure;

use Symfony\Component\HttpFoundation\Request;

class MobileApiRequest implements IApiRequest
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function get()
    {
        // TODO: Implement get() method.
    }
}
