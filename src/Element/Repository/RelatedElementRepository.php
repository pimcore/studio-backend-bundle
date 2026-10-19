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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\AbstractObject;
use function array_unique;
use function array_values;

/**
 * @internal
 */
final readonly class RelatedElementRepository implements RelatedElementRepositoryInterface
{
    public function __construct(
        private DbResolverInterface $dbResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getRelatedElementsData(string $elementType, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $query = match ($elementType) {
            ElementTypes::TYPE_OBJECT =>
                'SELECT id, type, className AS subtype, published, CONCAT(path, `key`) AS fullPath
                FROM objects WHERE id IN (:ids)',
            ElementTypes::TYPE_ASSET =>
                'SELECT id, type, type AS subtype, NULL AS published, CONCAT(path, filename) AS fullPath
                FROM assets WHERE id IN (:ids)',
            ElementTypes::TYPE_DOCUMENT =>
                'SELECT id, type, type AS subtype, published, CONCAT(path, `key`) AS fullPath
                FROM documents WHERE id IN (:ids)',
            default => null,
        };

        if ($query === null) {
            return [];
        }

        try {
            $rows = $this->dbResolver->get()->fetchAllAssociative(
                $query,
                ['ids' => array_values(array_unique($ids))],
                ['ids' => ArrayParameterType::INTEGER]
            );
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage(), $exception);
        }

        $result = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $result[$id] = new RelatedElementData(
                $id,
                $elementType,
                $this->getSubtype($elementType, $row),
                $row['fullPath'],
                $this->getPublished($elementType, $row)
            );
        }

        return $result;
    }

    /**
     * Same values as ElementDataService::getSubType(): the class name for data objects, the
     * element type for folders, assets and documents.
     */
    private function getSubtype(string $elementType, array $row): string
    {
        if ($elementType === ElementTypes::TYPE_OBJECT && $row['type'] === AbstractObject::OBJECT_TYPE_FOLDER) {
            return $row['type'];
        }

        return (string) $row['subtype'];
    }

    /**
     * Same values as ElementDataService::getPublished(): a flag for data objects (not folders)
     * and documents, null otherwise.
     */
    private function getPublished(string $elementType, array $row): ?bool
    {
        return match (true) {
            $elementType === ElementTypes::TYPE_ASSET => null,
            $elementType === ElementTypes::TYPE_OBJECT && $row['type'] === AbstractObject::OBJECT_TYPE_FOLDER => null,
            default => (bool) $row['published'],
        };
    }
}
