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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\Metadata;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\ColumnDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\Metadata\LocalizedValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementInterface;

/**
 * @internal
 */
final class DocumentResolver implements ColumnResolverInterface
{
    use ColumnDataTrait;
    use LocalizedValueTrait;

    public function resolve(Column $column, ElementInterface $element): ColumnData
    {
        $document = $this->getLocalizedValue($column, $element);

        if (!isset($document['document'][0])) {
            return $this->getColumnData($column, null);
        }

        return $this->getColumnData(
            $column,
            $document['document'][0]
        );
    }

    public function getType(): string
    {
        return ColumnType::METADATA_DOCUMENT->value;
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
        ];
    }
}
