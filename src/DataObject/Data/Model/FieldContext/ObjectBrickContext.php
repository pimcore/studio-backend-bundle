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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContext;

use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;

/**
 * @internal
 */
final class ObjectBrickContext
{
    public function __construct(
        private readonly AbstractData $contextObject,
        private readonly string $fieldName
    ) {
    }

    public function getContextObject(): AbstractData
    {
        return $this->contextObject;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }
}
