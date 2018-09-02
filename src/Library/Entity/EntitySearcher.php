<?php
namespace MyHammer\Library\Entity;

use MyHammer\Library\Service;
use MyHammer\Library\Supernova;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class EntitySearcher extends Service
{

    /**
     * @param string[] $extensions list of extensions to scan for entities
     * @return Entity[]
     */
    public function getEntities(array $extensions = null): array
    {
        return $this->getInstance(
            'entity:list:' . print_r($extensions, true),
            function () use ($extensions) {
                $entities = [];
                $settings = $this->serviceSettings();
                $extensions = $extensions ?? $settings['extensions'];
                foreach ($extensions as $extension) {
                    $dir = $settings['vendor_dir'] . '/digikala/' . $extension . '/src/Entity';
                    if (!is_dir($dir)) {
                        continue;
                    }
                    $finder = new Finder();
                    foreach ($finder->files()->name('*.php')->in($dir) as $file) {
                        /**
                         * @var SplFileInfo $file
                         */
                        $className = implode(
                            '\\',
                            [
                                'Digikala',
                                Supernova::convertPathToNamespace(substr($extension . '/Entity/' . $file->getRelativePathname(), 0, -4))
                            ]
                        );
                        /**
                         * @var Entity $className
                         */
                        $refrection = new \ReflectionClass($className);
                        if ($refrection->isAbstract() || !$refrection->isSubclassOf(Entity::class)) {
                            continue;
                        }
                        $entities[] = $className::newInstance();
                    }
                }
                return $entities;
            }
        );
    }
}
