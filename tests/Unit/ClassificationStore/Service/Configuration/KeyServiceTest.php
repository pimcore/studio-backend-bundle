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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\KeyService;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
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
            ->expects($this->once())
            ->method('update')
            ->with(
                29,
                'tessd',
                'tessd',
                null,
                'select',
                $definition + ['optionsProviderClass' => SelectOptionsOptionsProvider::class]
            )
            ->willReturn(new KeyConfig());

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
            ->expects($this->once())
            ->method('update')
            ->with(1, 'key', null, null, null, $definition)
            ->willReturn(new KeyConfig());

        $this->createService($repository)->updateKey(1, new KeyUpdate('key', definition: $definition));
    }

    /**
     * @throws Exception
     */
    public function testUpdateKeyPassesANullDefinitionThrough(): void
    {
        $repository = $this->createMock(KeyRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('update')
            ->with(1, 'key', 'Title', 'Description', 'input', null)
            ->willReturn(new KeyConfig());

        $this->createService($repository)->updateKey(1, new KeyUpdate('key', 'Title', 'Description', 'input'));
    }

    /**
     * @throws Exception
     */
    private function createService(KeyRepositoryInterface $repository): KeyService
    {
        $hydrator = $this->createMock(KeyHydratorInterface::class);
        $hydrator
            ->method('hydrateKeyDetail')
            ->willReturn(new KeyDetail(1, 'key', 1, 'select', true));

        return new KeyService(
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(FilterMapperServiceInterface::class),
            $repository,
            $hydrator,
        );
    }
}
