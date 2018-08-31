<?php
namespace MyHammer\Library\Entity;

use Digikala\Supernova\Service\Storage\DirectoryStorage;
use Digikala\Supernova\Service\Storage\FileStorage;
use Digikala\Supernova\Service\Storage\ImageStorage;

trait FieldMapperTrait
{
    private $mapping = [];

    protected function mapToDateTime(string $key): ?\DateTime
    {
        if (array_key_exists($key, $this->mapping)) {
            return $this->mapping[$key];
        }
        $value = $this->getField($key);
        $value = $this->mapping[$key] = $value ? date_create($value, new \DateTimeZone('UTC')) : null;
        return $value;
    }

    /**
     * @param string         $key
     * @param \DateTime|null $dateTime
     * @return FieldMapperTrait|mixed
     */
    protected function mapFromDateTime(string $key, ?\DateTime $dateTime): self
    {
        $this->mapping[$key] = $dateTime;
        if ($dateTime === null) {
            $this->setField($key, null);
        } else {
            $this->setField($key, date('Y-m-d H:i:s', $dateTime->getTimestamp()));
        }
        return $this;
    }

    protected function mapToArrayFromJson(string $key): ?array
    {
        if (array_key_exists($key, $this->mapping)) {
            return $this->mapping[$key];
        }
        $value = $this->getField($key);
        $value = $this->mapping[$key] = $value ? json_decode($value, true) : null;
        return $value;
    }

    protected function mapToArrayFromSet(string $key): ?array
    {
        if (array_key_exists($key, $this->mapping)) {
            return $this->mapping[$key];
        }
        $value = $this->getField($key);
        $value = $this->mapping[$key] = $value !== null ? explode(',', $value) : null;
        return $value;
    }

    protected function mapFromArrayToJson(string $key, ?array $data): self
    {
        $this->mapping[$key] = $data;
        if ($data === null) {
            $this->setField($key, null);
        } else {
            $this->setField($key, json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        return $this;
    }

    protected function mapToImageFromJson(string $key): ?ImageStorage
    {
        if (array_key_exists($key, $this->mapping)) {
            return $this->mapping[$key];
        }
        $value = $this->getField($key);
        $image = null;
        if ($value) {
            $image = new ImageStorage(json_decode($value, true));
        }
        $this->mapping[$key] = $image;
        return $image;
    }

    protected function mapToDirectoryFromJson(string $key): ?DirectoryStorage
    {
        if (array_key_exists($key, $this->mapping)) {
            return $this->mapping[$key];
        }
        $value = $this->getField($key);
        $directory = null;
        if ($value) {
            $directory = new DirectoryStorage(json_decode($value, true));
        }
        $this->mapping[$key] = $directory;
        return $directory;
    }

    protected function mapToFileFromJson(string $key): ?FileStorage
    {
        if (array_key_exists($key, $this->mapping)) {
            return $this->mapping[$key];
        }
        $value = $this->getField($key);
        $file = null;
        if ($value) {
            $file = new FileStorage(json_decode($value, true));
        }
        $this->mapping[$key] = $file;
        return $file;
    }

    /**
     * @param string $key
     * @return ImageStorage[]
     */
    protected function mapToImagesFromJson(string $key): array
    {
        if (array_key_exists($key, $this->mapping)) {
            return $this->mapping[$key];
        }
        $value = $this->getField($key);
        $images = [];
        if ($value) {
            foreach (json_decode($value, true) as $row) {
                $images[] = new ImageStorage($row);
            }
        }
        $this->mapping[$key] = $images;
        return $images;
    }

    protected function mapFromImageToJson(string $key, ?ImageStorage $image): self
    {
        $data = null;
        if ($image && $image->getSizesCount()) {
            $data = [];
            foreach ($image->getImages() as $imageKey => $value) {
                $definition = [
                    $value->getStoredFile()->getId(),
                    $value->getStoredFile()->getNamespace(),
                    $value->getWidth(),
                    $value->getHeight(),
                    $value->getFormat(),
                    $value->getStoredFile()->getServersIds()
                ];
                if ($tags = $value->getTags()) {
                    $definition[] = $tags;
                }
                $data[$imageKey] = $definition;
            }
        }
        $this->mapping[$key] = $image;
        if ($data === null) {
            $this->setField($key, null);
        } else {
            $this->setField($key, json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        return $this;
    }

    protected function mapFromDirectoryToJson(string $key, ?DirectoryStorage $directory): self
    {
        $data = null;
        if ($directory) {
            $data = [];
            foreach ($directory->getDirectories() as $directoryKey => $value) {
                $definition = [
                    $value->getStoredFile()->getId(),
                    $value->getStoredFile()->getNamespace(),
                    $value->getStoredFile()->getServersIds()
                ];
                if ($tags = $value->getTags()) {
                    $definition[] = $tags;
                }
                $data[$directoryKey] = $definition;
            }
        }
        $this->mapping[$key] = $directory;
        if ($data === null) {
            $this->setField($key, null);
        } else {
            $this->setField($key, json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        return $this;
    }

    protected function mapFromFileToJson(string $key, ?FileStorage $file): self
    {
        $data = null;
        if ($file) {
            $data = [];
            foreach ($file->getFiles() as $fileKey => $value) {
                $definition = [
                    $value->getStoredFile()->getId(),
                    $value->getStoredFile()->getNamespace(),
                    $value->getName(),
                    $value->getSize(),
                    $value->getStoredFile()->getServersIds()
                ];
                if ($tags = $value->getTags()) {
                    $definition[] = $tags;
                }
                $data[$fileKey] = $definition;
            }
        }
        $this->mapping[$key] = $file;
        if ($data === null) {
            $this->setField($key, null);
        } else {
            $this->setField($key, json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        return $this;
    }

    /**
     * @param string $key
     * @param ImageStorage[] $images
     * @return self
     */
    protected function mapFromImagesToJson(string $key, array $images): self
    {
        $data = [];
        foreach ($images as $image) {
            if ($image && $image->getSizesCount()) {
                $sizes = [];
                foreach ($image->getImages() as $imageKey => $value) {
                    $definition = [
                        $value->getStoredFile()->getId(),
                        $value->getStoredFile()->getNamespace(),
                        $value->getWidth(),
                        $value->getHeight(),
                        $value->getFormat(),
                        $value->getStoredFile()->getServersIds()
                    ];
                    if ($tags = $value->getTags()) {
                        $definition[] = $tags;
                    }
                    $sizes[$imageKey] = $definition;
                }
                $data[] = $sizes;
            }
        }

        $this->mapping[$key] = $images;
        if (!$data) {
            $this->setField($key, null);
        } else {
            $this->setField($key, json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        return $this;
    }
}
