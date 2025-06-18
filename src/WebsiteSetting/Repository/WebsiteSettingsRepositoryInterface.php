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

namespace Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema\WebsiteSettingsUpdate;
use Pimcore\Model\WebsiteSetting;
use Pimcore\Model\WebsiteSetting\Listing;

/**
 * @internal
 */
interface WebsiteSettingsRepositoryInterface
{
    /**
     * @throws ElementSavingFailedException
     */
    public function create(string $name, string $type): WebsiteSetting;

    /**
     * @throws ElementSavingFailedException
     */
    public function update(WebsiteSetting $setting, WebsiteSettingsUpdate $parameters): WebsiteSetting;

    public function getListing(FilterParameter $parameters): Listing;

    /**
     * @throws NotFoundException
     */
    public function getById(int $id): WebsiteSetting;
}
