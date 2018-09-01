<?php

namespace MyHammer\Infrastructure\Request;

interface ApiResponseInterface
{
    public function error(string $message);
}
