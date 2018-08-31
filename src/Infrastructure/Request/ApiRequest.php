<?php

namespace MyHammer\Infrastructure\Request;

interface ApiRequest
{
    public function get(string $key);
}
