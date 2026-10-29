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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Document\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocumentServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\TranslationService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\Service as CoreDocumentService;
use Pimcore\Model\User;
use ReflectionProperty;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class TranslationServiceTest extends Unit
{
    /**
     * Regression test: the foreach over the translation links must not overwrite the language
     * of the requested document (pimcore/platform-version#598).
     *
     * @throws Exception
     */
    public function testGetDocumentTranslationsReturnsLanguageOfRequestedDocument(): void
    {
        $document = $this->makeEmpty(Page::class, [
            'getId' => 2079,
            'getProperty' => 'en',
        ]);

        $service = $this->getTranslationService($document, ['en' => 2079, 'de' => 1835]);

        $translations = $service->getDocumentTranslations(2079);

        $this->assertSame('en', $translations->getLanguage());

        $links = $translations->getTranslationLinks();
        $this->assertCount(2, $links);
        $this->assertSame('en', $links[0]->getLanguage());
        $this->assertSame(2079, $links[0]->getDocumentId());
        $this->assertSame('de', $links[1]->getLanguage());
        $this->assertSame(1835, $links[1]->getDocumentId());
    }

    /**
     * @param array<string, int> $links
     *
     * @throws Exception
     */
    private function getTranslationService(Document $document, array $links): TranslationService
    {
        $service = new TranslationService(
            $this->makeEmpty(DocumentServiceInterface::class, ['getDocumentElement' => $document]),
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => new User()]),
        );

        // getTranslations() is a magic method (via the Dao) on the core service, so it cannot be stubbed directly
        $coreService = new class($links) extends CoreDocumentService {
            /**
             * @param array<string, int> $links
             */
            public function __construct(private readonly array $links)
            {
            }

            public function getTranslations(Document $document, string $task = 'open'): array
            {
                return $this->links;
            }
        };

        (new ReflectionProperty(TranslationService::class, 'coreDocumentService'))->setValue($service, $coreService);

        return $service;
    }
}
