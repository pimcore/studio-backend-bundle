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
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;

/**
 * @internal
 */
final readonly class FieldContextData
{
    public function __construct(
        private ?AbstractData $objectbrick = null,
        private ?BlockElement $blockElement = null,
        private ?string $language = null,
    )
    {
    }

    public function getObjectbrick(): ?AbstractData
    {
        return $this->objectbrick;
    }

    public function getBlockElement(): ?BlockElement
    {
        return $this->blockElement;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getContext(): array
    {
        return match (true) {
            $this->objectbrick !== null => ['containerType' => ContainerTypes::OBJECT_BRICK->value],
            $this->blockElement !== null => ['containerType' => ContainerTypes::BLOCK->value],
            default => [],
        };
    }
}