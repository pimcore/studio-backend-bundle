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

/**
 * @internal
 */
enum UserPermissions: string
{
    case PERMISSIONS_CATEGORY = 'Pimcore Studio Backend Bundle';
    case DEFINITIONS_TABLE = 'users_permission_definitions';
    case APPLICATION_LOGGING = 'application_logging';
    case ASSETS = 'assets';
    case DATA_OBJECTS = 'objects';
    case DOCUMENTS = 'documents';
    case DOCUMENT_TYPES = 'document_types';
    case ELEMENT_TYPE_PERMISSION  = 'ELEMENT_TYPE_PERMISSION';
    case EMAILS = 'emails';
    case GDPR = 'gdpr_data_extractor';
    case NOTES_EVENTS = 'notes_events';
    case NOTIFICATIONS = 'notifications';
    case NOTIFICATIONS_SEND = 'notifications_send';
    case OBJECTS_SORT_METHOD = 'objects_sort_method';
    case PERSPECTIVE_EDITOR = 'studio_perspective_editor';
    case PIMCORE_ADMIN = 'ROLE_PIMCORE_ADMIN';
    case PIMCORE_USER = 'ROLE_PIMCORE_USER';
    case PREDEFINED_PROPERTIES = 'predefined_properties';
    case RECYCLE_BIN = 'recyclebin';
    case REDIRECTS = 'redirects';
    case SHARE_CONFIGURATIONS = 'share_configurations';
    case TAGS_CONFIGURATION = 'tags_configuration';
    case TAGS_ASSIGNMENT = 'tags_assignment';
    case TAGS_SEARCH = 'tags_search';
    case THUMBNAILS = 'thumbnails';
    case TRANSLATIONS = 'translations';
    case USER_MANAGEMENT = 'users';
    case USER_PASSWORD = 'USER_PASSWORD';
    case WEBSITE_SETTINGS = 'website_settings';
    case WIDGET_EDITOR = 'studio_perspective_widget_editor';
    case CLASS_DEFINITION = 'class';
}
