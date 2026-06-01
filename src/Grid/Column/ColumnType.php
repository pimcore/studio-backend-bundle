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

enum ColumnType: string
{
    case SYSTEM_STRING = 'system.string';
    case SYSTEM_FILE_SIZE = 'system.fileSize';
    case SYSTEM_INTEGER = 'system.integer';
    case SYSTEM_ID = 'system.id';
    case SYSTEM_DATETIME = 'system.datetime';
    case SYSTEM_TIME = 'system.time';
    case SYSTEM_DATE = 'system.date';
    case SYSTEM_BOOLEAN = 'system.boolean';
    case SYSTEM_UNREFERENCED = 'system.unreferenced';
    case SYSTEM_TAG = 'system.tag';
    case SYSTEM_PQL_QUERY = 'system.pql';
    case SYSTEM_NUMBER = 'system.number';
    case SYSTEM_SELECT = 'system.select';
    case SYSTEM_FULLTEXT = 'system.fulltext';
    case SYSTEM_QUANTITY_VALUE = 'system.quantity_value';
    case SYSTEM_INPUT_QUANTITY_VALUE = 'system.input_quantity_value';
    case SYSTEM_RGBA = 'system.rgba';
    case METADATA_SELECT = 'metadata.select';
    case METADATA_INPUT = 'metadata.input';
    case METADATA_DATE = 'metadata.date';
    case METADATA_ASSET = 'metadata.asset';
    case METADATA_DOCUMENT = 'metadata.document';
    case METADATA_DATA_OBJECT = 'metadata.object';
    case METADATA_TEXTAREA = 'metadata.textarea';
    case METADATA_CHECKBOX = 'metadata.checkbox';
    case METADATA_STRING = 'metadata.string';
    case CLASSIFICATION_STORE_STRING = 'classificationstore.string';
    case CLASSIFICATION_STORE_NUMBER = 'classificationstore.number';
    case CLASSIFICATION_STORE_RGBA = 'classificationstore.rgba';
    case CLASSIFICATION_STORE_DATE = 'classificationstore.date';
    case CLASSIFICATION_STORE_DATETIME = 'classificationstore.datetime';
    case CLASSIFICATION_STORE_TIME = 'classificationstore.time';
    case CLASSIFICATION_STORE_QUANTITY_VALUE = 'classificationstore.quantity_value';
    case CLASSIFICATION_STORE_INPUT_QUANTITY_VALUE = 'classificationstore.input_quantity_value';
    case CLASSIFICATION_STORE_QUANTITY_VALUE_RANGE = 'classificationstore.quantity_value_range';
    case CLASSIFICATION_STORE_SELECT = 'classificationstore.select';
    case CLASSIFICATION_STORE_BOOLEAN = 'classificationstore.boolean';
    case CRM_CONSENT = 'crm.consent';
    case DATAOBJECT_RELATION = 'dataobject.relation';
}
