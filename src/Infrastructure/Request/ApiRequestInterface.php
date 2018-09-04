<?php

namespace MyHammer\Infrastructure\Request;

use Symfony\Component\HttpFoundation\Request;

interface ApiRequestInterface
{
    public function get(string $key);
    public function getRequest():Request;
}
