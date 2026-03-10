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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Event\BulkImport;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\BulkImport\BulkImportPrepareResponse;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class BulkImportPrepareEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.class.bulk_import_prepare';

    public function __construct(
        private readonly BulkImportPrepareResponse $bulkImportPrepareResponse,
    ) {
        parent::__construct($this->bulkImportPrepareResponse);
    }

    public function getBulkImportPrepareResponse(): BulkImportPrepareResponse
    {
        return $this->bulkImportPrepareResponse;
    }
}
