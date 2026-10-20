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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Data\Adapter;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\LocalizedFieldsAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\LanguageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class LocalizedFieldsAdapterTest extends Unit
{
    public function testInheritedValueResolutionIsRestrictedToLanguagesTheUserIsAllowedToView(): void
    {
        $adapter = $this->createAdapter(allowedLanguages: ['en'], allLanguages: ['en', 'de', 'fr']);
        $object = $this->makeEmpty(Concrete::class);
        $fieldDefinition = $this->makeEmpty(Localizedfields::class, [
            'getChildren' => [$this->makeEmpty(Input::class, ['getName' => 'title'])],
        ]);

        $result = $adapter->getFieldInheritance(
            $object,
            $fieldDefinition,
            'localizedfields',
            new FieldContextData(resolveInheritedValue: true)
        );

        // an actual (opted-in) inherited value is real localized content and must not be built for a
        // language the current user is not allowed to view, even though the class configures more of them
        $this->assertSame(['en'], array_keys($result['title']));
    }

    public function testInheritableMetadataWithoutValueResolutionCoversEveryConfiguredLanguage(): void
    {
        $adapter = $this->createAdapter(allowedLanguages: ['en'], allLanguages: ['en', 'de', 'fr']);
        $object = $this->makeEmpty(Concrete::class);
        $fieldDefinition = $this->makeEmpty(Localizedfields::class, [
            'getChildren' => [$this->makeEmpty(Input::class, ['getName' => 'title'])],
        ]);

        $result = $adapter->getFieldInheritance(
            $object,
            $fieldDefinition,
            'localizedfields',
            new FieldContextData(resolveInheritedValue: false)
        );

        // without the opt-in, no value ever crosses the wire - the inheritable/inherited flags remain
        // structural metadata and are still built for every configured language, unfiltered by permission
        $this->assertSame(['en', 'de', 'fr'], array_keys($result['title']));
    }

    public function testNullLocalizedDataKeepsTheLocalizedFieldUnchanged(): void
    {
        // the detail data of localized fields without any value is null, e.g. in a newly added object brick,
        // and the editor sends it back unchanged when the brick is saved
        $adapter = $this->createAdapter(allowedLanguages: ['en'], allLanguages: ['en', 'de', 'fr']);
        $localizedField = new Localizedfield(['en' => ['title' => 'Existing']]);
        $object = $this->makeEmpty(Concrete::class, [
            'get' => $localizedField,
            'getClass' => new ClassDefinition(),
        ]);

        $result = $adapter->getDataForSetter(
            $object,
            $this->makeEmpty(Localizedfields::class),
            'localizedfields',
            ['localizedfields' => null],
            $this->makeEmpty(UserInterface::class, ['isAdmin' => true])
        );

        $this->assertSame($localizedField, $result);
        $this->assertSame(['en' => ['title' => 'Existing']], $result->getItems());
    }

    public function testSetterDataForwardsTheContainerContextAndLanguageToChildAdapters(): void
    {
        // localized fields inside a container (e.g. an object brick) must keep that container in the context of
        // their children, so a nested block reads its stored items from the container and not from the object
        $captured = [];
        $childAdapter = $this->makeEmpty(SetterDataInterface::class, [
            'getDataForSetter' => static function (
                Concrete $element,
                Data $fieldDefinition,
                string $key,
                array $data,
                UserInterface $user,
                ?FieldContextData $contextData = null
            ) use (&$captured): mixed {
                $captured[] = $contextData;

                return $data[$key] ?? null;
            },
        ]);
        $adapter = $this->createAdapter(
            allowedLanguages: ['en', 'de'],
            allLanguages: ['en', 'de'],
            dataAdapterService: $this->makeEmpty(DataAdapterServiceInterface::class, [
                'tryDataAdapter' => $childAdapter,
            ])
        );

        $fieldDefinition = $this->makeEmpty(Localizedfields::class, [
            'getFieldDefinition' => $this->makeEmpty(Input::class, ['getName' => 'myBlock']),
        ]);
        $class = new ClassDefinition();
        $class->setFieldDefinitions(['localizedfields' => $fieldDefinition]);
        $localizedField = new Localizedfield();
        $brick = $this->makeEmpty(AbstractData::class, ['get' => $localizedField]);

        $result = $adapter->getDataForSetter(
            $this->makeEmpty(Concrete::class, ['getClass' => $class]),
            $fieldDefinition,
            'localizedfields',
            ['localizedfields' => ['myBlock' => ['de' => 'value']]],
            $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
            new FieldContextData(contextObject: $brick)
        );

        $this->assertCount(1, $captured);
        $this->assertInstanceOf(FieldContextData::class, $captured[0]);
        $this->assertSame($brick, $captured[0]->getContextObject());
        $this->assertSame('de', $captured[0]->getLanguage());
        $this->assertSame($localizedField, $result);
        $this->assertSame(['de' => ['myBlock' => 'value']], $result->getItems());
    }

    /**
     * @param string[] $allowedLanguages
     * @param string[] $allLanguages
     */
    private function createAdapter(
        array $allowedLanguages,
        array $allLanguages,
        ?DataAdapterServiceInterface $dataAdapterService = null
    ): LocalizedFieldsAdapter {
        return new LocalizedFieldsAdapter(
            $dataAdapterService ?? $this->makeEmpty(DataAdapterServiceInterface::class),
            $this->makeEmpty(DataServiceInterface::class),
            $this->makeEmpty(InheritanceServiceInterface::class, [
                'processFieldDefinition' => new InheritanceData(1, inheritable: true),
            ]),
            $this->makeEmpty(LanguageServiceInterface::class, [
                'getUserAllowedLanguages' => $allowedLanguages,
            ]),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class),
            ]),
            $this->makeEmpty(ToolResolverInterface::class, [
                'getValidLanguages' => $allLanguages,
            ])
        );
    }
}
