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

use Exception;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StaticResolverBundle\Lib\CacheResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\AdminResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Hydrator\TranslationsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Repository\TranslationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorService;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PublicTranslations;
use Pimcore\Translation\Translator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;

/**
 * @covers \Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorService
 */
final class TranslatorServiceTest extends TestCase
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
    private function mockTranslatorService(bool $loggedIn = true): TranslatorServiceInterface
    {
        $translator = $this->createMock(Translator::class);
        $repository = $this->createMock(TranslationRepositoryInterface::class);
        
        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->method('isLoggedIn')->willReturn($loggedIn);
        
        $adminResolver = $this->createMock(AdminResolverInterface::class);
        $listingFilter = $this->createMock(ListingFilterInterface::class);
        $filterMapper = $this->createMock(FilterMapperServiceInterface::class);
        $translationsHydrator = $this->createMock(TranslationsHydratorInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $cacheResolver = $this->createMock(CacheResolverInterface::class);
        $toolResolver = $this->createMock(ToolResolverInterface::class);

        return new TranslatorService(
            $translator,
            $repository,
            $securityService,
            $adminResolver,
            $listingFilter,
            $filterMapper,
            $translationsHydrator,
            $eventDispatcher,
            $cacheResolver,
            $toolResolver
        );
    }
}
