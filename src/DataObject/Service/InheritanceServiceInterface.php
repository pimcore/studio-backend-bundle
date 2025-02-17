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

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
interface InheritanceServiceInterface
{
    /**
     * @throws NotFoundException
     */
    public function getInheritanceData(
        Concrete $object,
        array $fieldDefinitions
    ): array;

    /**
     * @throws NotFoundException
     */
    public function processFieldDefinition(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): array|InheritanceData;

    /**
     * @throws NotFoundException
     */
    public function getOriginId(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): int;
}
