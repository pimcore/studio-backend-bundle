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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyTypeEngineFitResolver;
use Pimcore\Element\MarshallerService;
use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;

use Pimcore\Model\DataObject\ClassDefinition\Data\Geopoint;
use Pimcore\Model\DataObject\ClassDefinition\Loader\DataLoaderInterface;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineInterface;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Classificationstore\KeyTypeSupport;
use stdClass;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * The three conditions core's key save asks, put by this bundle: an accepted type, one the value
 * columns do not hold, and an engine without the `StructuredValues` capability.
 *
 * @internal
 */
final class KeyTypeEngineFitResolverTest extends Unit
{
    private const string EAV = 'eav';

    public function testAStructuredTypeIsRefusedByAnEngineWithoutTheCapability(): void
    {
        $this->assertSame(self::EAV, $this->createResolver(false)->findRefusingEngine('geopoint', null, null));
    }

    public function testAnEngineWithTheCapabilityHoldsTheStructuredType(): void
    {
        $this->assertNull($this->createResolver(true)->findRefusingEngine('geopoint', null, null));
    }

    public function testATypeTheValueColumnsHoldIsHeldByEveryEngine(): void
    {
        $this->assertNull($this->createResolver(false)->findRefusingEngine('input', null, null));
    }

    public function testATypeOutsideTheAcceptedSetIsNotGoverned(): void
    {
        $this->assertNull($this->createResolver(false)->findRefusingEngine('block', null, null));
    }

    public function testAWrapperIsHeldExactlyWhenItsDelegateIs(): void
    {
        $resolver = $this->createResolver(false);

        $this->assertSame(self::EAV, $resolver->findRefusingEngine('encryptedField', 'geopoint', null));
        $this->assertNull($resolver->findRefusingEngine('encryptedField', 'input', null));
    }

    public function testAnInstallationWithoutAnEngineResolverRefusesNothing(): void
    {
        $resolver = new KeyTypeEngineFitResolver($this->createSupport(), null);

        $this->assertNull($resolver->findRefusingEngine('geopoint', null, null));
    }

    public function testOnlyTheWithdrawnTypesAreFound(): void
    {
        $this->assertSame(
            ['consent', 'permissionManyToOneRelation'],
            $this->createResolver(true)->findWithdrawnTypes(['consent', 'input', 'permissionManyToOneRelation'])
        );
        $this->assertSame([], $this->createResolver(true)->findWithdrawnTypes(['input']));
    }

    public function testADelegateTheKeyTypesExcludeIsFoundAndNothingElse(): void
    {
        $resolver = $this->createResolver(true);

        $this->assertSame('urlSlug', $resolver->findDelegateThatIsNotAKeyType('urlSlug'));
        $this->assertNull($resolver->findDelegateThatIsNotAKeyType('input'));
        $this->assertNull($resolver->findDelegateThatIsNotAKeyType(null));
    }

    public function testAnInstallationWithoutTheTypeSupportAsksNothing(): void
    {
        $resolver = new KeyTypeEngineFitResolver(null, null);

        $this->assertSame([], $resolver->findWithdrawnTypes(['consent']));
        $this->assertNull($resolver->findDelegateThatIsNotAKeyType('urlSlug'));
    }

    private function createResolver(bool $structured): KeyTypeEngineFitResolver
    {
        $engine = $this->makeEmpty(EngineInterface::class, [
            'supports' => static fn (EngineFeature $feature): bool => $structured
                && $feature === EngineFeature::StructuredValues,
        ]);

        return new KeyTypeEngineFitResolver(
            $this->createSupport(),
            new EngineResolver([self::EAV => $engine])
        );
    }

    /**
     * The real support over a loader that resolves the fixture's types and a marshaller locator
     * that packs `input` only, which is the whole of what separates a held type from an unheld one.
     */
    private function createSupport(): KeyTypeSupport
    {
        $loader = $this->makeEmpty(DataLoaderInterface::class, [
            'supports' => true,
            'build' => static fn (string $type): object => $type === 'geopoint'
                ? new Geopoint()
                : throw new UnsupportedException($type),
        ]);
        $locator = new ServiceLocator(['classificationstore_input' => static fn (): object => new stdClass()]);

        return new KeyTypeSupport($loader, new MarshallerService($locator));
    }
}
