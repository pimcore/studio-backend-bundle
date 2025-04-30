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

use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class DocumentEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.document';

    public function __construct(
        private readonly Document $document
    ) {
        parent::__construct($document);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getDocument(): Document
    {
        return $this->document;
    }
}
