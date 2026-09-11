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
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\Concrete;
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

    /**
     * @param string[] $allowedLanguages
     * @param string[] $allLanguages
     */
    private function createAdapter(array $allowedLanguages, array $allLanguages): LocalizedFieldsAdapter
    {
        return new LocalizedFieldsAdapter(
            $this->makeEmpty(DataAdapterServiceInterface::class),
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
