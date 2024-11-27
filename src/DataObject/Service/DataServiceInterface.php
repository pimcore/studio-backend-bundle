<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\ClassData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
interface DataServiceInterface
{
    /**
     * @throws NotFoundException
     */
    public function getObjectData(Concrete $dataObject): array;

    /**
     * @throws NotFoundException
     */
    public function getObjectClassData(Concrete $dataObject): ClassData;

    public function getNormalizedValue(
        mixed $value,
        Data $fieldDefinition
    ): mixed;

    public function getAdapterSetterValue(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        ?FieldContextData $contextData = null
    ): ?array;
}
