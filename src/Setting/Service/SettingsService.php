<?php

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\UnknownSettingsLocationException;
use Pimcore\Bundle\StudioBackendBundle\Setting\Provider\SettingsStoreSettingsProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Provider\SymfonySettingsProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Request\SettingsStoreRequest;
use Pimcore\Bundle\StudioBackendBundle\Setting\Request\SymfonySettingsRequest;

final readonly class SettingsService implements SettingsServiceInterface
{

    public function __construct(
        private SymfonySettingsProviderInterface $symfonySettingsProvider,
        private SettingsStoreSettingsProviderInterface $settingsStoreSettingsProvider

    ) {
    }

    public function getSymfonySettings(SymfonySettingsRequest $settingsRequest): array
    {
        return $this->symfonySettingsProvider->getSettings($settingsRequest);
    }

    public function getSettingsStoreSettings(SettingsStoreRequest $settingsRequest): array
    {
        return $this->settingsStoreSettingsProvider->getSettings($settingsRequest);
    }
}