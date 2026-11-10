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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\Configuration;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\CoreBundle\OptionsProvider\SelectOptionsOptionsProvider;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyWriteResult;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\KeyService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapTriggerInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\NullKeyOptionsResolver;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function json_encode;

/**
 * @internal
 */
final class KeyServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testUpdateKeyAddsTheDefaultProviderClassForSelectOptions(): void
    {
        $definition = [
            'fieldtype' => 'select',
            'name' => 'tessd',
            'optionsProviderType' => 'select_options',
            'optionsProviderData' => 'AardingWCD',
        ];

        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->method('getById')
            ->willReturn(new KeyConfig());
        $repository
            ->expects($this->once())
            ->method('update')
            ->with(
                29,
                new KeyUpdate(
                    'tessd',
                    'tessd',
                    null,
                    'select',
                    $definition + ['optionsProviderClass' => SelectOptionsOptionsProvider::class]
                )
            )
            ->willReturn(new KeyWriteResult(new KeyConfig(), null));

        $this->createService($repository)->updateKey(
            29,
            new KeyUpdate('tessd', 'tessd', null, 'select', $definition)
        );
    }

    /**
     * @throws Exception
     */
    public function testUpdateKeyKeepsAnExplicitlyConfiguredProviderClass(): void
    {
        $definition = [
            'fieldtype' => 'select',
            'optionsProviderType' => 'select_options',
            'optionsProviderClass' => 'App\\OptionsProvider\\MyProvider',
        ];

        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->method('getById')
            ->willReturn(new KeyConfig());
        $repository
            ->expects($this->once())
            ->method('update')
            ->with(1, new KeyUpdate('key', definition: $definition))
            ->willReturn(new KeyWriteResult(new KeyConfig(), null));

        $this->createService($repository)->updateKey(1, new KeyUpdate('key', definition: $definition));
    }

    /**
     * @throws Exception
     */
    public function testUpdateKeyPassesANullDefinitionThrough(): void
    {
        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->method('getById')
            ->willReturn(new KeyConfig());
        $repository
            ->expects($this->once())
            ->method('update')
            ->with(1, new KeyUpdate('key', 'Title', 'Description', 'input'))
            ->willReturn(new KeyWriteResult(new KeyConfig(), null));

        $this->createService($repository)->updateKey(1, new KeyUpdate('key', 'Title', 'Description', 'input'));
    }

    /**
     * @throws Exception
     */
    public function testUpdateKeyForwardsThePresenceFlagsWithoutFlatteningThem(): void
    {
        // A definition makes the service hand on a new instance, so the flags have to survive the copy.
        $parameters = new KeyUpdate(
            name: 'key',
            title: null,
            description: '',
            definition: ['fieldtype' => 'input'],
            titleSupplied: true,
            descriptionSupplied: true
        );

        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->method('getById')
            ->willReturn(new KeyConfig());
        $repository
            ->expects($this->once())
            ->method('update')
            ->with(1, $parameters)
            ->willReturn(new KeyWriteResult(new KeyConfig(), null));

        $this->createService($repository)->updateKey(1, $parameters);
    }

    /**
     * @throws Exception
     */
    public function testCreateKeyPassesTheSuppliedTitleToTheRepository(): void
    {
        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('create')
            ->with('weight', 3, 'Weight')
            ->willReturn(new KeyConfig());

        $this->createService($repository)->createKey(new KeyCreate('weight', 3, 'Weight'));
    }

    /**
     * @throws Exception
     */
    public function testCreateKeyPassesANullTitleThrough(): void
    {
        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('create')
            ->with('length', 3, null)
            ->willReturn(new KeyConfig());

        $this->createService($repository)->createKey(new KeyCreate('length', 3));
    }

    /**
     * @throws Exception
     */
    public function testCreateKeyPassesAnEmptyTitleThrough(): void
    {
        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('create')
            ->with('depth', 3, '')
            ->willReturn(new KeyConfig());

        $this->createService($repository)->createKey(new KeyCreate('depth', 3, ''));
    }

    /**
     * @throws Exception
     */
    public function testCreateKeyClearsTheKeyRelationMemo(): void
    {
        $resolver = $this->expectClearedOnce();

        $this->createService($this->writableRepository(), $resolver)->createKey(new KeyCreate('key', 1));
    }

    /**
     * @throws Exception
     */
    public function testUpdateKeyClearsTheKeyRelationMemo(): void
    {
        $resolver = $this->expectClearedOnce();

        $this->createService($this->writableRepository(), $resolver)->updateKey(1, new KeyUpdate('key'));
    }

    /**
     * @throws Exception
     */
    public function testSoftDeleteKeyClearsTheKeyRelationMemo(): void
    {
        $resolver = $this->expectClearedOnce();

        $this->createService($this->writableRepository(), $resolver)->softDeleteKey(1);
    }

    /**
     * @throws Exception
     */
    public function testRestoreKeyClearsTheKeyRelationMemo(): void
    {
        $resolver = $this->expectClearedOnce();

        $this->createService($this->restorableRepository(), $resolver)->restoreKey(1);
    }

    /**
     * @throws Exception
     */
    public function testRestoreKeyClearsTheKeyRelationMemoWhenTheRepositoryThrows(): void
    {
        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->method('restore')
            ->willThrowException(new NotFoundException('key configuration', 1));
        $resolver = $this->expectClearedOnce();

        $this->expectException(NotFoundException::class);

        $this->createService($repository, $resolver)->restoreKey(1);
    }

    /**
     * Restoring a key twice answers the same enabled detail both times, with no refusal.
     *
     * @throws Exception
     */
    public function testRestoreKeyIsIdempotent(): void
    {
        $config = new KeyConfig();
        $config->setId(5);
        $config->setName('weight');
        $config->setType('input');
        $config->setEnabled(true);
        $config->setDefinition('{"fieldtype":"input"}');
        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->method('restore')
            ->willReturn($config);
        $service = $this->createService(
            $repository,
            null,
            new KeyHydrator(new InMemoryKeyLocalizationResolver(), new NullKeyOptionsResolver())
        );

        $first = $service->restoreKey(5);
        $second = $service->restoreKey(5);

        $this->assertTrue($first->isEnabled());
        $this->assertSame($first->getId(), $second->getId());
        $this->assertSame($first->getName(), $second->getName());
        $this->assertSame($first->getDefinition(), $second->getDefinition());
        $this->assertTrue($second->isEnabled());
    }

    /**
     * @throws Exception
     */
    private function restorableRepository(): KeyRepositoryInterface
    {
        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->method('restore')
            ->willReturn(new KeyConfig());

        return $repository;
    }

    /**
     * The flag written on the key is the flag the same call reports back, so a client that renders
     * the response needs no second read: the repository double writes what it was handed and the
     * real hydrator reads it off the config it got back.
     *
     * @dataProvider localizedProvider
     *
     * @throws Exception
     */
    public function testUpdateKeyWritesTheLocalizationFlagAndReportsItBack(?bool $localized): void
    {
        $keyLocalizationResolver = new InMemoryKeyLocalizationResolver();
        $config = new KeyConfig();
        $config->setId(1);
        $config->setName('key');
        $config->setType('select');

        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->method('update')
            ->willReturnCallback(
                static function (
                    int $id,
                    KeyUpdate $parameters
                ) use ($config, $keyLocalizationResolver): KeyWriteResult {
                    if ($parameters->isLocalizedSupplied()) {
                        $keyLocalizationResolver->setLocalized($config, $parameters->getLocalized());
                    }

                    return new KeyWriteResult($config, $config->getDefinition());
                }
            );

        $detail = $this
            ->createService($repository, null, new KeyHydrator($keyLocalizationResolver, new NullKeyOptionsResolver()))
            ->updateKey(1, new KeyUpdate(name: 'key', localized: $localized, localizedSupplied: true));

        $this->assertSame($localized, $keyLocalizationResolver->getLocalized($config));
        $this->assertSame($localized, $detail->getLocalized());
    }

    /**
     * @return array<string, array{0: ?bool}>
     */
    public static function localizedProvider(): array
    {
        return [
            'made localized' => [true],
            'made not localized' => [false],
            'made to inherit again' => [null],
        ];
    }

    /**
     * A created key states nothing, so it inherits: the create endpoint writes no flag and the
     * detail it reports back carries null rather than a decision nobody asked for.
     *
     * @throws Exception
     */
    public function testCreateKeyReportsAKeyThatStatesNoLocalizationFlag(): void
    {
        $created = new KeyConfig();
        $created->setId(1);
        $created->setName('weight');
        $created->setType('input');

        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->method('create')
            ->willReturn($created);

        $detail = $this
            ->createService($repository, null, new KeyHydrator(new InMemoryKeyLocalizationResolver(), new NullKeyOptionsResolver()))
            ->createKey(new KeyCreate('weight', 3));

        $this->assertNull($detail->getLocalized());
    }

    /**
     * Declaring `variants` or `multiValue` on a key of a store whose engine reports neither
     * capability is accepted: the write goes through, the declaration is stored verbatim, and the
     * response carries neither an error nor a note about it.
     *
     * The stubbed engine is the one reporting the empty capability set, and it is reachable from
     * nowhere in this service - which is the point: the update path consults no engine at all, so
     * there is no capability that could refuse the declaration. The object data path is where a
     * capability is judged, and {@see \Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Data\Adapter\ClassificationStoreAdapterTest}
     * covers it.
     *
     * @throws Exception
     */
    public function testADeclarationIsStoredWhateverTheStoresEngineReports(): void
    {
        $definition = [
            'fieldtype' => 'quantityValue',
            'variants' => ['metric', 'imperial'],
            'multiValue' => true,
        ];

        $keyConfig = new KeyConfig();
        $keyConfig->setId(29);
        $keyConfig->setName('weight');
        $keyConfig->setType('quantityValue');

        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->method('update')
            ->willReturnCallback(static function (int $id, KeyUpdate $parameters) use ($keyConfig): KeyWriteResult {
                $definitionBefore = $keyConfig->getDefinition();
                $keyConfig->setDefinition((string) json_encode($parameters->getDefinition()));

                return new KeyWriteResult($keyConfig, $definitionBefore);
            });

        $keyDetail = $this
            ->createService($repository, keyHydrator: new KeyHydrator(new InMemoryKeyLocalizationResolver(), new NullKeyOptionsResolver()))
            ->updateKey(29, new KeyUpdate('weight', null, null, 'quantityValue', $definition));

        $this->assertSame($definition, $keyDetail->getDefinition());
    }

    private function expectClearedOnce(): KeyRelationResolverInterface
    {
        $resolver = $this->createMock(KeyRelationResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('clear');

        return $resolver;
    }

    /**
     * @throws Exception
     */
    private function writableRepository(): KeyRepositoryInterface
    {
        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->method('getById')
            ->willReturn(new KeyConfig());
        $repository
            ->method('create')
            ->willReturn(new KeyConfig());
        $repository
            ->method('update')
            ->willReturn(new KeyWriteResult(new KeyConfig(), null));

        return $repository;
    }

    /**
     * @throws Exception
     */
    private function createService(
        KeyRepositoryInterface $repository,
        ?KeyRelationResolverInterface $resolver = null,
        ?KeyHydratorInterface $keyHydrator = null
    ): KeyService {
        if (!$keyHydrator) {
            $keyHydrator = $this->createMock(KeyHydratorInterface::class);
            $keyHydrator
                ->method('hydrateKeyDetail')
                ->willReturn(new KeyDetail(1, 'key', 1, 'select', true));
        }

        return new KeyService(
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(FilterMapperServiceInterface::class),
            $resolver ?? $this->createMock(KeyRelationResolverInterface::class),
            $repository,
            $keyHydrator,
            $this->createMock(SearchIndexRemapTriggerInterface::class),
        );
    }
}
