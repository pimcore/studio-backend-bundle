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

use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\RelationNormalizationContext;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;

/**
 * @internal
 */
readonly class ElementDataService implements ElementDataServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        protected SecurityServiceInterface $securityService,
        protected RelationNormalizationContext $normalizationContext,
    ) {
    }

    public function getRelatedElementData(ElementInterface $element): RelatedElementData
    {
        $hasAccess = true;
        $canEdit = true;
        try {
            $user = $this->securityService->getCurrentUser();
            /** @var User $user */
            $hasAccess = $element->isAllowed('view', $user);
            $parent = $this->normalizationContext->getParent();
            if ($parent !== null) {
                $canEdit = $parent->isAllowed('save', $user) || $parent->isAllowed('publish', $user);
            }
        } catch (UserNotFoundException) {
            $hasAccess = false;
            $canEdit = false;
        }

        return new RelatedElementData(
            $element->getId(),
            $this->getElementType($element, true),
            $this->getSubType($element),
            $element->getRealFullPath(),
            $this->getPublished($element),
            $hasAccess,
            $canEdit,
        );
    }

    protected function getSubType(ElementInterface $element): string
    {
        if ($element instanceof Concrete) {
            return $element->getClassName();
        }

        return $element->getType();
    }

    protected function getPublished(ElementInterface $element): ?bool
    {
        if ($element instanceof Concrete || $element instanceof Document) {
            return $element->getPublished();
        }

        return null;
    }
}
