<?php

use MyHammer\Library\Assert\ValidateException;
use PHPUnit\Framework\TestCase;

class ApiApplicationRequestTest extends TestCase
{
    private $request;

    public function setUp()
    {
        parent::setUp();
        $this->request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    }

    /** @test */
    public function shouldThrowExceptionWithNoDeviceTypeInHeader()
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('it seems request is not from mobile');
        new \MyHammer\Infrastructure\Request\ApiApplicationRequest($this->request);
    }

    /** @test */
    public function shouldThrowExceptionWithWrongDeviceTypeInHeader()
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('sorry, the WINDOWS is not supported!');
        $this->request->headers->set('device-type', 'WINDOWS');
        new \MyHammer\Infrastructure\Request\ApiApplicationRequest($this->request);
    }

    /** @test */
    public function shouldNotThrowExceptionWithAndroidDeviceTypeInHeader()
    {
        $this->request->headers->set('device-type', 'android');
        $this->request->headers->set('version', '1.4.5');
        new \MyHammer\Infrastructure\Request\ApiApplicationRequest($this->request);
        $this->assertTrue(true);
    }

    /** @test */
    public function shouldNotThrowExceptionWithIosDeviceTypeInHeader()
    {
        $this->request->headers->set('device-type', 'ios');
        $this->request->headers->set('version', '1.4.5');
        new \MyHammer\Infrastructure\Request\ApiApplicationRequest($this->request);
        $this->assertTrue(true);
    }

    /** @test */
    public function shouldThrowExceptionWithNoVersionInHeader()
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('no valid version');
        $this->request->headers->set('device-type', \MyHammer\Infrastructure\Request\ApiApplicationRequest::ANDROID);
        new \MyHammer\Infrastructure\Request\ApiApplicationRequest($this->request);
    }

    /** @test */
    public function shouldGetRightVersionFromHeader()
    {
        $this->request->headers->set('device-type', \MyHammer\Infrastructure\Request\ApiApplicationRequest::ANDROID);
        $this->request->headers->set('version', '1.4.5');
        $requestApi = new \MyHammer\Infrastructure\Request\ApiApplicationRequest($this->request);
        $this->assertEquals($requestApi->getVersion(), '1.4.5');
    }

    /** @test */
    public function shouldGetRightDeviceTypeFromHeader()
    {
        $this->request->headers->set('device-type', \MyHammer\Infrastructure\Request\ApiApplicationRequest::ANDROID);
        $this->request->headers->set('version', '1.4.5');
        $requestApi = new \MyHammer\Infrastructure\Request\ApiApplicationRequest($this->request);
        $this->assertEquals($requestApi->getDeviceType(), \MyHammer\Infrastructure\Request\ApiApplicationRequest::ANDROID);
    }
}
