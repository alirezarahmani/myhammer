<?php

namespace MyHammer\Infrastructure\Request;

interface RequestInterface
{
    public function get(string $key);
}
