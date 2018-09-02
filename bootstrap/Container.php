<?php

namespace Loader;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

class Container
{
    public static function load()
    {
        $compiledClassName = 'MyCachedContainer';
        $cacheDir = getenv('VENDOR_DIR') . '/../cache/';
        $cachedContainerFile = "{$cacheDir}container" . '.php';
        if (!is_file($cachedContainerFile)) {
//            $configFile = getenv('VENDOR_DIR') . '/../config/setting.yml';
//            Assertion::file($configFile, 'sorry, the ' . $configFile . ' found.');
            $container = new ContainerBuilder(new ParameterBag());
//            $config = Yaml::parse(file_get_contents($configFile));

//            $container->register('setting', SettingsService::class)->addArgument($config)->setPublic(true);
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
        return $container;
    }
}
