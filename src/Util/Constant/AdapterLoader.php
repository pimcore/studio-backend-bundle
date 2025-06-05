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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constant;

enum AdapterLoader: string
{
    case METADATA_ADAPTER_TAG = 'pimcore.studio_backend.metadata_adapter';
    case DOCUMENT_TYPE_ADAPTER_TAG = 'pimcore.studio_backend.document_type_adapter';
}
