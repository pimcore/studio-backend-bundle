<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Version\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DocumentVersion;
use Pimcore\Model\Document;

/**
 * @internal
 */
final class DocumentVersionHydrator
{
    public function hydrate(
        Document $document
    ): DocumentVersion
    {
        return new DocumentVersion(
            $document->getModificationDate(),
            $document->getRealFullPath(),
            $document->isPublished(),
        );
    }

}