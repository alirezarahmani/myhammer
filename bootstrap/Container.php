<?php

namespace Loader;

use Assert\Assertion;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Yaml\Yaml;


class Boot
{
    public static function getContainer()
    {
        $compiledClassName = 'MyCachedContainer';
        $cacheDir = getenv('PROJECT_DIR') . '/cache/';
        $cachedContainerFile = "{$cacheDir}container" . '.php';
        if (!is_file($cachedContainerFile)) {
//            $configFile = getenv('PROJECT_DIR') . '/config/setting.yml';
//            Assertion::file($configFile, 'sorry, the ' . $configFile . ' found.');
            $container = new ContainerBuilder(new ParameterBag());
//            $config = Yaml::parse(file_get_contents($configFile));
//            $container->register(SettingsService::class, SettingsService::class)->addArgument($config)->setPublic(true);
            $container->compile();
            file_put_contents($cachedContainerFile, (new PhpDumper($container))->dump(['class' => $compiledClassName]));
        }
    }
}
