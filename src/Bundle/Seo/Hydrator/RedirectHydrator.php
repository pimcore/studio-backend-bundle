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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Hydrator;

use Pimcore\Bundle\SeoBundle\Model\Redirect as RedirectModel;
use Pimcore\Bundle\SeoBundle\Redirect\TargetResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\Redirect;
use Pimcore\Model\Element\ElementType;

/**
 * @internal
 */
final readonly class RedirectHydrator implements RedirectHydratorInterface
{
    public function __construct(
        private TargetResolverInterface $targetResolver,
    ) {
    }

    public function hydrate(RedirectModel $redirect): Redirect
    {
        return new Redirect(
            id: $redirect->getId(),
            type: $redirect->getType(),
            source: $redirect->getSource(),
            sourceSite: $redirect->getSourceSite(),
            passThroughParameters: $redirect->getPassThroughParameters(),
            target: $this->getTarget($redirect->getTarget(), $redirect->getTargetType()),
            targetType: $redirect->getTargetType()?->value,
            targetSite: $redirect->getTargetSite(),
            statusCode: $redirect->getStatusCode(),
            priority: $redirect->getPriority(),
            regex: $redirect->getRegex(),
            active: $redirect->getActive(),
            expiry: $redirect->getExpiry(),
            creationDate: $redirect->getCreationDate(),
            modificationDate: $redirect->getModificationDate(),
            userOwner: $redirect->getUserOwner(),
            userModification: $redirect->getUserModification(),
        );
    }

    /**
     * Targets are stored as element IDs and shown as element paths; anything that does not
     * resolve - a plain URL, or an element that is gone - is passed through unchanged.
     */
    private function getTarget(?string $target, ?ElementType $targetType): ?string
    {
        if ($target === null) {
            return null;
        }

        return $this->targetResolver->resolvePath($target, $targetType) ?? $target;
    }
}
