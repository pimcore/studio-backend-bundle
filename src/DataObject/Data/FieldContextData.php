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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data;

use Pimcore\Bundle\StudioBackendBundle\Util\Constant\DataObject\ContainerTypes;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;

/**
 * @internal
 */
final readonly class FieldContextData
{
    public function __construct(
        private ?AbstractData    $objectbrick = null,
        private ?array           $blockElements = null,
        private ?string          $language = null,
        private ?Fieldcollection $fieldCollection = null,
    ) {
    }

    public function getObjectbrick(): ?AbstractData
    {
        return $this->objectbrick;
    }

    public function getBlockElements(): ?array
    {
        return $this->blockElements;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getContext(): array
    {
        return match (true) {
            $this->objectbrick !== null => ['containerType' => ContainerTypes::OBJECT_BRICK->value],
            $this->blockElements !== null => ['containerType' => ContainerTypes::BLOCK->value],
            $this->fieldCollection !== null => ['containerType' => ContainerTypes::FIELD_COLLECTION->value],
            default => [],
        };
    }

    public function getFieldCollection(): ?Fieldcollection
    {
        return $this->fieldCollection;
    }
}
