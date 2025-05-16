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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocType;
use Pimcore\Model\Document\DocType as DocTypeModel;

/**
 * @internal
 */
final class DocTypeHydrator implements DocTypeHydratorInterface
{
    public function hydrate(DocTypeModel $docType): DocType
    {
        return new DocType(
            id: $docType->getId(),
            name: $docType->getName(),
            type: $docType->getType(),
            group: $docType->getGroup(),
            controller: $docType->getController(),
            template: $docType->getTemplate(),
            priority: $docType->getPriority(),
            creationDate: $docType->getCreationDate(),
            modificationDate: $docType->getModificationDate(),
            staticGeneratorEnabled: $docType->getStaticGeneratorEnabled(),
            writeable: $docType->isWriteable()
        );
    }
}
