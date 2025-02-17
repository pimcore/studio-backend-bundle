<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DocumentSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\DocumentEvent;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\UserPermissionTrait;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowDetailsServiceInterface;
use Pimcore\Model\Document as DocumentModel;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class DocumentService implements DocumentServiceInterface
{
    use ElementProviderTrait;
    use UserPermissionTrait;

    public function __construct(
        private DocumentSearchServiceInterface $documentSearchService,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
        private WorkflowDetailsServiceInterface $workflowDetailsService
    ) {
    }

    /**
     * @throws SearchException|NotFoundException|UserNotFoundException
     */
    public function getDocument(int $id, bool $getWorkflowAvailable = true): Document
    {
        $user = $this->securityService->getCurrentUser();
        $document = $this->documentSearchService->getDocumentById(
            $id,
            $user
        );

        if ($getWorkflowAvailable) {
            $document->setHasWorkflowAvailable($this->workflowDetailsService->hasElementWorkflowsById(
                $id,
                ElementTypes::TYPE_DOCUMENT,
                $user
            ));
        }
        $this->dispatchDocumentEvent($document);

        return $document;
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getDocumentForUser(int $id, UserInterface $user): Document
    {
        $document = $this->documentSearchService->getDocumentById($id, $user);

        $this->dispatchDocumentEvent($document);

        return $document;
    }

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getDocumentElement(
        UserInterface $user,
        int $documentId,
    ): DocumentModel {
        $document = $this->getElement($this->serviceResolver, ElementTypes::TYPE_DOCUMENT, $documentId);
        $this->securityService->hasElementPermission($document, $user, ElementPermissions::VIEW_PERMISSION);

        if (!$document instanceof DocumentModel) {
            throw new InvalidElementTypeException($document->getType());
        }

        return $document;
    }

    private function dispatchDocumentEvent(mixed $document): void
    {
        $this->eventDispatcher->dispatch(
            new DocumentEvent($document),
            DocumentEvent::EVENT_NAME
        );
    }
}
