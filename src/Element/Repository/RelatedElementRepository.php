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
    /** Same columns as ManyToManyRelationTrait::loadRelationSummaries() in core. */
    private const array QUERIES = [
        ElementTypes::TYPE_OBJECT =>
            'SELECT id, parentId, path, `key` AS filename, type, className, published FROM objects WHERE id IN (:ids)',
        ElementTypes::TYPE_ASSET =>
            'SELECT id, parentId, path, filename, type, NULL AS className, 1 AS published FROM assets WHERE id IN (:ids)',
        ElementTypes::TYPE_DOCUMENT =>
            'SELECT id, parentId, path, `key` AS filename, type, NULL AS className, published FROM documents WHERE id IN (:ids)',
    ];

    public function __construct(
        private DbResolverInterface $dbResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getRelatedElementSummaries(string $elementType, array $ids): array
    {
        $query = self::QUERIES[$elementType] ?? null;
        if ($ids === [] || $query === null) {
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

        $summaries = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $isObject = $elementType === ElementTypes::TYPE_OBJECT;
            $summaries[$id] = [
                'id' => $id,
                'parentId' => (int) $row['parentId'],
                'type' => $elementType,
                'subtype' => $row['type'],
                'className' => $isObject && $row['type'] !== AbstractObject::OBJECT_TYPE_FOLDER
                    ? $row['className']
                    : null,
                'fullpath' => $id === 1 && $isObject ? '/' : $row['path'] . $row['filename'],
                'filename' => $row['filename'],
                'published' => (bool) $row['published'],
            ];
        }

        return $summaries;
    }
}
