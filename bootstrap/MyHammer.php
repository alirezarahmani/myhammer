<?php

namespace Loader;

use MyHammer\Domain\Model\Entity\EntityModel;
use MyHammer\Library\Assert\Assertion;
use MyHammer\Library\Cache\Storages\APCUCacheStorage;
use MyHammer\Library\Cache\Storages\MemcachedCacheStorage;
use MyHammer\Library\Service\CacheService;
use MyHammer\Library\Service\MemcachedService;
use MyHammer\Library\Service\MysqlService;
use MyHammer\Library\Service\SettingsService;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

class MyHammer
{
    private static $containerBuilder;

    private function __construct(Container $containerBuilder)
    {
        self::$containerBuilder = $containerBuilder;
    }

    public static function initialize()
    {
        $compiledClassName = 'MyCachedContainer';
        $cacheDir = getenv('VENDOR_DIR') . '/../cache/';
        $cachedContainerFile = "{$cacheDir}container" . '.php';

        if (!is_file($cachedContainerFile)) {
            $configFile = getenv('VENDOR_DIR') . '/../config/setting.yml';
            Assertion::file($configFile, ' the ' . $configFile . ' found.');
            $container = new ContainerBuilder(new ParameterBag());
            $config = Yaml::parse(file_get_contents($configFile));

            $container->register(SettingsService::class, SettingsService::class)->addArgument($config)->setPublic(true);
            $container->register(MemcachedService::class, MemcachedService::class)->setPublic(true);
            $container->register(MemcachedCacheStorage::class, MemcachedCacheStorage::class)
                ->setPublic(true)
                ->addArgument(new Reference(MemcachedService::class));
            $container->register(APCUCacheStorage::class, APCUCacheStorage::class)
                ->setPublic(true);
            $container->register(EntityModel::MY_HAMMER_LOCAL, CacheService::class)
                ->setPublic(true)
                ->addArgument(new Reference(APCUCacheStorage::class));
            $container->register(EntityModel::MY_HAMMER_SHARED, CacheService::class)
                ->setPublic(true)
                ->addArgument(new Reference(MemcachedCacheStorage::class));
            $container->register(MysqlService::class, MysqlService::class)
                ->setPublic(true);
            $container->compile();
            file_put_contents($cachedContainerFile, (new PhpDumper($container))->dump(['class' => $compiledClassName]));
        }
        /** @noinspection PhpIncludeInspection */
        include_once $cachedContainerFile;
        /**
         * @var Container $container
         */
        $container =  new $compiledClassName();
        $request = Request::createFromGlobals();
        $container->set(Request::class, $request);
        new static($container);
    }

    public static function getContainer(): Container
    {
        return self::$containerBuilder;
    }
}
