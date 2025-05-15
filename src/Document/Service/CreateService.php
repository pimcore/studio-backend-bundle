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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocTypeResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentAddParameters;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\DocumentTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Document;
use Pimcore\Model\Document\DocType;
use Pimcore\Model\Document\Email;
use Pimcore\Model\Document\Hardlink;
use Pimcore\Model\Document\Link;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\Service;
use Pimcore\Model\Document\Snippet;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\UserInterface;
use Pimcore\Resolver\ResolverInterface;

/**
 * @internal
 */
final readonly class CreateService implements CreateServiceInterface
{
    private const array PAGE_SNIPPET_TYPES = [
        DocumentTypes::PAGE->value, DocumentTypes::SNIPPET->value, DocumentTypes::EMAIL->value
    ];

    public function __construct(
        private DocumentResolverInterface $documentResolver,
        private DocTypeResolverInterface $docTypeResolver,
        private ElementSaveServiceInterface $elementSaveService,
        private ResolverInterface $classResolver,
        private ServiceResolverInterface $serviceResolver,
        private string $defaultController
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function createDocument(Document $parent, DocumentAddParameters $parameters, UserInterface $user): int
    {
        $isTranslation = false;
        $baseTranslationDocument = $this->getBaseTranslationDocument($parameters);
        if ($baseTranslationDocument instanceof Document) {
            $isTranslation = true;
        }

        $data = $this->createData($user, $parameters, $baseTranslationDocument);
        $document = $this->createDocumentElement($data, $parent->getId(), $parameters);

        if ($isTranslation) {
            $this->addLanguageProperty($document, $baseTranslationDocument, $parameters->getLanguage());
        }

        try {
            $this->elementSaveService->save($document, $user);
        } catch (Exception $e) {
            throw new ElementSavingFailedException($document->getId(), $e->getMessage());
        }

        // New Document has to be saved already
        if ($isTranslation) {
            $this->addNewTranslation($baseTranslationDocument, $document);
        }

        return $document->getId();
    }

    private function createData(
        UserInterface $user,
        DocumentAddParameters $parameters,
        ?Document $baseTranslationDocument
    ): array
    {
        $data = $this->addBaseData($user, $parameters);
        $data = $this->addDocTypeData($data, $parameters, $baseTranslationDocument);

        return $this->addContentInheritanceData($data, $parameters);
    }

    private function addBaseData(UserInterface $user, DocumentAddParameters $parameters): array
    {
        return [
            'userOwner' => $user->getId(),
            'published' => false,
            'type' => $parameters->getType(),
            'key' => $this->serviceResolver->getValidKey($parameters->getKey(), ElementTypes::TYPE_DOCUMENT),
        ];
    }

    private function addDocTypeData(
        array $data,
        DocumentAddParameters $parameters,
        ?Document $baseTranslationDocument
    ): array {
        $docTypeId = $parameters->getDocTypeId();
        if ($docTypeId !== null) {
            return $this->applyDocTypeData($data, $docTypeId);
        }

        if ($baseTranslationDocument instanceof PageSnippet) {
            return $this->applyBaseDocumentData($data, $baseTranslationDocument);
        }

        if ($this->requiresDefaultController($parameters)) {
            $data['controller'] = $this->defaultController;
        }

        return $data;
    }

    private function applyDocTypeData(array $data, string $docTypeId): array
    {
        $docType = $this->docTypeResolver->getById($docTypeId);
        if (!$docType) {
            return $data;
        }

        return $this->applyBaseDocumentData($data, $docType);
    }

    private function applyBaseDocumentData(array $data, PageSnippet|DocType $document): array
    {
        $data['template'] = $document->getTemplate();
        $data['controller'] = $document->getController();
        $data['staticGeneratorEnabled'] = $document->getStaticGeneratorEnabled();

        return $data;
    }

    private function requiresDefaultController(DocumentAddParameters $parameters): bool
    {
        return in_array($parameters->getType(), self::PAGE_SNIPPET_TYPES, true);
    }

    private function addContentInheritanceData(array $data, DocumentAddParameters $parameters): array
    {
        if (
            $parameters->getInheritanceSourceId() !== null &&
            in_array($parameters->getType(), self::PAGE_SNIPPET_TYPES, true)
        ) {
            $data['contentMainDocumentId'] = $parameters->getInheritanceSourceId();
        }

        return $data;
    }

    private function getBaseTranslationDocument(DocumentAddParameters $parameters): ?Document
    {
        $id = $parameters->getTranslationsSourceId();
        if ($id === null) {
            return null;
        }

        return $this->documentResolver->getById($id);
    }

    private function addLanguageProperty(
        Document $document,
        Document $baseTranslationDocument,
        ?string $language
    ): void
    {
        $properties = $baseTranslationDocument->getProperties();
        $properties = array_merge($properties, $document->getProperties());
        $document->setProperties($properties);
        $document->setProperty('language', 'text', $language, false, true);
    }

    private function addNewTranslation(Document $baseTranslationDocument, Document $document): void
    {
        $service = new Service();
        $service->addTranslation($baseTranslationDocument, $document);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function createDocumentElement(array $data, int $parentId, DocumentAddParameters $parameters): Document
    {
        $documentClass = match ($parameters->getType()) {
            DocumentTypes::EMAIL->value => Email::class,
            DocumentTypes::HARDLINK->value => Hardlink::class,
            DocumentTypes::LINK->value => Link::class,
            DocumentTypes::PAGE->value => Page::class,
            DocumentTypes::SNIPPET->value => Snippet::class,
            default => $this->getCustomDocumentClass($parameters->getType())
        };

        $document = $this->documentResolver->createByClassName($documentClass, $parentId, $data, false);

        if ($document instanceof Page) {
            $document->setTitle($parameters->getTitle() ?? '');
            $document->setProperty('navigation_name', 'text', $parameters->getNavigationName());
        }

        return $document;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getCustomDocumentClass(string $customType): string
    {
        $className = $this->classResolver->resolve($customType);
        if (!is_subclass_of($className, Document::class)) {
            throw new InvalidArgumentException("Class $className must extend " . Document::class);
        }

        return $className;
    }
}
