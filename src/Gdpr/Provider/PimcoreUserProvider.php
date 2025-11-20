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
use Pimcore\Db;
use Pimcore\Model\User;
use Pimcore\Model\User\Listing;

/**
 * @internal
 */
final readonly class PimcoreUserProvider implements DataProviderInterface
{
    /**
     * {@inheritdoc}
     */
        private string $logsDir;

    public function __construct(string $logsDir)
    {
        $this->logsDir = $logsDir;
    }

    public function findData(SearchTerms $terms): array
    {
        $listing = new Listing();
        $conditionParts = [];
        $params = [];

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

        $listing->setCondition(implode(' AND ', $conditionParts), $params);

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
                    'versions'  => $this->getVersionDataForUser($user),
                    'usageLog'  => $this->getUsageLogDataForUser($user),
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
        $listing->setCondition('id = ?', [$id]);
        $listing->setLimit(1);

        $users = $listing->getUsers();

        if (empty($users)) {
            throw new NotFoundException('Pimcore User', $id);
        }

        $user = $users[0];


        return [
                'id'        => $user->getId(),
                'name'      => $user->getName(),
                'firstname' => $user->getFirstname(),
                'lastname'  => $user->getLastname(),
                'email'     => $user->getEmail(),
                'versions'  => $this->getVersionDataForUser($user),
                'usageLog'  => $this->getUsageLogDataForUser($user),
            ];
    }

    protected function getVersionDataForUser(User\AbstractUser $user): array
    {
        return Db::get()->fetchAllAssociative(
            "SELECT ctype, cid, note, FROM_UNIXTIME(`date`) AS 'date'
            FROM versions
            WHERE userId = ?",
            [$user->getId()]
        );
    }

    protected function getUsageLogDataForUser(User\AbstractUser $user): array
    {
        $pattern = ' [' . $user->getId() . ',';
        $matches = [];

        $this->readPlainFile($this->logsDir . '/usage.log', $pattern, $matches);

        $archiveFiles = glob($this->logsDir . '/usage-archive-*.log.gz');
        foreach ($archiveFiles as $archiveFile) {
            $this->readGzFile($archiveFile, $pattern, $matches);
        }

        return $matches;
    }

    private function readPlainFile(string $file, string $pattern, array &$matches): void
    {
        $handle = @fopen($file, 'r');
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle);
                if ($buffer && strpos($buffer, $pattern) !== false) {
                    $matches[] = $buffer;
                }
            }
            fclose($handle);
        }
    }

    private function readGzFile(string $file, string $pattern, array &$matches): void
    {
        $handle = @gzopen($file, 'r');
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle);
                if ($buffer && strpos($buffer, $pattern) !== false) {
                    $matches[] = $buffer;
                }
            }
            fclose($handle);
        }
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
            new GdprDataColumn('versions', 'Versions'),
            new GdprDataColumn('usageLog', 'Usage Log'),
        ];
    }
}
