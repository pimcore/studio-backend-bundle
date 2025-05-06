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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\Metadata;

use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocumentServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\StudioElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\ColumnDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\Metadata\LocalizedValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

/**
 * @internal
 */
final class DocumentResolver implements ColumnResolverInterface, StudioElementColumnResolverInterface
{
    use ColumnDataTrait;
    use LocalizedValueTrait;

    public function __construct(
        private readonly DocumentServiceInterface $documentService
    ) {
    }

    public function resolveForStudioElement(Column $column, StudioElementInterface $element): ColumnData
    {
        $document = $this->getLocalizedValue($column, $element);

        if (!isset($document['document'])) {
            return $this->getColumnData($column, null);
        }

        try {
            $relatedDocument = $this->documentService->getDocument(
                reset($document['document']),
                false
            );
        } catch (NotFoundException) {
            return $this->getColumnData($column, null);
        }

        return $this->getColumnData(
            $column,
            [
                'id' => $relatedDocument->getId(),
                'fullPath' => $relatedDocument->getFullPath(),
                'type' => $relatedDocument->getType(),
                'isPublished' => $relatedDocument->isPublished(),
            ]
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
