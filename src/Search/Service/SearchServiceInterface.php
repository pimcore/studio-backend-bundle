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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter\SimpleSearchParameter;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\AssetSearchPreview;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\DataObjectSearchPreview;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\DocumentSearchPreview;

/**
 * @internal
 */
interface SearchServiceInterface
{
    /**
     * @throws SearchException|UserNotFoundException
     */
    public function doSimpleSearch(SimpleSearchParameter $parameters): Collection;

    /**
     * @throws ForbiddenException|InvalidElementTypeException|NotFoundException|UserNotFoundException
     */
    public function getSearchPreview(
        ElementParameters $parameters
    ): AssetSearchPreview|DataObjectSearchPreview|DocumentSearchPreview;
}
