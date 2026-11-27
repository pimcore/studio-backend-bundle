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
use Error;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocTypeResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentAddParameters;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\CreateService;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocumentTypeServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\Document;
use Pimcore\Model\Document\DocType;
use Pimcore\Model\Document\Email;
use Pimcore\Model\Document\Link;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\UserInterface;
use Pimcore\Resolver\ResolverInterface;
use ReflectionClass;

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
     * @throws Exception
     */
    public function testUnviewableTranslationSourceIsRejectedWithoutSideEffects(): void
    {
        $this->assertRejectedWithoutSideEffects(
            new DocumentAddParameters(key: 'tr', type: 'page', translationsSourceId: 4, language: 'de')
        );
    }

    /**
     * @throws Exception
     */
    public function testUnviewableInheritanceSourceIsRejectedWithoutSideEffects(): void
    {
        $this->assertRejectedWithoutSideEffects(
            new DocumentAddParameters(key: 'inh', type: 'page', inheritanceSourceId: 4)
        );
    }

    /**
     * @throws Exception
     */
    public function testMissingTranslationSourceIsNotFound(): void
    {
        $created = false;
        $service = $this->buildService(
            $this->makeEmpty(DocumentResolverInterface::class, [
                'getById' => null,
                'createByClassName' => function () use (&$created): Document {
                    $created = true;

                    return $this->makeEmpty(Page::class, ['getId' => 9]);
                },
            ]),
            null,
            []
        );

        try {
            $service->createDocument(
                $this->makeEmpty(Page::class, ['getId' => self::PARENT_ID]),
                new DocumentAddParameters(key: 'tr', type: 'page', translationsSourceId: 404, language: 'de'),
                $this->makeEmpty(UserInterface::class, ['getId' => 1])
            );
            $this->fail('Expected NotFoundException');
        } catch (NotFoundException) {
            $this->assertFalse($created);
        }
    }

    /**
     * @throws Exception
     */
    public function testInheritanceSourceIsCheckedForViewPermission(): void
    {
        $source = $this->makeEmpty(Page::class, ['getId' => 4]);
        $checked = [];
        $user = $this->makeEmpty(UserInterface::class, ['getId' => 1]);

        $service = $this->buildService(
            $this->makeEmpty(DocumentResolverInterface::class, [
                'getById' => $source,
                'createByClassName' => $this->makeEmpty(Page::class, ['getId' => 9]),
            ]),
            null,
            [],
            $this->makeEmpty(SecurityServiceInterface::class, [
                'hasElementPermission' => static function ($element, $u, string $permission) use (&$checked): void {
                    $checked[] = [$element, $u, $permission];
                },
            ])
        );

        $service->createDocument(
            $this->makeEmpty(Page::class, ['getId' => self::PARENT_ID]),
            new DocumentAddParameters(key: 'inh', type: 'page', inheritanceSourceId: 4),
            $user
        );

        $this->assertNotSame([], $checked);
        foreach ($checked as [$element, $checkedUser, $permission]) {
            $this->assertSame($source, $element);
            $this->assertSame($user, $checkedUser);
            $this->assertSame(ElementPermissions::VIEW_PERMISSION, $permission);
        }
    }

    /**
     * @throws Exception
     */
    public function testViewableInheritanceSourceIsAssignedAsMainDocument(): void
    {
        $assigned = null;
        $created = $this->makeEmpty(Page::class, [
            'getId' => 9,
            'setContentMainDocumentId' => function (int $id) use (&$assigned, &$created) {
                $assigned = $id;

                return $created;
            },
        ]);

        $service = $this->buildService(
            $this->makeEmpty(DocumentResolverInterface::class, [
                'getById' => $this->makeEmpty(Page::class, ['getId' => 4]),
                'createByClassName' => $created,
            ]),
            null,
            []
        );

        $id = $service->createDocument(
            $this->makeEmpty(Page::class, ['getId' => self::PARENT_ID]),
            new DocumentAddParameters(key: 'inh', type: 'page', inheritanceSourceId: 4),
            $this->makeEmpty(UserInterface::class, ['getId' => 1])
        );

        $this->assertSame(9, $id);
        $this->assertSame(4, $assigned);
    }

    /**
     * @throws Exception
     */
    public function testViewableTranslationSourceDataIsUsed(): void
    {
        $captured = null;
        $properties = null;
        $checked = [];
        $user = $this->makeEmpty(UserInterface::class, ['getId' => 1]);
        $source = $this->makeEmpty(Page::class, [
            'getId' => 4,
            'getTemplate' => 'source/template.html.twig',
            'getController' => 'App\\Controller\\SourceController::indexAction',
            'getStaticGeneratorEnabled' => false,
            'getProperties' => ['seo' => 'source-value'],
        ]);
        $created = $this->makeEmpty(Page::class, [
            'getId' => 9,
            'getProperties' => [],
            'setProperties' => static function (array $value) use (&$properties): void {
                $properties = $value;
            },
        ]);

        $service = $this->buildService(
            $this->makeEmpty(DocumentResolverInterface::class, [
                'getById' => $source,
                'createByClassName' => function (string $className, int $parentId, array $data) use (
                    &$captured,
                    $created
                ): Document {
                    $captured = $data;

                    return $created;
                },
            ]),
            null,
            [],
            $this->makeEmpty(SecurityServiceInterface::class, [
                'hasElementPermission' => static function ($element, $u, string $permission) use (&$checked): void {
                    $checked[] = [$element, $u, $permission];
                },
            ])
        );

        try {
            $service->createDocument(
                $this->makeEmpty(Page::class, ['getId' => self::PARENT_ID]),
                new DocumentAddParameters(key: 'tr', type: 'page', translationsSourceId: 4, language: 'de'),
                $user
            );
        } catch (Error) {
            // writing the translation link needs a booted kernel; everything before it has run
        }

        $this->assertSame([[$source, $user, ElementPermissions::VIEW_PERMISSION]], $checked);
        $this->assertIsArray($captured);
        $this->assertSame('source/template.html.twig', $captured['template']);
        $this->assertSame('App\\Controller\\SourceController::indexAction', $captured['controller']);
        $this->assertSame(['seo' => 'source-value'], $properties);
    }

    /**
     * @throws Exception
     */
    public function testRefusedFirstSaveKeepsItsCause(): void
    {
        $cause = new ValidationException('Title is missing');
        $service = $this->buildService(
            $this->makeEmpty(DocumentResolverInterface::class, [
                'createByClassName' => $this->makeEmpty(Page::class, ['getId' => 9]),
            ]),
            null,
            [],
            onSave: static function () use ($cause): never {
                throw $cause;
            }
        );

        try {
            $service->createDocument(
                $this->makeEmpty(Page::class, ['getId' => self::PARENT_ID]),
                new DocumentAddParameters(key: 'about', type: 'page'),
                $this->makeEmpty(UserInterface::class, ['getId' => 1])
            );
            $this->fail('Expected ElementSavingFailedException');
        } catch (ElementSavingFailedException $exception) {
            $this->assertSame($cause, $exception->getPrevious());
        }
    }

    /**
     * @throws Exception
     */
    private function assertRejectedWithoutSideEffects(DocumentAddParameters $parameters): void
    {
        $created = false;
        $saved = false;

        $service = $this->buildService(
            $this->makeEmpty(DocumentResolverInterface::class, [
                'getById' => $this->makeEmpty(Page::class, ['getId' => 4]),
                'createByClassName' => function () use (&$created): Document {
                    $created = true;

                    return $this->makeEmpty(Page::class, ['getId' => 9]);
                },
            ]),
            null,
            [],
            $this->makeEmpty(SecurityServiceInterface::class, [
                'hasElementPermission' => static function (): void {
                    throw new ForbiddenException();
                },
            ]),
            static function () use (&$saved): void {
                $saved = true;
            }
        );

        try {
            $service->createDocument(
                $this->makeEmpty(Page::class, ['getId' => self::PARENT_ID]),
                $parameters,
                $this->makeEmpty(UserInterface::class, ['getId' => 1])
            );
            $this->fail('Expected ForbiddenException');
        } catch (Error) {
            // the unguarded flow reaches the translation link write, which needs a booted kernel
            $this->fail('Expected ForbiddenException before the translation link is written');
        } catch (ForbiddenException) {
            $this->assertFalse($created);
            $this->assertFalse($saved);
        }
    }

    private function buildService(
        DocumentResolverInterface $documentResolver,
        ?DocType $docType,
        array $defaultControllers,
        ?SecurityServiceInterface $securityService = null,
        ?callable $onSave = null
    ): CreateService {
        $arguments = [
            'documentResolver' => $documentResolver,
            'docTypeResolver' => $this->makeEmpty(DocTypeResolverInterface::class, ['getById' => $docType]),
            'documentTypeService' => $this->makeEmpty(DocumentTypeServiceInterface::class, ['tryTypeAdapter' => null]),
            'elementSaveService' => $this->makeEmpty(
                ElementSaveServiceInterface::class,
                $onSave === null ? [] : ['save' => $onSave]
            ),
            'classResolver' => $this->makeEmpty(ResolverInterface::class),
            'serviceResolver' => $this->makeEmpty(ServiceResolverInterface::class, [
                'getValidKey' => static fn (string $key): string => $key,
            ]),
            'defaultController' => self::DEFAULT_CONTROLLER,
            'defaultControllers' => $defaultControllers,
        ];

        $constructor = (new ReflectionClass(CreateService::class))->getConstructor();
        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->getName() === 'securityService') {
                $arguments['securityService'] = $securityService
                    ?? $this->makeEmpty(SecurityServiceInterface::class);
            }
        }

        return new CreateService(...$arguments);
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

        $service = $this->buildService($documentResolver, $docType, $defaultControllers);

        $parent = $this->makeEmpty(Page::class, ['getId' => self::PARENT_ID]);
        $user = $this->makeEmpty(UserInterface::class, ['getId' => 1]);

        $service->createDocument($parent, $parameters, $user);

        $this->assertIsArray($captured, 'The document resolver was not asked to create the document.');

        return $captured;
    }
}
