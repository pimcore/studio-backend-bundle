<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Tests\Unit\Service\Translator;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioApiBundle\Service\TranslatorService;
use Pimcore\Bundle\StudioApiBundle\Service\TranslatorServiceInterface;
use Pimcore\Bundle\StudioApiBundle\Util\Constants\PublicTranslations;
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

        $translations = $translatorService->getAllTranslations($locale);

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
        return new TranslatorService($translator);
    }
}