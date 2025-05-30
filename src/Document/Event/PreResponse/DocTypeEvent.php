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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocType;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class DocTypeEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.document.doc_type';

    public function __construct(
        private readonly DocType $docType
    ) {
        parent::__construct($docType);
    }

    /**
     * Use this to get additional info out of the response object
     */
    public function getDocType(): DocType
    {
        return $this->docType;
    }
}
