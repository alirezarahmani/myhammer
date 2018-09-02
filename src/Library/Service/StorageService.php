<?php
namespace MyHammer\Library\Service;

use MyHammer\Library\Entity\StorageEntity;
use MyHammer\Library\Event\StorageNamespaceEvent;
use MyHammer\Library\Events;
use MyHammer\Library\Lib\Service;
use MyHammer\Library\Service\Mysql\Expression;
use MyHammer\Library\Service\Storage\Directory;
use MyHammer\Library\Service\Storage\DirectoryStorage;
use MyHammer\Library\Service\Storage\File;
use MyHammer\Library\Service\Storage\FileNotFoundException;
use MyHammer\Library\Service\Storage\FileStorage;
use MyHammer\Library\Service\Storage\Image;
use MyHammer\Library\Service\Storage\ImageDefinition;
use MyHammer\Library\Service\Storage\ImageStorage;
use MyHammer\Library\Service\Storage\StorageProviderInterface;
use MyHammer\Library\Service\Storage\StoredFile;
use Gregwar\Image\Image as ImageCropper;

class StorageService extends Service
{

    private $provider;
    private $clusterCode;
    private $namespaces;
    private $namespacesIds;
    private $cdn;
    private $forceHttps;

    public function __construct(StorageProviderInterface $provider, string $clusterCode)
    {

        $this->provider = $provider;
        $this->clusterCode = $clusterCode;
        $event = new StorageNamespaceEvent();
        $this->serviceEventDispatcher()->dispatch(Events::STORAGE_NAMESPACE, $event);
        $this->namespaces = $event->getNamespaces();
        $this->namespacesIds = array_flip($this->namespaces);
        $this->cdn = [];
        $settings = $this->serviceSettings()['storage'];
        $this->forceHttps = $settings['https'];
        foreach ($settings['servers'] as $server) {
            if (isset($server['cdn'])) {
                $this->cdn[$server['id']] = $server['cdn'];
            }
        }
    }

    public function saveImagesFromFile(string $filePath, int $namespace, array $imageDefinitions): ImageStorage
    {
        $imageStorage = new ImageStorage();
        $count = count($imageDefinitions);
        foreach ($imageDefinitions as $key => $imageDefinition) {
            if ($count == 1 && $key === 0) {
                $key = 'default';
            }
            $imageStorage->addImage($key, $this->saveImageFromFile($filePath, $namespace, $imageDefinition));
        }
        return $imageStorage;
    }

    public function saveFilesFromFile(string $filePath, int $namespace, string $fileName = null, array $tags = []): FileStorage
    {
        $fileStorage = new FileStorage();
        $fileStorage->addFile($this->saveFileFromFile($filePath, $namespace, $fileName, $tags));
        return $fileStorage;
    }

    public function saveFilesFromZip(string $filePath, int $namespace, array $tags = []): DirectoryStorage
    {
        $directoryStorage = new DirectoryStorage();
        $directoryStorage->addDirectory($this->saveFileFromZip($filePath, $namespace, $tags));
        return $directoryStorage;
    }

    public function saveFileFromFile(string $filePath, int $namespace, string $fileName = null, array $tags = []): File
    {
        $storedFile = $this->saveFromFile($filePath, $namespace);
        $name = $fileName ?? pathinfo($filePath)['filename'] . pathinfo($filePath)['extension'];
        return new File($storedFile, $name, filesize($filePath), $tags);
    }

    public function saveFileFromZip(string $filePath, int $namespace, array $tags = []): Directory
    {
        $storedFile = $this->saveFromZip($filePath, $namespace);
        return new Directory($storedFile, $tags);
    }

