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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Repository\Configuration;

use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Exception;
use Pimcore;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\CollectionConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\GroupConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollectionRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Cache\Core\CoreCacheHandler;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig\Dao as CollectionConfigDao;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * The pairs of a group and a collection are written only when the request supplied them: a write
 * that omits them leaves the model uninitialized, which is what keeps the column out of the save,
 * a supplied map replaces the stored one whole, and a supplied null or empty map clears it.
 *
 * @internal
 */
final class CustomMetadataWriteTest extends Unit
{
    private const int ID = 5;

    private const string NAME = 'Measurements';

    private const array PAIRS = ['indexed' => 'yes', 'title.de' => 'Gewicht'];

    private const array OTHER_PAIRS = ['source' => 'erp'];

    private ?KernelInterface $kernelBeforeStub = null;

    private bool $kernelStubbed = false;

    public function _before(): void
    {
        // what the models' own save reaches past the dao: the cache handler and the event dispatcher
        $container = new Container();
        $container->set(CoreCacheHandler::class, $this->makeEmpty(CoreCacheHandler::class));
        $container->set('event_dispatcher', new EventDispatcher());

        $kernelProperty = new ReflectionProperty(Pimcore::class, 'kernel');
        $this->kernelBeforeStub = $kernelProperty->getValue();
        Pimcore::setKernel($this->makeEmpty(KernelInterface::class, ['getContainer' => $container]));
        $this->kernelStubbed = true;
    }

    public function _after(): void
    {
        if (!$this->kernelStubbed) {
            return;
        }

        // Pimcore::setKernel() does not take null, so put the previous kernel back the only way there is
        (new ReflectionProperty(Pimcore::class, 'kernel'))->setValue(null, $this->kernelBeforeStub);
        $this->kernelStubbed = false;
    }

    /**
     * @throws Exception
     */
    public function testAGroupUpdateFollowsTheAbsentSuppliedNullRule(): void
    {
        $config = new GroupConfig();
        $config->setId(self::ID);
        $config->setDao($this->makeEmpty(GroupConfigDao::class));
        $repository = new GroupRepository(
            $this->makeEmpty(GroupConfigResolverInterface::class, ['getById' => $config]),
            $this->makeEmpty(ListingFilterInterface::class),
            $this->makeEmpty(SearchHelperServiceInterface::class),
            $this->makeEmpty(Connection::class),
        );

        $this->assertSequence(
            $config,
            static fn (bool $supplied, ?array $pairs): mixed => $repository->update(
                self::ID,
                self::NAME,
                null,
                $supplied,
                $pairs
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testACollectionUpdateFollowsTheAbsentSuppliedNullRule(): void
    {
        $config = new CollectionConfig();
        $config->setId(self::ID);
        $config->setDao($this->makeEmpty(CollectionConfigDao::class));
        $repository = new CollectionRepository(
            $this->makeEmpty(CollectionConfigResolverInterface::class, ['getById' => $config]),
            $this->makeEmpty(ListingFilterInterface::class),
            $this->makeEmpty(SearchHelperServiceInterface::class),
        );

        $this->assertSequence(
            $config,
            static fn (bool $supplied, ?array $pairs): mixed => $repository->update(
                self::ID,
                self::NAME,
                null,
                $supplied,
                $pairs
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testAnArtifactSaveWritesThePairsOnlyWhenTheEntrySuppliesThem(): void
    {
        $config = new GroupConfig();
        $config->setId(self::ID);
        $config->setDao($this->makeEmpty(GroupConfigDao::class));
        $repository = new GroupRepository(
            $this->makeEmpty(GroupConfigResolverInterface::class),
            $this->makeEmpty(ListingFilterInterface::class),
            $this->makeEmpty(SearchHelperServiceInterface::class),
            $this->makeEmpty(Connection::class),
        );

        $this->assertSequence(
            $config,
            static fn (bool $supplied, ?array $pairs): mixed => $repository->saveFromArtifact(
                $config,
                1,
                '01GROUP',
                self::NAME,
                null,
                $supplied,
                $pairs
            )
        );
    }

    /**
     * update(pairs) -> update(absent) -> update(other pairs) -> update(null), and an empty map.
     *
     * @param callable(bool, ?array<string, mixed>): mixed $write
     */
    private function assertSequence(GroupConfig|CollectionConfig $config, callable $write): void
    {
        // absent on a model that never held any: the column stays out of the save
        $write(false, null);
        $this->assertFalse($config->isCustomMetadataInitialized());

        $write(true, self::PAIRS);
        $this->assertSame(self::PAIRS, $config->getCustomMetadata());

        // absent: the stored pairs are neither touched nor re-written
        $config->setCustomMetadata(self::PAIRS);
        $write(false, null);
        $this->assertSame(self::PAIRS, $config->getCustomMetadata());

        // a supplied object replaces the stored map whole, not member by member
        $write(true, self::OTHER_PAIRS);
        $this->assertSame(self::OTHER_PAIRS, $config->getCustomMetadata());

        $write(true, null);
        $this->assertNull($config->getCustomMetadata());

        $write(true, self::PAIRS);
        $write(true, []);
        $this->assertNull($config->getCustomMetadata());
    }
}
