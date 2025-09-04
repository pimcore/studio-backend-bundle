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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\SearchGridParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
interface GridSearchServiceInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function getAssetSearchGrid(SearchGridParameter $gridParameter): Collection;

    /**
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws Exception
     */
    public function getDataObjectSearchGrid(SearchGridParameter $searchParameter, ?string $classId): Collection;

    /**
     * @throws InvalidArgumentException
     */
    public function getDocumentSearchGrid(SearchGridParameter $searchParameter): Collection;
}
