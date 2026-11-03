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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

/**
 * The field names an artifact entry carries. Export writes them and apply reads them, so the
 * format lives here once rather than as a literal on either side of it.
 *
 * @internal
 */
enum ArtifactField: string
{
    use EnumToValueArrayTrait;

    case IDENTIFIER = 'identifier';
    case NAME = 'name';
    case DESCRIPTION = 'description';
    case TITLE = 'title';
    case TYPE = 'type';
    case DEFINITION = 'definition';
    case ENABLED = 'enabled';
    case SORTER = 'sorter';
    case MANDATORY = 'mandatory';
    case METADATA = 'metadata';
    case DEFINITION_OVERRIDE = 'definitionOverride';
    case SUB_GROUP = 'subGroup';
    case COLLECTION = 'collection';
    case GROUP = 'group';
    case KEY = 'key';
}
