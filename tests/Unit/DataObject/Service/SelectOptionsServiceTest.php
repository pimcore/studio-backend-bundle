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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinition\Helper\OptionsProviderResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Hydrator\SelectOptionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter\SelectOptionsParameter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\SelectOption;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\SelectOptionsService;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\FieldDefinitionWrapper;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\DotNotationParserInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Regression test for PEES-1645: unsaved changes sent along with the select-options
 * request use the Studio data format (e.g. localizedfields as attribute -> language)
 * and therefore must be applied through the Studio data adapters, not through the
 * classic editmode decoding.
 *
 * @internal
 */
final class SelectOptionsServiceTest extends Unit
{
    private const string FIELD_NAME = 'status';

    private const array STUDIO_SHAPED_CHANGED_DATA = [
        'localizedfields' => [
            'title' => ['de' => 'Titel', 'en' => 'Title'],
        ],
        'sku' => 'ABC-123',
    ];

    public function testChangedDataIsAppliedThroughTheStudioDataService(): void
    {
        $object = $this->createObject();
        $user = $this->makeEmpty(UserInterface::class);

        $dataService = $this->makeEmpty(DataServiceInterface::class, [
            'updateEditableData' => Expected::once(
                function (Concrete $element, array $editableData, UserInterface $editingUser) use ($object, $user): void {
                    $this->assertSame($object, $element);
                    $this->assertSame(self::STUDIO_SHAPED_CHANGED_DATA, $editableData);
                    $this->assertSame($user, $editingUser);
                }
            ),
        ]);

        $service = $this->createService($object, $dataService, $user);

        $options = $service->getSelectOptions(
            new SelectOptionsParameter(
                objectId: 42,
                fieldName: self::FIELD_NAME,
                context: ['containerType' => 'object', 'fieldname' => self::FIELD_NAME],
                changedData: self::STUDIO_SHAPED_CHANGED_DATA,
            )
        );

        $this->assertCount(1, $options);
        $this->assertSame('Scheduled', $options[0]->getKey());
    }

    public function testDataServiceIsNotCalledWithoutChangedData(): void
    {
        $object = $this->createObject();
        $user = $this->makeEmpty(UserInterface::class);

        $dataService = $this->makeEmpty(DataServiceInterface::class, [
            'updateEditableData' => Expected::never(),
        ]);

        $service = $this->createService($object, $dataService, $user);

        $options = $service->getSelectOptions(
            new SelectOptionsParameter(
                objectId: 42,
                fieldName: self::FIELD_NAME,
                context: ['containerType' => 'object', 'fieldname' => self::FIELD_NAME],
            )
        );

        $this->assertCount(1, $options);
    }

    private function createObject(): Concrete
    {
        $classDefinition = new ClassDefinition();
        $classDefinition->setId('EV');
        $classDefinition->setName('Event');

        return $this->makeEmpty(Concrete::class, [
            'getId' => 42,
            'getClass' => $classDefinition,
            'getClassId' => 'EV',
        ]);
    }

    private function createService(
        Concrete $object,
        DataServiceInterface $dataService,
        UserInterface $user,
    ): SelectOptionsService {
        $fieldDefinition = new Select();
        $fieldDefinition->setName(self::FIELD_NAME);
        $fieldDefinition->setOptionsProviderClass('App\OptionsProvider\StatusProvider');

        $provider = $this->makeEmpty(SelectOptionsProviderInterface::class, [
            'getOptions' => [['key' => 'Scheduled', 'value' => 'Scheduled']],
        ]);

        return new SelectOptionsService(
            $this->makeEmpty(ConcreteObjectResolverInterface::class, ['getById' => $object]),
            $dataService,
            $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => $user]),
            $this->makeEmpty(OptionsProviderResolverInterface::class, ['resolveProvider' => $provider]),
            $this->makeEmpty(SelectOptionHydratorInterface::class, [
                'hydrate' => static fn (array $data): SelectOption => new SelectOption($data['key'], $data['value']),
            ]),
            $this->makeEmpty(EventDispatcherInterface::class, [
                'dispatch' => static fn (object $event): object => $event,
            ]),
            $this->makeEmpty(DotNotationParserInterface::class, [
                'parse' => new FieldDefinitionWrapper($fieldDefinition, 'object', self::FIELD_NAME),
            ]),
        );
    }
}
