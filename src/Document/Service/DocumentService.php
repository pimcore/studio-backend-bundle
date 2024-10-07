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

use Pimcore\Bundle\StudioBackendBundle\DataIndex\DocumentSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\DocumentEvent;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\UserPermissionTrait;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class DocumentService implements DocumentServiceInterface
{
    use UserPermissionTrait;

    public function __construct(
        private DocumentSearchServiceInterface $documentSearchService,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService
    ) {
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getDocument(int $id, bool $checkPermissionsForCurrentUser = true): Document
    {
        $document = $this->documentSearchService->getDocumentById(
            $id,
            $this->getUserForPermissionCheck($this->securityService, $checkPermissionsForCurrentUser)
        );

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

    private function dispatchDocumentEvent(mixed $document): void
    {
        $this->eventDispatcher->dispatch(
            new DocumentEvent($document),
            DocumentEvent::EVENT_NAME
        );
    }
}
