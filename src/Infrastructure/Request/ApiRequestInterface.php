<?php

namespace MyHammer\Infrastructure\Request;

interface ApiRequestInterface
{
    public function get(string $key);
}
