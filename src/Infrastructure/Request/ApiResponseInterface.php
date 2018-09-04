<?php

namespace MyHammer\Infrastructure\Request;

interface ApiResponseInterface
{
    public function error(array $errors);
    public function success();
}
