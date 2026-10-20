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

    public function testDetailDataOfTheEditViewIsNotBuiltAsHistoricalVersion(): void
    {
        $contextData = $this->resolveDetailContext(null);

        $this->assertSame('en', $contextData->getLanguage());
        $this->assertFalse($contextData->isHistoricalVersion());
    }

    public function testDetailDataOfAVersionForwardsTheHistoricalVersionContextToObjectLevelLocalizedFields(): void
    {
        $contextData = $this->resolveDetailContext(new FieldContextData(historicalVersion: true));

        $this->assertSame('en', $contextData->getLanguage());
        $this->assertTrue($contextData->isHistoricalVersion());
    }

    public function testDetailDataOfAVersionDoesNotForwardTheHistoricalVersionContextIntoContainers(): void
    {
        // only the localized fields of the object itself are part of what a version stores; the ones of an
        // object brick (or field collection item) are computed live, so they must not be hydrated as historical
        $contextData = $this->resolveDetailContext(
            new FieldContextData(contextObject: $this->makeEmpty(AbstractData::class), historicalVersion: true)
        );

        $this->assertSame('en', $contextData->getLanguage());
        $this->assertFalse($contextData->isHistoricalVersion());
    }

    private function resolveDetailContext(?FieldContextData $contextData): FieldContextData
    {
        $captured = [];
        $dataService = $this->makeEmpty(DataServiceInterface::class, [
            'getDetailValue' => function (
                Concrete $object,
                mixed $value,
                Data $fieldDefinition,
                ?FieldContextData $contextData = null
            ) use (&$captured): mixed {
                $captured[] = $contextData;

                return $value;
            },
        ]);
        $adapter = $this->createAdapter(allowedLanguages: ['en'], allLanguages: ['en'], dataService: $dataService);

        $localizedFieldsDefinition = $this->makeEmpty(Localizedfields::class, [
            'getFieldDefinition' => $this->makeEmpty(Input::class, ['getName' => 'title']),
            'getFieldDefinitions' => [],
            'normalize' => ['en' => ['title' => 'value']],
        ]);
        $class = new ClassDefinition();
        $class->setFieldDefinitions(['localizedfields' => $localizedFieldsDefinition]);
        $object = $this->makeEmpty(Concrete::class, ['getClass' => $class]);
        $localizedField = new Localizedfield(['en' => ['title' => 'value']]);
        $localizedField->setObject($object);

        $result = $adapter->getDetailData($object, $localizedField, $localizedFieldsDefinition, $contextData);

        $this->assertSame(['title' => ['en' => 'value']], $result);
        $this->assertCount(1, $captured);
        $this->assertInstanceOf(FieldContextData::class, $captured[0]);

        return $captured[0];
    }

    /**
     * @param string[] $allowedLanguages
     * @param string[] $allLanguages
     */
    private function createAdapter(
        array $allowedLanguages,
        array $allLanguages,
        ?DataServiceInterface $dataService = null
    ): LocalizedFieldsAdapter {
        return new LocalizedFieldsAdapter(
            $this->makeEmpty(DataAdapterServiceInterface::class),
            $dataService ?? $this->makeEmpty(DataServiceInterface::class),
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
