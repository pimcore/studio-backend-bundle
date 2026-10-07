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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Service\Translator;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\CacheResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilter;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\LanguageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Hydrator\TranslationsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Repository\TranslationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorService;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PublicTranslations;
use Pimcore\Config;
use Pimcore\Model\Translation\Listing;
use Pimcore\Model\UserInterface;
use Pimcore\Translation\Translator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;

final class TranslatorServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetAllTranslations(): void
    {
        $translatorService = $this->mockTranslatorService();
        $locale = 'en';

        $translations = $translatorService->getAllTranslationsByLocale($locale, true);

        $this->assertEquals($locale, $translations->getLocale());
        $this->assertEmpty($translations->getKeys());
    }

    public function testGetAllTranslationsNotLoggedIn(): void
    {
        $translatorService = $this->mockTranslatorService(false);
        $locale = 'en';

        $translations = $translatorService->getAllTranslationsByLocale($locale, true);

        $this->assertEquals($locale, $translations->getLocale());
        $this->assertCount(count(PublicTranslations::PUBLIC_KEYS), $translations->getKeys());
    }

    /**
     * @throws Exception
     */
    public function testGetTranslationsForKeys(): void
    {
        $translatorService = $this->mockTranslatorService();
        $locale = 'fr';
        $keys = PublicTranslations::PUBLIC_KEYS;

        $translations = $translatorService->getTranslationsForKeys($locale, $keys);

        $this->assertEquals($locale, $translations->getLocale());
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $translations->getKeys());
        }
    }

    /**
     * @throws Exception
     */
    public function testGetTranslationListUsesDomainSpecificLanguages(): void
    {
        $user = $this->makeEmpty(UserInterface::class);
        $websiteLanguages = ['en', 'fr', 'fr_BE', 'nl_BE'];

        $languageService = $this->createMock(LanguageServiceInterface::class);
        $languageService->expects($this->once())
            ->method('getTranslationAllowedLanguages')
            ->with($user, 'messages')
            ->willReturn($websiteLanguages);

        $listing = $this->createMock(Listing::class);
        $listing->expects($this->once())
            ->method('setLanguages')
            ->with($websiteLanguages);
        $listing->expects($this->once())
            ->method('addConditionParam')
            ->with('1 = 1');

        $repository = $this->createMock(TranslationRepositoryInterface::class);
        $repository->method('getTranslationList')->willReturn($listing);
        $repository->expects($this->once())
            ->method('joinLanguageColumns')
            ->with($listing, ['fr_BE'], 'messages')
            ->willReturn($listing);

        $listingFilter = $this->makeEmpty(ListingFilterInterface::class, [
            'applyFilters' => $listing,
        ]);

        $filterParameter = new FilterParameter(sortFilter: new SortFilter('fr_BE', 'DESC'));

        $translatorService = $this->mockTranslatorService(
            repository: $repository,
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'isLoggedIn' => true,
                'getCurrentUser' => $user,
            ]),
            languageService: $languageService,
            listingFilter: $listingFilter,
            filterMapper: $this->makeEmpty(FilterMapperServiceInterface::class, [
                'getFilterParameters' => $filterParameter,
            ]),
        );

        $parameter = new CollectionFilterParameter($filterParameter);

        $this->assertSame($listing, $translatorService->getTranslationList('messages', $parameter));
    }

    /**
     * @throws Exception
     */
    public function testGetTranslationListJoinsLanguagesOfAdditionalSortFilters(): void
    {
        $listing = $this->createMock(Listing::class);

        $repository = $this->createMock(TranslationRepositoryInterface::class);
        $repository->method('getTranslationList')->willReturn($listing);
        $repository->expects($this->once())
            ->method('joinLanguageColumns')
            ->with($listing, ['fr_BE', 'nl_BE'], 'messages')
            ->willReturn($listing);

        $filterParameter = new FilterParameter(
            sortFilter: new SortFilter('key', 'ASC'),
            additionalSortFilters: [new SortFilter('fr_BE', 'DESC'), new SortFilter('nl_BE', 'ASC')]
        );

        $translatorService = $this->mockTranslatorService(
            repository: $repository,
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class),
            ]),
            languageService: $this->makeEmpty(LanguageServiceInterface::class, [
                'getTranslationAllowedLanguages' => ['en', 'fr_BE', 'nl_BE'],
            ]),
            listingFilter: $this->makeEmpty(ListingFilterInterface::class, [
                'applyFilters' => $listing,
            ]),
            filterMapper: $this->makeEmpty(FilterMapperServiceInterface::class, [
                'getFilterParameters' => $filterParameter,
            ]),
        );

        $translatorService->getTranslationList('messages', new CollectionFilterParameter($filterParameter));
    }

    /**
     * @throws Exception
     */
    public function testGetTranslationListWithoutFiltersUsesDomainSpecificLanguages(): void
    {
        $user = $this->makeEmpty(UserInterface::class);
        $websiteLanguages = ['fr_BE'];

        $listing = $this->createMock(Listing::class);
        $listing->expects($this->once())
            ->method('setLanguages')
            ->with($websiteLanguages);
        // The core listing cache is not scoped by languages, so it must be bypassed
        $listing->expects($this->once())
            ->method('addConditionParam')
            ->with('1 = 1');

        $translatorService = $this->mockTranslatorService(
            repository: $this->makeEmpty(TranslationRepositoryInterface::class, [
                'getTranslationList' => $listing,
            ]),
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $user,
            ]),
            languageService: $this->makeEmpty(LanguageServiceInterface::class, [
                'getTranslationAllowedLanguages' => $websiteLanguages,
            ]),
        );

        $this->assertSame(
            $listing,
            $translatorService->getTranslationList('messages', new CollectionFilterParameter())
        );
    }

    /**
     * @throws Exception
     */
    private function mockTranslatorService(
        bool $loggedIn = true,
        ?TranslationRepositoryInterface $repository = null,
        ?SecurityServiceInterface $securityService = null,
        ?LanguageServiceInterface $languageService = null,
        ?ListingFilterInterface $listingFilter = null,
        ?FilterMapperServiceInterface $filterMapper = null,
    ): TranslatorServiceInterface {
        $translator = $this->makeEmpty(Translator::class);
        $repository ??= $this->makeEmpty(TranslationRepositoryInterface::class);
        $securityService ??= $this->makeEmpty(SecurityServiceInterface::class, [
            'isLoggedIn' => $loggedIn,
        ]);
        $languageService ??= $this->makeEmpty(LanguageServiceInterface::class);
        $listingFilter ??= $this->makeEmpty(ListingFilterInterface::class);
        $filterMapper ??= $this->makeEmpty(FilterMapperServiceInterface::class);
        $translationsHydrator = $this->makeEmpty(TranslationsHydratorInterface::class);
        $eventDispatcher = $this->makeEmpty(EventDispatcherInterface::class);
        $cacheResolver = $this->makeEmpty(CacheResolverInterface::class);
        $toolResolver = $this->makeEmpty(ToolResolverInterface::class);

        return new TranslatorService(
            new Config(),
            $translator,
            $repository,
            $securityService,
            $languageService,
            $listingFilter,
            $filterMapper,
            $translationsHydrator,
            $eventDispatcher,
            $cacheResolver,
            $toolResolver
        );
    }
}
