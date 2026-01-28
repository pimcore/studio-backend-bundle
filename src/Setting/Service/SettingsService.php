<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Setting\Event\PreResponse\CountryEvent;
use Pimcore\Bundle\StudioBackendBundle\Setting\Hydrator\CountryHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\SystemSettingsConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_key_exists;
use function sprintf;
use function strlen;

/**
 * @internal
 */
final readonly class SettingsService implements SettingsServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private CountryHydratorInterface $countryHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private LocaleServiceInterface $localeService,
        private SettingProviderLoaderInterface $settingProviderLoader,
        private UpdateSettingProviderLoaderInterface $updateSettingProviderLoader,
        private SystemSettingsConfig $systemSettingsConfig
    ) {
    }

    public function getSettings(): array
    {
        $settings = [];
        foreach ($this->settingProviderLoader->loadSettingProviders() as $settingProvider) {
            $settings = [
                ... $settings,
                ... $settingProvider->getSettings(),
            ];
        }

        return $settings;
    }

    /**
     * @throws InvalidElementTypeException
     */
    public function getTreePageSize(string $elementType): int
    {
        $settings = $this->getSettings();
        $elementType = $this->getCoreElementType($elementType);
        $settingKey = $elementType . '_tree_paging_limit';
        if (!array_key_exists($settingKey, $settings)) {
            throw new InvalidElementTypeException(
                sprintf('No tree paging limit setting found for element type "%s"', $elementType)
            );
        }

        return $settings[$settingKey];
    }

    public function getAvailableCountries(): array
    {
        $countries = $this->localeService->getDisplayRegions();
        asort($countries);

        $availableCountries = [];
        foreach ($countries as $code => $name) {
            if (strlen($code) === 2) {
                $country = $this->countryHydrator->hydrate($name, $code);
                $this->eventDispatcher->dispatch(
                    new CountryEvent($country),
                    CountryEvent::EVENT_NAME
                );
                $availableCountries[] = $country;
            }
        }

        return $availableCountries;
    }

    public function updateSettings(array $data): void
    {
        $preparedData = [];
        foreach ($this->updateSettingProviderLoader->loadUpdateSettingProviders() as $settingProvider) {
            $preparedData = [
                ... $data,
                ... $settingProvider->prepareSettingsForUpdate($data),
            ];
        }

        $this->systemSettingsConfig->save($preparedData);

        //Todo stop workers
        //TOdo clear cache
    }
}
