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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\SendNotificationParameters;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Notification;
use Pimcore\Model\Notification\Service\UserService;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class SendNotificationService implements SendNotificationServiceInterface
{
    public function __construct(
        private ElementServiceInterface $elementService,
        private RoleRepositoryInterface $roleRepository,
        private UserRepositoryInterface $userRepository,
        private UserService $userService
    ) {
    }

    /**
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws UserNotFoundException
     * @throws NotFoundException
     */
    public function sendNotification(SendNotificationParameters $parameters, UserInterface $sender): void
    {
        $this->validateParameters($parameters);
        $recipients = $this->getRecipients($parameters, $sender->getId());
        $attachment = $this->getAttachment($parameters, $sender);

        foreach ($recipients as $recipient) {
            $this->sendNotificationToUser($recipient, $sender, $attachment, $parameters);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateParameters(SendNotificationParameters $parameters): void
    {
        if ($parameters->getMessage() === '') {
            throw new InvalidArgumentException('Message cannot be empty');
        }

        if ($parameters->getTitle() === '') {
            throw new InvalidArgumentException('Title cannot be empty');
        }
    }

    /**
     * @throws NotFoundException
     */
    private function getRecipients(SendNotificationParameters $parameters, int $senderId): array
    {
        try {
            $user = $this->userRepository->getUserById($parameters->getRecipientId());

            return [$user];
        } catch (UserNotFoundException) {
        }

        $role = $this->roleRepository->getRoleById($parameters->getRecipientId());
        $roleUsers = $this->userRepository->getUserListingByRoleId($role->getId(), $senderId);

        return $this->userService->filterUsersWithPermission($roleUsers->getUsers());
    }

    /**
     * @throws AccessDeniedException
     * @throws NotFoundException
     */
    private function getAttachment(SendNotificationParameters $parameters, UserInterface $sender): ?ElementInterface
    {
        if ($parameters->getAttachmentId() === null || $parameters->getAttachmentType() === null) {
            return null;
        }

        return $this->elementService->getAllowedElementById(
            $parameters->getAttachmentType(),
            $parameters->getAttachmentId(),
            $sender
        );
    }

    /**
     * @throws DatabaseException
     */
    private function sendNotificationToUser(
        UserInterface $recipient,
        UserInterface $sender,
        ?ElementInterface $element,
        SendNotificationParameters $parameters
    ): void {
        try {
            $notification = new Notification();
            /** @var User $recipient */
            $notification->setRecipient($recipient);
            /** @var User $sender */
            $notification->setSender($sender);
            $notification->setTitle($parameters->getTitle());
            $notification->setMessage($parameters->getMessage());
            $notification->setLinkedElement($element);
            $notification->save();
        } catch (Exception $e) {
            throw new DatabaseException(sprintf('Failed to send notification: %s', $e->getMessage()));
        }
    }
}
