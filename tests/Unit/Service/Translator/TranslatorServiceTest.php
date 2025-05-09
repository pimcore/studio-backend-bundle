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
use Pimcore\Bundle\StudioBackendBundle\Translation\Repository\TranslationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorService;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PublicTranslations;
use Pimcore\Translation\Translator;

final class TranslatorServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetAllTranslations(): void
    {
        $translatorService = $this->mockTranslatorService();
        $locale = 'en';

        $translations = $translatorService->getAllTranslations($locale, true);

        $this->assertEquals($locale, $translations->getLocale());
        $this->assertEmpty($translations->getKeys());
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
    private function mockTranslatorService(): TranslatorServiceInterface
    {
        $translator = $this->makeEmpty(Translator::class);
        $repository = $this->makeEmpty(TranslationRepositoryInterface::class);

        return new TranslatorService($translator, $repository);
    }
}
