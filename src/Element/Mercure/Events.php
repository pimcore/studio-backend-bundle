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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Mercure;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

/**
 * @internal
 */
enum Events: string
{
    use EnumToValueArrayTrait;

    case DELETION_FINISHED = 'deletion-finished';
    case BATCH_DELETION_FINISHED = 'batch-deletion-finished';
    case CLONING_FINISHED = 'cloning-finished';
    case PATCH_FINISHED = 'patch-finished';
    case REWRITE_REFERENCES_FINISHED = 'rewrite-references-finished';
}
