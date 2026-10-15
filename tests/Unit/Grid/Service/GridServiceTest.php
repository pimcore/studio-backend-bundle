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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DataObjectSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid\GridSearchInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\StudioElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnCollectorLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnDefinitionLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnResolverLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridService;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\WorkflowPermissionMergerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ProvidedGridElement;
use Pimcore\Bundle\StudioBackendBundle\Response\Element;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\WorkflowPermissionsAwareInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class GridServiceTest extends Unit
{
    /**
     * A stale ElasticSearch index can reference an object that no longer exists in the
     * database (sync lag). A single missing element must not break the whole grid: it
     * should be skipped and logged, and the remaining rows still returned.
     *
     * @see https://github.com/pimcore/studio-backend-bundle/issues/1894
     */
    public function testGetDataObjectGridSkipsElementsMissingFromDatabase(): void
    {
        $missingId = 534;
        $existingId = 100;

        $searchResult = new DataObjectSearchResult(
            items: [
                $this->makeEmpty(StudioElementInterface::class, ['getId' => $missingId]),
                $this->makeEmpty(StudioElementInterface::class, ['getId' => $existingId]),
            ],
            currentPage: 1,
            pageSize: 10,
            totalItems: 2,
        );

        $existingObject = $this->makeEmpty(AbstractObject::class);
        $serviceResolver = $this->makeEmpty(ServiceResolverInterface::class, [
            'getElementById' => static fn (string $type, int|string $id): ?AbstractObject =>
                $id === $missingId ? null : $existingObject,
        ]);

        $service = $this->createService(
            gridSearch: $this->makeEmpty(GridSearchInterface::class, [
                'searchDataObjects' => $searchResult,
            ]),
            serviceResolver: $serviceResolver,
            // The missing element must produce exactly one warning.
            logger: $this->makeEmpty(LoggerInterface::class, ['warning' => Expected::once()]),
        );

        // Empty column set keeps the test focused on the element-loading behaviour.
        $gridParameter = new GridParameter(folderId: 1, columns: [], filters: null);

        $result = $service->getDataObjectGrid($gridParameter, null);

        // Only the existing element is returned; the missing one is skipped, not fatal.
        $this->assertCount(1, $result->getItems());
        // The search-reported total is intentionally left untouched.
        $this->assertSame(2, $result->getTotalItems());
    }

    /**
     * A row whose element has no workflow with permissions must neither load the core
     * element nor invoke the workflow permission merger; the user permissions pass through.
     */
    public function testWorkflowMergeIsSkippedWithoutWorkflowPermissions(): void
    {
        $permissions = new Permissions();

        $service = $this->createService(
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => Expected::never(),
            ]),
            workflowPermissionMerger: $this->makeEmpty(WorkflowPermissionMergerInterface::class, [
                'mergeWorkflowPermissions' => Expected::never(),
            ]),
            columnResolverLoader: $this->createColumnResolverLoader(),
        );

        $data = $service->getGridDataForElement(
            $this->createTestColumnCollection(),
            $this->createStudioElement($permissions, false),
            ElementTypes::TYPE_ASSET,
            1
        );

        $this->assertSame($permissions, $data['permissions']);
    }

    /**
     * A row whose element has a workflow with permissions must load the core element once,
     * invoke the merger once, and expose the merged permissions on the row payload.
     */
    public function testWorkflowMergeIsAppliedForWorkflowElement(): void
    {
        $userPermissions = new Permissions();
        $mergedPermissions = new Permissions(delete: false);

        $service = $this->createService(
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => Expected::once($this->makeEmpty(AbstractObject::class)),
            ]),
            workflowPermissionMerger: $this->makeEmpty(WorkflowPermissionMergerInterface::class, [
                'mergeWorkflowPermissions' => Expected::once($mergedPermissions),
            ]),
            columnResolverLoader: $this->createColumnResolverLoader(),
        );

        $data = $service->getGridDataForElement(
            $this->createTestColumnCollection(),
            $this->createStudioElement($userPermissions, true),
            ElementTypes::TYPE_ASSET,
            1
        );

        $this->assertSame($mergedPermissions, $data['permissions']);
    }

    /**
     * A non-admin user restricted to "en" for viewing must not receive values for other
     * locales, regardless of which column resolver would have produced them.
     *
     * @see https://pimcore.atlassian.net/browse/PEES-1063
     */
    public function testIsLocaleViewableForElementReturnsFalseWhenLocaleNotAllowed(): void
    {
        $user = $this->buildUser(isAdmin: false);
        $element = $this->makeEmpty(Concrete::class);

        $service = $this->createService(
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getLanguagePermissions' => ['en' => 1],
            ]),
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $user,
            ]),
        );

        self::assertFalse($service->isLocaleViewableForElement($element, 'de', $user));
        self::assertTrue($service->isLocaleViewableForElement($element, 'en', $user));
    }

    /**
     * An allowed locale with an empty value can silently fall back to the default language
     * (LocalizedValueTrait::getLocalizedValue()). If that fallback language is not itself
     * permitted, the column must be denied - otherwise a restricted language's value could leak
     * out through the fallback even though the requested locale was legitimately allowed.
     *
     * @see https://pimcore.atlassian.net/browse/PEES-1063
     */
    public function testIsLocaleViewableForElementReturnsFalseWhenFallbackLanguageIsDenied(): void
    {
        $user = $this->buildUser(isAdmin: false);
        $element = $this->makeEmpty(Concrete::class);

        $service = $this->createService(
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                // Only "en" is allowed - "de" (the system default language) is not.
                'getLanguagePermissions' => ['en' => 1],
            ]),
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $user,
            ]),
            localizedFieldResolver: $this->makeEmpty(LocalizedFieldResolverInterface::class, [
                'doGetFallbackValues' => true,
            ]),
            toolResolver: $this->makeEmpty(ToolResolverInterface::class, [
                'getDefaultLanguage' => 'de',
            ]),
        );

        self::assertFalse($service->isLocaleViewableForElement($element, 'en', $user));
    }

    /**
     * When fallback is enabled but the default language is itself allowed (or is the same as
     * the requested locale), there is no additional exposure and the column stays viewable.
     */
    public function testIsLocaleViewableForElementReturnsTrueWhenFallbackLanguageIsAllowed(): void
    {
        $user = $this->buildUser(isAdmin: false);
        $element = $this->makeEmpty(Concrete::class);

        $service = $this->createService(
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getLanguagePermissions' => ['en' => 1, 'de' => 1],
            ]),
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $user,
            ]),
            localizedFieldResolver: $this->makeEmpty(LocalizedFieldResolverInterface::class, [
                'doGetFallbackValues' => true,
            ]),
            toolResolver: $this->makeEmpty(ToolResolverInterface::class, [
                'getDefaultLanguage' => 'de',
            ]),
        );

        self::assertTrue($service->isLocaleViewableForElement($element, 'en', $user));
    }

    /**
     * No workspace language restriction configured (null permission set) means every locale
     * stays viewable - this mirrors the pre-existing Object Editor behaviour.
     */
    public function testIsLocaleViewableForElementReturnsTrueWhenNoRestrictionConfigured(): void
    {
        $user = $this->buildUser(isAdmin: false);
        $element = $this->makeEmpty(Concrete::class);

        $service = $this->createService(
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getLanguagePermissions' => null,
            ]),
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $user,
            ]),
        );

        self::assertTrue($service->isLocaleViewableForElement($element, 'de', $user));
    }

    /**
     * Admins are exempt from language-view restrictions.
     */
    public function testIsLocaleViewableForElementReturnsTrueForAdminUser(): void
    {
        $user = $this->buildUser(isAdmin: true);
        $element = $this->makeEmpty(Concrete::class);

        $service = $this->createService(
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getLanguagePermissions' => Expected::never(),
            ]),
        );

        self::assertTrue($service->isLocaleViewableForElement($element, 'de', $user));
    }

    /**
     * Non-localized columns without a locale (e.g. system columns) carry no language data
     * and are never subject to language filtering.
     */
    public function testIsLocaleViewableForElementReturnsTrueWhenNonLocalizedColumnHasNoLocale(): void
    {
        $user = $this->buildUser(isAdmin: false);
        $element = $this->makeEmpty(Concrete::class);

        $service = $this->createService(
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getLanguagePermissions' => Expected::never(),
            ]),
        );

        self::assertTrue($service->isLocaleViewableForElement($element, null, $user, isLocalizedField: false));
    }

    /**
     * A localized field read without an explicit locale is implicitly served in the current
     * request locale (Localizedfield::getLanguage()). Omitting the locale from the column
     * configuration must therefore authorize that effective locale - otherwise a user
     * restricted to e.g. "fr" could read another language's value by simply leaving the
     * locale out of the client-supplied column config.
     *
     * @see https://pimcore.atlassian.net/browse/PEES-1063
     */
    public function testIsLocaleViewableForElementChecksImplicitRequestLocaleForLocalizedField(): void
    {
        $user = $this->buildUser(isAdmin: false);
        $element = $this->makeEmpty(Concrete::class);

        $service = $this->createService(
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getLanguagePermissions' => ['fr' => 1],
            ]),
            toolResolver: $this->makeEmpty(ToolResolverInterface::class, [
                'isValidLanguage' => true,
            ]),
            localeService: $this->makeEmpty(LocaleServiceInterface::class, [
                'getLocale' => 'de',
            ]),
        );

        self::assertFalse($service->isLocaleViewableForElement($element, null, $user, isLocalizedField: true));
    }

    /**
     * Without a (valid) request locale, core resolves an implicit read to the default
     * language - that language must be authorized as well.
     */
    public function testIsLocaleViewableForElementChecksImplicitDefaultLanguageForLocalizedField(): void
    {
        $user = $this->buildUser(isAdmin: false);
        $element = $this->makeEmpty(Concrete::class);

        $service = $this->createService(
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getLanguagePermissions' => ['fr' => 1],
            ]),
            toolResolver: $this->makeEmpty(ToolResolverInterface::class, [
                'isValidLanguage' => false,
                'getDefaultLanguage' => 'de',
            ]),
        );

        self::assertFalse($service->isLocaleViewableForElement($element, null, $user, isLocalizedField: true));

        $allowedService = $this->createService(
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getLanguagePermissions' => ['de' => 1],
            ]),
            toolResolver: $this->makeEmpty(ToolResolverInterface::class, [
                'isValidLanguage' => false,
                'getDefaultLanguage' => 'de',
            ]),
        );

        self::assertTrue($allowedService->isLocaleViewableForElement($element, null, $user, isLocalizedField: true));
    }

    /**
     * When no implicit locale can be resolved at all (no request locale, no default language
     * configured), there is no language to authorize and the value stays viewable.
     */
    public function testIsLocaleViewableForElementReturnsTrueForLocalizedFieldWithoutResolvableLocale(): void
    {
        $user = $this->buildUser(isAdmin: false);
        $element = $this->makeEmpty(Concrete::class);

        $service = $this->createService(
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getLanguagePermissions' => Expected::never(),
            ]),
            toolResolver: $this->makeEmpty(ToolResolverInterface::class, [
                'isValidLanguage' => false,
                'getDefaultLanguage' => null,
            ]),
        );

        self::assertTrue($service->isLocaleViewableForElement($element, null, $user, isLocalizedField: true));
    }

    /**
     * Only concrete data objects carry per-locale workspace permissions; other element types
     * (assets, documents) are unaffected by this check.
     */
    public function testIsLocaleViewableForElementReturnsTrueForNonDataObjectElements(): void
    {
        $user = $this->buildUser(isAdmin: false);
        $element = $this->makeEmpty(AbstractObject::class);

        $service = $this->createService(
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getLanguagePermissions' => Expected::never(),
            ]),
        );

        self::assertTrue($service->isLocaleViewableForElement($element, 'de', $user));
    }

    /**
     * When no user is passed explicitly, the current session user is resolved and used for
     * the permission check - this is what the default grid-browsing path relies on.
     */
    public function testIsLocaleViewableForElementFallsBackToCurrentUser(): void
    {
        $currentUser = $this->buildUser(isAdmin: false);
        $element = $this->makeEmpty(Concrete::class);

        $service = $this->createService(
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getLanguagePermissions' => ['en' => 1],
            ]),
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $currentUser,
            ]),
        );

        self::assertFalse($service->isLocaleViewableForElement($element, 'de'));
    }

    /**
     * End-to-end wiring check for the actual grid data path: a denied locale must make
     * getGridDataForElement() return a null value AND must never invoke the column's resolver.
     * The isLocaleViewableForElement() unit tests above cover the permission logic in isolation,
     * but an inversion or removal of the guard around the resolver call would not be caught by
     * those alone.
     *
     * @see https://pimcore.atlassian.net/browse/PEES-1063
     */
    public function testGetGridDataForElementRedactsValueForDeniedLocaleAndNeverCallsResolver(): void
    {
        $user = $this->buildUser(isAdmin: false);
        $element = $this->makeEmpty(Concrete::class);
        $column = new Column(key: 'name', locale: 'de', type: 'dataobject.input', group: null, config: []);

        // A plain implementation (rather than a mocked interface) so it can implement both
        // ColumnResolverInterface and CoreElementColumnResolverInterface, exactly like a real
        // column resolver does, and track whether it was actually invoked.
        $deniedResolver = new class implements ColumnResolverInterface, CoreElementColumnResolverInterface {
            public bool $wasCalled = false;

            public function getType(): string
            {
                return 'dataobject.input';
            }

            public function supportedElementTypes(): array
            {
                return [ElementTypes::TYPE_OBJECT];
            }

            public function resolveForCoreElement(Column $column, ElementInterface $element): ColumnData
            {
                $this->wasCalled = true;

                return new ColumnData(key: 'name', locale: $column->getLocale(), value: 'LEAKED', fieldType: 'input');
            }
        };

        $service = $this->createService(
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => $element,
            ]),
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getLanguagePermissions' => ['en' => 1],
            ]),
            securityService: $this->makeEmpty(SecurityServiceInterface::class),
            columnResolverLoader: $this->makeEmpty(ColumnResolverLoaderInterface::class, [
                'loadColumnResolvers' => ['dataobject.input' => $deniedResolver],
            ]),
        );

        $data = $service->getGridDataForElement(
            new ColumnCollection([$column]),
            null,
            ElementTypes::TYPE_OBJECT,
            1,
            false,
            $user,
        );

        self::assertNull($data['columns'][0]->getValue());
        self::assertFalse($deniedResolver->wasCalled, 'the resolver must not be invoked for a denied locale');
    }

    /**
     * The client controls the column configuration, so it can simply omit the locale for a
     * localized field: the resolver then calls the bare getter and core implicitly serves the
     * request locale or the default language. The grid data path must detect that the column
     * targets a localized field and authorize the implicit locale - otherwise the explicit-locale
     * guard above is trivially bypassed.
     *
     * @see https://pimcore.atlassian.net/browse/PEES-1063
     */
    public function testGetGridDataForElementRedactsLocalizedFieldColumnWithoutExplicitLocale(): void
    {
        $user = $this->buildUser(isAdmin: false);

        // "name" is a localized class field: not a direct field definition, but present inside
        // the localizedfields container - exactly how core resolves an implicit localized read.
        $localizedFields = $this->makeEmpty(Localizedfields::class, [
            'getFieldDefinition' => $this->makeEmpty(Data::class),
        ]);
        // ClassDefinition is final and cannot be doubled - a real instance seeded through
        // addFieldDefinition() behaves exactly like a loaded definition for this lookup.
        $classDefinition = new ClassDefinition();
        $classDefinition->addFieldDefinition('localizedfields', $localizedFields);
        $element = $this->makeEmpty(Concrete::class, [
            'getClass' => $classDefinition,
        ]);

        $column = new Column(key: 'name', locale: null, type: 'dataobject.input', group: null, config: []);

        $deniedResolver = new class implements ColumnResolverInterface, CoreElementColumnResolverInterface {
            public bool $wasCalled = false;

            public function getType(): string
            {
                return 'dataobject.input';
            }

            public function supportedElementTypes(): array
            {
                return [ElementTypes::TYPE_OBJECT];
            }

            public function resolveForCoreElement(Column $column, ElementInterface $element): ColumnData
            {
                $this->wasCalled = true;

                return new ColumnData(key: 'name', locale: $column->getLocale(), value: 'LEAKED', fieldType: 'input');
            }
        };

        $service = $this->createService(
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => $element,
            ]),
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getLanguagePermissions' => ['fr' => 1],
            ]),
            securityService: $this->makeEmpty(SecurityServiceInterface::class),
            columnResolverLoader: $this->makeEmpty(ColumnResolverLoaderInterface::class, [
                'loadColumnResolvers' => ['dataobject.input' => $deniedResolver],
            ]),
            toolResolver: $this->makeEmpty(ToolResolverInterface::class, [
                'isValidLanguage' => true,
            ]),
            localeService: $this->makeEmpty(LocaleServiceInterface::class, [
                // The implicit read locale "de" is not among the user's viewable languages.
                'getLocale' => 'de',
            ]),
        );

        $data = $service->getGridDataForElement(
            new ColumnCollection([$column]),
            null,
            ElementTypes::TYPE_OBJECT,
            1,
            false,
            $user,
        );

        self::assertNull($data['columns'][0]->getValue());
        self::assertFalse(
            $deniedResolver->wasCalled,
            'the resolver must not be invoked when the implicit locale of a localized field is denied'
        );
    }

    /**
     * A caller holding the element (built from another state than the database) passes it in:
     * the columns are resolved from that element and nothing is loaded by id.
     */
    public function testGetGridDataForElementResolvesProvidedCoreElementWithoutLoadingById(): void
    {
        $service = $this->createService(
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => Expected::never(),
            ]),
            columnResolverLoader: $this->createCoreKeyColumnResolverLoader(),
        );

        $data = $service->getGridDataForElement(
            $this->createCoreKeyColumnCollection(),
            null,
            ElementTypes::TYPE_OBJECT,
            7,
            false,
            null,
            $this->makeEmpty(AbstractObject::class, ['getKey' => 'provided-key']),
        );

        self::assertSame(7, $data['id']);
        self::assertSame('provided-key', $data['columns'][0]->getValue());
    }

    /**
     * The view permission is checked on the provided element, also for element types whose
     * columns are resolved from the index entry.
     *
     * @dataProvider elementTypeProvider
     */
    public function testGetGridDataForElementChecksViewPermissionOnProvidedCoreElement(string $elementType): void
    {
        $user = $this->buildUser(isAdmin: false);
        $provided = $this->makeEmpty(AbstractObject::class);

        $service = $this->createService(
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'hasElementPermission' => Expected::once(
                    static function (ElementInterface $element, $checkedUser, string $permission) use ($provided, $user): void {
                        self::assertSame($provided, $element);
                        self::assertSame($user, $checkedUser);
                        self::assertSame(ElementPermissions::VIEW_PERMISSION, $permission);

                        throw new ForbiddenException();
                    }
                ),
            ]),
            columnResolverLoader: $this->createCoreKeyColumnResolverLoader(),
        );

        $this->expectException(ForbiddenException::class);

        $service->getGridDataForElement(
            $this->createCoreKeyColumnCollection(),
            null,
            $elementType,
            7,
            false,
            $user,
            $provided,
        );
    }

    public static function elementTypeProvider(): array
    {
        return [
            'data object' => [ElementTypes::TYPE_OBJECT],
            'asset' => [ElementTypes::TYPE_ASSET],
        ];
    }

    /**
     * The workflow place permissions are merged from the provided element, not from the
     * element stored under the same id.
     */
    public function testWorkflowMergeUsesProvidedCoreElement(): void
    {
        $provided = $this->makeEmpty(AbstractObject::class);
        $mergedPermissions = new Permissions(delete: false);

        $service = $this->createService(
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => Expected::never(),
            ]),
            workflowPermissionMerger: $this->makeEmpty(WorkflowPermissionMergerInterface::class, [
                'mergeWorkflowPermissions' => Expected::once(
                    static function (Permissions $permissions, ElementInterface $element) use ($provided, $mergedPermissions): Permissions {
                        self::assertSame($provided, $element);

                        return $mergedPermissions;
                    }
                ),
            ]),
            columnResolverLoader: $this->createColumnResolverLoader(),
        );

        $data = $service->getGridDataForElement(
            $this->createTestColumnCollection(),
            $this->createStudioElement(new Permissions(), true),
            ElementTypes::TYPE_ASSET,
            1,
            false,
            null,
            $provided,
        );

        self::assertSame($mergedPermissions, $data['permissions']);
    }

    /**
     * Language view permissions apply to a provided element as to a loaded one.
     *
     * @see https://pimcore.atlassian.net/browse/PEES-1063
     */
    public function testGetGridDataForElementRedactsDeniedLocaleOfProvidedCoreElement(): void
    {
        $resolver = $this->createCoreKeyColumnResolver();

        $service = $this->createService(
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getLanguagePermissions' => ['en' => 1],
            ]),
            columnResolverLoader: $this->makeEmpty(ColumnResolverLoaderInterface::class, [
                'loadColumnResolvers' => ['test.key' => $resolver],
            ]),
        );

        $data = $service->getGridDataForElement(
            new ColumnCollection([new Column(key: 'key', locale: 'de', type: 'test.key', group: null, config: [])]),
            null,
            ElementTypes::TYPE_OBJECT,
            7,
            false,
            $this->buildUser(isAdmin: false),
            $this->makeEmpty(Concrete::class, ['getKey' => 'LEAKED']),
        );

        self::assertNull($data['columns'][0]->getValue());
        self::assertFalse($resolver->wasCalled, 'the resolver must not be invoked for a denied locale');
    }

    /**
     * A provided page keeps its order and the caller's total, takes the row id and lock state
     * from each entry and the values from each element, checks each against the current user
     * and dispatches the column data event per cell.
     */
    public function testGetGridForProvidedElementsResolvesPageInOrder(): void
    {
        $user = $this->buildUser(isAdmin: false);

        $service = $this->createService(
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => Expected::never(),
            ]),
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => Expected::once($user),
                'hasElementPermission' => Expected::exactly(2),
            ]),
            columnResolverLoader: $this->createCoreKeyColumnResolverLoader(),
            eventDispatcher: $this->makeEmpty(EventDispatcherInterface::class, [
                'dispatch' => Expected::exactly(2, static fn (object $event): object => $event),
            ]),
        );

        $result = $service->getGridForProvidedElements(
            $this->createCoreKeyGridParameter(),
            [
                $this->createProvidedElement(12, 'second-in-index', true),
                $this->createProvidedElement(3, 'first-in-index', false),
            ],
            240,
            ElementTypes::TYPE_OBJECT,
        );

        self::assertSame(240, $result->getTotalItems());
        $items = $result->getItems();
        self::assertCount(2, $items);
        self::assertSame(12, $items[0]['id']);
        self::assertSame('second-in-index', $items[0]['columns'][0]->getValue());
        self::assertTrue($items[0]['isLocked']);
        self::assertSame(3, $items[1]['id']);
        self::assertSame('first-in-index', $items[1]['columns'][0]->getValue());
        self::assertFalse($items[1]['isLocked']);
    }

    /**
     * An element the current user may not view is left out of the page, as the search index
     * leaves it out of Studio's own grid; the other rows and the caller's total stay.
     */
    public function testGetGridForProvidedElementsLeavesOutElementsTheUserMayNotView(): void
    {
        $hidden = $this->createProvidedElement(5, 'hidden', false);

        $service = $this->createService(
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->buildUser(isAdmin: false),
                'hasElementPermission' => static function (ElementInterface $element) use ($hidden): void {
                    if ($element === $hidden->getElement()) {
                        throw new ForbiddenException();
                    }
                },
            ]),
            columnResolverLoader: $this->createCoreKeyColumnResolverLoader(),
        );

        $result = $service->getGridForProvidedElements(
            $this->createCoreKeyGridParameter(),
            [$hidden, $this->createProvidedElement(6, 'visible', false)],
            2,
            ElementTypes::TYPE_OBJECT,
        );

        self::assertSame(2, $result->getTotalItems());
        self::assertCount(1, $result->getItems());
        self::assertSame(6, $result->getItems()[0]['id']);
    }

    /**
     * Fallback languages follow the grid parameter while the page resolves and are restored
     * afterwards, as in Studio's data object grid.
     */
    public function testGetGridForProvidedElementsAppliesAndRestoresFallbackLanguages(): void
    {
        $calls = [];

        $service = $this->createService(
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->buildUser(isAdmin: true),
            ]),
            localizedFieldResolver: $this->makeEmpty(LocalizedFieldResolverInterface::class, [
                'getGetFallbackValues' => false,
                'setGetFallbackValues' => static function (bool $value) use (&$calls): void {
                    $calls[] = $value;
                },
            ]),
            columnResolverLoader: $this->createCoreKeyColumnResolverLoader(),
        );

        $service->getGridForProvidedElements(
            new GridParameter(folderId: 1, columns: [], filters: null, applyFallbackLanguages: true),
            [$this->createProvidedElement(1, 'key', false)],
            1,
            ElementTypes::TYPE_OBJECT,
        );

        self::assertSame([true, false], $calls);
    }

    public function testGetGridForProvidedElementsReturnsEmptyPageWithCallersTotal(): void
    {
        $service = $this->createService(
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->buildUser(isAdmin: true),
            ]),
        );

        $result = $service->getGridForProvidedElements(
            $this->createCoreKeyGridParameter(),
            [],
            40,
            ElementTypes::TYPE_OBJECT,
        );

        self::assertSame(40, $result->getTotalItems());
        self::assertSame([], $result->getItems());
    }

    /**
     * The Studio UI can send `config: false` (or omit it) for a column. That must not fatal with a
     * TypeError in the Column constructor.
     *
     * @dataProvider invalidColumnConfigProvider
     */
    public function testGetConfigurationForExportNormalizesInvalidColumnConfig(array $columnOverride): void
    {
        $definition = $this->createExportableColumnConfiguration();

        $result = $this->createService()->getConfigurationForExport(
            [['key' => 'cs', 'type' => 'dataobject.classificationstore', ...$columnOverride]],
            [$definition]
        );

        $columns = $result->getColumns();
        $this->assertCount(1, $columns);
        $this->assertSame([], $columns[0]->getConfig());
    }

    public static function invalidColumnConfigProvider(): array
    {
        return [
            'false' => [['config' => false]],
            'null' => [['config' => null]],
            'missing' => [[]],
        ];
    }

    /**
     * @dataProvider malformedColumnProvider
     */
    public function testGetConfigurationForExportThrowsInvalidArgumentForMalformedColumn(array $column): void
    {
        $definition = $this->createExportableColumnConfiguration();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid column configuration');

        $this->createService()->getConfigurationForExport(
            [['key' => 'cs', 'type' => 'dataobject.classificationstore', ...$column]],
            [$definition]
        );
    }

    public static function malformedColumnProvider(): array
    {
        return [
            'non numeric width' => [['width' => 'abc']],
            'non array group' => [['group' => 'data_object']],
        ];
    }

    /**
     * User is final and cannot be doubled - a real instance with just the admin flag set is
     * enough for the permission checks under test here.
     */
    private function buildUser(bool $isAdmin): User
    {
        $user = new User();
        $user->setAdmin($isAdmin);

        return $user;
    }

    private function createStudioElement(Permissions $permissions, bool $hasWorkflowWithPermissions): Element
    {
        return new class($permissions, $hasWorkflowWithPermissions) extends Element implements WorkflowPermissionsAwareInterface {
            public function __construct(
                private readonly Permissions $studioPermissions,
                private readonly bool $workflowFlag,
            ) {
                parent::__construct(
                    1,
                    1,
                    '/test',
                    new ElementIcon('name', 'pimcore_icon'),
                    1,
                    null,
                    null,
                    false,
                    null,
                    null,
                    ElementTypes::TYPE_ASSET
                );
            }

            public function getPermissions(): Permissions
            {
                return $this->studioPermissions;
            }

            public function getHasWorkflowWithPermissions(): bool
            {
                return $this->workflowFlag;
            }
        };
    }

    private function createExportableColumnConfiguration(): ColumnConfiguration
    {
        return new ColumnConfiguration(
            'cs',
            ['data_object'],
            false,
            false,
            true,
            false,
            false,
            null,
            'dataobject.classificationstore',
            'classificationstore',
            [],
        );
    }

    private function createTestColumnCollection(): ColumnCollection
    {
        return new ColumnCollection([
            new Column(
                key: 'id',
                locale: null,
                type: 'test.type',
                group: null,
                config: []
            ),
        ]);
    }

    private function createColumnResolverLoader(): ColumnResolverLoaderInterface
    {
        $resolver = new class() implements ColumnResolverInterface, StudioElementColumnResolverInterface {
            public function getType(): string
            {
                return 'test.type';
            }

            public function supportedElementTypes(): array
            {
                return [ElementTypes::TYPE_ASSET];
            }

            public function resolveForStudioElement(Column $column, StudioElementInterface $element): ColumnData
            {
                return new ColumnData($column->getKey(), $column->getLocale(), null, 'input');
            }
        };

        return $this->makeEmpty(ColumnResolverLoaderInterface::class, [
            'loadColumnResolvers' => ['test.type' => $resolver],
        ]);
    }

    /**
     * A core-element resolver for data objects and assets that reads the element's key, so a
     * test can tell which element a value came from.
     */
    private function createCoreKeyColumnResolver(): ColumnResolverInterface
    {
        return new class implements ColumnResolverInterface, CoreElementColumnResolverInterface {
            public bool $wasCalled = false;

            public function getType(): string
            {
                return 'test.key';
            }

            public function supportedElementTypes(): array
            {
                return [ElementTypes::TYPE_OBJECT, ElementTypes::TYPE_ASSET];
            }

            public function resolveForCoreElement(Column $column, ElementInterface $element): ColumnData
            {
                $this->wasCalled = true;

                return new ColumnData($column->getKey(), $column->getLocale(), $element->getKey(), 'input');
            }
        };
    }

    private function createCoreKeyColumnResolverLoader(): ColumnResolverLoaderInterface
    {
        return $this->makeEmpty(ColumnResolverLoaderInterface::class, [
            'loadColumnResolvers' => ['test.key' => $this->createCoreKeyColumnResolver()],
        ]);
    }

    private function createCoreKeyColumnCollection(): ColumnCollection
    {
        return new ColumnCollection([
            new Column(key: 'key', locale: null, type: 'test.key', group: null, config: []),
        ]);
    }

    private function createCoreKeyGridParameter(): GridParameter
    {
        return new GridParameter(
            folderId: 1,
            columns: [['key' => 'key', 'locale' => null, 'type' => 'test.key']],
            filters: null
        );
    }

    private function createProvidedElement(int $id, string $key, bool $isLocked): ProvidedGridElement
    {
        return new ProvidedGridElement(
            $this->makeEmpty(StudioElementInterface::class, [
                'getId' => $id,
                'getIsLocked' => $isLocked,
                'getPermissions' => new Permissions(),
            ]),
            $this->makeEmpty(AbstractObject::class, ['getKey' => $key]),
        );
    }

    private function createService(
        ?GridSearchInterface $gridSearch = null,
        ?ServiceResolverInterface $serviceResolver = null,
        ?LoggerInterface $logger = null,
        ?WorkflowPermissionMergerInterface $workflowPermissionMerger = null,
        ?ColumnResolverLoaderInterface $columnResolverLoader = null,
        ?DataObjectServiceResolverInterface $dataObjectServiceResolver = null,
        ?SecurityServiceInterface $securityService = null,
        ?LocalizedFieldResolverInterface $localizedFieldResolver = null,
        ?ToolResolverInterface $toolResolver = null,
        ?LocaleServiceInterface $localeService = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): GridService {
        return new GridService(
            $this->makeEmpty(ColumnDefinitionLoaderInterface::class),
            $columnResolverLoader ?? $this->makeEmpty(ColumnResolverLoaderInterface::class),
            $this->makeEmpty(ColumnCollectorLoaderInterface::class),
            $gridSearch ?? $this->makeEmpty(GridSearchInterface::class),
            $eventDispatcher ?? $this->makeEmpty(EventDispatcherInterface::class, [
                'dispatch' => static fn (object $event): object => $event,
            ]),
            $securityService ?? $this->makeEmpty(SecurityServiceInterface::class),
            $serviceResolver ?? $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(ClassDefinitionResolverInterface::class),
            $localizedFieldResolver ?? $this->makeEmpty(LocalizedFieldResolverInterface::class),
            $workflowPermissionMerger ?? $this->makeEmpty(WorkflowPermissionMergerInterface::class),
            $logger ?? $this->makeEmpty(LoggerInterface::class),
            $dataObjectServiceResolver ?? $this->makeEmpty(DataObjectServiceResolverInterface::class),
            $toolResolver ?? $this->makeEmpty(ToolResolverInterface::class),
            $localeService ?? $this->makeEmpty(LocaleServiceInterface::class),
        );
    }
}
