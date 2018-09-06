<?php
namespace MyHammer\Library\Event;

use Symfony\Component\EventDispatcher\Event;

class StaticFilesEvent extends Event
{
    private $packages = [];
    private $dirs = [];
    private $sourceDirs = [];

    public function addFileToPackage(string $code, string $path, int $priority = 0) : self
    {
        $this->packages[$code][] = [$path, $priority];
        return $this;
    }

    public function addMergedFileToPackage(string $code, string $mergeCode, array $paths, int $priority = 0) : self
    {
        if (!isset($this->packages[$code][$mergeCode])) {
            $this->packages[$code][$mergeCode] = [$paths, $priority];
            return $this;
        }
        $this->packages[$code][$mergeCode][0] = array_replace($this->packages[$code][$mergeCode][0], $paths);
        return $this;
    }

    public function addFilesToPackage(string $code, array $paths, int $priority = 0) : self
    {
        if ($paths) {
            foreach ($paths as $key => $path) {
                if (is_array($path)) {
                    $this->addMergedFileToPackage($code, $key, $path, $priority);
                } else {
                    $this->addFileToPackage($code, $path, $priority);
                }
            }
        } elseif (!isset($this->packages[$code])) {
            $this->packages[$code] = [];
        }
        return $this;
    }

    public function addDirectory(string $dirPath): self
    {
        $this->dirs[$dirPath] = $dirPath;
        return $this;
    }

    public function addSourceDirectory(string $code, string $dirPath, array $inlucdeFiles): self
    {
        $this->sourceDirs[$code] = [$dirPath, $inlucdeFiles];
        return $this;
    }

    public function getPackages(): array
    {
        return $this->packages;
    }

    public function getDirectories(): array
    {
        return $this->dirs;
    }

    public function getSourceDirectories(): array
    {
        return $this->sourceDirs;
    }
}
