<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column;

/**
 * @internal
 */
enum ColumnType: string
{
    case SYSTEM_STRING = 'system.string';
    case SYSTEM_FILE_SIZE = 'system.fileSize';
    case SYSTEM_INTEGER = 'system.integer';
    case SYSTEM_DATETIME = 'system.datetime';
    case SYSTEM_TAG = 'system.tag';
    case SYSTEM_UNREFERENCED_DEPENDENCIES = 'system.unreferencedDependencies';
    case METADATA_SELECT = 'metadata.select';
    case METADATA_INPUT = 'metadata.input';
    case METADATA_DATE = 'metadata.date';
    case METADATA_ASSET = 'metadata.asset';
    case METADATA_DOCUMENT = 'metadata.document';
    case METADATA_DATA_OBJECT = 'metadata.object';
    case METADATA_TEXTAREA = 'metadata.textarea';
    case METADATA_CHECKBOX = 'metadata.checkbox';
    case METADATA_STRING = 'metadata.string';
}
