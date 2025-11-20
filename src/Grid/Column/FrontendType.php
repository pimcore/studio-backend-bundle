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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column;

/**
 * @internal
 */
enum FrontendType: string
{
    case ELEMENT_DROPZONE = 'element_dropzone';
    case INPUT = 'input';
    case ID = 'id';
    case TEXTAREA = 'textarea';
    case SELECT = 'select';
    case MULTISELECT = 'multiselect';
    case CHECKBOX = 'checkbox';
    case DATETIME = 'datetime';
    case IMAGE = 'image';
    case ASSET_LINK = 'asset-link';

    case OBJECT_LINK = 'object-link';
    case ASSET_PREVIEW = 'asset-preview';
    case BOOLEAN = 'boolean';
}
