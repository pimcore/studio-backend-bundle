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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Hydrator\Configuration;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\StoreHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\StoreEngineResolverInterface;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;

/**
 * The store's engine and the capability set its engine reports reach both read surfaces by value,
 * and the two answers a client has to tell apart stay two answers there.
 *
 * The `required` list of either schema is asserted elsewhere
 * ({@see \Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Schema\Configuration\RequiredMembersUnchangedTest}),
 * which is a different claim: that nothing a client already deserializes changed. That an optional
 * member is actually hydrated is this one, and the pair of them is what a reader of the PR
 * description is told. The key's own type on the relations payload has its sibling in
 * {@see \Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Hydrator\KeyTypePublicationTest}.
 *
 * @internal
 */
final class EnginePublicationTest extends Unit
{
    private const string ENGINE = 'eav';

    private const array CAPABILITIES = ['PerKeyLocalization', 'Variants'];

    public function testBothReadSurfacesPublishTheEngineAndItsCapabilities(): void
    {
        $hydrator = new StoreHydrator($this->createResolver(self::ENGINE, self::CAPABILITIES));
        $storeConfig = $this->createStoreConfig();

        $detail = $hydrator->hydrateStoreDetail($storeConfig);
        $treeNode = $hydrator->hydrateStoreTreeNode($storeConfig);

        $this->assertSame(self::ENGINE, $detail->getEngine());
        $this->assertSame(self::CAPABILITIES, $detail->getEngineCapabilities());

        // the tree is the only read surface of a store that already exists, so it carries both too
        $this->assertSame(self::ENGINE, $treeNode->getEngine());
        $this->assertSame(self::CAPABILITIES, $treeNode->getEngineCapabilities());
    }

    /**
     * An engine reporting no capability and a question that could not be put are two answers, and
     * they stay two on the wire: the empty set is published as the empty array and "no answer" as
     * null, which is what a client compares its declaration against.
     */
    public function testNoCapabilityAndNoAnswerStayTwoAnswers(): void
    {
        $storeConfig = $this->createStoreConfig();

        $reportsNone = new StoreHydrator($this->createResolver(self::ENGINE, []));
        $this->assertSame([], $reportsNone->hydrateStoreDetail($storeConfig)->getEngineCapabilities());
        $this->assertSame([], $reportsNone->hydrateStoreTreeNode($storeConfig)->getEngineCapabilities());

        $cannotBeAsked = new StoreHydrator($this->createResolver(null, null));
        $this->assertNull($cannotBeAsked->hydrateStoreDetail($storeConfig)->getEngine());
        $this->assertNull($cannotBeAsked->hydrateStoreDetail($storeConfig)->getEngineCapabilities());
        $this->assertNull($cannotBeAsked->hydrateStoreTreeNode($storeConfig)->getEngine());
        $this->assertNull($cannotBeAsked->hydrateStoreTreeNode($storeConfig)->getEngineCapabilities());
    }

    /**
     * @param string[]|null $capabilities
     */
    private function createResolver(?string $engine, ?array $capabilities): StoreEngineResolverInterface
    {
        return $this->makeEmpty(StoreEngineResolverInterface::class, [
            'getEngine' => static fn (): ?string => $engine,
            'getEngineCapabilities' => static fn (): ?array => $capabilities,
        ]);
    }

    private function createStoreConfig(): StoreConfig
    {
        $storeConfig = new StoreConfig();
        $storeConfig->setId(1);
        $storeConfig->setName('Technical attributes');

        return $storeConfig;
    }
}
