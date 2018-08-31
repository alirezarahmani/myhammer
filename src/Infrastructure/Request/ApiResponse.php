<?php

namespace MyHammer\Infrastructure\Request;

interface IApiResponse
{
    public function asJson(array $input);
}
