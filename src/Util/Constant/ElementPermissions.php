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
final class ElementPermissions
{
    public const string LIST_PERMISSION = 'list';

    public const string VIEW_PERMISSION = 'view';

    public const string  PUBLISH_PERMISSION = 'publish';

    public const string UNPUBLISH_PERMISSION = 'unpublish';

    public const string DELETE_PERMISSION = 'delete';

    public const string RENAME_PERMISSION = 'rename';

    public const string CREATE_PERMISSION = 'create';

    public const string SETTINGS_PERMISSION = 'settings';

    public const string VERSIONS_PERMISSION = 'versions';

    public const string PROPERTIES_PERMISSION = 'properties';

    public const string LANGUAGE_EDIT_PERMISSIONS = 'localizedEdit';

    public const string LANGUAGE_VIEW_PERMISSIONS = 'localizedView';

    public const string CUSTOM_LAYOUT_PERMISSIONS = 'layouts';

    public const array LANGUAGE_PERMISSIONS = [
        self::LANGUAGE_EDIT_PERMISSIONS,
        self::LANGUAGE_VIEW_PERMISSIONS,
    ];
}
