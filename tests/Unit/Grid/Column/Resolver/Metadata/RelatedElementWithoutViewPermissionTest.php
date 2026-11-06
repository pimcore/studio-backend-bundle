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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Column\Resolver\Metadata;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\AssetMetaData;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocumentServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\Metadata\AssetResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\Metadata\DataObjectResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\Metadata\DocumentResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\StudioElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;

/**
 * @internal
 */
final class RelatedElementWithoutViewPermissionTest extends Unit
{
    /**
     * A related element the user may not view must leave the column empty instead of failing the whole grid.
     *
     * @dataProvider resolverProvider
     */
    public function testRelatedElementWithoutViewPermissionResolvesToEmptyColumn(string $type): void
    {
        $throwForbidden = static function (): never {
            throw new ForbiddenException();
        };

        $resolver = match ($type) {
            'asset' => new AssetResolver(
                $this->makeEmpty(AssetServiceInterface::class, ['getAsset' => $throwForbidden])
            ),
            'object' => new DataObjectResolver(
                $this->makeEmpty(DataObjectServiceInterface::class, ['getDataObject' => $throwForbidden])
            ),
            'document' => new DocumentResolver(
                $this->makeEmpty(DocumentServiceInterface::class, ['getDocument' => $throwForbidden])
            ),
        };

        $this->assertNull($this->resolve($resolver, $type)->getValue());
    }

    public static function resolverProvider(): array
    {
        return [
            'asset' => ['asset'],
            'object' => ['object'],
            'document' => ['document'],
        ];
    }

    private function resolve(StudioElementColumnResolverInterface $resolver, string $type): ColumnData
    {
        $column = new Column(key: 'related', locale: null, type: 'metadata.' . $type, group: null, config: []);
        $asset = $this->makeEmpty(Asset::class, [
            'getMetadata' => [new AssetMetaData('related', null, [$type => [9]])],
        ]);

        return $resolver->resolveForStudioElement($column, $asset);
    }
}
