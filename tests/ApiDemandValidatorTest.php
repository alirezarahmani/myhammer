<?php

use MyHammer\Library\Assert\ValidateException;
use PHPUnit\Framework\TestCase;

class ApiJsonResponseTest extends TestCase
{
    private $jsonResponse;

    public function setUp()
    {
        parent::setUp();
        $this->jsonResponse = new \MyHammer\Infrastructure\Request\ApiJsonResponse();
    }

    /** @test */
    public function successResponseWithPayloadShouldBeEqual()
    {
        $this->assertEquals(
            new \Symfony\Component\HttpFoundation\JsonResponse([
                'Data' => ['hi'],
                'Status' => true,
                'Message' => ''
            ]),
            $this->jsonResponse->success(['hi'])
        );
    }

    /** @test */
    public function errorResponseWithPayloadShouldBeEqual()
    {
        $this->assertEquals(
            new \Symfony\Component\HttpFoundation\JsonResponse([
                'Data' => [],
                'Status' => false,
                'Message' => []
            ]),
            $this->jsonResponse->error([])
        );
    }
}
