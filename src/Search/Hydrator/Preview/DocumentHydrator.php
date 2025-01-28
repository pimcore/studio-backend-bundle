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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Hydrator\Preview;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\BinaryServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\Document\PageSearchPreview;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\DocumentSearchPreview;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;

/**
 * @internal
 */
final readonly class DocumentHydrator implements DocumentHydratorInterface
{
    use ElementProviderTrait;

    private const string PROPERTY_NAVIGATION = 'navigation_name';

    private const string PROPERTY_LANGUAGE = 'language';

    public function __construct(
        private BinaryServiceInterface $binaryService,
        private ToolResolverInterface $toolResolver,
        private UserServiceInterface $userService,
    ) {
    }

    public function hydrate(Document $document): DocumentSearchPreview
    {
        return new DocumentSearchPreview(
            $document->getId(),
            $this->getElementType($document),
            $document->getType(),
            $document->getUserOwner(),
            $document->getUserOwner() !== null ?
                $this->userService->getUserNameById($document->getUserOwner()) :
                null,
            $document->getUserModification(),
            $document->getUserModification() !== null ?
                $this->userService->getUserNameById($document->getUserModification()) :
                null,
            $document->getCreationDate(),
            $document->getModificationDate(),
            $this->getDocumentLanguage($document),
            $this->getDocumentData($document)
        );
    }

    private function getDocumentLanguage(Document $document): ?string
    {
        $language = $document->getProperty(self::PROPERTY_LANGUAGE);
        if ($language === null) {
            return null;
        }

        try {
            return $this->toolResolver->getSupportedLocales()[$language] ?? null;
        } catch (Exception) {
            return null;
        }
    }

    private function getDocumentData(Document $document): ?PageSearchPreview
    {
        if (!$document instanceof Page) {
            return null;
        }

        return new PageSearchPreview(
            $document->getTitle(),
            $document->getDescription(),
            $document->getProperty(self::PROPERTY_NAVIGATION),
            $this->binaryService->hasPagePreview($document)
        );
    }
}
