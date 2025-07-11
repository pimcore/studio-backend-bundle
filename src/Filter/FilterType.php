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

namespace Pimcore\Bundle\StudioBackendBundle\Filter;

/**
 * @internal
 */
enum FilterType: string
{
    case DATE = 'date';
    case EQUALS = 'equals';
    case LIKE = 'like';
    case TRANSLATION_LIKE = 'translationLike';
    case PAGE = 'page';
    case PAGE_SIZE = 'page.size';
    case PROPERTY_NAME = 'property.name';
    case PROPERTY_ELEMENT_TYPE = 'property.element.type';
}
