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
use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\DocTypeEvent;
use Pimcore\Bundle\StudioBackendBundle\Document\Hydrator\DocTypeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Repository\DocTypeRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocType as DocTypeSchema;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocTypeAddParameters;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocTypeUpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\UserPermissionTrait;
use Pimcore\Model\Document\DocType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_key_exists;
use function sprintf;

/**
 * @internal
 */
final readonly class DocTypeService implements DocTypeServiceInterface
{
    use UserPermissionTrait;

    public function __construct(
        private DocTypeHydratorInterface $hydrator,
        private DocTypeRepositoryInterface $docTypeRepository,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function addDocType(DocTypeAddParameters $parameters): DocTypeSchema
    {
        $this->validateType($parameters->getType());
        $docType = $this->docTypeRepository->addDocType();
        $docType->setName($parameters->getName());
        $docType->setType($parameters->getType());

        try {
            $docType->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException(id: null, error: $e->getMessage(), previous: $e);
        }

        return $this->getDocType($docType);
    }

    /**
     * {@inheritDoc}
     */
    public function updateDocType(string $id, DocTypeUpdateParameters $parameters): DocTypeSchema
    {
        $this->validateType($parameters->getType());
        $docType = $this->docTypeRepository->getById($id);
        $docType->setName($parameters->getName());
        $docType->setType($parameters->getType());
        $docType->setGroup($parameters->getGroup());
        $docType->setController($parameters->getController());
        $docType->setTemplate($parameters->getTemplate());
        $docType->setPriority($parameters->getPriority());
        $docType->setStaticGeneratorEnabled($parameters->isStaticGeneratorEnabled());

        try {
            $docType->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException(id: null, error: $e->getMessage(), previous: $e);
        }

        return $this->getDocType($docType);
    }

    /**
     * {@inheritDoc}
     */
    public function listDocTypes(?string $type): array
    {
        $docTypes = [];
        $docTypeList = $this->docTypeRepository->listDocTypes($type);
        foreach ($docTypeList as $docType) {
            if (!$this->securityService->getCurrentUser()->isAllowed(
                $docType->getId(),
                ElementTypes::DOC_TYPE
            )) {
                continue;
            }

            $docTypes[] = $this->getDocType($docType);
        }

        return $docTypes;
    }

    private function getDocType(DocType $docType): DocTypeSchema
    {
        $hydrated = $this->hydrator->hydrate($docType);
        $this->eventDispatcher->dispatch(new DocTypeEvent($hydrated), DocTypeEvent::EVENT_NAME);

        return $hydrated;
    }

    private function validateType(string $type): void
    {
        $types = $this->docTypeRepository->getTypesConfiguration();
        if (!array_key_exists($type, $types)) {
            throw new InvalidArgumentException(sprintf('Invalid DocType type: %s', $type));
        }
    }
}
