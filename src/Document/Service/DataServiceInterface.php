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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service;

use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Model\Document as DocumentModel;
use Pimcore\Model\Version;

/**
 * @internal
 */
interface DataServiceInterface
{
    public function setDocumentDetailData(
        Document $document,
        DocumentModel $element,
        ?Version $documentVersion = null,
    ): void;
}
