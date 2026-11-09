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
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
            ->createService($repository, null, new KeyHydrator($keyLocalizationResolver))
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
            ->createService($repository, null, new KeyHydrator(new InMemoryKeyLocalizationResolver()))
            ->createKey(new KeyCreate('weight', 3));

        $this->assertNull($detail->getLocalized());
    }

    /**
     * @throws Exception
     */
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
