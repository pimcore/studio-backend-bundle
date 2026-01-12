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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Event\FieldCollection;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\FieldCollectionConfig;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class ConfigEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.field_collection.config';

    public function __construct(
        private readonly FieldCollectionConfig $fieldCollectionConfig
    ) {
        parent::__construct($fieldCollectionConfig);
    }

    /**
     * Use this to get additional info out of the response object
     */
    public function getFieldCollectionConfig(): FieldCollectionConfig
    {
        return $this->fieldCollectionConfig;
    }
}

