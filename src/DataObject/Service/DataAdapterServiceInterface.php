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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
interface DataAdapterServiceInterface
{
    public function getAdaptersMapping(): array;

    /**
     * @throws InvalidArgumentException
     */
    public function getFieldDefinitionAdapterClass(string $fieldDefinitionType): string;

    /**
     * @throws InvalidArgumentException
     */
    public function getDataAdapter(string $fieldDefinitionType): SetterDataInterface;

    public function tryDataAdapter(string $fieldDefinitionType): ?SetterDataInterface;
}
