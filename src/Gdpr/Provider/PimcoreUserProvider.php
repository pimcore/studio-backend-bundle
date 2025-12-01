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

use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request\SearchTerms;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataColumn;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataRow;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Db;
use Pimcore\Model\User;
use Pimcore\Model\User\Listing;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
final readonly class PimcoreUserProvider implements DataProviderInterface
{
    public function __construct(
        private string $logsDir,
        private SecurityServiceInterface $securityService
    ) {

    }

    /**
     * {@inheritdoc}
     */
    public function findData(SearchTerms $terms, FilterParameter $options): Collection
    {
        $listing = new Listing();

        if ($terms->getId() !== null) {
            $listing->addConditionParam(
                'id = :id',
                ['id' => $terms->getId()]
            );
        }

        if ($terms->getFirstname() !== null) {
            $listing->addConditionParam(
                'firstname LIKE :firstname',
                ['firstname' => '%' . $terms->getFirstname() . '%']
            );
        }

        if ($terms->getLastname() !== null) {
            $listing->addConditionParam(
                'lastname LIKE :lastname',
                ['lastname' => '%' . $terms->getLastname() . '%']
            );
        }

        if ($terms->getEmail() !== null) {
            $listing->addConditionParam(
                'email LIKE :email',
                ['email' => '%' . $terms->getEmail() . '%']
            );
        }

        $this->applySearchOptions($listing, $options);

        $users = $listing->getUsers();

        $columns = $this->getAvailableColumns();

        $rows = array_map(
            fn ($user) => new GdprDataRow(
                [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'email' => $user->getEmail(),
                     '__gdprIsDeletable' => $user->getId() != $this->securityService->getCurrentUser()->getId(),
                ],
                $columns
            ),
            $users
        );

        return new Collection(
            totalItems: $listing->getTotalCount(),
            items: $rows
        );
    }

    private function applySearchOptions(Listing $listing, FilterParameter $options): void
    {
        $listing->setOffset($options->getStart());
        $listing->setLimit($options->getPageSize());

        $sortFilter = $options->getSortFilter();

        if ($sortFilter->getKey() && $sortFilter->getDirection()) {
            $listing->setOrderKey($sortFilter->getKey());
            $listing->setOrder(
                strtolower($sortFilter->getDirection()) === SortDirection::DESC->value
                    ? SortDirection::DESC->value
                    : SortDirection::ASC->value
            );
        }
    }

    public function getDeleteSwaggerOperationId(): string
    {
        return 'user_delete_by_id';
    }

    /**
     * {@inheritdoc}
     */
    public function getSingleItemForDownload(int $id): Response
    {
        $user = User::getById($id);

        if (!$user) {
            throw new NotFoundException('Pimcore User', $id);
        }

        $userData = $user->getObjectVars();

        unset($userData['password']);

        $userData['versions'] = $this->getVersionDataForUser($user);
        $userData['usageLog'] = $this->getUsageLogDataForUser($user);

        return new JsonResponse($userData);
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
            new GdprDataColumn('__gdprIsDeletable', 'Is Deletable'),
        ];
    }
}
