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

namespace Pimcore\Bundle\StudioBackendBundle\WebsiteSettings\Repository;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\WebsiteSetting\WebsiteSettingResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSettings\Schema\WebsiteSettingsUpdate;
use Pimcore\Model\WebsiteSetting;
use Pimcore\Model\WebsiteSetting\Listing;

/**
 * @internal
 */
final readonly class WebsiteSettingsRepository implements WebsiteSettingsRepositoryInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ListingFilterInterface $listingFilter,
        private ServiceResolverInterface $serviceResolver,
        private WebsiteSettingResolverInterface $websiteSettingsResolver
    ) {

    }

    /**
     * {@inheritdoc}
     */
    public function create(string $name, string $type): WebsiteSetting
    {
        $setting = new WebsiteSetting();
        $setting->setName($name);
        $setting->setType($type);
        try {
            $setting->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException(null, $e->getMessage(), $e);
        }

        return $setting;
    }

    /**
     * {@inheritdoc}
     */
    public function update(WebsiteSetting $setting, WebsiteSettingsUpdate $parameters): WebsiteSetting
    {
        $setting->setName($parameters->getName());
        $setting->setLanguage($parameters->getLanguage());
        $setting->setSiteId($parameters->getSiteId());
        $this->updateData($setting, $parameters->getData());
        try {
            $setting->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException($setting->getId(), $e->getMessage(), $e);
        }

        return $setting;
    }

    public function getListing(FilterParameter $parameters): Listing
    {
        $listing = new Listing();
        $this->listingFilter->applyFilters($parameters, $listing);

        return $listing;
    }

    /**
     * {@inheritdoc}
     */
    public function getById(int $id): WebsiteSetting
    {
        $setting = $this->websiteSettingsResolver->getById($id);
        if ($setting === null) {
            throw new NotFoundException('website setting', $id);
        }

        return $setting;
    }

    private function updateData(WebsiteSetting $setting, null|string|bool $parameterData): void
    {
        $data = $parameterData;
        if (in_array($setting->getType(), ElementTypes::ALLOWED_TYPES, true)) {
            try {
                $data = $this->getElementByPath($this->serviceResolver, $setting->getType(), $parameterData);
            } catch (NotFoundException) {
                $data = null;
            }
        }

        $setting->setData($data);
    }
}
