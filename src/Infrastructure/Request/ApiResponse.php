<?php

namespace MyHammer\Infrastructure\Request;

interface ApiResponse
{
    public function asJson(array $input);
    public function error(string $message);
}
