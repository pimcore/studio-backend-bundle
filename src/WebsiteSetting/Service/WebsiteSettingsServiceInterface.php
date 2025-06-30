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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema\WebsiteSetting;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema\WebsiteSettingsAdd;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema\WebsiteSettingsUpdate;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema\WebsiteSettingType;

/**
 * @internal
 */
interface WebsiteSettingsServiceInterface
{
    /**
     * @throws ElementSavingFailedException
     */
    public function addWebsiteSetting(WebsiteSettingsAdd $parameters): WebsiteSetting;

    /**
     * @throws ElementSavingFailedException|NotFoundException
     */
    public function updateWebsiteSetting(int $id, WebsiteSettingsUpdate $parameters): WebsiteSetting;

    /**
     * @throws InvalidArgumentException
     */
    public function listWebsiteSettings(CollectionFilterParameter $parameters): Collection;

    /**
     * @throws NotFoundException
     */
    public function deleteWebsiteSetting(int $id): void;

    /**
     * @return WebsiteSettingType[]
     */
    public function listTypes(): array;
}
