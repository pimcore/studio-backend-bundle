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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Publisher;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementPublishingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\Document;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version;

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
        UserInterface $user,
        Version $version
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
            // the published state is written into the version itself, so the history shows which version went live
            $versionDocument->save(['existingVersion' => $version]);
        } catch (Exception $e) {
            throw new ElementPublishingFailedException(
                $versionDocument->getId(),
                $e->getMessage()
            );
        }
    }
}
