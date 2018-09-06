<?php
namespace MyHammer\Library\Event;

use Symfony\Component\EventDispatcher\Event;

class LocaleEvent extends Event
{

    private $defaultLocale;
    private $locales = [];
    private $translations = [];
    private $domainMappings = [];

    public function enableLocale(string $locale, string ...$fallBackLocales): self
    {
        $this->locales[$locale] = $fallBackLocales;
        return $this;
    }

    public function setDefaultLocale(string $locale): self
    {
        $this->defaultLocale = $locale;
        return $this;
    }

    public function addDomainMapping(string $domain, string $mapName): self
    {
        $this->domainMappings[$mapName] = $domain;
        return $this;
    }

    public function getDomainMappings(): array
    {
        return $this->domainMappings;
    }

    public function addTranslation(string $locale, string $vendorYmlFilePath, string $domain): self
    {
        $this->translations[] = [
            'locale' => $locale,
            'file' => $vendorYmlFilePath,
            'domain' => $domain
        ];
        return $this;
    }

    public function getDefaultLocale(): ?string
    {
        return $this->defaultLocale;
    }

    public function getLocales(): array
    {
        return $this->locales;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }
}
