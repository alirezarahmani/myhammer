<?php

use MyHammer\Library\Assert\ValidateException;
use PHPUnit\Framework\TestCase;

class ApiApplicationRequestTest extends TestCase
{
    private $request;

    public function setUp()
    {
        parent::setUp();
        $this->jsonRsponse = new \MyHammer\Infrastructure\Request\ApiJsonResponse();
    }

    /** @test */
    public function shouldThrowExceptionWithNoDeviceTypeInHeader()
    {
        $this->assertEquals(new \Symfony\Component\HttpFoundation\JsonResponse(), ->success());
    }
}
