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

namespace Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Service;

use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\WebsiteSettingTypes;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Event\PreResponse\WebsiteSettingEvent;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Event\PreResponse\WebsiteSettingTypeEvent;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Hydrator\WebsiteSettingsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Repository\WebsiteSettingsRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema\WebsiteSetting;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema\WebsiteSettingsAdd;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema\WebsiteSettingsUpdate;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema\WebsiteSettingType;
use Pimcore\Model\WebsiteSetting as WebsiteSettingModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class WebsiteSettingsService implements WebsiteSettingsServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private FilterMapperServiceInterface $filterMapper,
        private WebsiteSettingsHydratorInterface $hydrator,
        private WebsiteSettingsRepositoryInterface $websiteSettingsRepository
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function addWebsiteSetting(WebsiteSettingsAdd $parameters): WebsiteSetting
    {
        $setting = $this->websiteSettingsRepository->create($parameters->getName(), $parameters->getType());

        return $this->getHydratedSetting($setting);
    }

    /**
     * {@inheritdoc}
     */
    public function updateWebsiteSetting(int $id, WebsiteSettingsUpdate $parameters): WebsiteSetting
    {
        $setting = $this->websiteSettingsRepository->getById($id);
        $setting = $this->websiteSettingsRepository->update($setting, $parameters);

        return $this->getHydratedSetting($setting);
    }

    /**
     * {@inheritdoc}
     */
    public function listWebsiteSettings(CollectionFilterParameter $parameters): Collection
    {
        $listing = $this->websiteSettingsRepository->getListing(
            $this->filterMapper->getFilterParameters($parameters)
        );
        $settings = $listing->load();
        $list = [];

        foreach ($settings as $setting) {
            $list[] = $this->getHydratedSetting($setting);
        }

        return new Collection(
            $listing->count(),
            $list
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deleteWebsiteSetting(int $id): void
    {
        $this->websiteSettingsRepository->getById($id)->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function listTypes(): array
    {
        $types = WebsiteSettingTypes::toNameValueArray();
        $hydrated = [];
        foreach ($types as $title => $key) {
            $item = new WebsiteSettingType($key, $title);
            $this->eventDispatcher->dispatch(
                new WebsiteSettingTypeEvent($item),
                WebsiteSettingTypeEvent::EVENT_NAME
            );
            $hydrated[] = $item;
        }

        return $hydrated;
    }

    private function getHydratedSetting(WebsiteSettingModel $setting): WebsiteSetting
    {
        $entry = $this->hydrator->hydrate($setting);
        $this->eventDispatcher->dispatch(
            new WebsiteSettingEvent($entry),
            WebsiteSettingEvent::EVENT_NAME
        );

        return $entry;
    }
}
