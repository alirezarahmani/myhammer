<?php
namespace MyHammer\Library\Event;

use Digikala\Supernova\Services;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class RouteEvent extends Event
{

    use Services;

    private $request;
    private $isMobile;
    private $controllers = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->isMobile = $this->serviceMobile()->isMobile();
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function addController(
        string $controllerClass,
        string $uri,
        string $method,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        array $methods = [],
        string $condition = ''
    ): self {
        return $this->addNamedController(
            "$controllerClass:$method",
            $controllerClass,
            $uri,
            $method,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            $methods,
            $condition
        );
    }

    public function addDesktopMobileController(
        string $desktopControllerClass,
        string $mobileControllerClass,
        string $uri,
        string $method,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        array $methods = [],
        string $condition = ''
    ): self {
        return $this->addController(
            $this->isMobile ? $mobileControllerClass : $desktopControllerClass,
            $uri,
            $method,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            $methods,
            $condition
        );
    }

    public function addDesktopController(
        string $controllerClass,
        string $uri,
        string $method,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        array $methods = [],
        string $condition = ''
    ): self {
        if ($this->isMobile) {
            return $this;
        }
        return $this->addController(
            $controllerClass,
            $uri,
            $method,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            $methods,
            $condition
        );
    }

    public function addMobileController(
        string $controllerClass,
        string $uri,
        string $method,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        array $methods = [],
        string $condition = ''
    ): self {
        if (!$this->isMobile) {
            return $this;
        }
        return $this->addController(
            $controllerClass,
            $uri,
            $method,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            $methods,
            $condition
        );
    }

    public function addGetController(
        string $controllerClass,
        string $uri,
        string $method,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        string $condition = ''
    ): self {
        return $this->addNamedController(
            "$controllerClass:$method",
            $controllerClass,
            $uri,
            $method,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            ['get'],
            $condition
        );
    }

    public function addDesktopMobileGetController(
        string $desktopControllerClass,
        string $mobileControllerClass,
        string $uri,
        string $method,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        string $condition = ''
    ): self {
        return $this->addController(
            $this->isMobile ? $mobileControllerClass : $desktopControllerClass,
            $uri,
            $method,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            ['get'],
            $condition
        );
    }

    public function addDesktopGetController(
        string $controllerClass,
        string $uri,
        string $method,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        string $condition = ''
    ): self {
        if ($this->isMobile) {
            return $this;
        }
        return $this->addController(
            $controllerClass,
            $uri,
            $method,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            ['get'],
            $condition
        );
    }

    public function addMobileGetController(
        string $controllerClass,
        string $uri,
        string $method,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        string $condition = ''
    ): self {
        if (!$this->isMobile) {
            return $this;
        }
        return $this->addController(
            $controllerClass,
            $uri,
            $method,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            ['get'],
            $condition
        );
    }

    public function addPostController(
        string $controllerClass,
        string $uri,
        string $method,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        string $condition = ''
    ): self {
        return $this->addNamedController(
            "$controllerClass:$method",
            $controllerClass,
            $uri,
            $method,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            ['post'],
            $condition
        );
    }

    public function addDesktopMobilePostController(
        string $desktopControllerClass,
        string $mobileControllerClass,
        string $uri,
        string $method,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        string $condition = ''
    ): self {
        return $this->addController(
            $this->isMobile ? $mobileControllerClass : $desktopControllerClass,
            $uri,
            $method,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            ['post'],
            $condition
        );
    }

    public function addDesktopPostController(
        string $controllerClass,
        string $uri,
        string $method,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        string $condition = ''
    ): self {
        if ($this->isMobile) {
            return $this;
        }
        return $this->addController(
            $controllerClass,
            $uri,
            $method,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            ['post'],
            $condition
        );
    }

    public function addMobilePostController(
        string $controllerClass,
        string $uri,
        string $method,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        string $condition = ''
    ): self {
        if (!$this->isMobile) {
            return $this;
        }
        return $this->addController(
            $controllerClass,
            $uri,
            $method,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            ['post'],
            $condition
        );
    }

    public function addPutController(
        string $controllerClass,
        string $uri,
        string $method,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        string $condition = ''
    ): self {
        return $this->addNamedController(
            "$controllerClass:$method",
            $controllerClass,
            $uri,
            $method,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            ['put'],
            $condition
        );
    }

    public function addDesktopMobilePutController(
        string $desktopControllerClass,
        string $mobileControllerClass,
        string $uri,
        string $method,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        string $condition = ''
    ): self {
        return $this->addController(
            $this->isMobile ? $mobileControllerClass : $desktopControllerClass,
            $uri,
            $method,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            ['put'],
            $condition
        );
    }

    public function addDesktopPutController(
        string $controllerClass,
        string $uri,
        string $method,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        string $condition = ''
    ): self {
        if ($this->isMobile) {
            return $this;
        }
        return $this->addController(
            $controllerClass,
            $uri,
            $method,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            ['put'],
            $condition
        );
    }

    public function addMobilePutController(
        string $controllerClass,
        string $uri,
        string $method,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        string $condition = ''
    ): self {
        if (!$this->isMobile) {
            return $this;
        }
        return $this->addController(
            $controllerClass,
            $uri,
            $method,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            ['put'],
            $condition
        );
    }

    public function addNamedController(
        string $name,
        string $controllerClass,
        string $uri,
        string $method,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        array $methods = [],
        string $condition = ''
    ): self {
        return $this->addEntry(
            $controllerClass,
            $uri,
            $method,
            $name,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            $methods,
            $condition
        );
    }

    public function addControllers(string $controllerClass, array $methods): self
    {
        foreach ($methods as $uri => $method) {
            $this->addController($controllerClass, $uri, $method);
        }
        return $this;
    }

    public function addDesktopMobileControllers(string $desktopControllerClass, string $mobileontrollerClass, array $methods): self
    {
        return $this->addControllers($this->isMobile ? $mobileontrollerClass : $desktopControllerClass, $methods);
    }

    public function getControllers(): array
    {
        return $this->controllers;
    }

    private function addEntry(
        string $controllerClass,
        string $uri,
        string $method,
        string $name,
        array $defaults = null,
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        array $methods = [],
        string $condition = ''
    ): self {
        if (!isset($this->controllers[$controllerClass])) {
            $this->controllers[$controllerClass] = [];
        }
        $this->controllers[$controllerClass][$name] = [
            $uri,
            $method,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            $methods,
            $condition
        ];
        return $this;
    }
}
