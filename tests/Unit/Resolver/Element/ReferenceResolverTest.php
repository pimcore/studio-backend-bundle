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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Resolver\Element;

use Closure;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Resolver\Element\ReferenceResolver;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;

/**
 * Documents, assets and data objects have independent id sequences,
 * so the same numeric id regularly exists once per element type.
 */
final class ReferenceResolverTest extends Unit
{
    private const int SHARED_ID = 4;

    public function testResolveKeepsElementsWithTheSameIdApart(): void
    {
        $resolver = $this->getResolver();

        $asset = $resolver->resolve($this->getAsset());
        $document = $resolver->resolve($this->getDocument());
        $object = $resolver->resolve($this->getDataObject());

        $this->assertSame(self::SHARED_ID, $asset['id']);
        $this->assertSame('asset-four', $asset['filename']);
        $this->assertSame('/asset-four', $asset['fullPath']);

        $this->assertSame(self::SHARED_ID, $document['id']);
        $this->assertSame('document-four', $document['key']);
        $this->assertSame('/document-four', $document['fullPath']);

        $this->assertSame(self::SHARED_ID, $object['id']);
        $this->assertSame('object-four', $object['key']);
        $this->assertSame('/object-four', $object['fullPath']);
    }

    /**
     * The cache is filled by whichever element is resolved first,
     * so the result must not depend on the order.
     */
    public function testResolveIsIndependentOfResolveOrder(): void
    {
        $resolver = $this->getResolver();

        $document = $resolver->resolve($this->getDocument());
        $asset = $resolver->resolve($this->getAsset());

        $this->assertSame('/document-four', $document['fullPath']);
        $this->assertSame('/asset-four', $asset['fullPath']);
    }

    public function testResolveServesTheSameElementFromCache(): void
    {
        $resolver = $this->getResolver();
        $lookups = 0;
        $document = $this->getDocument(static function () use (&$lookups): string {
            $lookups++;

            return '/document-four';
        });

        $first = $resolver->resolve($document);
        $second = $resolver->resolve($document);

        $this->assertSame($first, $second);
        $this->assertSame(1, $lookups);
    }

    /**
     * Path used by the metadata hydrator, which only knows the type and the id.
     */
    public function testResolveDataResolvesAgainstTheGivenType(): void
    {
        $resolver = $this->getResolver([
            ElementTypes::TYPE_ASSET => $this->getAsset(),
            ElementTypes::TYPE_DOCUMENT => $this->getDocument(),
            ElementTypes::TYPE_OBJECT => $this->getDataObject(),
        ]);

        $asset = $resolver->resolveData(ElementTypes::TYPE_ASSET, self::SHARED_ID);
        $document = $resolver->resolveData(ElementTypes::TYPE_DOCUMENT, self::SHARED_ID);
        $object = $resolver->resolveData(ElementTypes::TYPE_DATA_OBJECT, self::SHARED_ID);

        $this->assertSame('/asset-four', $asset['fullPath']);
        $this->assertSame('/document-four', $document['fullPath']);
        $this->assertSame('/object-four', $object['fullPath']);
    }

    /**
     * @param array<string, ElementInterface> $elementsByType keyed by core element type
     */
    private function getResolver(array $elementsByType = []): ReferenceResolver
    {
        return new ReferenceResolver(
            $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => static fn (string $type, int $id): ?ElementInterface => $elementsByType[$type] ?? null,
            ])
        );
    }

    private function getAsset(): Asset
    {
        return $this->makeEmpty(Asset::class, [
            'getId' => self::SHARED_ID,
            'getObjectVars' => ['id' => self::SHARED_ID, 'filename' => 'asset-four', 'path' => '/', 'type' => 'folder'],
            'getFullPath' => '/asset-four',
        ]);
    }

    private function getDocument(?Closure $fullPath = null): Document
    {
        return $this->makeEmpty(Document::class, [
            'getId' => self::SHARED_ID,
            'getObjectVars' => ['id' => self::SHARED_ID, 'key' => 'document-four', 'path' => '/', 'type' => 'snippet'],
            'getFullPath' => $fullPath ?? '/document-four',
        ]);
    }

    private function getDataObject(): AbstractObject
    {
        return $this->makeEmpty(AbstractObject::class, [
            'getId' => self::SHARED_ID,
            'getObjectVars' => ['id' => self::SHARED_ID, 'key' => 'object-four', 'path' => '/', 'type' => 'folder'],
            'getFullPath' => '/object-four',
        ]);
    }
}
