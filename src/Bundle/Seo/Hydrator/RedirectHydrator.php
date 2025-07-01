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
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\Redirect;

/**
 * @internal
 */
final readonly class RedirectHydrator implements RedirectHydratorInterface
{
    public function hydrate(RedirectModel $redirect): Redirect
    {
        return new Redirect(
            id: $redirect->getId(),
            type: $redirect->getType(),
            source: $redirect->getSource(),
            sourceSite: $redirect->getSourceSite(),
            passThroughParameters: $redirect->getPassThroughParameters(),
            target: $redirect->getTarget(),
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
}
