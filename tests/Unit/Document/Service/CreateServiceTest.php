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
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocTypeResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentAddParameters;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\CreateService;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocumentTypeServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Model\Document;
use Pimcore\Model\Document\DocType;
use Pimcore\Model\Document\Email;
use Pimcore\Model\Document\Link;
use Pimcore\Model\Document\Page;
use Pimcore\Model\UserInterface;
use Pimcore\Resolver\ResolverInterface;

/**
 * A new page, snippet or email document created without a predefined document type gets the
 * controller configured for its type, and only falls back to the single core default when no
 * controller is configured for that type.
 *
 * @internal
 */
final class CreateServiceTest extends Unit
{
    private const string DEFAULT_CONTROLLER = 'App\\Controller\\DefaultController::defaultAction';

    private const string EMAIL_CONTROLLER = 'App\\Controller\\MailController::defaultAction';

    private const string DOC_TYPE_CONTROLLER = 'App\\Controller\\LandingPageController::defaultAction';

    private const int PARENT_ID = 1;

    /**
     * @throws Exception
     */
    public function testEmailDocumentGetsControllerConfiguredForEmails(): void
    {
        $data = $this->createAndCaptureData(
            new DocumentAddParameters(key: 'newsletter', type: 'email'),
            $this->makeEmpty(Email::class, ['getId' => 7]),
            ['email' => self::EMAIL_CONTROLLER]
        );

        $this->assertSame(self::EMAIL_CONTROLLER, $data['controller']);
    }

    /**
     * @throws Exception
     */
    public function testTypeWithoutConfiguredControllerFallsBackToCoreDefault(): void
    {
        $data = $this->createAndCaptureData(
            new DocumentAddParameters(key: 'about', type: 'page', title: 'About'),
            $this->makeEmpty(Page::class, ['getId' => 8]),
            ['email' => self::EMAIL_CONTROLLER]
        );

        $this->assertSame(self::DEFAULT_CONTROLLER, $data['controller']);
    }

    /**
     * @throws Exception
     */
    public function testWithoutAnyPerTypeControllerEveryTypeUsesCoreDefault(): void
    {
        $data = $this->createAndCaptureData(
            new DocumentAddParameters(key: 'newsletter', type: 'email'),
            $this->makeEmpty(Email::class, ['getId' => 9]),
            []
        );

        $this->assertSame(self::DEFAULT_CONTROLLER, $data['controller']);
    }

    /**
     * @throws Exception
     */
    public function testPredefinedDocumentTypeControllerWinsOverPerTypeDefault(): void
    {
        $data = $this->createAndCaptureData(
            new DocumentAddParameters(key: 'newsletter', type: 'email', docTypeId: '3'),
            $this->makeEmpty(Email::class, ['getId' => 10]),
            ['email' => self::EMAIL_CONTROLLER],
            $this->makeEmpty(DocType::class, [
                'getController' => self::DOC_TYPE_CONTROLLER,
                'getTemplate' => 'mail/landing.html.twig',
                'getStaticGeneratorEnabled' => false,
            ])
        );

        $this->assertSame(self::DOC_TYPE_CONTROLLER, $data['controller']);
    }

    /**
     * @throws Exception
     */
    public function testLinkDocumentGetsNoController(): void
    {
        $data = $this->createAndCaptureData(
            new DocumentAddParameters(key: 'external', type: 'link'),
            $this->makeEmpty(Link::class, ['getId' => 11]),
            ['email' => self::EMAIL_CONTROLLER]
        );

        $this->assertArrayNotHasKey('controller', $data);
    }

    /**
     * Runs CreateService::createDocument() and returns the data array handed to the document
     * resolver, which is what the new document is built from.
     *
     * @throws Exception
     */
    private function createAndCaptureData(
        DocumentAddParameters $parameters,
        Document $document,
        array $defaultControllers,
        ?DocType $docType = null
    ): array {
        $captured = null;

        $documentResolver = $this->makeEmpty(DocumentResolverInterface::class, [
            'createByClassName' => function (string $className, int $parentId, array $data) use (
                &$captured,
                $document
            ): Document {
                $captured = $data;

                return $document;
            },
        ]);

        $service = new CreateService(
            documentResolver: $documentResolver,
            docTypeResolver: $this->makeEmpty(DocTypeResolverInterface::class, ['getById' => $docType]),
            documentTypeService: $this->makeEmpty(DocumentTypeServiceInterface::class, ['tryTypeAdapter' => null]),
            elementSaveService: $this->makeEmpty(ElementSaveServiceInterface::class),
            classResolver: $this->makeEmpty(ResolverInterface::class),
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getValidKey' => static fn (string $key): string => $key,
            ]),
            defaultController: self::DEFAULT_CONTROLLER,
            defaultControllers: $defaultControllers
        );

        $parent = $this->makeEmpty(Page::class, ['getId' => self::PARENT_ID]);
        $user = $this->makeEmpty(UserInterface::class, ['getId' => 1]);

        $service->createDocument($parent, $parameters, $user);

        $this->assertIsArray($captured, 'The document resolver was not asked to create the document.');

        return $captured;
    }
}
