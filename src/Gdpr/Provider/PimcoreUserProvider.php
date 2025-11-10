<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 * @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider;

use Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request\SearchTerms;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataColumn;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\User\Listing;

/**
 * Searches for Pimcore backend users.
 *
 * @internal
 */
final readonly class PimcoreUserProvider implements DataProviderInterface
{
    public function findData(?SearchTerms $terms): array
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
        //check the result and test it , pending
        if (!empty($conditionParts)) {
            $listing->setCondition(implode(' OR ', $conditionParts), $params);
        }

        $users = $listing->getUsers();

        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->getId(),
                'name' => $user->getName(),
              //  'firstname' => $user->getFirstname(),
               // 'lastname' => $user->getLastname(),
               // 'email' => $user->getEmail(),
            ];
        }

      return $results;
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

    public function getRequiredPermission(): UserPermissions
    {
        return UserPermissions::PIMCORE_USER;
    }

    public function getAvailableColumns(): array
    {
        return [
            new GdprDataColumn('id', 'ID'),
            new GdprDataColumn('name', 'Username'),
          //  new GdprDataColumn('firstname', 'First Name'),
          //  new GdprDataColumn('lastname', 'Last Name'),
          //  new GdprDataColumn('email', 'Email'),
        ];
        //How to get email firstname lastname
    }

}