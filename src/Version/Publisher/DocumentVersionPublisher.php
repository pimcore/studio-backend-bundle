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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Publisher;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementPublishingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\Document;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class DocumentVersionPublisher implements DocumentVersionPublisherInterface
{
    public function __construct(
        private DocumentResolverInterface $documentResolver
    ) {
    }

    /**
     * @throw NotFoundException
     */
    public function publish(
        Document $versionDocument,
        UserInterface $user
    ): void {
        $currentDocument = $this->documentResolver->getById($versionDocument->getId());
        if (!$currentDocument) {
            throw new NotFoundException('Version', $versionDocument->getId());
        }

        try {
            $versionDocument->setPublished(true);
            $versionDocument->setKey($currentDocument->getKey());
            $versionDocument->setPath($currentDocument->getPath());
            $versionDocument->setUserModification($user->getId());
            $versionDocument->save();
        } catch (Exception $e) {
            throw new ElementPublishingFailedException(
                $versionDocument->getId(),
                $e->getMessage()
            );
        }
    }
}
