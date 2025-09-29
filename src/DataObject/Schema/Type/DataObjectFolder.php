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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type;

use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\WorkflowAvailableTrait;

#[Schema(
    schema: 'DataObjectFolder',
    title: 'Data Object Folder',
    required: ['hasWorkflowAvailable'],
    type: 'object'
)]
final class DataObjectFolder extends DataObject
{
    use WorkflowAvailableTrait;
}
