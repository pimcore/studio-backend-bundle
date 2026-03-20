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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Event\BulkExport;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\BulkExport\BulkExportAvailableItem;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class BulkExportAvailableItemEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.class.bulk_export_available_item';

    public function __construct(private readonly BulkExportAvailableItem $bulkExportAvailableItem)
    {
        parent::__construct($this->bulkExportAvailableItem);
    }

    public function getBulkExportAvailableItem(): BulkExportAvailableItem
    {
        return $this->bulkExportAvailableItem;
    }
}
