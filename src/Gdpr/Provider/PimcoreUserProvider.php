<?php

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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request\SearchTerms;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataColumn;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataRow;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\User\Listing;
use function count;

/**
 * @internal
 */
final readonly class PimcoreUserProvider implements DataProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function findData(SearchTerms $terms): array
    {
        $listing = new Listing();
        $conditionParts = [];
        $params = [];

        $conditionParts[] = "`type` = 'user'";
        // Only try to build if we actually have search terms
        if ($terms !== null) {
            if ($terms->id !== null) {
                $conditionParts[] = 'id = ?';
                $params[] = $terms->id;
            }
            if ($terms->firstname !== null) {
                $conditionParts[] = 'firstname LIKE ?';
                $params[] = '%' . $terms->firstname . '%';
            }
            if ($terms->lastname !== null) {
                $conditionParts[] = 'lastname LIKE ?';
                $params[] = '%' . $terms->lastname . '%';
            }
            if ($terms->email !== null) {
                $conditionParts[] = 'email LIKE ?';
                $params[] = '%' . $terms->email . '%';
            }
        }

        // If we have conditions, apply them.
        if (count($conditionParts) > 1) {
            $listing->setCondition(implode(' AND ', $conditionParts), $params);
        } else {
            // Only the "type = 'user'" condition exists
            $listing->setCondition($conditionParts[0]);
        }

        $users = $listing->getUsers();

        $columns = $this->getAvailableColumns();

        return array_map(
            fn ($user) => new GdprDataRow(
                [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'email' => $user->getEmail(),
                ],
                $columns
            ),
            $users
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSingleItemForDownload(int $id): array
    {
        $listing = new Listing();
        $listing->setCondition('id = ? AND `type` = ?', [$id, 'user']);
        $listing->setLimit(1);

        $users = $listing->getUsers();

        if (empty($users)) {
            throw new NotFoundException('Pimcore User', $id);
        }

        $user = $users[0];

        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
        ];
    }

    public function getName(): string
    {
        return 'Pimcore Users';
    }

    public function getKey(): string
    {
        return 'pimcore_users';
    }

    public function getSortPriority(): int
    {
        return 5;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredPermissions(): array
    {
        return [UserPermissions::PIMCORE_USER->value];
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableColumns(): array
    {
        return [
            new GdprDataColumn('id', 'ID'),
            new GdprDataColumn('name', 'Username'),
            new GdprDataColumn('firstname', 'First Name'),
            new GdprDataColumn('lastname', 'Last Name'),
            new GdprDataColumn('email', 'Email'),
        ];
    }
}
