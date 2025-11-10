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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider;

use Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request\SearchTerms;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataColumn;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;

/**
 * @internal
 */
final readonly class DataObjectProvider implements DataProviderInterface
{
    public function findData(?SearchTerms $terms): array
    {
        //how to use lib to get the data
        // Return dummy data for testing, regardless of the query.
        $results = [
            [
                'id' => 1,
                'path' => '/test/object-1',
                'className' => 'Customer',
            ],
            [
                'id' => 2,
                'path' => '/test/object-2',
                'className' => 'Customer',
            ],
            [
                'id' => 3,
                'path' => '/test/object-3',
                'className' => 'Order',
            ],
        ];

        return $results;
    }

    public function getName(): string
    {
        return 'Data Objects';
    }

    public function getKey(): string
    {
        return 'data_objects';
    }

    public function getSortPriority(): int
    {
        // Give it a high priority so it shows up first in the list.
        return 10;
    }

    public function getAvailableColumns(): array
    {
        return [
            new GdprDataColumn(
                key: 'id',
                label: 'ID'
            ),
            new GdprDataColumn(
                key: 'path',
                label: 'Path'
            ),
            new GdprDataColumn(
                key: 'className',
                label: 'Class Name'
            ),
        ];
    }

    public function getRequiredPermission(): UserPermissions
    {
        return UserPermissions::DATA_OBJECTS;
    }
}
