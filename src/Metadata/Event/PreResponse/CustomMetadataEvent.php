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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\CustomMetadata;

final class CustomMetadataEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.asset_custom_metadata';

    public function __construct(
        private readonly CustomMetadata $customMetadata
    ) {
        parent::__construct($customMetadata);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getCustomMetadata(): CustomMetadata
    {
        return $this->customMetadata;
    }
}
