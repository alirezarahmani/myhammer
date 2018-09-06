<?php
namespace MyHammer\Library\Event;

use MyHammer\Library\Controller\LoggedUserResolver;
use Digikala\Supernova\Service\CacheService;
use Symfony\Component\EventDispatcher\Event;

class ResponseCacheEvent extends Event
{
    private $controllers = [];
    private $loggedUserResolver;

    public function setLoggedUserResolver(LoggedUserResolver $resolver): self
    {
        $this->loggedUserResolver = $resolver;
        return $this;
    }

    public function addControllerForAll(
        string $controllerClass,
        string $method,
        CacheService $cacheService,
        int $cacheTTL
    ): self {
        return $this->addController($controllerClass, $method, $cacheService, $cacheTTL, false, true);
    }

    public function addControllerUnLoggedOnly(
        string $controllerClass,
        string $method,
        CacheService $cacheService,
        int $cacheTTL
    ): self {
        return $this->addController($controllerClass, $method, $cacheService, $cacheTTL, false, false);
    }

    public function addControllerDividedByLogged(
        string $controllerClass,
        string $method,
        CacheService $cacheService,
        int $cacheTTL
    ): self {
        return $this->addController($controllerClass, $method, $cacheService, $cacheTTL, true, true);
    }

    private function addController(
        string $controllerClass,
        string $method,
        CacheService $cacheService,
        int $cacheTTL,
        bool $divideByLogged,
        bool $withLogged
    ): self {
        if (!isset($this->controllers[$controllerClass])) {
            $this->controllers[$controllerClass] = [];
        }
        $this->controllers[$controllerClass][$method] = [
            $cacheService,
            $cacheTTL,
            $divideByLogged,
            $withLogged
        ];
        return $this;
    }

    public function getController(string $controllerClass, string $method): ?array
    {
        return $this->controllers[$controllerClass][$method] ?? null;
    }

    public function getLoggedUserResolver(): ?LoggedUserResolver
    {
        return $this->loggedUserResolver;
    }
}
