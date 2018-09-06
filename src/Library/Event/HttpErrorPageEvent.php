<?php
namespace MyHammer\Library\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpErrorPageEvent extends Event
{
    private $exception;
    private $request;
    private $response;

    public function __construct(\Exception $exception, Request $request, Response $response)
    {
        $this->exception = $exception;
        $this->request = $request;
        $this->response = $response;
    }

    public function getException() : \Exception
    {
        return $this->exception;
    }

    public function getRequest() : Request
    {
        return $this->request;
    }

    public function getResponse() : Response
    {
        return $this->response;
    }
}