    public function saveImageFromFile(string $filePath, int $namespace, ImageDefinition $imageDefinition, array $tags = []): Image
    {
        $image = ImageCropper::open($filePath);
        $image->setCacheDir(sys_get_temp_dir());
        $image->setCacheDirMode(0777);
        if ($imageDefinition->getMaxHeight()) {
            $image->cropResize($imageDefinition->getMaxWidth(), $imageDefinition->getMaxHeight())->applyOperations();
        } elseif ($imageDefinition->getExactHeight()) {
            $image->zoomCrop(
                $imageDefinition->getExactWidth(),
                $imageDefinition->getExactHeight(),
                '0xffffff',
                'center',
                'center'
            );
        }
        if ($watermarkFile = $imageDefinition->getWatermarkFile()) {
            $watermark = ImageCropper::open($watermarkFile);
            $watermark->cropResize(
                round($image->width() * $imageDefinition->getWatermarkMaxWidthScale() / 100),
                round($image->height() * $imageDefinition->getWatermarkMaxHeightScale() / 100)
            )->applyOperations();
            $position = $imageDefinition->getWatermarkPosition();
            if ($position == ImageDefinition::WATERMARK_POSITION_RANDOM) {
                $position = ImageDefinition::WATERMARK_POSITIONS[
                    array_rand(ImageDefinition::WATERMARK_POSITIONS, 1)
                ];
            }
            if ($position == ImageDefinition::WATERMARK_POSITION_BOTTOM_RIGHT) {
                $image->merge(
                    $watermark,
                    $image->width()-$watermark->width() - $imageDefinition->getWatermarkMargin(),
                    $image->height()-$watermark->height() - $imageDefinition->getWatermarkMargin()
                );
            } elseif ($position == ImageDefinition::WATERMARK_POSITION_TOP_RIGHT) {
                $image->merge(
                    $watermark,
                    $image->width()-$watermark->width() - $imageDefinition->getWatermarkMargin(),
                    $imageDefinition->getWatermarkMargin()
                );
            } elseif ($position == ImageDefinition::WATERMARK_POSITION_BOTTOM_LEFT) {
                $image->merge(
                    $watermark,
                    $imageDefinition->getWatermarkMargin(),
                    $image->height()-$watermark->height() - $imageDefinition->getWatermarkMargin()
                );
            } elseif ($position == ImageDefinition::WATERMARK_POSITION_TOP_LEFT) {
                $image->merge(
                    $watermark,
                    $imageDefinition->getWatermarkMargin(),
                    $imageDefinition->getWatermarkMargin()
                );
            }
        }
        $finalImage = $image->cacheFile($imageDefinition->getFormat(), $imageDefinition->getQuality());
        $storedFile = $this->saveFromFile($finalImage, $namespace);
        $image = new Image(
            $storedFile,
            $image->width(),
            $image->height(),
            $imageDefinition->getFormat(),
            $tags
        );
        $this->serviceFile()->deleteFile($finalImage);
        return $image;
    }

    public function saveFromFile(string $filePath, int $namespace): StoredFile
    {
        $namespaceName = $this->getNamespaceName($namespace);
        $id = $this->createId($namespaceName);
        $ids = $this->provider->save($id, $namespaceName, file_get_contents($filePath));
        return new StoredFile($id, $namespace, $ids);
    }

    public function saveFromZip(string $filePath, int $namespace): StoredFile
    {
        $namespaceName = $this->getNamespaceName($namespace);
        $id = $this->createId($namespaceName);
        $ids = $this->provider->saveZip($id, $namespaceName, file_get_contents($filePath));
        return new StoredFile($id, $namespace, $ids);
    }

    public function delete(StoredFile $storedFile)
    {
        $this->provider->delete($storedFile->getId(), $this->getNamespaceName($storedFile->getNamespace()), $storedFile->getServersIds());
    }

    public function get(StoredFile $storedFile): string
    {
        $namespaceName = $this->getNamespaceName($storedFile->getNamespace());
        $value = $this->provider->get($storedFile->getId(), $namespaceName, $storedFile->getServersIds());
        if ($value === null) {
            throw new FileNotFoundException($namespaceName, $storedFile->getId());
        }
        return $value;
    }

    public function getNamespaceName(int $namespace): string
    {
        return $this->namespaces[$namespace];
    }

    public function getNamespaceId(string $namespaceName): int
    {
        return $this->namespacesIds[$namespaceName];
    }

    public function getCdnDomain(StoredFile $file): string
    {
        $ids = $file->getServersIds();
        return ($this->forceHttps ? 'https://' : 'http://') . $this->cdn[$ids[array_rand($ids, 1)]];
    }

    private function createId(string $namespaceName): int
    {
        $redisKey = 'storage:counter:' . $namespaceName;
        $counter = $this->serviceRedis()->incr($redisKey);
        if ($counter == 1) {
            $storage = StorageEntity::getOneByQuery(new Expression('namespace = ?', [$namespaceName]));
            if ($storage) {
                $counter = $storage->getCounter() + 1;
                if ($counter != 1) {
                    $this->serviceRedis()->set($redisKey, $counter, TimeService::YEAR);
                }
            }
        }
        $storage = StorageEntity::getOneByIndex(StorageEntity::INDEX_NAMESPACE, $namespaceName);
        if (!$storage) {
            $storage = StorageEntity::newInstance();
            $storage->setNamespace($namespaceName);
        }
        $storage->setCounter($counter);
        $storage->flushLazy();
        return $counter;
    }
}
