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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\System;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\StudioElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\ColumnDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\SimpleGetterTrait;
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
final class IdResolver implements
    ColumnResolverInterface,
    StudioElementColumnResolverInterface,
    CoreElementColumnResolverInterface
{
    use SimpleGetterTrait;
    use ColumnDataTrait;

    /**
     * @throws InvalidArgumentException
     */
    public function resolveForStudioElement(Column $column, StudioElementInterface $element): ColumnData
    {
        return $this->getColumnData(
            $column,
            $this->getValue($column, $element),
            $this->getType()
        );
    }

    public function resolveForCoreElement(Column $column, ElementInterface $element): ColumnData
    {
        return $this->getColumnData(
            $column,
            $this->getValue($column, $element),
            $this->getType()
        );
    }

    public function getType(): string
    {
        return ColumnType::SYSTEM_ID->value;
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
            ElementTypes::TYPE_DOCUMENT,
            ElementTypes::TYPE_OBJECT,
        ];
    }
}
