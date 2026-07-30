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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DataObjectSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid\GridSearchInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnCollectorLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnDefinitionLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnResolverLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridService;
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
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
     * Columns without a locale (e.g. system columns) are never subject to language filtering.
     */
    public function testIsLocaleViewableForElementReturnsTrueWhenColumnHasNoLocale(): void
    {
        $user = $this->buildUser(isAdmin: false);
        $element = $this->makeEmpty(Concrete::class);

        $service = $this->createService(
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getLanguagePermissions' => Expected::never(),
            ]),
        );

        self::assertTrue($service->isLocaleViewableForElement($element, null, $user));
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
     * User is final and cannot be doubled - a real instance with just the admin flag set is
     * enough for the permission checks under test here.
     */
    private function buildUser(bool $isAdmin): User
    {
        $user = new User();
        $user->setAdmin($isAdmin);

        return $user;
    }

    private function createService(
        ?GridSearchInterface $gridSearch = null,
        ?ServiceResolverInterface $serviceResolver = null,
        ?LoggerInterface $logger = null,
        ?DataObjectServiceResolverInterface $dataObjectServiceResolver = null,
        ?SecurityServiceInterface $securityService = null,
    ): GridService {
        return new GridService(
            $this->makeEmpty(ColumnDefinitionLoaderInterface::class),
            $this->makeEmpty(ColumnResolverLoaderInterface::class),
            $this->makeEmpty(ColumnCollectorLoaderInterface::class),
            $gridSearch ?? $this->makeEmpty(GridSearchInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class),
            $securityService ?? $this->makeEmpty(SecurityServiceInterface::class),
            $serviceResolver ?? $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(ClassDefinitionResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
            $logger ?? $this->makeEmpty(LoggerInterface::class),
            $dataObjectServiceResolver ?? $this->makeEmpty(DataObjectServiceResolverInterface::class),
        );
    }
}
