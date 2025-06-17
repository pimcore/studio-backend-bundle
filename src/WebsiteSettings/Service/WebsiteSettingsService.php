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

namespace Pimcore\Bundle\StudioBackendBundle\WebsiteSettings\Service;

use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSettings\Event\PreResponse\WebsiteSettingEvent;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSettings\Hydrator\WebsiteSettingsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSettings\Repository\WebsiteSettingsRepositoryInterface;
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

    public function listWebsiteSettings(CollectionFilterParameter $parameters): Collection
    {
        $listing = $this->websiteSettingsRepository->getListing($this->getFilterParameters($parameters));
        $settings = $listing->load();
        $list = [];

        foreach ($settings as $setting) {
            $entry = $this->hydrator->hydrate($setting);
            $this->eventDispatcher->dispatch(
                new WebsiteSettingEvent($entry),
                WebsiteSettingEvent::EVENT_NAME
            );

            $list[] = $entry;
        }

        return new Collection(
            $listing->count(),
            $list
        );
    }

    private function getFilterParameters(CollectionFilterParameter $parameters): FilterParameter
    {
        $filterParameters = new FilterParameter();
        if ($parameters->getFilters()) {
            $filterParameters = $this->filterMapper->map($parameters);
        }

        return $filterParameters;
    }
}
