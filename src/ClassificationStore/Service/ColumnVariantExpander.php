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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use function array_key_exists;
use function is_int;

/**
 * @internal
 */
final readonly class ColumnVariantExpander implements ColumnVariantExpanderInterface
{
    private const string COLUMN_TYPE = 'dataobject.classificationstore';

    private const string VARIANT = 'variant';

    public function __construct(
        private KeyConfigResolverInterface $keyConfigResolver,
        private KeyQualifierResolverInterface $keyQualifierResolver,
        private ColumnReferenceServiceInterface $columnReferenceService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function expand(array $columns, ?string $classId = null): array
    {
        $expanded = [];
        foreach ($columns as $column) {
            $variants = $this->getVariants($column, $classId);
            if ($variants === []) {
                $expanded[] = $column;

                continue;
            }

            foreach ($variants as $variant) {
                $expanded[] = $this->withVariant($column, $variant);
            }

            // the default slot, for as long as the key permits an unqualified value
            $expanded[] = $column;
        }

        return $expanded;
    }

    /**
     * @return string[]
     */
    private function getVariants(Column $column, ?string $classId): array
    {
        $config = $column->getConfig();
        if ($column->getType() !== self::COLUMN_TYPE || array_key_exists(self::VARIANT, $config)) {
            return [];
        }

        $keyId = $this->getKeyId($column, $classId);
        if ($keyId === null) {
            return [];
        }

        $keyConfig = $this->keyConfigResolver->getById($keyId);

        return $keyConfig instanceof KeyConfig ? $this->keyQualifierResolver->getVariants($keyConfig) ?? [] : [];
    }

    /**
     * The key the column names, the identifiers being the reference where the column carries both, as the
     * cells resolve it. Without a class they cannot be resolved, and the integer pair they supersede is not
     * consulted in their place.
     */
    private function getKeyId(Column $column, ?string $classId): ?int
    {
        $config = $column->getConfig();
        if ($classId !== null) {
            try {
                $reference = $this->columnReferenceService->resolve($classId, $column->getKey(), $config);
            } catch (InvalidArgumentException) {
                // the cells report it per row, where the column is read
                return null;
            }

            if ($reference !== null) {
                return $reference->getKeyId();
            }
        } elseif (isset($config['groupIdentifier'], $config['keyIdentifier'])) {
            return null;
        }

        return is_int($config['keyId'] ?? null) ? $config['keyId'] : null;
    }

    private function withVariant(Column $column, string $variant): Column
    {
        return new Column(
            key: $column->getKey(),
            locale: $column->getLocale(),
            type: $column->getType(),
            group: $column->getGroup(),
            config: [...$column->getConfig(), self::VARIANT => $variant],
            width: $column->getWidth(),
        );
    }
}
