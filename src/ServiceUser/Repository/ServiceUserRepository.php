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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\Repository;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\User\Exception\DuplicateUserNameException;
use Pimcore\Model\User\ServiceUser;
use Pimcore\Model\User\ServiceUser\Listing as ServiceUserListing;
use function sprintf;

/**
 * @internal
 */
final readonly class ServiceUserRepository implements ServiceUserRepositoryInterface
{
    private const string TYPE = 'Service user';

    public function __construct(
        private UserResolverInterface $userResolver,
    ) {
    }

    public function listServiceUsers(): array
    {
        $listing = new ServiceUserListing();
        $listing->setOrderKey('name');
        $listing->setOrder('ASC');

        return $listing->getServiceUsers();
    }

    public function getServiceUser(int $id): ServiceUser
    {
        $serviceUser = $this->userResolver->getById($id);
        if (!$serviceUser instanceof ServiceUser) {
            throw new NotFoundException(self::TYPE, $id);
        }

        return $serviceUser;
    }

    public function saveServiceUser(ServiceUser $serviceUser): void
    {
        try {
            $serviceUser->save();
        } catch (DuplicateUserNameException|UniqueConstraintViolationException) {
            throw new ElementExistsException(
                sprintf('A user or service user named "%s" already exists.', (string) $serviceUser->getName())
            );
        } catch (Exception $exception) {
            throw new DatabaseException('The service user could not be saved.', $exception);
        }
    }

    public function deleteServiceUser(ServiceUser $serviceUser): void
    {
        try {
            $serviceUser->delete();
        } catch (Exception $exception) {
            throw new DatabaseException('The service user could not be deleted.', $exception);
        }
    }
}
