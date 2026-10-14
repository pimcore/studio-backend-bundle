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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;
use function is_subclass_of;
use function strlen;
use function substr;
use function ucfirst;

/**
 * @internal
 */
final readonly class ElementDataService implements ElementDataServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
    ) {
    }

    public function getRelatedElementData(ElementInterface $element): RelatedElementData
    {
        return new RelatedElementData(
            $element->getId(),
            $this->getElementType($element, true),
            $this->getSubType($element),
            $element->getRealFullPath(),
            $this->getPublished($element),
            $this->hasViewAccess($element)
        );
    }

    /**
     * Builds the related element data from a relation summary without loading the related element.
     *
     * The view permission is checked on a model that only carries the summary data (identity, path, parent), which
     * is all the core permission check (workspaces, workflow, permission event) works with. Elements whose model
     * cannot be built from the summary are loaded instead.
     *
     * @param array{
     *     id: int,
     *     parentId: int,
     *     type: string,
     *     subtype: string,
     *     className: string|null,
     *     fullpath: string,
     *     filename: string,
     *     published: bool
     * } $summary
     */
    public function getRelatedElementDataFromSummary(array $summary): RelatedElementData
    {
        $element = $this->createElementFromSummary($summary);
        if ($element === null) {
            $element = $this->serviceResolver->getElementById($summary['type'], $summary['id']);
            if ($element === null) {
                return $this->createRelatedElementDataFromSummary($summary, false);
            }

            return $this->getRelatedElementData($element);
        }

        return $this->createRelatedElementDataFromSummary($summary, $this->hasViewAccess($element));
    }

    private function hasViewAccess(ElementInterface $element): bool
    {
        $user = $this->getCurrentUser();
        if ($user === null) {
            return false;
        }

        return $element->isAllowed(ElementPermissions::VIEW_PERMISSION, $user);
    }

    private function getCurrentUser(): ?User
    {
        try {
            $user = $this->securityService->getCurrentUser();
        } catch (UserNotFoundException) {
            return null;
        }

        return $user instanceof User ? $user : null;
    }

    private function createRelatedElementDataFromSummary(array $summary, bool $hasViewAccess): RelatedElementData
    {
        $isConcrete = $summary['type'] === ElementTypes::TYPE_OBJECT && $summary['className'] !== null;
        $isPublished = $isConcrete || $summary['type'] === ElementTypes::TYPE_DOCUMENT ? $summary['published'] : null;

        return new RelatedElementData(
            $summary['id'],
            $summary['type'],
            $summary['className'] ?? $summary['subtype'],
            $summary['fullpath'],
            $isPublished,
            $hasViewAccess
        );
    }

    private function createElementFromSummary(array $summary): ?ElementInterface
    {
        $element = match ($summary['type']) {
            ElementTypes::TYPE_OBJECT => $this->createObjectFromSummary($summary),
            ElementTypes::TYPE_ASSET => new Asset(),
            ElementTypes::TYPE_DOCUMENT => new Document(),
            default => null,
        };
        if ($element === null) {
            return null;
        }

        $filename = $summary['filename'];
        $path = $filename === '' ? $summary['fullpath'] : substr($summary['fullpath'], 0, -strlen($filename));
        $element->setId($summary['id']);
        $element->setParentId($summary['parentId']);
        $element->setPath($path);
        $element->setType($summary['subtype']);
        if ($element instanceof Asset) {
            $element->setFilename($filename);
        } else {
            $element->setKey($filename);
        }

        if ($element instanceof Concrete || $element instanceof Document) {
            $element->setPublished($summary['published']);
        }

        return $element;
    }

    private function createObjectFromSummary(array $summary): Concrete|Folder|null
    {
        if ($summary['className'] === null) {
            return new Folder();
        }

        $class = 'Pimcore\\Model\\DataObject\\' . ucfirst($summary['className']);

        return is_subclass_of($class, Concrete::class) ? new $class() : null;
    }

    private function getSubType(ElementInterface $element): string
    {
        if ($element instanceof Concrete) {
            return $element->getClassName();
        }

        return $element->getType();
    }

    private function getPublished(ElementInterface $element): ?bool
    {
        if ($element instanceof Concrete || $element instanceof Document) {
            return $element->getPublished();
        }

        return null;
    }
}
