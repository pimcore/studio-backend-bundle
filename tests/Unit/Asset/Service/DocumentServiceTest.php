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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\ConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\DocumentResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\DocumentService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Document\AdapterInterface;
use Pimcore\Model\Asset\Document;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
final class DocumentServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testPreviewOfDocumentWithoutPageCountIsConvertedByAdapter(): void
    {
        $stream = fopen('php://memory', 'rb');
        $adapter = $this->makeEmpty(AdapterInterface::class, [
            'getPdf' => Expected::once($stream),
        ]);

        $response = $this->getDocumentService($adapter)->getPreviewStream($this->getDocxWithoutPageCount());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(MimeTypes::PDF->value, $response->headers->get('Content-Type'));
    }

    /**
     * @throws Exception
     */
    public function testPreviewThrowsNotFoundWhenConversionFails(): void
    {
        $adapter = $this->makeEmpty(AdapterInterface::class, [
            'getPdf' => Expected::once(static function (): never {
                throw new Exception('Conversion failed');
            }),
        ]);

        $this->expectException(ElementStreamResourceNotFoundException::class);

        $this->getDocumentService($adapter)->getPreviewStream($this->getDocxWithoutPageCount());
    }

    /**
     * @throws Exception
     */
    public function testPreviewThrowsEnvironmentExceptionForUnsupportedFileType(): void
    {
        $adapter = $this->makeEmpty(AdapterInterface::class, [
            'getPdf' => Expected::never(),
        ]);

        $this->expectException(EnvironmentException::class);

        $this->getDocumentService($adapter, false)->getPreviewStream($this->getDocxWithoutPageCount());
    }

    /**
     * @throws Exception
     */
    private function getDocxWithoutPageCount(): Document
    {
        return $this->makeEmpty(Document::class, [
            'getId' => 1,
            'getType' => 'document',
            'getFilename' => 'test.docx',
            'getMimeType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'getPageCount' => null,
            'getFileSize' => '1 KB',
        ]);
    }

    /**
     * @throws Exception
     */
    private function getDocumentService(AdapterInterface $adapter, bool $isFileTypeSupported = true): DocumentService
    {
        return new DocumentService(
            $this->makeEmpty(ConfigResolverInterface::class),
            $this->makeEmpty(DocumentResolverInterface::class, [
                'getDefaultAdapter' => $adapter,
                'isFileTypeSupported' => $isFileTypeSupported,
            ]),
            $this->makeEmpty(MessageBusInterface::class)
        );
    }
}
