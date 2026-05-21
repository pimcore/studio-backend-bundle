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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

/**
 * @internal
 */
enum ElementTreeWidgets: string
{
    use EnumToValueArrayTrait;

    case DEFAULT_ASSET_TREE = 'studio_asset_tree_widget';
    case DEFAULT_DATA_OBJECT_TREE = 'studio_data_object_tree_widget';
    case DEFAULT_DOCUMENT_TREE = 'studio_document_tree_widget';
}
