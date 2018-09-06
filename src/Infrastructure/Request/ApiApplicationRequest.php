<?php

namespace MyHammer\Infrastructure\Request;

use MyHammer\Library\Assert\Assertion;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class ApiApplicationRequest extends ParameterBag implements ApiRequestInterface
{
    /**
     * @var Request
     */
    private $request;
    const ANDROID = 'android';
    const IOS = 'ios';
    const SUPPORTED_PLATFORM = [self::ANDROID, self::IOS];
    //@todo: remove hardcoded version
    const SUPPORTED_VERSION = '1.4.5';

    public function __construct(Request $request)
    {
        $this->request = $request;
        Assertion::true($this->request->headers->has('device-type'), 'it seems request is not from mobile');
        Assertion::inArray(
            $device = $this->request->headers->get('device-type'),
            self::SUPPORTED_PLATFORM,
            'sorry, the ' . $device . ' is not supported!'
        );
        Assertion::true(
            $this->request->headers->has('version'),
            'no valid version'
        );
        Assertion::eq(
            self::SUPPORTED_VERSION,
            $version = $this->request->headers->get('version'),
            'the version ' . $version . ' is not a valid version'
        );
    }

    public function getRequest():Request
    {
        return $this->request;
    }

    public function getVersion():string
    {
        return $this->request->headers->get('version');
    }

    public function getDeviceType():string
    {
        return $this->request->headers->get('device-type');
    }
}
